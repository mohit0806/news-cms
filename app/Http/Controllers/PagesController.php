<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\TermTranslation;
use App\Support\Editor\BodyRenderer;
use App\Support\ReservedSlugs;
use App\Support\Slugger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PagesController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,published'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        if (isset($validated['per_page'])) {
            session(['pages.per_page' => $validated['per_page']]);
        }

        $pages = Page::query()
            ->when(! empty($validated['search']), fn ($query) => $query->where(
                fn ($q) => $q->where('title', 'like', '%'.$validated['search'].'%')
                    ->orWhere('slug', 'like', '%'.$validated['search'].'%')
            ))
            ->when(! empty($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->orderByDesc('updated_at')
            ->paginate(session('pages.per_page', 15))
            ->withQueryString();

        return view('pages.index', ['pages' => $pages, 'filters' => $validated]);
    }

    public function create(): View
    {
        return view('pages.create', ['languages' => $this->languages()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePage($request);

        Page::create($validated + [
            'created_by' => $request->user()->getKey(),
            'updated_by' => $request->user()->getKey(),
        ]);

        return redirect()->route('pages.index')->with('status', __('Page created.'));
    }

    public function edit(Page $page): View
    {
        $bodyText = $page->body_json === null
            ? ''
            : (new BodyRenderer)->render($page->body_json)->text;

        return view('pages.edit', ['page' => $page, 'languages' => $this->languages(), 'bodyText' => $bodyText]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $this->validatePage($request, $page);

        $page->forceFill($validated + ['updated_by' => $request->user()->getKey()])->save();

        return redirect()->route('pages.index')->with('status', __('Page updated.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('pages.index')->with('status', __('Page moved to trash.'));
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
     * @return array{language: string, title: string, slug: string, body_json: array<string, mixed>, body_html: string, template: string, status: string, meta_title: string|null, meta_description: string|null, allow_indexing: bool}
     */
    private function validatePage(Request $request, ?Page $page = null): array
    {
        $request->merge(['allow_indexing' => $request->boolean('allow_indexing')]);

        $validated = $request->validate([
            'language' => ['required', 'string', Rule::in($this->languages())],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:190'],
            'body' => ['nullable', 'string', 'max:100000'],
            'template' => ['required', 'string', Rule::in(['default', 'full-width'])],
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'allow_indexing' => ['required', 'boolean'],
        ]);

        $language = (string) $validated['language'];
        $slug = trim((string) ($validated['slug'] ?? ''));

        if ($slug === '') {
            $slug = Slugger::slug($validated['title']);
        }

        if (ReservedSlugs::isReserved($slug)) {
            throw ValidationException::withMessages(['slug' => __('This slug is reserved for site routes.')]);
        }

        $taken = Page::query()
            ->where('language', $language)
            ->where('slug', $slug)
            ->when($page !== null, fn ($query) => $query->whereKeyNot($page->getKey()))
            ->exists()
            || TermTranslation::query()
                ->where('language', $language)
                ->where('slug', $slug)
                ->whereHas('term', fn ($query) => $query->where('vocabulary', 'category'))
                ->exists();

        if ($taken) {
            $slug = Slugger::unique($slug, fn (string $candidate): bool => Page::query()
                ->where('language', $language)
                ->where('slug', $candidate)
                ->when($page !== null, fn ($query) => $query->whereKeyNot($page->getKey()))
                ->exists()
                || ReservedSlugs::isReserved($candidate)
                || TermTranslation::query()
                    ->where('language', $language)
                    ->where('slug', $candidate)
                    ->whereHas('term', fn ($query) => $query->where('vocabulary', 'category'))
                    ->exists());
        }

        $paragraphs = array_values(array_filter(
            preg_split('/\R{2,}/', trim((string) ($validated['body'] ?? ''))) ?: []
        ));

        $doc = [
            'schema' => 1,
            'type' => 'doc',
            'content' => array_map(
                fn (string $paragraph): array => [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $paragraph]],
                ],
                $paragraphs
            ),
        ];

        $rendered = (new BodyRenderer)->render($doc);

        return [
            'language' => $language,
            'title' => $validated['title'],
            'slug' => $slug,
            'body_json' => $doc,
            'body_html' => $rendered->html,
            'template' => $validated['template'],
            'status' => $validated['status'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'allow_indexing' => (bool) $validated['allow_indexing'],
        ];
    }
}
