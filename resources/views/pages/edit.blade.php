<x-layouts::app :title="__('Edit page')">
    <x-ui.page-header title="Edit page" sub="Content, template, and SEO." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('pages.update', $page) }}">
        @csrf
        @method('PUT')
        @include('pages.form')

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save page') }}</flux:button>
            <flux:button :href="route('pages.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    <form method="POST" action="{{ route('pages.destroy', $page) }}" class="mt-8">
        @csrf
        @method('DELETE')
        <flux:button type="submit" variant="danger">{{ __('Move to trash') }}</flux:button>
    </form>
</x-layouts::app>
