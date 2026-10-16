<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\HomepageUpdated;
use App\Models\LayoutBlock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'language' => ['nullable', 'string', 'max:5'],
        ]);

        $languages = $this->languages();
        $language = $validated['language'] ?? $languages[0];

        if (! in_array($language, $languages, true)) {
            $language = $languages[0];
        }

        $blocks = LayoutBlock::query()
            ->where('language', $language)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return view('homepage.index', [
            'blocks' => $blocks,
            'language' => $language,
            'languages' => $languages,
        ]);
    }

    public function create(Request $request): View
    {
        return view('homepage.create', [
            'languages' => $this->languages(),
            'language' => $request->query('language', $this->languages()[0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBlock($request);
        $maxSort = LayoutBlock::query()
            ->where('language', $validated['language'])
            ->max('sort');
        $validated['sort'] = $maxSort === null ? 0 : ((int) $maxSort) + 1;

        $block = LayoutBlock::create($validated);

        event(new HomepageUpdated($block->language));

        return redirect()
            ->route('homepage.index', ['language' => $block->language])
            ->with('status', __('Homepage block added.'));
    }

    public function edit(LayoutBlock $block): View
    {
        return view('homepage.edit', ['block' => $block, 'languages' => $this->languages()]);
    }

    public function update(Request $request, LayoutBlock $block): RedirectResponse
    {
        $validated = $this->validateBlock($request);
        unset($validated['sort']);

        $block->forceFill($validated)->save();

        event(new HomepageUpdated($block->language));

        return redirect()
            ->route('homepage.index', ['language' => $block->language])
            ->with('status', __('Homepage block updated.'));
    }

    public function destroy(LayoutBlock $block): RedirectResponse
    {
        $language = $block->language;
        $block->delete();

        event(new HomepageUpdated($language));

        return redirect()
            ->route('homepage.index', ['language' => $language])
            ->with('status', __('Homepage block removed.'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string'],
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer'],
            'order.*.sort' => ['required', 'integer', 'min:0'],
        ]);

        $language = (string) $validated['language'];
        $rows = [];

        foreach ((array) ($validated['order'] ?? []) as $row) {
            $rows[] = [
                'id' => (int) (is_array($row) ? ($row['id'] ?? 0) : 0),
                'sort' => (int) (is_array($row) ? ($row['sort'] ?? 0) : 0),
            ];
        }

        $ids = array_column($rows, 'id');
        $owned = LayoutBlock::query()
            ->where('language', $language)
            ->whereKey($ids)
            ->pluck('id')
            ->all();

        if (count($owned) !== count(array_unique($ids))) {
            return back()->withErrors(['order' => __('Unknown block in order.')]);
        }

        foreach ($rows as $row) {
            LayoutBlock::query()->whereKey($row['id'])->update(['sort' => $row['sort']]);
        }

        event(new HomepageUpdated($language));

        return redirect()
            ->route('homepage.index', ['language' => $language])
            ->with('status', __('Homepage order saved.'));
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
     * @return array{region: string, language: string, widget: string, title: string|null, config_json: array<string, mixed>|null, sort: int, is_active: bool}
     */
    private function validateBlock(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        $validated = $request->validate([
            'language' => ['required', 'string', Rule::in($this->languages())],
            'widget' => ['required', 'string', Rule::in(LayoutBlock::WIDGETS)],
            'title' => ['nullable', 'string', 'max:255'],
            'config' => ['nullable', 'json'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validated['widget'] === 'custom-html' && ! $request->user()->can('blocks.raw_html')) {
            abort(403, 'Custom HTML blocks require the raw HTML permission.');
        }

        $config = isset($validated['config']) && $validated['config'] !== ''
            ? json_decode((string) $validated['config'], true)
            : null;

        return [
            'region' => 'home',
            'language' => (string) $validated['language'],
            'widget' => (string) $validated['widget'],
            'title' => $validated['title'] ?? null,
            'config_json' => is_array($config) ? $config : null,
            'sort' => 0,
            'is_active' => (bool) $validated['is_active'],
        ];
    }
}
