<x-layouts::app :title="__('New author')">
    <x-ui.page-header title="New author" sub="Byline profile." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('authors.store') }}">
        @csrf
        @include('authors.form', ['author' => null])

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create author') }}</flux:button>
            <flux:button :href="route('authors.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
