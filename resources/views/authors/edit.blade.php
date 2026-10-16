<x-layouts::app :title="__('Edit author')">
    <x-ui.page-header title="Edit author" sub="Byline profile and status." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('authors.update', $author) }}">
        @csrf
        @method('PUT')
        @include('authors.form')

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save author') }}</flux:button>
            <flux:button :href="route('authors.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    <div class="mt-8 flex items-center gap-2">
        @if ($author->is_active)
            <form method="POST" action="{{ route('authors.deactivate', $author) }}">
                @csrf
                <flux:button type="submit" variant="danger">{{ __('Deactivate author') }}</flux:button>
            </form>
        @else
            <form method="POST" action="{{ route('authors.activate', $author) }}">
                @csrf
                <flux:button type="submit" variant="primary">{{ __('Activate author') }}</flux:button>
            </form>
        @endif
    </div>
</x-layouts::app>
