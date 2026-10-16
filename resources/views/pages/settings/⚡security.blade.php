<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';



    /**
     * Mount the component.
     */
    public function mount(): void
    {
        //
    }

    /**
     * Enable two-factor authentication for the currently authenticated user.
     */
    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable): void
    {
        $enable(Auth::user());

        Flux::toast(variant: 'success', text: __('Two-factor authentication enabled.'));
    }

    /**
     * Regenerate the user's two-factor recovery codes.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $generate(Auth::user());

        Flux::toast(variant: 'success', text: __('Recovery codes regenerated.'));
    }

    /**
     * Disable two-factor authentication for the currently authenticated user.
     */
    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable): void
    {
        $disable(Auth::user());

        Flux::toast(variant: 'success', text: __('Two-factor authentication disabled.'));
    }

    /**
     * Log out all other sessions for the currently authenticated user.
     */
    public function logoutOtherSessions(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password');

            throw $e;
        }

        Auth::logoutOtherDevices($validated['current_password']);

        $this->reset('current_password');

        Flux::toast(variant: 'success', text: __('Other sessions logged out.'));
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }


}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Security settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>


    </x-pages::settings.layout>

    <x-pages::settings.layout :heading="__('Active sessions')" :subheading="__('End every session except this one')">
        <form method="POST" wire:submit="logoutOtherSessions" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="logout-others-button">
                    {{ __('Log out other sessions') }}
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>

    @if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::twoFactorAuthentication()))
        <x-pages::settings.layout :heading="__('Two-factor authentication')" :subheading="__('Add extra security to your account')">
            @if (! auth()->user()->hasEnabledTwoFactorAuthentication())
                <flux:button wire:click="enableTwoFactorAuthentication" variant="primary" class="mt-6" data-test="enable-2fa-button">
                    {{ __('Enable') }}
                </flux:button>
            @else
                <div class="mt-6 space-y-6">
                    <p class="text-sm">{{ __('Two-factor authentication is enabled. Scan the code below with your authenticator app if you need to re-enroll.') }}</p>
                    <div>{!! auth()->user()->twoFactorQrCodeSvg() !!} {{-- xss-ok: first-party Fortify QR SVG, no user input --}}</div>
                    <div>
                        <flux:heading size="sm">{{ __('Recovery codes') }}</flux:heading>
                        <ul class="mt-2 space-y-1 font-mono text-sm">
                            @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="regenerateRecoveryCodes" variant="ghost">{{ __('Regenerate codes') }}</flux:button>
                        <flux:button wire:click="disableTwoFactorAuthentication" variant="danger">{{ __('Disable') }}</flux:button>
                    </div>
                </div>
            @endif
        </x-pages::settings.layout>
    @endif

</section>
