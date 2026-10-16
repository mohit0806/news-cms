<x-layouts::app :title="__('Edit article')">
    <x-ui.page-header :title="$article->title" sub="Draft editing with conflict protection." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('articles.update', $article) }}">
        @csrf
        @method('PUT')
        @include('articles.form')

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save article') }}</flux:button>
            <flux:button :href="route('articles.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </form>

    <form method="POST" action="{{ route('articles.destroy', $article) }}" class="mt-8">
        @csrf
        @method('DELETE')
        <flux:button type="submit" variant="danger">{{ __('Move to trash') }}</flux:button>
    </form>

    <div class="mt-8 flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
        @can('submit', $article)
            @if ($article->status === 'draft')
                <form method="POST" action="{{ route('articles.submit', $article) }}">
                    @csrf
                    <flux:button type="submit" variant="primary">{{ __('Submit for review') }}</flux:button>
                </form>
            @endif
        @endcan
        @can('articles.publish')
            @if ($article->status === 'in_review')
                <form method="POST" action="{{ route('articles.reject', $article) }}" class="flex items-end gap-2">
                    @csrf
                    <flux:input name="note" :label="__('Rejection note')" />
                    <flux:button type="submit" variant="ghost">{{ __('Send back to draft') }}</flux:button>
                </form>
            @endif
            @if (in_array($article->status, ['draft', 'in_review', 'unpublished'], true))
                <form method="POST" action="{{ route('articles.publish', $article) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <flux:input name="published_at" type="datetime-local" :label="__('Publish at (blank for now)')" />
                    <flux:button type="submit" variant="primary">{{ __('Publish') }}</flux:button>
                </form>
            @endif
        @endcan
        @can('articles.unpublish')
            @if (in_array($article->status, ['published', 'scheduled'], true))
                <form method="POST" action="{{ route('articles.unpublish', $article) }}">
                    @csrf
                    <flux:button type="submit" variant="danger">{{ $article->status === 'scheduled' ? __('Cancel schedule') : __('Unpublish') }}</flux:button>
                </form>
            @endif
        @endcan
    </div>
</x-layouts::app>
