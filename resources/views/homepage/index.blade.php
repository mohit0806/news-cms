<x-layouts::app :title="__('Homepage')">
    <x-ui.page-header title="Homepage" sub="Ordered blocks per language. Manual picks win at render.">
        <x-slot:actions>
            <flux:button :href="route('homepage.create', ['language' => $language])" variant="primary" wire:navigate>{{ __('New block') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('homepage.index') }}" class="mb-4 flex items-end gap-2">
        <flux:select name="language" :label="__('Language')">
            @foreach ($languages as $option)
                <option value="{{ $option }}" @selected($language === $option)>{{ $option }}</option>
            @endforeach
        </flux:select>
        <flux:button type="submit" variant="ghost">{{ __('Switch') }}</flux:button>
    </form>

    <form method="POST" action="{{ route('homepage.reorder') }}">
        @csrf
        <input type="hidden" name="language" value="{{ $language }}" />
        <x-ui.form-errors />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Widget') }}</flux:table.column>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Sort') }}</flux:table.column>
                <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($blocks as $block)
                    <flux:table.row :key="$block->getKey()">
                        <flux:table.cell><span class="font-mono text-sm">{{ $block->widget }}</span></flux:table.cell>
                        <flux:table.cell><a href="{{ route('homepage.edit', $block) }}" wire:navigate>{{ $block->title ?? '—' }}</a></flux:table.cell>
                        <flux:table.cell>
                            @if ($block->is_active)
                                <flux:badge color="green">Active</flux:badge>
                            @else
                                <flux:badge color="zinc">Inactive</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <input type="hidden" name="order[{{ $loop->index }}][id]" value="{{ $block->getKey() }}" />
                            <flux:input :name="'order['.$loop->index.'][sort]'" type="number" :value="$block->sort" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <button type="submit" form="delete-block-{{ $block->getKey() }}" class="text-sm text-red-600">{{ __('Remove') }}</button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-ui.empty-state title="No blocks" body="Add the first homepage block." />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($blocks->isNotEmpty())
            <div class="mt-2">
                <flux:button type="submit" variant="ghost">{{ __('Save order') }}</flux:button>
            </div>
        @endif
    </form>

    @foreach ($blocks as $block)
        <form id="delete-block-{{ $block->getKey() }}" method="POST" action="{{ route('homepage.destroy', $block) }}">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-layouts::app>
