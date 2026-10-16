<x-layouts::app :title="__('Edit menu')">
    <x-ui.page-header :title="$menu->name" sub="Items, order, and link health." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('menus.update', $menu) }}" class="flex flex-wrap items-end gap-2">
        @csrf
        @method('PUT')
        <flux:input name="name" :label="__('Name')" :value="old('name', $menu->name)" />
        <flux:button type="submit" variant="primary">{{ __('Rename') }}</flux:button>
    </form>

    <h2 class="mt-8 text-base font-semibold">{{ __('Items') }}</h2>

    <form method="POST" action="{{ route('menus.items.reorder', $menu) }}">
        @csrf
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Label') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Health') }}</flux:table.column>
                <flux:table.column>{{ __('Sort') }}</flux:table.column>
                <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($menu->items as $item)
                    <flux:table.row :key="$item->getKey()">
                        <flux:table.cell>
                            @if ($item->parent_id !== null)<span class="mr-1 text-zinc-400">↳</span>@endif
                            {{ $item->label }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $item->type }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($item->isBroken())
                                <flux:badge color="red">Broken link</flux:badge>
                            @else
                                <flux:badge color="green">OK</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:input :name="'items['.$loop->index.'][sort]'" type="number" :value="$item->sort" />
                            <input type="hidden" :name="'items['.$loop->index.'][id]'" :value="$item->getKey()" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <button type="submit" form="delete-item-{{ $item->getKey() }}" class="text-sm text-red-600">{{ __('Remove') }}</button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-ui.empty-state title="No items" body="Add the first link below." />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($menu->items->isNotEmpty())
            <div class="mt-2">
                <flux:button type="submit" variant="ghost">{{ __('Save order') }}</flux:button>
            </div>
        @endif
    </form>

    @foreach ($menu->items as $item)
        <form id="delete-item-{{ $item->getKey() }}" method="POST" action="{{ route('menus.items.destroy', [$menu, $item]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    <h2 class="mt-8 text-base font-semibold">{{ __('Add item') }}</h2>

    <form method="POST" action="{{ route('menus.items.store', $menu) }}" class="mt-2 grid gap-4 md:grid-cols-2">
        @csrf
        <x-ui.form-errors />

        <flux:input name="label" :label="__('Label')" :value="old('label')" />
        <flux:select name="type" :label="__('Type')">
            @foreach (['category', 'page', 'article', 'tag', 'url'] as $type)
                <option value="{{ $type }}" @selected(old('type') === $type)>{{ $type }}</option>
            @endforeach
        </flux:select>
        <flux:input name="target_id" type="number" :label="__('Target ID (non-URL types)')" :value="old('target_id')" />
        <flux:input name="url" :label="__('URL (link items)')" :value="old('url')" />
        <flux:input name="parent_id" type="number" :label="__('Parent item ID (max 2 levels)')" :value="old('parent_id')" />
        <flux:switch name="open_new_tab" :label="__('Open in new tab')" :checked="old('open_new_tab', false)" />

        <div>
            <flux:button type="submit" variant="primary">{{ __('Add item') }}</flux:button>
        </div>
    </form>

    <form method="POST" action="{{ route('menus.destroy', $menu) }}" class="mt-8">
        @csrf
        @method('DELETE')
        <flux:button type="submit" variant="danger">{{ __('Delete menu') }}</flux:button>
    </form>
</x-layouts::app>
