<x-layouts::app :title="__('Merge tags')">
    <x-ui.page-header title="Merge tags" sub="Re-points articles, records a 301, removes the source." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('tags.merge.store') }}">
        @csrf
        <x-ui.form-errors />

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select name="source_id" :label="__('Merge this tag')">
                @foreach ($tags as $tag)
                    <option value="{{ $tag->getKey() }}">{{ $tag->translation()?->name }} ({{ $tag->translation()?->slug }})</option>
                @endforeach
            </flux:select>
            <flux:select name="target_id" :label="__('Into this tag')">
                @foreach ($tags as $tag)
                    <option value="{{ $tag->getKey() }}">{{ $tag->translation()?->name }} ({{ $tag->translation()?->slug }})</option>
                @endforeach
            </flux:select>
        </div>

        <div class="mt-4">
            <flux:button type="submit" variant="primary">{{ __('Merge tags') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
