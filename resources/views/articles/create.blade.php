<x-layouts::app :title="__('New article')">
    <x-ui.page-header title="New article" sub="Saved as draft." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('articles.store') }}">
        @csrf
        @include('articles.form', ['article' => null])

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create draft') }}</flux:button>
            <flux:button :href="route('articles.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
