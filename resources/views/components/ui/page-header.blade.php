@props(['title', 'sub' => null])

<div class="relative mb-6 w-full">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
            @if ($sub !== null)
                <flux:subheading size="lg" class="mb-6">{{ $sub }}</flux:subheading>
            @endif
        </div>
        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
    <flux:separator variant="subtle" />
</div>
