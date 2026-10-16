<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\ContentSettings;
use App\Settings\GeneralSettings;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('settings index lists groups for authorized users', function () {
    // Guest first: actingAs() persists for later calls within one test.
    $this->get(route('settings.index'))->assertRedirect(route('login'));

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $response = $this->actingAs($admin)->get(route('settings.index'));

    $response->assertOk();
    $response->assertSee('Content');

    $this->actingAs($reporter)->get(route('settings.index'))->assertForbidden();
});

test('settings edit renders group fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->get(route('settings.edit', 'content'));

    $response->assertOk();
    $response->assertSee('Articles per page');
});

test('unknown settings group 404s', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)->get(route('settings.edit', 'nope'))->assertNotFound();
    $this->actingAs($admin)->put(route('settings.update', 'nope'), [])->assertNotFound();
});

test('settings update persists valid values', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->put(route('settings.update', 'content'), [
        'articles_per_page' => '12',
        'trash_retention_days' => '30',
        'revision_count' => '50',
        'breaking_default_hours' => '2',
        // Toggles unchecked: absent from the payload.
    ]);

    $response->assertRedirect();
    expect(app(ContentSettings::class)->articles_per_page)->toBe(12)
        ->and(app(ContentSettings::class)->require_featured_image)->toBeFalse();
});

test('settings update rejects invalid values', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)->put(route('settings.update', 'content'), [
        'articles_per_page' => '0',
        'trash_retention_days' => '30',
        'revision_count' => '50',
        'breaking_default_hours' => '2',
    ])->assertSessionHasErrors('articles_per_page');
});

test('language-mapped settings persist per language', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)->put(route('settings.update', 'general'), [
        'site_name' => ['en' => 'News', 'hi' => 'समाचार'],
        'tagline' => [],
        'logo_light' => null,
        'logo_dark' => null,
        'favicon' => null,
        'default_language' => 'en',
        'display_timezone' => 'Asia/Kolkata',
    ])->assertRedirect();

    expect(app(GeneralSettings::class)->site_name)->toBe(['en' => 'News', 'hi' => 'समाचार']);
});
