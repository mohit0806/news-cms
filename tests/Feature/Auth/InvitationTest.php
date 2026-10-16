<?php

declare(strict_types=1);

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function inviteAsAdmin(string $email = 'new@example.com', string $role = 'Reporter'): Invitation
{
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Mail::fake();

    $response = test()->actingAs($admin)->post(route('invitations.store'), [
        'email' => $email,
        'role' => $role,
    ]);

    $response->assertRedirect();

    return Invitation::query()->where('email', $email)->firstOrFail();
}

function signedAcceptUrl(Invitation $invitation): string
{
    return URL::signedRoute('invitations.accept', ['invitation' => $invitation->getKey()], $invitation->expires_at);
}

test('admin can invite and the mail carries a signed URL', function () {
    $invitation = inviteAsAdmin();

    Mail::assertQueued(InvitationMail::class, fn ($mail) => $mail->hasTo('new@example.com'));
    expect($invitation->role)->toBe('Reporter')
        ->and($invitation->expires_at->greaterThan(now()->addHours(47)))->toBeTrue();
});

test('inviting requires users.manage', function () {
    // Guest first: actingAs() persists for later calls within one test.
    $this->post(route('invitations.store'), ['email' => 'a@example.com', 'role' => 'Reporter'])->assertRedirect(route('login'));

    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');
    $plain = User::factory()->create();

    $this->actingAs($reporter)->post(route('invitations.store'), ['email' => 'a@example.com', 'role' => 'Reporter'])->assertForbidden();
    $this->actingAs($plain)->post(route('invitations.store'), ['email' => 'a@example.com', 'role' => 'Reporter'])->assertForbidden();
});

test('invite validation rejects bad input', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)->post(route('invitations.store'), ['email' => 'taken@example.com', 'role' => 'Reporter'])
        ->assertSessionHasErrors('email');
    $this->actingAs($admin)->post(route('invitations.store'), ['email' => 'x@example.com', 'role' => 'Nope'])
        ->assertSessionHasErrors('role');
});

test('invitee accepts with name and password and is logged in with the role', function () {
    $invitation = inviteAsAdmin();

    $this->get(signedAcceptUrl($invitation))->assertOk();

    $response = $this->post(signedAcceptUrl($invitation), [
        'name' => 'New Person',
        'password' => '1234567890',
        'password_confirmation' => '1234567890',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();

    expect($user->hasRole('Reporter'))->toBeTrue()
        ->and($invitation->refresh()->accepted_at)->not->toBeNull();
});

test('expired invitations are rejected', function () {
    $invitation = inviteAsAdmin();

    $this->travel(49)->hours();

    $this->get(signedAcceptUrl($invitation))->assertForbidden();
    $this->post(signedAcceptUrl($invitation), ['name' => 'X', 'password' => '1234567890', 'password_confirmation' => '1234567890'])
        ->assertForbidden();
});

test('used invitations cannot be accepted twice', function () {
    $invitation = inviteAsAdmin();

    $this->post(signedAcceptUrl($invitation), [
        'name' => 'New Person',
        'password' => '1234567890',
        'password_confirmation' => '1234567890',
    ])->assertRedirect();

    // Signature still valid, but the invitation is consumed.
    $freshUrl = URL::signedRoute('invitations.accept', ['invitation' => $invitation->getKey()], now()->addHour());

    $this->get($freshUrl)->assertGone();
    $this->post($freshUrl, ['name' => 'Other', 'password' => '1234567890', 'password_confirmation' => '1234567890'])
        ->assertGone();

    expect(User::query()->where('email', 'new@example.com')->count())->toBe(1);
});

test('tampered signatures are rejected', function () {
    $invitation = inviteAsAdmin();

    $this->get("/invitations/accept/{$invitation->getKey()}?signature=tampered")->assertForbidden();
});
