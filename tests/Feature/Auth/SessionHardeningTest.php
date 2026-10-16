<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    // Harden exactly what .env.example documents for production-like use.
    config()->set('session.driver', 'database');
    config()->set('session.cookie', 'cms_session');
    config()->set('session.path', '/admin');
});

test('login sets the scoped admin session cookie', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $cookies = $response->headers->getCookies();
    $names = array_map(fn ($c) => $c->getName().' @ '.$c->getPath(), $cookies);

    expect($names)->toContain('cms_session @ /admin');
});

test('session cookie carries secure attributes', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === 'cms_session');

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax');
});

test('idle timeout defaults to 120 minutes and expires stale sessions', function () {
    expect(config('session.lifetime'))->toBe(120);

    config()->set('session.lifetime', 1);

    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();

    $this->travel(61)->seconds();

    // New request lifecycle: the test process reuses the app (and the guard's
    // memoized user) across calls, unlike real HTTP. Refresh to simulate it.
    $this->refreshApplication();
    config()->set('session.lifetime', 1);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('deactivated users are denied and their sessions deleted', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    expect(DB::table('sessions')->where('user_id', $user->getKey())->exists())->toBeTrue();

    $user->forceFill(['is_active' => false])->save();

    // Same new-lifecycle reason as above: the guard memoizes the user object
    // for the life of the test process.
    $this->refreshApplication();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    expect(DB::table('sessions')->where('user_id', $user->getKey())->exists())->toBeFalse();
});

test('successful login records last_login_at', function () {
    $user = User::factory()->create(['last_login_at' => null]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect($user->refresh()->last_login_at)->not->toBeNull();
});

test('session id rotates on login', function () {
    $user = User::factory()->create();

    $before = session()->getId();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect(session()->getId())->not->toBe($before);
});

test('other sessions can be logged out with password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('current_password', 'password')
        ->call('logoutOtherSessions')
        ->assertHasNoErrors();
});
