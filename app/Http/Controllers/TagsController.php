<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Redirect;
use App\Models\Term;
use App\Models\TermTranslation;
use App\Support\Slugger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TagsController extends Controller
{
    /**
     * Reporter holds `tags.manage` create-only (Q-P1-01): index/create/store
     * pass on `tags.manage`, while edit/update/destroy/merge additionally
     * require `categories.manage` (editorial curation). Enforced server-side.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'in:yes,no'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        if (isset($validated['per_page'])) {
            session(['tags.per_page' => $validated['per_page']]);
        }

        $locale = (string) config('app.locale', 'en');

        $terms = Term::query()
            ->where('vocabulary', 'tag')
            ->with(['translations' => fn ($query) => $query->where('language', $locale)])
            ->when(! empty($validated['search']), fn ($query) => $query->whereHas(
                'translations',
                fn ($q) => $q->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhere('slug', 'like', '%'.$validated['search'].'%')
            ))
            ->when(isset($validated['active']), fn ($query) => $query->where(
                'is_active', $validated['active'] === 'yes'
            ))
            ->orderBy('id', 'desc')
            ->paginate(session('tags.per_page', 15))
            ->withQueryString();

        return view('tags.index', ['terms' => $terms, 'filters' => $validated]);
    }

    public function create(): View
    {
        return view('tags.create', ['languages' => $this->languages()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $translations = $this->validateTranslations($request);

        $term = Term::create(['vocabulary' => 'tag', 'is_active' => true]);

        foreach ($translations as $language => $row) {
            $term->translations()->create(['language' => $language] + $row);
        }

        return redirect()->route('tags.index')->with('status', __('Tag created.'));
    }

    public function mergeForm(): View
    {
        $tags = Term::query()
            ->where('vocabulary', 'tag')
            ->with(['translations' => fn ($query) => $query->where('language', config('app.locale', 'en'))])
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        return view('tags.merge', ['tags' => $tags]);
    }

    public function merge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_id' => ['required', 'integer', 'different:target_id'],
            'target_id' => ['required', 'integer'],
        ]);

        $source = Term::query()->where('vocabulary', 'tag')->findOrFail((int) $validated['source_id']);
        $target = Term::query()->where('vocabulary', 'tag')->findOrFail((int) $validated['target_id']);

        $sourceTranslation = $source->translation('en') ?? $source->translations->first();
        $targetTranslation = $target->translation('en') ?? $target->translations->first();
        $sourceSlug = $sourceTranslation?->slug;
        $targetSlug = $targetTranslation?->slug;

        DB::transaction(function () use ($source, $target, $sourceSlug, $targetSlug, $request): void {
            DB::table('termables')
                ->where('term_id', $source->getKey())
                ->whereNotExists(function ($query) use ($target): void {
                    $query->select(DB::raw(1))
                        ->from('termables as existing')
                        ->whereColumn('existing.termable_type', 'termables.termable_type')
                        ->whereColumn('existing.termable_id', 'termables.termable_id')
                        ->where('existing.term_id', $target->getKey());
                })
                ->update(['term_id' => $target->getKey()]);

            DB::table('termables')->where('term_id', $source->getKey())->delete();

            if ($sourceSlug !== null && $targetSlug !== null && $sourceSlug !== $targetSlug) {
                Redirect::updateOrCreate(
                    ['from_path' => '/tag/'.$sourceSlug],
                    ['to_url' => '/tag/'.$targetSlug, 'status_code' => 301, 'created_by' => $request->user()->getKey()]
                );
            }

            $source->delete();
        });

        return redirect()->route('tags.index')->with('status', __('Tags merged.'));
    }

    public function destroy(Term $tag): RedirectResponse
    {
        if ($tag->vocabulary !== 'tag') {
            abort(404);
        }

        $tag->delete();

        return redirect()->route('tags.index')->with('status', __('Tag deleted.'));
    }

    /**
     * @return list<string>
     */
    private function languages(): array
    {
        $languages = config('app.site_languages', ['en']);

        return is_array($languages) && $languages !== [] ? array_values($languages) : ['en'];
    }

    /**
     * @return array<string, array{name: string, slug: string}>
     */
    private function validateTranslations(Request $request): array
    {
        $languages = $this->languages();
        $default = (string) config('app.locale', 'en');

        $request->validate([
            'translations' => ['required', 'array'],
            "translations.{$default}.name" => ['required', 'string', 'max:255'],
        ]);

        $translations = [];

        foreach ($languages as $language) {
            $name = trim((string) $request->input("translations.{$language}.name", ''));
            $slug = trim((string) $request->input("translations.{$language}.slug", ''));

            if ($name === '' && $language !== $default) {
                continue;
            }

            if ($name === '') {
                throw ValidationException::withMessages([
                    "translations.{$language}.name" => __('A name is required.'),
                ]);
            }

            if ($slug === '') {
                $slug = Slugger::slug($name);
            }

            $taken = fn (string $candidate): bool => TermTranslation::query()
                ->where('language', $language)
                ->where('slug', $candidate)
                ->exists();

            $translations[$language] = [
                'name' => $name,
                'slug' => $taken($slug) ? Slugger::unique($slug, $taken) : $slug,
            ];
        }

        return $translations;
    }
}
