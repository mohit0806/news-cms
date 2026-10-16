<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function enableTwoFactor(User $user): User
{
    $secret = (new Google2FA)->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, fn () => Str::random(10))->all())),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user->refresh();
}

function currentOtp(User $user): string
{
    return (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
}

test('login with 2FA challenges before authenticating', function () {
    $user = enableTwoFactor(User::factory()->create());

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('valid TOTP completes the challenge', function () {
    $user = enableTwoFactor(User::factory()->create());

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['code' => currentOtp($user)])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user->refresh());
});

test('recovery code completes the challenge and is consumed', function () {
    $user = enableTwoFactor(User::factory()->create());
    $code = json_decode(decrypt($user->two_factor_recovery_codes), true)[0];

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['recovery_code' => $code])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(json_decode(decrypt($user->refresh()->two_factor_recovery_codes), true))->not->toContain($code);
});

test('2FA can be enabled and disabled from security settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect();

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();

    $this->actingAs($user)->delete(route('two-factor.disable'))->assertRedirect();

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('FORCE_ADMIN_2FA redirects admins without 2FA to setup', function () {
    config()->set('app.force_admin_2fa', true);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('security.edit'));
    $this->actingAs($reporter)->get(route('dashboard'))->assertOk();
});

test('FORCE_ADMIN_2FA passes admins with confirmed 2FA', function () {
    config()->set('app.force_admin_2fa', true);

    $admin = enableTwoFactor(User::factory()->create());
    $admin->assignRole('Admin');

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
});

test('FORCE_ADMIN_2FA off imposes nothing', function () {
    config()->set('app.force_admin_2fa', false);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
});
