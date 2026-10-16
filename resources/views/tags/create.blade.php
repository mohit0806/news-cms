<x-layouts::app :title="__('New tag')">
    <x-ui.page-header title="New tag" sub="Reporters may create tags; curation stays editorial." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('tags.store') }}">
        @csrf
        <x-ui.form-errors />

        @foreach ($languages as $language)
            <fieldset class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                <legend class="px-1 text-sm font-medium">{{ __('Content language: :lang', ['lang' => $language]) }}</legend>
                <div class="grid gap-4">
                    <flux:input :name="'translations['.$language.'][name]'" :label="__('Name')" :value="old('translations.'.$language.'.name')" />
                    <x-ui.slug-field :name="'translations['.$language.'][slug]'" :label="__('Slug')" :value="old('translations.'.$language.'.slug')" :hint="__('Leave blank to generate from the name.')" />
                </div>
            </fieldset>
        @endforeach

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create tag') }}</flux:button>
            <flux:button :href="route('tags.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
