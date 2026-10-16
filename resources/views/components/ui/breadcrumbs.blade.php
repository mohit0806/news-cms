@props(['items' => []])

@if ($items !== [])
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1 text-sm">
            @foreach ($items as $item)
                <li class="flex items-center gap-1">
                    @if (! $loop->last && ! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="text-zinc-500 hover:text-zinc-800 dark:text-white/70 dark:hover:text-white">{{ $item['label'] }}</a>
                        <span aria-hidden="true" class="text-zinc-300 dark:text-zinc-600">/</span>
                    @else
                        <span aria-current="page" class="font-medium">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
