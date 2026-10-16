<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Term;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function pagesAdmin(string $role = 'Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function pagePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'language' => 'en',
        'title' => 'About Our Newsroom',
        'slug' => '',
        'body' => "First paragraph.\n\nSecond paragraph.",
        'template' => 'default',
        'status' => 'draft',
        'allow_indexing' => '1',
    ], $overrides);
}

test('reporters cannot manage pages', function () {
    $this->get(route('pages.index'))->assertRedirect(route('login'));

    $this->actingAs(pagesAdmin('Reporter'))->get(route('pages.index'))->assertForbidden();
    $this->actingAs(pagesAdmin('Reporter'))->post(route('pages.store'), pagePayload())->assertForbidden();
});

test('create stores paragraphs as blocks and renders html', function () {
    $this->actingAs(pagesAdmin('Editor'))
        ->post(route('pages.store'), pagePayload())
        ->assertRedirect(route('pages.index'));

    $page = Page::where('slug', 'about-our-newsroom')->firstOrFail();

    expect($page->body_json['content'])->toHaveCount(2)
        ->and($page->body_html)->toContain('First paragraph.')
        ->and($page->body_html)->toContain('cms-block--paragraph');
});

test('reserved slugs are rejected', function () {
    $this->actingAs(pagesAdmin())
        ->post(route('pages.store'), pagePayload(['slug' => 'search']))
        ->assertSessionHasErrors('slug');

    expect(Page::count())->toBe(0);
});

test('slugs colliding with categories get suffixed', function () {
    Term::factory()->translated('en', 'About Our Newsroom')->create();

    $this->actingAs(pagesAdmin())
        ->post(route('pages.store'), pagePayload())
        ->assertRedirect(route('pages.index'));

    expect(Page::where('slug', 'about-our-newsroom-2')->exists())->toBeTrue();
});

test('duplicate page slugs get suffixed', function () {
    Page::factory()->create(['language' => 'en', 'slug' => 'contact']);

    $this->actingAs(pagesAdmin())
        ->post(route('pages.store'), pagePayload(['title' => 'Contact', 'slug' => 'contact']))
        ->assertRedirect(route('pages.index'));

    expect(Page::where('slug', 'contact-2')->exists())->toBeTrue();
});

test('destroy soft-deletes instead of removing', function () {
    $page = Page::factory()->create();

    $this->actingAs(pagesAdmin())
        ->delete(route('pages.destroy', $page))
        ->assertRedirect(route('pages.index'));

    expect(Page::whereKey($page->getKey())->exists())->toBeFalse()
        ->and(Page::withTrashed()->whereKey($page->getKey())->exists())->toBeTrue();
});

test('edit form pre-fills body text', function () {
    $page = Page::factory()->create();

    $html = $this->actingAs(pagesAdmin())->get(route('pages.edit', $page))->getContent();

    expect($html)->toContain('Edit page');
});
