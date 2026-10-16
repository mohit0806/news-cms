<x-layouts::auth :title="__('Two-factor challenge')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Two-factor challenge')" :description="__('Enter the code from your authenticator app')" />

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="code"
                :label="__('Authentication code')"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                autofocus
            />

            <x-ui.form-errors :messages="$errors->all()" />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Verify') }}
            </flux:button>
        </form>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="recovery_code"
                :label="__('Or use a recovery code')"
                type="text"
                autocomplete="off"
            />

            <flux:button type="submit" variant="ghost" class="w-full">
                {{ __('Use recovery code') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
