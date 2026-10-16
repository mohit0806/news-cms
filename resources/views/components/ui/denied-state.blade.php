@props(['title' => null, 'body' => null])

<div class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center" role="alert">
    <flux:icon.lock-closed class="size-8 text-zinc-300 dark:text-zinc-600" />
    <flux:heading size="sm">{{ $title ?? __('Not permitted') }}</flux:heading>
    @if ($body !== null)
        <flux:text>{{ $body }}</flux:text>
    @endif
</div>
