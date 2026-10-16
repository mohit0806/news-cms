<x-layouts::app :title="__('Edit category')">
    <x-ui.page-header title="Edit category" sub="Section settings and translations." />
    <x-ui.flash-status />

    <form method="POST" action="{{ route('categories.update', $category) }}" x-data="{ dirty: false }" @change="dirty = true" @submit="dirty = false">
        @csrf
        @method('PUT')
        @include('categories.form')

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save category') }}</flux:button>
            <flux:button :href="route('categories.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    <div class="mt-8">
        <flux:modal.trigger name="delete-category">
            <flux:button variant="danger">{{ __('Delete category') }}</flux:button>
        </flux:modal.trigger>

        <x-ui.confirm-dialog name="delete-category" :title="__('Delete this category?')">
            <x-slot:body>
                <p>{{ __('Categories with articles or children cannot be deleted.') }}</p>
            </x-slot:body>
            <x-slot:confirm>
                <flux:button type="submit" variant="danger" form="delete-category-form">{{ __('Delete') }}</flux:button>
            </x-slot:confirm>
        </x-ui.confirm-dialog>
    </div>

    <form id="delete-category-form" method="POST" action="{{ route('categories.destroy', $category) }}">
        @csrf
        @method('DELETE')
    </form>
</x-layouts::app>
