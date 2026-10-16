<x-layouts::app :title="__('New menu')">
    <x-ui.page-header title="New menu" sub="One menu per location and language." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('menus.store') }}">
        @csrf
        <x-ui.form-errors />

        <div class="grid gap-4 md:grid-cols-3">
            <flux:input name="name" :label="__('Name')" :value="old('name')" />
            <flux:select name="location" :label="__('Location')">
                @foreach (['header', 'footer', 'mobile'] as $location)
                    <option value="{{ $location }}" @selected(old('location') === $location)>{{ $location }}</option>
                @endforeach
            </flux:select>
            <flux:select name="language" :label="__('Language')">
                @foreach ($languages as $language)
                    <option value="{{ $language }}" @selected(old('language', 'en') === $language)>{{ $language }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create menu') }}</flux:button>
            <flux:button :href="route('menus.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
