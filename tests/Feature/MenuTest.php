<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Term;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function menusAdmin(string $role = 'Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('reporters cannot manage menus', function () {
    $this->get(route('menus.index'))->assertRedirect(route('login'));

    $this->actingAs(menusAdmin('Reporter'))->get(route('menus.index'))->assertForbidden();
});

test('duplicate location-language menus are rejected', function () {
    Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);

    $this->actingAs(menusAdmin())
        ->post(route('menus.store'), ['name' => 'Header 2', 'location' => 'header', 'language' => 'en'])
        ->assertSessionHasErrors('location');

    expect(Menu::count())->toBe(1);
});

test('items nest to two levels only', function () {
    $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);
    $top = $menu->items()->create(['label' => 'News', 'type' => 'url', 'url' => 'https://example.com', 'sort' => 0]);
    $child = $menu->items()->create(['label' => 'Local', 'type' => 'url', 'url' => 'https://example.com/l', 'parent_id' => $top->getKey(), 'sort' => 1]);

    $this->actingAs(menusAdmin())
        ->post(route('menus.items.store', $menu), [
            'label' => 'Too deep', 'type' => 'url', 'url' => 'https://example.com/x', 'parent_id' => $child->getKey(),
        ])
        ->assertSessionHasErrors('parent_id');

    expect($menu->items()->count())->toBe(2);
});

test('broken targets are flagged on the edit screen', function () {
    $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);
    $page = Page::factory()->create(['status' => 'published']);
    $menu->items()->create(['label' => 'About', 'type' => 'page', 'target_id' => $page->getKey(), 'sort' => 0]);
    $menu->items()->create(['label' => 'Ghost', 'type' => 'page', 'target_id' => 999999, 'sort' => 1]);

    $html = $this->actingAs(menusAdmin())->get(route('menus.edit', $menu))->getContent();

    expect($html)->toContain('Broken link')
        ->toContain('About');
});

test('unpublished articles flag their menu items', function () {
    $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);
    $draft = Article::factory()->create(['status' => 'draft']);
    $menu->items()->create(['label' => 'Draft story', 'type' => 'article', 'target_id' => $draft->getKey(), 'sort' => 0]);

    $html = $this->actingAs(menusAdmin())->get(route('menus.edit', $menu))->getContent();

    expect($html)->toContain('Broken link');

    $draft->forceFill(['status' => 'published', 'published_at' => now()])->save();

    $html = $this->actingAs(menusAdmin())->get(route('menus.edit', $menu))->getContent();

    expect($html)->not->toContain('Broken link');
});

test('reorder persists sort values within the menu', function () {
    $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);
    $first = $menu->items()->create(['label' => 'A', 'type' => 'url', 'url' => 'https://a.example', 'sort' => 0]);
    $second = $menu->items()->create(['label' => 'B', 'type' => 'url', 'url' => 'https://b.example', 'sort' => 1]);

    $this->actingAs(menusAdmin())
        ->post(route('menus.items.reorder', $menu), [
            'items' => [
                ['id' => $first->getKey(), 'sort' => 1],
                ['id' => $second->getKey(), 'sort' => 0],
            ],
        ])
        ->assertRedirect(route('menus.edit', $menu));

    expect($first->refresh()->sort)->toBe(1)
        ->and($second->refresh()->sort)->toBe(0);
});

test('reorder rejects foreign items', function () {
    $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);
    $other = Menu::create(['name' => 'Footer', 'location' => 'footer', 'language' => 'en']);
    $foreign = $other->items()->create(['label' => 'X', 'type' => 'url', 'url' => 'https://x.example', 'sort' => 0]);

    $this->actingAs(menusAdmin())
        ->post(route('menus.items.reorder', $menu), [
            'items' => [['id' => $foreign->getKey(), 'sort' => 0]],
        ])
        ->assertSessionHasErrors('items');
});

test('category and tag targets resolve against vocabulary', function () {
    $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'language' => 'en']);
    $category = Term::factory()->translated()->create();
    $tag = Term::factory()->tag()->translated()->create();
    $menu->items()->create(['label' => 'Section', 'type' => 'category', 'target_id' => $category->getKey(), 'sort' => 0]);
    $menu->items()->create(['label' => 'Topic', 'type' => 'tag', 'target_id' => $tag->getKey(), 'sort' => 1]);

    $html = $this->actingAs(menusAdmin())->get(route('menus.edit', $menu))->getContent();

    expect($html)->not->toContain('Broken link');
});
