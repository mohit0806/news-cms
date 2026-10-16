@props(['messages' => [], 'title' => null])

@if ($messages !== [])
    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
        <p class="text-sm font-semibold text-red-800 dark:text-red-200">
            {{ $title ?? __('There were problems with your submission.') }}
        </p>
        <ul class="mt-2 list-disc space-y-1 ps-5 text-sm text-red-700 dark:text-red-300">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
