<x-layouts::app :title="__('Dashboard')">
    <x-ui.page-header title="Dashboard" sub="Entry points to the administration areas available to you." />

    <x-ui.flash-status />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (\App\Support\AdminNav::visibleItems() as $item)
            @if ($item['route'] !== 'dashboard')
                <a href="{{ route($item['route']) }}" wire:navigate
                   class="rounded-lg border border-zinc-200 bg-white p-4 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center gap-2">
                        <flux:icon :name="$item['icon']" class="size-5" />
                        <flux:heading size="sm">{{ __($item['label']) }}</flux:heading>
                    </div>
                    <flux:text class="mt-1 text-sm">{{ __($item['description']) }}</flux:text>
                </a>
            @endif
        @endforeach
    </div>
</x-layouts::app>
