<?php

declare(strict_types=1);

use App\Models\User;

test('create-admin bootstraps an administrator with a role', function () {
    $this->artisan('app:create-admin', [
        'email' => 'boss@example.com',
        '--password' => 'supersecret10',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'boss@example.com')->firstOrFail();

    expect($user->hasRole('Admin'))->toBeTrue()
        ->and($user->is_active)->toBeTrue();
});

test('create-admin rejects duplicates and unknown roles without partial writes', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('app:create-admin', [
        'email' => 'taken@example.com',
        '--password' => 'supersecret10',
    ])->assertFailed();

    $this->artisan('app:create-admin', [
        'email' => 'new2@example.com',
        '--password' => 'supersecret10',
        '--role' => 'Nope',
    ])->assertFailed();

    expect(User::query()->where('email', 'new2@example.com')->exists())->toBeFalse();
});
