<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('password rule accepts 10 characters and rejects 9', function () {
    $ok = Validator::make(['password' => '1234567890'], ['password' => ['required', Password::default()]]);
    $short = Validator::make(['password' => '123456789'], ['password' => ['required', Password::default()]]);

    expect($ok->passes())->toBeTrue()
        ->and($short->fails())->toBeTrue()
        ->and($short->errors()->has('password'))->toBeTrue();
});

test('login throttles after five attempts per email and IP', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors();
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertStatus(429);
});

test('throttle keys isolate different emails', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => 'other@example.com', 'password' => 'wrong-password'])
        ->assertSessionHasErrors()
        ->assertStatus(302);
});

test('unknown email and wrong password yield the same message', function () {
    $user = User::factory()->create();
    $expected = __('auth.failed');

    $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'password'])
        ->assertInvalid(['email' => $expected]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertInvalid(['email' => $expected]);
});
