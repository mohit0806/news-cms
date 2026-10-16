<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SystemStatus;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('system status probes report live values', function () {
    $checks = SystemStatus::checks();
    $labels = array_column($checks, 'label');

    expect($labels)->toContain('PHP version', 'Database connection', 'Pending migrations', 'Failed jobs', 'Redis ping', 'Disk free');

    foreach ($checks as $check) {
        expect($check)->toHaveKeys(['label', 'ok', 'detail']);
    }

    $db = array_values(array_filter($checks, fn ($c) => $c['label'] === 'Database connection'))[0];

    expect($db['ok'])->toBeTrue()
        ->and($db['detail'])->toBe('newscms_test');
});

test('system status page renders for users with system.view', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $response = $this->actingAs($user)->get(route('system.status'));

    $response->assertOk();
    $response->assertSee('System status');
    $response->assertSee('Pending migrations');
});

test('system status page denies users without system.view', function () {
    $plain = User::factory()->create();
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $this->actingAs($plain)->get(route('system.status'))->assertForbidden();
    $this->actingAs($reporter)->get(route('system.status'))->assertForbidden();
});

test('system status page redirects guests to login', function () {
    $this->get(route('system.status'))->assertRedirect(route('login'));
});
