<?php

declare(strict_types=1);

use App\Models\User;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

// Public registration is disabled (Q-P1-02; TRD §2: no public reader accounts).
// The starter-kit RegistrationTest self-skips while the feature is off; these
// tests lock the disabled state instead of deleting that coverage.

test('registration routes are not registered', function () {
    expect(fn () => route('register'))->toThrow(RouteNotFoundException::class);
});

test('registration posts are rejected', function () {
    $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
