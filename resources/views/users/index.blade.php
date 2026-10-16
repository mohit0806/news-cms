<x-layouts::app :title="__('Users')">
    <x-ui.page-header title="Users" sub="Staff accounts and role assignments.">
        <x-slot:actions>
            <flux:button :href="route('users.create')" variant="primary" wire:navigate>{{ __('New user') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash-status />

    <form method="GET" action="{{ route('users.index') }}">
        <x-ui.index-toolbar>
            <x-slot:filters>
                <flux:input name="search" :label="__('Search')" :value="$filters['search'] ?? ''" />
                <flux:select name="role" :label="__('Role')">
                    <option value="">{{ __('All roles') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="active" :label="__('Status')">
                    <option value="">{{ __('Any status') }}</option>
                    <option value="yes" @selected(($filters['active'] ?? '') === 'yes')>{{ __('Active') }}</option>
                    <option value="no" @selected(($filters['active'] ?? '') === 'no')>{{ __('Inactive') }}</option>
                </flux:select>
                <flux:button type="submit" variant="ghost">{{ __('Apply') }}</flux:button>
            </x-slot:filters>
            <x-slot:meta>{{ trans_choice('{1} :count user|[2,*] :count users', $users->total(), ['count' => $users->total()]) }}</x-slot:meta>
        </x-ui.index-toolbar>
    </form>

    <form method="POST" action="{{ route('users.bulk-deactivate') }}">
        @csrf
        <flux:table>
            <flux:table.columns>
                <flux:table.column><span class="sr-only">{{ __('Select') }}</span></flux:table.column>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($users as $user)
                    <flux:table.row :key="$user->getKey()">
                        <flux:table.cell><flux:checkbox name="ids[]" :value="$user->getKey()" /></flux:table.cell>
                        <flux:table.cell><a href="{{ route('users.edit', $user) }}" wire:navigate>{{ $user->name }}</a></flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>{{ $user->getRoleNames()->first() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($user->is_active)
                                <flux:badge color="green">Active</flux:badge>
                            @else
                                <flux:badge color="zinc">Inactive</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-ui.empty-state title="No users" body="No accounts match these filters." />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4 flex items-center gap-2">
            <flux:button type="submit" variant="danger">{{ __('Deactivate selected') }}</flux:button>
        </div>
    </form>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts::app>
