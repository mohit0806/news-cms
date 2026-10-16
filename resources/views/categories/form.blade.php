{{-- Category form fields (P2-T01.2). Expects $languages, $parents, and optionally $category. --}}
<x-ui.form-errors />

@php($tabItems = [])
@foreach ($languages as $language)
    @php($tabItems[] = ['label' => $language, 'url' => '#lang-'.$language, 'active' => $loop->first])
@endforeach
<x-ui.language-tabs :items="$tabItems" />

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <flux:select name="parent_id" :label="__('Parent category')">
        <option value="">{{ __('Top level') }}</option>
        @foreach ($parents as $parent)
            @php($translation = $parent->translation())
            <option value="{{ $parent->getKey() }}" @selected((string) old('parent_id', $category->parent_id ?? '') === (string) $parent->getKey())>{{ $translation?->name ?? $parent->getKey() }}</option>
        @endforeach
    </flux:select>

    <flux:input name="sort" type="number" :label="__('Sort order')" :value="old('sort', $category->sort ?? 0)" />
    <flux:input name="color" :label="__('Accent color (hex)')" placeholder="#1d4ed8" :value="old('color', $category->color ?? '')" />

    <flux:switch name="show_in_menu" :label="__('Show in menus')" :checked="old('show_in_menu', $category->show_in_menu ?? false)" />
    <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $category->is_active ?? true)" />
</div>

@foreach ($languages as $language)
    @php($existing = isset($category) ? $category->translations->firstWhere('language', $language) : null)
    <fieldset id="lang-{{ $language }}" class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
        <legend class="px-1 text-sm font-medium">{{ __('Content language: :lang', ['lang' => $language]) }}</legend>
        <div class="grid gap-4">
            <flux:input :name="'translations['.$language.'][name]'" :label="__('Name')" :value="old('translations.'.$language.'.name', $existing?->name)" />
            <x-ui.slug-field :name="'translations['.$language.'][slug]'" :label="__('Slug')" :value="old('translations.'.$language.'.slug', $existing?->slug)" :hint="__('Leave blank to generate from the name.')" />
            <flux:textarea :name="'translations['.$language.'][description]'" :label="__('Description')" :value="old('translations.'.$language.'.description', $existing?->description)" />
        </div>
    </fieldset>
@endforeach

<x-ui.media-picker :label="__('Category image')">
    <x-slot:preview>
        <p class="text-sm text-zinc-500">{{ __('Image attachment wires up with the media library in T05.4.') }}</p>
    </x-slot:preview>
    <x-slot:trigger>
        <flux:button type="button" x-data @click="$dispatch('media-picker:open')">{{ __('Choose image') }}</flux:button>
    </x-slot:trigger>
</x-ui.media-picker>
