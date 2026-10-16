<x-layouts::app :title="__('Articles')">
    <x-ui.page-header title="Articles" sub="Drafts through published, with trash.">
        <x-slot:actions>
            <flux:button :href="route('articles.create')" variant="primary" wire:navigate>{{ __('New article') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('articles.index') }}">
        <x-ui.index-toolbar>
            <x-slot:filters>
                <flux:input name="search" :label="__('Search')" :value="$filters['search'] ?? ''" />
                <flux:select name="status" :label="__('Status')">
                    <option value="">{{ __('Any status') }}</option>
                    @foreach (['draft', 'in_review', 'scheduled', 'published', 'unpublished', 'trashed'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="category" :label="__('Category')">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->getKey() }}" @selected((string) ($filters['category'] ?? '') === (string) $category->getKey())>{{ $category->translation()?->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="author" :label="__('Author')">
                    <option value="">{{ __('All authors') }}</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->getKey() }}" @selected((string) ($filters['author'] ?? '') === (string) $author->getKey())>{{ $author->name }}</option>
                    @endforeach
                </flux:select>
                <flux:button type="submit" variant="ghost">{{ __('Apply') }}</flux:button>
            </x-slot:filters>
            <x-slot:meta>{{ trans_choice('{1} :count article|[2,*] :count articles', $articles->total(), ['count' => $articles->total()]) }}</x-slot:meta>
        </x-ui.index-toolbar>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Updated') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($articles as $article)
                <flux:table.row :key="$article->getKey()">
                    <flux:table.cell><a href="{{ route('articles.edit', $article) }}" wire:navigate>{{ $article->title }}</a></flux:table.cell>
                    <flux:table.cell><flux:badge :color="$article->status === 'published' ? 'green' : 'zinc'">{{ $article->status }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $article->updated_at?->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <x-ui.empty-state title="No articles" body="No articles match these filters." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $articles->links() }}</div>
</x-layouts::app>
