<x-layouts::app :title="__('Audit log')">
    <x-ui.page-header title="Audit log" sub="Append-only history. Records cannot be edited or deleted from here." />

    <form method="GET" action="{{ route('audit.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <flux:input name="user_id" :label="__('User ID')" :value="$filters['user_id'] ?? ''" type="number" class="max-w-32" />
        <flux:select name="action" :label="__('Action')">
            <option value="">{{ __('All actions') }}</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
            @endforeach
        </flux:select>
        <flux:input name="date_from" :label="__('From')" type="date" :value="$filters['date_from'] ?? ''" />
        <flux:input name="date_to" :label="__('To')" type="date" :value="$filters['date_to'] ?? ''" />
        <flux:button type="submit" variant="primary">{{ __('Filter') }}</flux:button>
        <flux:button :href="route('audit.export', request()->query())" variant="ghost">{{ __('Export CSV') }}</flux:button>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Time</flux:table.column>
            <flux:table.column>Actor</flux:table.column>
            <flux:table.column>Action</flux:table.column>
            <flux:table.column>Subject</flux:table.column>
            <flux:table.column>IP</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($activities as $activity)
                <flux:table.row :key="$activity->getKey()">
                    <flux:table.cell>{{ $activity->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $activity->causer?->email ?? '—' }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $activity->event }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $activity->subject_type ? class_basename($activity->subject_type).' #'.$activity->subject_id : '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $activity->getProperty('ip') ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <x-ui.empty-state title="No audit records" body="No events match these filters." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $activities->links() }}</div>
</x-layouts::app>
