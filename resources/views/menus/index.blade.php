<x-layouts::app :title="__('Menus')">
    <x-ui.page-header title="Menus" sub="Header, footer, and mobile navigation per language.">
        <x-slot:actions>
            <flux:button :href="route('menus.create')" variant="primary" wire:navigate>{{ __('New menu') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Location') }}</flux:table.column>
            <flux:table.column>{{ __('Language') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($menus as $menu)
                <flux:table.row :key="$menu->getKey()">
                    <flux:table.cell><a href="{{ route('menus.edit', $menu) }}" wire:navigate>{{ $menu->name }}</a></flux:table.cell>
                    <flux:table.cell>{{ $menu->location }}</flux:table.cell>
                    <flux:table.cell>{{ $menu->language }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <x-ui.empty-state title="No menus" body="Create one menu per location and language." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-layouts::app>
