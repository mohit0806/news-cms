<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Term;
use App\Models\TermTranslation;
use App\Support\ReservedSlugs;
use App\Support\Slugger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriesController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'in:yes,no'],
            'sort' => ['nullable', 'in:name,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        if (isset($validated['per_page'])) {
            session(['categories.per_page' => $validated['per_page']]);
        }

        $locale = (string) config('app.locale', 'en');

        $terms = Term::query()
            ->where('vocabulary', 'category')
            ->with(['translations' => fn ($query) => $query->where('language', $locale)])
            ->when(! empty($validated['search']), fn ($query) => $query->whereHas(
                'translations',
                fn ($q) => $q->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhere('slug', 'like', '%'.$validated['search'].'%')
            ))
            ->when(isset($validated['active']), fn ($query) => $query->where(
                'is_active', $validated['active'] === 'yes'
            ))
            ->orderBy('sort')
            ->orderBy('id')
            ->paginate(session('categories.per_page', 15))
            ->withQueryString();

        return view('categories.index', ['terms' => $terms, 'filters' => $validated]);
    }

    public function create(): View
    {
        return view('categories.create', [
            'languages' => $this->languages(),
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTerm($request);

        $term = Term::create([
            'vocabulary' => 'category',
            'parent_id' => $validated['parent_id'],
            'sort' => $validated['sort'],
            'color' => $validated['color'],
            'show_in_menu' => $validated['show_in_menu'],
            'is_active' => $validated['is_active'],
        ]);

        $this->saveTranslations($term, $validated['translations']);

        return redirect()->route('categories.index')->with('status', __('Category created.'));
    }

    public function edit(Term $category): RedirectResponse|View
    {
        if ($category->vocabulary !== 'category') {
            abort(404);
        }

        return view('categories.edit', [
            'category' => $category->load('translations'),
            'languages' => $this->languages(),
            'parents' => $this->parentOptions($category->getKey()),
        ]);
    }

    public function update(Request $request, Term $category): RedirectResponse
    {
        if ($category->vocabulary !== 'category') {
            abort(404);
        }

        $validated = $this->validateTerm($request, $category);

        $category->forceFill([
            'parent_id' => $validated['parent_id'],
            'sort' => $validated['sort'],
            'color' => $validated['color'],
            'show_in_menu' => $validated['show_in_menu'],
            'is_active' => $validated['is_active'],
        ])->save();

        $this->saveTranslations($category, $validated['translations']);

        return redirect()->route('categories.index')->with('status', __('Category updated.'));
    }

    public function destroy(Term $category): RedirectResponse
    {
        if ($category->vocabulary !== 'category') {
            abort(404);
        }

        if ($category->children()->exists()) {
            return back()->withErrors(['category' => __('Reassign or delete child categories first.')]);
        }

        if ($this->hasArticles($category)) {
            return back()->withErrors(['category' => __('This category has articles. Reassign them before deleting.')]);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', __('Category deleted.'));
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
     * @return Collection<int, Term>
     */
    private function parentOptions(?int $except = null): Collection
    {
        return Term::query()
            ->where('vocabulary', 'category')
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->with(['translations' => fn ($query) => $query->where('language', config('app.locale', 'en'))])
            ->orderBy('sort')
            ->get()
            ->filter(fn (Term $term): bool => $term->depth() < Term::MAX_DEPTH);
    }

    /**
     * @return array{parent_id: int|null, sort: int, color: string|null, show_in_menu: bool, is_active: bool, translations: array<string, array{name: string, slug: string, description: string|null}>}
     */
    private function validateTerm(Request $request, ?Term $category = null): array
    {
        $languages = $this->languages();
        $default = (string) config('app.locale', 'en');

        $request->merge([
            'show_in_menu' => $request->boolean('show_in_menu'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where('vocabulary', 'category')],
            'sort' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_in_menu' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'translations' => ['required', 'array'],
            "translations.{$default}.name" => ['required', 'string', 'max:255'],
        ]);

        foreach ($languages as $language) {
            $request->validate([
                "translations.{$language}.name" => ['nullable', 'string', 'max:255'],
                "translations.{$language}.slug" => ['nullable', 'string', 'max:190'],
                "translations.{$language}.description" => ['nullable', 'string', 'max:2000'],
            ]);
        }

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($parentId !== null) {
            $parent = Term::query()->where('vocabulary', 'category')->findOrFail($parentId);

            if ($parent->depth() >= Term::MAX_DEPTH || ($category !== null && $parent->getKey() === $category->getKey())) {
                throw ValidationException::withMessages(['parent_id' => __('Categories nest up to 3 levels.')]);
            }
        }

        $translations = [];

        foreach ($languages as $language) {
            $input = $request->input("translations.{$language}", []);
            $name = trim((string) ($input['name'] ?? ''));

            if ($name === '' && $language !== $default) {
                continue;
            }

            $slug = trim((string) ($input['slug'] ?? ''));

            if ($slug === '') {
                $slug = Slugger::slug($name);
            }

            if (ReservedSlugs::isReserved($slug)) {
                throw ValidationException::withMessages([
                    "translations.{$language}.slug" => __('This slug is reserved for site routes.'),
                ]);
            }

            $taken = fn (string $candidate): bool => TermTranslation::query()
                ->where('language', $language)
                ->where('slug', $candidate)
                ->when($category !== null, fn ($query) => $query->whereHas(
                    'term',
                    fn ($q) => $q->whereKeyNot($category->getKey())
                ))
                ->exists();

            $translations[$language] = [
                'name' => $name,
                'slug' => $taken($slug) ? Slugger::unique($slug, $taken) : $slug,
                'description' => $input['description'] ?? null,
            ];
        }

        return [
            'parent_id' => $parentId,
            'sort' => (int) ($validated['sort'] ?? 0),
            'color' => $validated['color'] ?? null,
            'show_in_menu' => (bool) $validated['show_in_menu'],
            'is_active' => (bool) $validated['is_active'],
            'translations' => $translations,
        ];
    }

    /**
     * @param  array<string, array{name: string, slug: string, description: string|null}>  $translations
     */
    private function saveTranslations(Term $term, array $translations): void
    {
        foreach ($translations as $language => $row) {
            $term->translations()->updateOrCreate(
                ['language' => $language],
                ['name' => $row['name'], 'slug' => $row['slug'], 'description' => $row['description']]
            );
        }

        $term->translations()->whereNotIn('language', array_keys($translations))->delete();
    }

    private function hasArticles(Term $term): bool
    {
        return Article::query()
            ->where('primary_category_id', $term->getKey())
            ->exists()
            || DB::table('termables')
                ->where('term_id', $term->getKey())
                ->where('termable_type', Article::class)
                ->exists();
    }
}
