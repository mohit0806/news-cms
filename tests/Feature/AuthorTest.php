<?php

declare(strict_types=1);

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\QueryException;

test('authors are created with factory defaults', function () {
    $author = Author::factory()->create();

    expect($author->slug)->not->toBeEmpty()
        ->and($author->is_active)->toBeTrue()
        ->and($author->user_id)->toBeNull();
});

test('slugs are unique', function () {
    Author::factory()->create(['slug' => 'jane-reporter']);

    expect(fn () => Author::factory()->create(['slug' => 'jane-reporter']))
        ->toThrow(QueryException::class);
});

test('email is private and never serialized', function () {
    $author = Author::factory()->create(['email' => 'private@example.com']);

    expect($author->toArray())->not->toHaveKey('email')
        ->and($author->toJson())->not->toContain('private@example.com')
        ->and($author->getAttributes())->toHaveKey('email');
});

test('authors optionally link to a staff user', function () {
    $user = User::factory()->create();
    $author = Author::factory()->create(['user_id' => $user->getKey()]);

    expect($author->user->is($user))->toBeTrue();
});

test('authors can be deactivated', function () {
    $author = Author::factory()->create(['is_active' => false]);

    expect($author->refresh()->is_active)->toBeFalse();
});
