{{--
    Index toolbar composition pattern: callers provide Livewire-bound controls
    through slots; this component owns layout only. Proven by the Users index.
--}}
<div class="mb-4 flex flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">{{ $filters ?? '' }}</div>
        <div class="flex flex-wrap items-center gap-2">{{ $actions ?? '' }}</div>
    </div>
    @isset($meta)
        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $meta }}</div>
    @endisset
</div>
