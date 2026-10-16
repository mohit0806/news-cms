<x-layouts::app :title="__('Roles')">
    <x-ui.page-header title="Roles" sub="Role definitions are code-owned and seeded; assignment happens on user accounts." />

    <x-ui.flash-status />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Members</flux:table.column>
            <flux:table.column>Permissions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($roles as $role)
                <flux:table.row :key="$role->getKey()">
                    <flux:table.cell>{{ $role->name }}</flux:table.cell>
                    <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                    <flux:table.cell>{{ $role->permissions_count }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</x-layouts::app>
