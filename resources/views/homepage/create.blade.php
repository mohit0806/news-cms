<x-layouts::app :title="__('New homepage block')">
    <x-ui.page-header title="New block" sub="Widget, picks, and visibility." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('homepage.store') }}">
        @csrf
        @include('homepage.form', ['block' => null])

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Add block') }}</flux:button>
            <flux:button :href="route('homepage.index', ['language' => $language])" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
