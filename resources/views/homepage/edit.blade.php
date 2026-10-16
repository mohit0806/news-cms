<x-layouts::app :title="__('Edit homepage block')">
    <x-ui.page-header title="Edit block" sub="Widget, picks, and visibility." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('homepage.update', $block) }}">
        @csrf
        @method('PUT')
        @include('homepage.form')

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save block') }}</flux:button>
            <flux:button :href="route('homepage.index', ['language' => $block->language])" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    <form method="POST" action="{{ route('homepage.destroy', $block) }}" class="mt-8">
        @csrf
        @method('DELETE')
        <flux:button type="submit" variant="danger">{{ __('Remove block') }}</flux:button>
    </form>
</x-layouts::app>
