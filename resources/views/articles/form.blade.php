{{-- Article form fields (P2-T03.3). Expects $categories, $authors, $tags, $languages, and optionally $article. Body stays a JSON document until the TipTap editor wires in (T04.3). --}}
<x-ui.form-errors />

@if ($errors->has('conflict'))
    <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        {{ $errors->first('conflict') }}
        <p class="mt-1">{{ __('Reload this page to see their version, then re-apply your changes or copy your text first.') }}</p>
    </div>
@endif

@if (isset($article))
    <div class="mb-4 flex items-center gap-2">
        <flux:badge color="zinc">{{ $article->status }}</flux:badge>
        <span class="text-sm text-zinc-500">{{ __('Version :version', ['version' => $article->row_version]) }}</span>
    </div>
    <input type="hidden" name="expected_row_version" value="{{ old('expected_row_version', $article->row_version) }}" />
@endif

<div class="grid gap-4 md:grid-cols-2">
    <flux:select name="language" :label="__('Language')">
        @foreach ($languages as $language)
            <option value="{{ $language }}" @selected(old('language', $article->language ?? 'en') === $language)>{{ $language }}</option>
        @endforeach
    </flux:select>
    <flux:input name="title" :label="__('Title')" :value="old('title', $article->title ?? '')" />
    <flux:input name="subtitle" :label="__('Subtitle')" :value="old('subtitle', $article->subtitle ?? '')" />
    <x-ui.slug-field name="slug" :label="__('Slug')" :value="old('slug', $article->slug ?? '')" :hint="__('Leave blank to generate from the title.')" />
</div>

<div class="mt-4">
    <flux:textarea name="summary" :label="__('Summary')" :value="old('summary', $article->summary ?? '')" />
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <flux:select name="primary_category_id" :label="__('Primary category')">
        @foreach ($categories as $category)
            <option value="{{ $category->getKey() }}" @selected((string) old('primary_category_id', $article->primary_category_id ?? '') === (string) $category->getKey())>{{ $category->translation()?->name }}</option>
        @endforeach
    </flux:select>
    <flux:select name="author_id" :label="__('Primary author')">
        @foreach ($authors as $author)
            <option value="{{ $author->getKey() }}" @selected((string) old('author_id', $article->author_id ?? '') === (string) $author->getKey())>{{ $author->name }}</option>
        @endforeach
    </flux:select>
    <flux:input name="dateline" :label="__('Dateline')" :value="old('dateline', $article->dateline ?? '')" />
    <flux:input name="source" :label="__('Source')" :value="old('source', $article->source ?? '')" />
</div>

<div class="mt-4">
    <flux:textarea name="body" :label="__('Body (block JSON until the visual editor lands)')" rows="10" :value="old('body', isset($article) && $article->body_json !== null ? json_encode($article->body_json, JSON_PRETTY_PRINT) : '')" class="font-mono" />
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <flux:select name="extra_categories[]" multiple :label="__('Extra categories (max 3)')">
        @foreach ($categories as $category)
            <option value="{{ $category->getKey() }}">{{ $category->translation()?->name }}</option>
        @endforeach
    </flux:select>
    <flux:select name="tags[]" multiple :label="__('Tags (max 15)')">
        @foreach ($tags as $tag)
            <option value="{{ $tag->getKey() }}">{{ $tag->translation()?->name }}</option>
        @endforeach
    </flux:select>
    <flux:select name="co_authors[]" multiple :label="__('Co-authors (max 5)')">
        @foreach ($authors as $author)
            <option value="{{ $author->getKey() }}">{{ $author->name }}</option>
        @endforeach
    </flux:select>
    <flux:select name="related[]" multiple :label="__('Related articles (max 6)')">
        @foreach ($recent as $recentArticle)
            <option value="{{ $recentArticle->getKey() }}">{{ $recentArticle->title }}</option>
        @endforeach
    </flux:select>
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <flux:input name="meta_title" :label="__('Meta title')" :value="old('meta_title', $article->meta_title ?? '')" />
    <flux:input name="meta_description" :label="__('Meta description')" :value="old('meta_description', $article->meta_description ?? '')" />
    <flux:input name="canonical_url" :label="__('Canonical URL')" :value="old('canonical_url', $article->canonical_url ?? '')" />
    <flux:switch name="allow_indexing" :label="__('Allow indexing')" :checked="old('allow_indexing', $article->allow_indexing ?? true)" />
    @can('articles.set_breaking')
        <flux:switch name="is_breaking" :label="__('Breaking news')" :checked="old('is_breaking', $article->is_breaking ?? false)" />
        <flux:input name="breaking_until" type="datetime-local" :label="__('Breaking until')" :value="old('breaking_until', isset($article) && $article->breaking_until ? $article->breaking_until->format('Y-m-d\TH:i') : '')" />
    @endcan
</div>

<x-ui.media-picker :label="__('Featured image')">
    <x-slot:preview>
        <p class="text-sm text-zinc-500">{{ __('Featured attachment wires up with the media library in T05.4.') }}</p>
    </x-slot:preview>
    <x-slot:trigger>
        <flux:button type="button" x-data @click="$dispatch('media-picker:open')">{{ __('Choose image') }}</flux:button>
    </x-slot:trigger>
</x-ui.media-picker>
