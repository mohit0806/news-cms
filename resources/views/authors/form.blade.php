{{-- Author form fields (P2-T02.2). Expects optionally $author. --}}
<x-ui.form-errors />

<div class="grid gap-4 md:grid-cols-2">
    <flux:input name="name" :label="__('Name')" :value="old('name', $author->name ?? '')" />
    <x-ui.slug-field name="slug" :label="__('Slug')" :value="old('slug', $author->slug ?? '')" :hint="__('Leave blank to generate from the name.')" />
    <flux:input name="email" type="email" :label="__('Private email')" :value="old('email', isset($author) ? $author->getAttributes()['email'] ?? '' : '')" :hint="__('Never shown publicly.')" />
    <flux:input name="user_id" type="number" :label="__('Linked staff user ID')" :value="old('user_id', $author->user_id ?? '')" :hint="__('Optional.')" />
</div>

<div class="mt-4">
    <flux:textarea name="bio" :label="__('Bio')" :value="old('bio', $author->bio ?? '')" />
</div>

<div class="mt-4">
    <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $author->is_active ?? true)" />
</div>

<x-ui.media-picker :label="__('Photo')">
    <x-slot:preview>
        <p class="text-sm text-zinc-500">{{ __('Photo attachment wires up with the media library in T05.4.') }}</p>
    </x-slot:preview>
    <x-slot:trigger>
        <flux:button type="button" x-data @click="$dispatch('media-picker:open')">{{ __('Choose photo') }}</flux:button>
    </x-slot:trigger>
</x-ui.media-picker>
