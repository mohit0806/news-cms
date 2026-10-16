{{-- Homepage block form (P2-T08.3). Expects $languages and optionally $block. --}}
<x-ui.form-errors />

<div class="grid gap-4 md:grid-cols-2">
    <flux:select name="language" :label="__('Language')">
        @foreach ($languages as $option)
            <option value="{{ $option }}" @selected(old('language', $block->language ?? $language ?? 'en') === $option)>{{ $option }}</option>
        </flux:select>
    </flux:select>
    <flux:select name="widget" :label="__('Widget')">
        @foreach (\App\Models\LayoutBlock::WIDGETS as $widget)
            <option value="{{ $widget }}" @selected(old('widget', $block->widget ?? '') === $widget)>{{ $widget }}</option>
        @endforeach
    </flux:select>
    <flux:input name="title" :label="__('Title')" :value="old('title', $block->title ?? '')" />
    <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $block->is_active ?? true)" />
</div>

<div class="mt-4">
    <flux:textarea name="config" :label="__('Configuration (JSON)')" rows="6" :value="old('config', isset($block) && $block->config_json !== null ? json_encode($block->config_json, JSON_PRETTY_PRINT) : '')" :hint="__('Manual picks and widget options. Unpublished picks are skipped at render.')" />
</div>
