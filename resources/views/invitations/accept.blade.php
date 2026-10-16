<x-layouts::auth :title="__('Accept invitation')">
    <form method="POST" action="{{ request()->url() }}" class="flex flex-col gap-6">
        @csrf

        <x-auth-header :title="__('Accept your invitation')" :description="__('Set your name and password to join :app.', ['app' => config('app.name')])" />

        <flux:input name="name" :label="__('Name')" required autofocus :value="old('name')" />

        <flux:input name="password" type="password" :label="__('Password')" required viewable />
        <flux:input name="password_confirmation" type="password" :label="__('Confirm password')" required viewable />

        <x-ui.form-errors :messages="$errors->all()" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('Create account') }}</flux:button>
    </form>
</x-layouts::auth>
