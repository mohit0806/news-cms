@props(['items' => []])

@if (count($items) > 1)
    <nav aria-label="{{ __('Language') }}">
        <ol class="flex flex-wrap items-center gap-1">
            @foreach ($items as $item)
                <li>
                    @if (! empty($item['active']))
                        <span aria-current="true" class="rounded-md bg-zinc-100 px-3 py-1.5 text-sm font-medium dark:bg-zinc-800">{{ $item['label'] }}</span>
                    @else
                        <a href="{{ $item['url'] }}" class="rounded-md px-3 py-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:text-white/70 dark:hover:text-white">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
