<x-layouts::app :title="__('New category')">
    <x-ui.page-header title="New category" sub="Top-level or nested section." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('categories.store') }}" x-data="{ dirty: false }" @change="dirty = true" @submit="dirty = false">
        @csrf
        @include('categories.form', ['category' => null])

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create category') }}</flux:button>
            <flux:button :href="route('categories.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
