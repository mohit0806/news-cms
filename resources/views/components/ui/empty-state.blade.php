@props(['icon' => 'inbox', 'title', 'body' => null])

<div class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
    <flux:icon :name="$icon" class="size-8 text-zinc-300 dark:text-zinc-600" />
    <flux:heading size="sm">{{ $title }}</flux:heading>
    @if ($body !== null)
        <flux:text>{{ $body }}</flux:text>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
