<x-layouts::app :title="__('Tags')">
    <x-ui.page-header title="Tags" sub="Flat labels with per-language slugs.">
        <x-slot:actions>
            <flux:button :href="route('tags.create')" variant="primary" wire:navigate>{{ __('New tag') }}</flux:button>
            @can('categories.manage')
                <flux:button :href="route('tags.merge')" variant="ghost" wire:navigate>{{ __('Merge tags') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('tags.index') }}">
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
            <x-slot:meta>{{ trans_choice('{1} :count tag|[2,*] :count tags', $terms->total(), ['count' => $terms->total()]) }}</x-slot:meta>
        </x-ui.index-toolbar>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($terms as $term)
                @php($translation = $term->translation())
                <flux:table.row :key="$term->getKey()">
                    <flux:table.cell>{{ $translation?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell><span class="font-mono text-sm">{{ $translation?->slug ?? '—' }}</span></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="2">
                        <x-ui.empty-state title="No tags" body="No tags match these filters." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $terms->links() }}</div>
</x-layouts::app>
