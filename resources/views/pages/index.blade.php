<x-layouts::app :title="__('Pages')">
    <x-ui.page-header title="Pages" sub="Standalone content at top-level URLs.">
        <x-slot:actions>
            <flux:button :href="route('pages.create')" variant="primary" wire:navigate>{{ __('New page') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('pages.index') }}">
        <x-ui.index-toolbar>
            <x-slot:filters>
                <flux:input name="search" :label="__('Search')" :value="$filters['search'] ?? ''" />
                <flux:select name="status" :label="__('Status')">
                    <option value="">{{ __('Any status') }}</option>
                    <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>{{ __('Draft') }}</option>
                    <option value="published" @selected(($filters['status'] ?? '') === 'published')>{{ __('Published') }}</option>
                </flux:select>
                <flux:button type="submit" variant="ghost">{{ __('Apply') }}</flux:button>
            </x-slot:filters>
            <x-slot:meta>{{ trans_choice('{1} :count page|[2,*] :count pages', $pages->total(), ['count' => $pages->total()]) }}</x-slot:meta>
        </x-ui.index-toolbar>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($pages as $page)
                <flux:table.row :key="$page->getKey()">
                    <flux:table.cell><a href="{{ route('pages.edit', $page) }}" wire:navigate>{{ $page->title }}</a></flux:table.cell>
                    <flux:table.cell><span class="font-mono text-sm">/{{ $page->slug }}</span></flux:table.cell>
                    <flux:table.cell>
                        @if ($page->status === 'published')
                            <flux:badge color="green">Published</flux:badge>
                        @else
                            <flux:badge color="zinc">Draft</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <x-ui.empty-state title="No pages" body="No pages match these filters." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $pages->links() }}</div>
</x-layouts::app>
