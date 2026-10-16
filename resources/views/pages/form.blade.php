{{-- Page form fields (P2-T08.1). Expects $languages and optionally $page. Body is plain paragraphs until the block editor wires in (T04.3). --}}
<x-ui.form-errors />

<div class="grid gap-4 md:grid-cols-2">
    <flux:select name="language" :label="__('Content language')">
        @foreach ($languages as $language)
            <option value="{{ $language }}" @selected(old('language', $page->language ?? 'en') === $language)>{{ $language }}</option>
        @endforeach
    </flux:select>
    <flux:select name="template" :label="__('Template')">
        <option value="default" @selected(old('template', $page->template ?? 'default') === 'default')>{{ __('Default') }}</option>
        <option value="full-width" @selected(old('template', $page->template ?? '') === 'full-width')>{{ __('Full width') }}</option>
    </flux:select>
    <flux:input name="title" :label="__('Title')" :value="old('title', $page->title ?? '')" />
    <x-ui.slug-field name="slug" :label="__('Slug')" :value="old('slug', $page->slug ?? '')" :hint="__('Unique across pages, categories, and reserved routes.')" />
</div>

<div class="mt-4">
    <flux:textarea name="body" :label="__('Body (one paragraph per blank line)')" rows="8" :value="old('body', $bodyText ?? '')" />
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <flux:input name="meta_title" :label="__('Meta title')" :value="old('meta_title', $page->meta_title ?? '')" />
    <flux:input name="meta_description" :label="__('Meta description')" :value="old('meta_description', $page->meta_description ?? '')" />
    <flux:select name="status" :label="__('Status')">
        <option value="draft" @selected(old('status', $page->status ?? 'draft') === 'draft')>{{ __('Draft') }}</option>
        <option value="published" @selected(old('status', $page->status ?? '') === 'published')>{{ __('Published') }}</option>
    </flux:select>
    <flux:switch name="allow_indexing" :label="__('Allow indexing')" :checked="old('allow_indexing', $page->allow_indexing ?? true)" />
</div>
