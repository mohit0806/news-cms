<x-layouts::app :title="__('New page')">
    <x-ui.page-header title="New page" sub="Standalone content." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('pages.store') }}">
        @csrf
        @include('pages.form', ['page' => null])

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create page') }}</flux:button>
            <flux:button :href="route('pages.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
