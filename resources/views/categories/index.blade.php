<x-layouts::app :title="__('Categories')">
    <x-ui.page-header title="Categories" sub="Three-level section hierarchy.">
        <x-slot:actions>
            <flux:button :href="route('categories.create')" variant="primary" wire:navigate>{{ __('New category') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('categories.index') }}">
        <x-ui.index-toolbar>
            <x-slot:filters>
                <flux:input name="search" :label="__('Search')" :value="$filters['search'] ?? ''" />
                <flux:select name="active" :label="__('Status')">
                    <option value="">{{ __('Any status') }}</option>
                    <option value="yes" @selected(($filters['active'] ?? '') === 'yes')>{{ __('Active') }}</option>
                    <option value="no" @selected(($filters['active'] ?? '') === 'no')>{{ __('Inactive') }}</option>
                </flux:select>
                <flux:button type="submit" variant="ghost">{{ __('Apply') }}</flux:button>
            </x-slot:filters>
            <x-slot:meta>{{ trans_choice('{1} :count category|[2,*] :count categories', $terms->total(), ['count' => $terms->total()]) }}</x-slot:meta>
        </x-ui.index-toolbar>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($terms as $term)
                @php($translation = $term->translation())
                <flux:table.row :key="$term->getKey()">
                    <flux:table.cell><a href="{{ route('categories.edit', $term) }}" wire:navigate>{{ $translation?->name ?? '—' }}</a></flux:table.cell>
                    <flux:table.cell><span class="font-mono text-sm">{{ $translation?->slug ?? '—' }}</span></flux:table.cell>
                    <flux:table.cell>
                        @if ($term->is_active)
                            <flux:badge color="green">Active</flux:badge>
                        @else
                            <flux:badge color="zinc">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <x-ui.empty-state title="No categories" body="No categories match these filters." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $terms->links() }}</div>
</x-layouts::app>
