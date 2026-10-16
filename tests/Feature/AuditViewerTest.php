<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('audit viewer requires audit.view', function () {
    // Guest first: actingAs() persists for later calls within one test.
    $this->get(route('audit.index'))->assertRedirect(route('login'));

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');
    $plain = User::factory()->create();

    $this->actingAs($admin)->get(route('audit.index'))->assertOk();
    $this->actingAs($reporter)->get(route('audit.index'))->assertForbidden();
    $this->actingAs($plain)->get(route('audit.index'))->assertForbidden();
});

test('audit viewer filters by action', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $response = $this->actingAs($admin)->get(route('audit.index', ['action' => 'login.success']));

    $response->assertOk();
    $response->assertSee('login.success');
});

test('audit CSV export downloads filtered rows', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $response = $this->actingAs($admin)->get(route('audit.export', ['action' => 'login.success']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('login.success');
});

test('audit export requires audit.view', function () {
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $this->actingAs($reporter)->get(route('audit.export'))->assertForbidden();
});
