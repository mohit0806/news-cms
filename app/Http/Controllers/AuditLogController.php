<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'subject_type' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Activity>
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Activity::query()->with(['causer', 'subject'])->latest('id');

        if (! empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('event', $filters['action']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $actions = Activity::query()->select('event')->distinct()->orderBy('event')->pluck('event')->all();

        return view('audit-log', [
            'activities' => $this->filteredQuery($filters)->paginate(25)->withQueryString(),
            'filters' => $filters,
            'actions' => $actions,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        $filename = 'audit-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['id', 'created_at', 'actor_id', 'actor_type', 'action', 'subject_type', 'subject_id', 'ip', 'user_agent', 'properties']);

            $this->filteredQuery($filters)->orderBy('id')->chunk(500, function (Collection $activities) use ($handle): void {
                foreach ($activities as $activity) {
                    fputcsv($handle, [
                        $activity->getKey(),
                        $activity->created_at?->toDateTimeString(),
                        $activity->causer_id,
                        $activity->causer_type,
                        $activity->event,
                        $activity->subject_type,
                        $activity->subject_id,
                        $activity->getProperty('ip'),
                        $activity->getProperty('user_agent'),
                        json_encode($activity->properties),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
