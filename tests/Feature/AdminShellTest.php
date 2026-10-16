<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\AdminNav;
use Illuminate\Support\Facades\Route;

test('registry entries are well-formed and point at real routes', function () {
    $items = AdminNav::items();

    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        expect($item)->toHaveKeys(['route', 'label', 'icon']);
        expect(Route::has($item['route']))->toBeTrue($item['route'].' is not a registered route');
    }
});

test('dashboard shows permitted entry points without placeholders', function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $adminPage = $this->actingAs($admin)->get(route('dashboard'));
    $adminPage->assertOk();
    $adminPage->assertSee('Users', false);
    $adminPage->assertSee('Settings', false);
    $adminPage->assertSee('Audit log', false);
    $adminPage->assertSee('<svg', false);
    $adminPage->assertDontSee('placeholder-pattern');

    $reporterPage = $this->actingAs($reporter)->get(route('dashboard'));
    $reporterPage->assertOk();
    $reporterPage->assertSee('Notifications', false);
    $reporterPage->assertDontSee('Users');
});

test('dashboard sidebar renders only registry entries', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Dashboard');
    $response->assertDontSee('Articles');
});

test('page header renders title, subheading, and actions', function () {
    $html = (string) $this->blade(
        '<x-ui.page-header title="Users" sub="Manage people"><x-slot:actions><button>Invite</button></x-slot:actions></x-ui.page-header>'
    );

    expect($html)->toContain('Users')
        ->toContain('Manage people')
        ->toContain('Invite')
        ->toContain('<h1');
});

test('breadcrumbs mark the current page and link the trail', function () {
    $html = (string) $this->blade(
        '<x-ui.breadcrumbs :items="$items" />',
        ['items' => [
            ['label' => 'Users', 'url' => '/admin/users'],
            ['label' => 'Invite'],
        ]]
    );

    expect($html)->toContain('aria-current="page"')
        ->toContain('/admin/users')
        ->toContain('Invite');
});

test('breadcrumbs render nothing without items', function () {
    expect((string) $this->blade('<x-ui.breadcrumbs />'))->not->toContain('<nav');
});
