@if (session()->has('status') || session()->has('toast'))
    <div role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
        {{ session('status', session('toast')) }}
    </div>
@endif
