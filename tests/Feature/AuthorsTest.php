<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Author;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function authorsAdmin(string $role = 'Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('reporters cannot manage authors', function () {
    $this->get(route('authors.index'))->assertRedirect(route('login'));

    $this->actingAs(authorsAdmin('Reporter'))->get(route('authors.index'))->assertForbidden();
    $this->actingAs(authorsAdmin('Reporter'))
        ->post(route('authors.store'), ['name' => 'Jane', 'is_active' => '1'])
        ->assertForbidden();
});

test('editors create authors with generated slugs', function () {
    $this->actingAs(authorsAdmin('Editor'))
        ->post(route('authors.store'), ['name' => 'Jane Reporter', 'is_active' => '1'])
        ->assertRedirect(route('authors.index'));

    $author = Author::where('name', 'Jane Reporter')->firstOrFail();

    expect($author->slug)->toBe('jane-reporter');
});

test('author form carries the photo picker contract trigger', function () {
    $html = $this->actingAs(authorsAdmin())->get(route('authors.create'))->getContent();

    expect($html)->toContain('media-picker:open')
        ->toContain('T05.4');
});

test('deactivation preserves the author and their articles', function () {
    $article = Article::factory()->create();
    $author = $article->author;

    $this->actingAs(authorsAdmin())
        ->post(route('authors.deactivate', $author))
        ->assertRedirect(route('authors.index'));

    expect($author->refresh()->is_active)->toBeFalse()
        ->and(Article::whereKey($article->getKey())->exists())->toBeTrue();

    $this->actingAs(authorsAdmin())
        ->post(route('authors.activate', $author))
        ->assertRedirect(route('authors.index'));

    expect($author->refresh()->is_active)->toBeTrue();
});

test('delete is blocked for authors with articles', function () {
    $article = Article::factory()->create();

    $this->actingAs(authorsAdmin())
        ->delete(route('authors.destroy', $article->author))
        ->assertSessionHasErrors('author');

    expect(Author::whereKey($article->author->getKey())->exists())->toBeTrue();
});

test('childless authors delete cleanly', function () {
    $author = Author::factory()->create();

    $this->actingAs(authorsAdmin())
        ->delete(route('authors.destroy', $author))
        ->assertRedirect(route('authors.index'));

    expect(Author::whereKey($author->getKey())->exists())->toBeFalse();
});

test('email stays private on the index', function () {
    Author::factory()->create(['name' => 'Private Person', 'email' => 'hidden@example.com']);

    $html = $this->actingAs(authorsAdmin())->get(route('authors.index'))->getContent();

    expect($html)->toContain('Private Person')
        ->not->toContain('hidden@example.com');
});
