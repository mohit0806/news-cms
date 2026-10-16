<x-layouts::app :title="__('Authors')">
    <x-ui.page-header title="Authors" sub="Public bylines, separate from staff accounts.">
        <x-slot:actions>
            <flux:button :href="route('authors.create')" variant="primary" wire:navigate>{{ __('New author') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('authors.index') }}">
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
            <x-slot:meta>{{ trans_choice('{1} :count author|[2,*] :count authors', $authors->total(), ['count' => $authors->total()]) }}</x-slot:meta>
        </x-ui.index-toolbar>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column>{{ __('Articles') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($authors as $author)
                <flux:table.row :key="$author->getKey()">
                    <flux:table.cell><a href="{{ route('authors.edit', $author) }}" wire:navigate>{{ $author->name }}</a></flux:table.cell>
                    <flux:table.cell><span class="font-mono text-sm">{{ $author->slug }}</span></flux:table.cell>
                    <flux:table.cell>{{ $author->articles_count }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($author->is_active)
                            <flux:badge color="green">Active</flux:badge>
                        @else
                            <flux:badge color="zinc">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <x-ui.empty-state title="No authors" body="No authors match these filters." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $authors->links() }}</div>
</x-layouts::app>
