<x-layouts::app :title="__('System status')">
    <x-ui.page-header title="System status" sub="Live probes — nothing here is hardcoded." />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Check</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Detail</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($checks as $check)
                <flux:table.row :key="$check['label']">
                    <flux:table.cell>{{ $check['label'] }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($check['ok'])
                            <flux:badge color="green">OK</flux:badge>
                        @else
                            <flux:badge color="red">Attention</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $check['detail'] }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</x-layouts::app>
