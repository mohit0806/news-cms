<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Articles\SaveArticle;
use App\Actions\Articles\TransitionArticle;
use App\Exceptions\ConflictException;
use App\Models\Article;
use App\Models\Author;
use App\Models\Term;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ArticlesController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,in_review,scheduled,published,unpublished,trashed'],
            'language' => ['nullable', 'string', 'max:5'],
            'category' => ['nullable', 'integer'],
            'author' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        if (isset($validated['per_page'])) {
            session(['articles.per_page' => $validated['per_page']]);
        }

        $query = ($validated['status'] ?? null) === 'trashed'
            ? Article::onlyTrashed()
            : Article::query();

        $articles = $query
            ->when(! empty($validated['search']), fn ($q) => $q->where(
                fn ($qq) => $qq->where('title', 'like', '%'.$validated['search'].'%')
                    ->orWhere('slug', 'like', '%'.$validated['search'].'%')
            ))
            ->when(! empty($validated['status']) && $validated['status'] !== 'trashed',
                fn ($q) => $q->where('status', $validated['status']))
            ->when(! empty($validated['language']), fn ($q) => $q->where('language', $validated['language']))
            ->when(! empty($validated['category']), fn ($q) => $q->where('primary_category_id', (int) $validated['category']))
            ->when(! empty($validated['author']), fn ($q) => $q->where('author_id', (int) $validated['author']))
            ->with(['primaryCategory.translations', 'author'])
            ->orderByDesc('updated_at')
            ->paginate(session('articles.per_page', 15))
            ->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'filters' => $validated,
            'categories' => Term::query()->where('vocabulary', 'category')->with([
                'translations' => fn ($q) => $q->where('language', config('app.locale', 'en')),
            ])->orderBy('sort')->get(),
            'authors' => Author::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('articles.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $article = SaveArticle::run($request->user(), null, $request->all());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('articles.edit', $article)->with('status', __('Article created as draft.'));
    }

    public function edit(Article $article): View
    {
        $this->authorize('update', $article);

        return view('articles.edit', ['article' => $article->load(['coAuthors', 'relatedArticles'])] + $this->formData());
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        try {
            $updated = SaveArticle::run($request->user(), $article, $request->all());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (ConflictException $exception) {
            return back()
                ->withErrors(['conflict' => $exception->getMessage()])
                ->withInput($request->except('expected_row_version'));
        }

        return redirect()->route('articles.edit', $updated)->with('status', __('Article saved.'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        return redirect()->route('articles.index')->with('status', __('Article moved to trash.'));
    }

    public function submit(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('submit', $article);

        try {
            TransitionArticle::run($request->user(), $article, 'in_review');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('articles.edit', $article)->with('status', __('Submitted for review.'));
    }

    public function reject(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        try {
            TransitionArticle::run($request->user(), $article, 'draft', ['note' => $validated['note']]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('articles.edit', $article)->with('status', __('Sent back to draft.'));
    }

    public function publish(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('publish', $article);

        $validated = $request->validate([
            'published_at' => ['nullable', 'date'],
            'is_breaking' => ['nullable', 'boolean'],
            'breaking_until' => ['nullable', 'date'],
        ]);

        try {
            $updated = TransitionArticle::run($request->user(), $article, 'published', [
                'published_at' => isset($validated['published_at']) && $validated['published_at'] !== ''
                    ? $validated['published_at']
                    : null,
                'is_breaking' => (bool) ($validated['is_breaking'] ?? $article->is_breaking),
                'breaking_until' => $validated['breaking_until'] ?? null,
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $message = $updated->status === 'scheduled'
            ? __('Scheduled for :date.', ['date' => $updated->published_at?->toDateTimeString()])
            : __('Article published.');

        return redirect()->route('articles.edit', $updated)->with('status', $message);
    }

    public function unpublish(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('unpublish', $article);

        try {
            TransitionArticle::run($request->user(), $article, $article->status === 'scheduled' ? 'draft' : 'unpublished');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('articles.edit', $article)->with('status', __('Article taken down.'));
    }

    public function restore(int $id, Request $request): RedirectResponse
    {
        $article = Article::withTrashed()->findOrFail($id);

        $this->authorize('restore', $article);

        $article->restore();

        return redirect()->route('articles.index')->with('status', __('Article restored from trash.'));
    }

    public function autosave(Request $request, Article $article): JsonResponse
    {
        $this->authorize('update', $article);

        try {
            SaveArticle::autosave($request->user(), $article, $request->all());
        } catch (ValidationException $exception) {
            return response()->json(['errors' => $exception->errors()], 422);
        }

        return response()->json(['saved_at' => now()->toDateTimeString()]);
    }

    /**
     * @return array{categories: Collection<int, Term>, authors: Collection<int, Author>, tags: Collection<int, Term>, languages: list<string>, recent: Collection<int, Article>}
     */
    private function formData(): array
    {
        return [
            'categories' => Term::query()->where('vocabulary', 'category')->with([
                'translations' => fn ($q) => $q->where('language', config('app.locale', 'en')),
            ])->orderBy('sort')->get(),
            'authors' => Author::query()->where('is_active', true)->orderBy('name')->get(),
            'tags' => Term::query()->where('vocabulary', 'tag')->with([
                'translations' => fn ($q) => $q->where('language', config('app.locale', 'en')),
            ])->orderByDesc('id')->limit(200)->get(),
            'languages' => $this->languages(),
            'recent' => Article::query()->orderByDesc('id')->limit(200)->get(['id', 'title']),
        ];
    }

    /**
     * @return list<string>
     */
    private function languages(): array
    {
        $languages = config('app.site_languages', ['en']);

        return is_array($languages) && $languages !== [] ? array_values($languages) : ['en'];
    }
}
