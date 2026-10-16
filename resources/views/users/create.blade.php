<x-layouts::app :title="__('New user')">
    <x-ui.breadcrumbs :items="[['label' => 'Users', 'url' => route('users.index')], ['label' => 'New user']]" />

    <x-ui.page-header title="New user" sub="Create an account directly, or invite by email instead." />

    <form method="POST" action="{{ route('users.store') }}" class="max-w-2xl space-y-6"
          x-data="{ dirty: false }" x-on:change="dirty = true" x-on:submit="dirty = false"
          x-on:beforeunload.window="if (dirty) { $event.preventDefault(); }">
        @csrf

        <x-ui.form-errors :messages="$errors->all()" />

        <flux:input name="name" :label="__('Name')" required :value="old('name')" autocomplete="name" />
        <flux:error name="name" />

        <flux:input name="email" :label="__('Email')" type="email" required :value="old('email')" autocomplete="email" />
        <flux:error name="email" />

        <flux:input name="password" :label="__('Temporary password')" type="password" required viewable
                    passwordrules="minlength: 10" />
        <flux:error name="password" />

        <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required viewable />

        <flux:select name="role" :label="__('Role')">
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
            @endforeach
        </flux:select>
        <flux:error name="role" />

        <div class="flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create user') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
