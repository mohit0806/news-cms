<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Application audit writer (PRD §16, ADR-0003).
 *
 * All P1 audit events flow through here so masking, request context, and
 * failure behavior stay consistent. Storage is the activitylog package table;
 * records are append-only (see AuditServiceProvider guards).
 */
final class Audit
{
    /**
     * Property-name fragments that must never be stored (passwords, tokens,
     * secrets, recovery codes, session material).
     *
     * @var list<string>
     */
    private const SENSITIVE_FRAGMENTS = [
        'password',
        'passwd',
        'secret',
        'token',
        'recovery',
        'authorization',
        'cookie',
        'session',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(string $action, ?Model $subject = null, array $properties = [], ?User $causer = null): ?Activity
    {
        try {
            $pending = activity()
                ->causedBy($causer ?? auth()->user())
                ->event($action)
                ->withProperties(array_merge(
                    self::mask($properties),
                    [
                        'ip' => request()->ip(),
                        'user_agent' => substr((string) request()->userAgent(), 0, 500),
                    ],
                ));

            if ($subject !== null) {
                $pending->performedOn($subject);
            }

            /** @var Activity|null $activity */
            $activity = $pending->log($action);

            return $activity;
        } catch (Throwable $e) {
            // Never silently conceal a security-critical failure: log loudly
            // and let the primary action continue (availability over audit
            // completeness; monitors alert on this channel).
            Log::critical('Audit write failed', [
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function mask(array $properties): array
    {
        $masked = [];

        foreach ($properties as $key => $value) {
            $name = strtolower((string) $key);

            foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
                if (str_contains($name, $fragment)) {
                    $masked[$key] = '[masked]';

                    continue 2;
                }
            }

            $masked[$key] = is_array($value) ? self::mask($value) : $value;
        }

        return $masked;
    }

    /**
     * Delete audit rows older than the retention window. Uses the query builder
     * (not the model) so the append-only model guards do not block pruning.
     */
    public static function prune(int $retentionDays): int
    {
        return DB::table('activity_log')
            ->where('created_at', '<', now()->subDays($retentionDays)->toDateTimeString())
            ->delete();
    }
}
