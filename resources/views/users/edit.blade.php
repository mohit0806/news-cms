<x-layouts::app :title="__('Edit user')">
    <x-ui.breadcrumbs :items="[['label' => 'Users', 'url' => route('users.index')], ['label' => $user->name]]" />

    <x-ui.page-header :title="$user->name" sub="Account, role, and status.">
        <x-slot:actions>
            @if ($user->is_active)
                <form method="POST" action="{{ route('users.deactivate', $user) }}">
                    @csrf
                    <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                </form>
            @else
                <form method="POST" action="{{ route('users.activate', $user) }}">
                    @csrf
                    <flux:button type="submit" variant="primary">{{ __('Activate') }}</flux:button>
                </form>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('users.update', $user) }}" class="max-w-2xl space-y-6"
          x-data="{ dirty: false }" x-on:change="dirty = true" x-on:submit="dirty = false"
          x-on:beforeunload.window="if (dirty) { $event.preventDefault(); }">
        @csrf
        @method('PUT')

        <x-ui.form-errors :messages="$errors->all()" />

        <flux:input name="name" :label="__('Name')" required :value="old('name', $user->name)" autocomplete="name" />
        <flux:error name="name" />

        <flux:input name="email" :label="__('Email')" type="email" required :value="old('email', $user->email)" autocomplete="email" />
        <flux:error name="email" />

        <flux:input name="locale" :label="__('Locale')" :value="old('locale', $user->locale)" />
        <flux:error name="locale" />

        <flux:select name="role" :label="__('Role')">
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $currentRole) === $role)>{{ $role }}</option>
            @endforeach
        </flux:select>
        <flux:error name="role" />

        <flux:switch name="is_active" :label="__('Active account')" :checked="old('is_active', $user->is_active)" value="1" />

        <div class="flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
