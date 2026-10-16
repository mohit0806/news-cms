<?php

declare(strict_types=1);

use App\Events\HomepageUpdated;
use App\Models\LayoutBlock;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function homepageAdmin(string $role = 'Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function blockPayload(array $overrides = []): array
{
    return array_replace([
        'language' => 'en',
        'widget' => 'latest',
        'title' => 'Latest news',
        'config' => '{"count": 10}',
        'is_active' => '1',
    ], $overrides);
}

test('reporters cannot manage homepage blocks', function () {
    $this->get(route('homepage.index'))->assertRedirect(route('login'));

    $this->actingAs(homepageAdmin('Reporter'))->get(route('homepage.index'))->assertForbidden();
    $this->actingAs(homepageAdmin('Reporter'))->post(route('homepage.store'), blockPayload())->assertForbidden();
});

test('store persists typed config and fires the purge event', function () {
    Event::fake([HomepageUpdated::class]);

    $this->actingAs(homepageAdmin('Editor'))
        ->post(route('homepage.store'), blockPayload())
        ->assertRedirect();

    $block = LayoutBlock::where('widget', 'latest')->firstOrFail();

    expect($block->config_json)->toBe(['count' => 10])
        ->and($block->sort)->toBe(0);

    Event::assertDispatched(HomepageUpdated::class, fn (HomepageUpdated $event): bool => $event->language === 'en');
});

test('update, reorder, and destroy all fire the purge event', function () {
    Event::fake([HomepageUpdated::class]);

    $block = LayoutBlock::create([
        'region' => 'home', 'language' => 'en', 'widget' => 'latest',
        'title' => 'Old', 'config_json' => null, 'sort' => 0, 'is_active' => true,
    ]);

    $this->actingAs(homepageAdmin())
        ->put(route('homepage.update', $block), blockPayload(['title' => 'New']))
        ->assertRedirect();

    $this->actingAs(homepageAdmin())
        ->post(route('homepage.reorder'), [
            'language' => 'en',
            'order' => [['id' => $block->getKey(), 'sort' => 3]],
        ])
        ->assertRedirect();

    expect($block->refresh()->sort)->toBe(3);

    $this->actingAs(homepageAdmin())
        ->delete(route('homepage.destroy', $block))
        ->assertRedirect();

    expect(LayoutBlock::whereKey($block->getKey())->exists())->toBeFalse();

    Event::assertDispatchedTimes(HomepageUpdated::class, 3);
});

test('invalid widget and malformed config are rejected', function () {
    $this->actingAs(homepageAdmin())
        ->post(route('homepage.store'), blockPayload(['widget' => 'ticker']))
        ->assertSessionHasErrors('widget');

    $this->actingAs(homepageAdmin())
        ->post(route('homepage.store'), blockPayload(['config' => '{oops']))
        ->assertSessionHasErrors('config');

    expect(LayoutBlock::count())->toBe(0);
});

test('custom html blocks require the raw html permission', function () {
    $payload = blockPayload(['widget' => 'custom-html', 'config' => '{"html": "<p>Hi</p>"}']);

    $role = Role::create(['name' => 'Curator', 'guard_name' => 'web']);
    $role->givePermissionTo('homepage.manage');

    $curator = User::factory()->create();
    $curator->assignRole('Curator');

    $this->actingAs($curator)->post(route('homepage.store'), $payload)->assertForbidden();
    expect(LayoutBlock::where('widget', 'custom-html')->exists())->toBeFalse();

    $role->givePermissionTo('blocks.raw_html');

    $this->actingAs($curator)->post(route('homepage.store'), $payload)->assertRedirect();
    expect(LayoutBlock::where('widget', 'custom-html')->exists())->toBeTrue();
});

test('unknown blocks in reorder are rejected', function () {
    $this->actingAs(homepageAdmin())
        ->post(route('homepage.reorder'), [
            'language' => 'en',
            'order' => [['id' => 999999, 'sort' => 0]],
        ])
        ->assertSessionHasErrors('order');
});
