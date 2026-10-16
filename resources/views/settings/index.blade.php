<x-layouts::app :title="__('Settings')">
    <x-ui.page-header title="Settings" sub="Grouped product controls. Environment secrets stay in .env files." />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($groups as $slug => [$class, $label])
            <a href="{{ route('settings.edit', $slug) }}" wire:navigate
               class="rounded-lg border border-zinc-200 bg-white p-4 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm">{{ __($label) }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ class_basename($class) }}</flux:text>
            </a>
        @endforeach
    </div>
</x-layouts::app>
