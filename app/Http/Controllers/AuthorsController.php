<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Author;
use App\Support\Slugger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthorsController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'in:yes,no'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        if (isset($validated['per_page'])) {
            session(['authors.per_page' => $validated['per_page']]);
        }

        $authors = Author::query()
            ->when(! empty($validated['search']), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhere('slug', 'like', '%'.$validated['search'].'%')
            ))
            ->when(isset($validated['active']), fn ($query) => $query->where(
                'is_active', $validated['active'] === 'yes'
            ))
            ->withCount('articles')
            ->orderBy('name')
            ->paginate(session('authors.per_page', 15))
            ->withQueryString();

        return view('authors.index', ['authors' => $authors, 'filters' => $validated]);
    }

    public function create(): View
    {
        return view('authors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAuthor($request);

        Author::create($validated);

        return redirect()->route('authors.index')->with('status', __('Author created.'));
    }

    public function edit(Author $author): View
    {
        return view('authors.edit', ['author' => $author]);
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $validated = $this->validateAuthor($request, $author);

        $author->forceFill($validated)->save();

        return redirect()->route('authors.index')->with('status', __('Author updated.'));
    }

    public function deactivate(Author $author): RedirectResponse
    {
        $author->forceFill(['is_active' => false])->save();

        return redirect()->route('authors.index')->with('status', __('Author deactivated.'));
    }

    public function activate(Author $author): RedirectResponse
    {
        $author->forceFill(['is_active' => true])->save();

        return redirect()->route('authors.index')->with('status', __('Author activated.'));
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->articles()->exists()) {
            return back()->withErrors(['author' => __('This author has articles. Deactivate instead of deleting.')]);
        }

        $author->delete();

        return redirect()->route('authors.index')->with('status', __('Author deleted.'));
    }

    /**
     * @return array{name: string, slug: string, bio: string|null, email: string|null, social_json: array<string, string>|null, user_id: int|null, is_active: bool}
     */
    private function validateAuthor(Request $request, ?Author $author = null): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('authors', 'slug')->ignore($author?->getKey())],
            'bio' => ['nullable', 'string', 'max:5000'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'is_active' => ['required', 'boolean'],
        ]);

        $slug = trim((string) ($validated['slug'] ?? ''));

        if ($slug === '') {
            $slug = Slugger::unique(
                Slugger::slug($validated['name']),
                fn (string $candidate): bool => Author::query()
                    ->where('slug', $candidate)
                    ->when($author !== null, fn ($query) => $query->whereKeyNot($author->getKey()))
                    ->exists()
            );
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug,
            'bio' => $validated['bio'] ?? null,
            'email' => $validated['email'] ?? null,
            'social_json' => null,
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            'is_active' => (bool) $validated['is_active'],
        ];
    }
}
