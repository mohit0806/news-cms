@props(['name', 'title', 'confirm' => null, 'cancel' => null])

<flux:modal :name="$name" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>
            <div class="mt-2">{{ $body }}</div>
        </div>
        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ $cancel ?? __('Cancel') }}</flux:button>
            </flux:modal.close>
            {{ $confirm }}
        </div>
    </div>
</flux:modal>
