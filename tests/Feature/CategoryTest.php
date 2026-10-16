<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Term;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function categoryAdmin(string $role = 'Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function categoryPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'sort' => 0,
        'show_in_menu' => '0',
        'is_active' => '1',
        'translations' => [
            'en' => ['name' => 'World News', 'slug' => '', 'description' => ''],
        ],
    ], $overrides);
}

test('guests are redirected and reporters are denied', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));

    $this->actingAs(categoryAdmin('Reporter'))->get(route('categories.index'))->assertForbidden();
    $this->actingAs(categoryAdmin('Reporter'))->post(route('categories.store'), categoryPayload())->assertForbidden();
});

test('editors manage categories', function () {
    $this->actingAs(categoryAdmin('Editor'))->get(route('categories.index'))->assertOk();
    $this->actingAs(categoryAdmin('Editor'))->get(route('categories.create'))->assertOk();
});

test('create generates slugs and stores translations', function () {
    $this->actingAs(categoryAdmin())
        ->post(route('categories.store'), categoryPayload())
        ->assertRedirect(route('categories.index'));

    $term = Term::query()->where('vocabulary', 'category')->firstOrFail();

    expect($term->translation('en')?->slug)->toBe('world-news')
        ->and($term->translation('hi')?->slug)->toBe('world-news');
});

test('reserved slugs are rejected', function () {
    $this->actingAs(categoryAdmin())
        ->post(route('categories.store'), categoryPayload([
            'translations' => ['en' => ['name' => 'Search', 'slug' => 'search']],
        ]))
        ->assertSessionHasErrors('translations.en.slug');

    expect(Term::query()->where('vocabulary', 'category')->count())->toBe(0);
});

test('duplicate slugs get collision suffixes', function () {
    Term::factory()->translated('en', 'World News')->create();

    $this->actingAs(categoryAdmin())
        ->post(route('categories.store'), categoryPayload())
        ->assertRedirect(route('categories.index'));

    expect(Term::query()->where('vocabulary', 'category')->count())->toBe(2);
});

test('nesting deeper than three levels is rejected', function () {
    $root = Term::factory()->translated()->create();
    $child = Term::factory()->translated()->create(['parent_id' => $root->getKey()]);
    $grandchild = Term::factory()->translated()->create(['parent_id' => $child->getKey()]);

    $this->actingAs(categoryAdmin())
        ->post(route('categories.store'), categoryPayload(['parent_id' => $grandchild->getKey()]))
        ->assertSessionHasErrors('parent_id');
});

test('delete is blocked for categories with articles', function () {
    $article = Article::factory()->create();

    $this->actingAs(categoryAdmin())
        ->delete(route('categories.destroy', $article->primary_category_id))
        ->assertSessionHasErrors('category');

    expect(Term::whereKey($article->primary_category_id)->exists())->toBeTrue();
});

test('delete is blocked for categories with children', function () {
    $root = Term::factory()->translated()->create();
    Term::factory()->translated()->create(['parent_id' => $root->getKey()]);

    $this->actingAs(categoryAdmin())
        ->delete(route('categories.destroy', $root))
        ->assertSessionHasErrors('category');

    expect(Term::whereKey($root->getKey())->exists())->toBeTrue();
});

test('empty categories delete cleanly', function () {
    $term = Term::factory()->translated()->create();

    $this->actingAs(categoryAdmin())
        ->delete(route('categories.destroy', $term))
        ->assertRedirect(route('categories.index'));

    expect(Term::whereKey($term->getKey())->exists())->toBeFalse();
});

test('create form carries the media picker contract trigger', function () {
    $html = $this->actingAs(categoryAdmin())->get(route('categories.create'))->getContent();

    expect($html)->toContain('media-picker:open')
        ->toContain('T05.4');
});
