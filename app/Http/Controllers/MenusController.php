<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MenusController extends Controller
{
    public function index(): View
    {
        $menus = Menu::query()->orderBy('language')->orderBy('location')->get();

        return view('menus.index', ['menus' => $menus]);
    }

    public function create(): View
    {
        return view('menus.create', ['languages' => $this->languages()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', Rule::in(['header', 'footer', 'mobile'])],
            'language' => ['required', 'string', Rule::in($this->languages())],
        ]);

        if (Menu::query()
            ->where('location', $validated['location'])
            ->where('language', $validated['language'])
            ->exists()) {
            throw ValidationException::withMessages([
                'location' => __('A menu already exists for this location and language.'),
            ]);
        }

        $menu = Menu::create($validated);

        return redirect()->route('menus.edit', $menu)->with('status', __('Menu created.'));
    }

    public function edit(Menu $menu): View
    {
        $menu->load(['items' => fn ($query) => $query->orderBy('sort')]);

        return view('menus.edit', ['menu' => $menu]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $menu->forceFill($validated)->save();

        return redirect()->route('menus.index')->with('status', __('Menu updated.'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('menus.index')->with('status', __('Menu deleted.'));
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['category', 'page', 'article', 'tag', 'url'])],
            'target_id' => ['nullable', 'integer'],
            'url' => ['nullable', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
            'open_new_tab' => ['nullable', 'boolean'],
        ]);

        if ($validated['type'] === 'url' && empty($validated['url'])) {
            throw ValidationException::withMessages(['url' => __('A URL is required for link items.')]);
        }

        if ($validated['type'] !== 'url' && empty($validated['target_id'])) {
            throw ValidationException::withMessages(['target_id' => __('A target is required for this item type.')]);
        }

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($parentId !== null) {
            $parent = $menu->items()->findOrFail($parentId);

            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages(['parent_id' => __('Menu items nest up to 2 levels.')]);
            }
        }

        $menu->items()->create([
            'parent_id' => $parentId,
            'label' => $validated['label'],
            'type' => $validated['type'],
            'target_id' => isset($validated['target_id']) ? (int) $validated['target_id'] : null,
            'url' => $validated['url'] ?? null,
            'open_new_tab' => (bool) ($validated['open_new_tab'] ?? false),
            'sort' => (int) $menu->items()->max('sort') + 1,
            'is_active' => true,
        ]);

        return redirect()->route('menus.edit', $menu)->with('status', __('Menu item added.'));
    }

    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->getKey()) {
            abort(404);
        }

        $item->delete();

        return redirect()->route('menus.edit', $menu)->with('status', __('Menu item removed.'));
    }

    public function reorder(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort' => ['required', 'integer', 'min:0'],
        ]);

        $rows = [];

        foreach ((array) ($validated['items'] ?? []) as $row) {
            $rows[] = [
                'id' => (int) (is_array($row) ? ($row['id'] ?? 0) : 0),
                'sort' => (int) (is_array($row) ? ($row['sort'] ?? 0) : 0),
            ];
        }

        $ids = array_column($rows, 'id');
        $owned = $menu->items()->whereKey($ids)->pluck('id')->all();

        if (count($owned) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['items' => __('Unknown menu item in order.')]);
        }

        foreach ($rows as $row) {
            $menu->items()->whereKey($row['id'])->update(['sort' => $row['sort']]);
        }

        return redirect()->route('menus.edit', $menu)->with('status', __('Menu order saved.'));
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
