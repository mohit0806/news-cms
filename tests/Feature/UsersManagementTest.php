<?php

declare(strict_types=1);

use App\Http\Controllers\UsersController;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function adminUser(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    return $admin;
}

test('users index lists accounts with filters for authorized users', function () {
    $admin = adminUser();
    User::factory()->create(['name' => 'Zed Reporter']);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertOk();
    $response->assertSee('Zed Reporter');

    $this->actingAs($admin)->get(route('users.index', ['search' => 'Zed']))->assertSee('Zed Reporter');
});

test('users management denies unauthorized users', function () {
    // Guest first: actingAs() persists for later calls within one test.
    $this->get(route('users.index'))->assertRedirect(route('login'));

    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $this->actingAs($reporter)->get(route('users.index'))->assertForbidden();
    $this->actingAs($reporter)->get(route('roles.index'))->assertForbidden();
    $this->actingAs($reporter)->post(route('users.store'), [])->assertForbidden();
});

test('admin can create a user with a role', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Editor',
        'email' => 'editor@example.com',
        'password' => '1234567890',
        'password_confirmation' => '1234567890',
        'role' => 'Editor',
    ]);

    $response->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'editor@example.com')->firstOrFail();

    expect($user->hasRole('Editor'))->toBeTrue();

    $this->actingAs($admin)->get(route('users.index'))->assertSee('User created.');
});

test('failed submissions keep input and show labeled errors', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Kept Name',
        'email' => 'not-an-email',
        'password' => '1234567890',
        'password_confirmation' => '1234567890',
        'role' => 'Editor',
    ]);

    $response->assertSessionHasErrors('email');

    $form = $this->actingAs($admin)->get(route('users.create'));

    $form->assertOk();
    $form->assertSee('Kept Name', false);
    $form->assertSee('role="alert"', false);
});

test('user forms carry labels and dirty-state wiring', function () {
    $admin = adminUser();

    $page = $this->actingAs($admin)->get(route('users.create'));

    $page->assertSee('Name', false);
    $page->assertSee('Role', false);
    $page->assertSee('beforeunload', false);
});

test('user creation validates input', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'X',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'short',
        'role' => 'Nope',
    ])->assertSessionHasErrors(['email', 'password', 'role']);
});

test('admin can update a user and change role', function () {
    $admin = adminUser();
    $user = User::factory()->create();
    $user->assignRole('Reporter');

    $response = $this->actingAs($admin)->put(route('users.update', $user), [
        'name' => 'Promoted',
        'email' => $user->email,
        'locale' => 'hi',
        'is_active' => '1',
        'role' => 'Editor',
    ]);

    $response->assertRedirect(route('users.index'));

    expect($user->refresh()->name)->toBe('Promoted')
        ->and($user->locale)->toBe('hi')
        ->and($user->hasRole('Editor'))->toBeTrue()
        ->and($user->hasRole('Reporter'))->toBeFalse();
});

test('admin can deactivate and reactivate users', function () {
    $admin = adminUser();
    $user = User::factory()->create();

    $this->actingAs($admin)->post(route('users.deactivate', $user))->assertRedirect();
    expect($user->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->post(route('users.activate', $user))->assertRedirect();
    expect($user->refresh()->is_active)->toBeTrue();
});

test('self-deactivation and last-admin deactivation are refused', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('users.deactivate', $admin))->assertStatus(422);

    $second = User::factory()->create();
    $second->assignRole('Admin');

    $this->actingAs($admin)->post(route('users.deactivate', $second))->assertRedirect();

    // Last-admin guard: unreachable via HTTP (an active Admin actor always counts
    // as another active admin), so it is verified directly as defense-in-depth.
    // Arrange a single active admin plus an inactive actor via direct writes.
    $admin->forceFill(['is_active' => false])->save();
    $sole = User::factory()->create();
    $sole->assignRole('Admin');
    $inactiveActor = User::factory()->create(['is_active' => false]);
    $inactiveActor->assignRole('Admin');

    $method = new ReflectionMethod(UsersController::class, 'ensureDeactivatable');
    $method->setAccessible(true);

    try {
        $method->invoke(new UsersController, $sole, $inactiveActor);
        $this->fail('Expected the last-admin guard to abort.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});

test('bulk deactivate affects selected users only', function () {
    $admin = adminUser();
    $one = User::factory()->create();
    $two = User::factory()->create();

    $this->actingAs($admin)->post(route('users.bulk-deactivate'), ['ids' => [$one->getKey(), $two->getKey()]])
        ->assertRedirect();

    expect($one->refresh()->is_active)->toBeFalse()
        ->and($two->refresh()->is_active)->toBeFalse()
        ->and($admin->refresh()->is_active)->toBeTrue();
});

test('roles index shows roles with counts', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->get(route('roles.index'));

    $response->assertOk();
    $response->assertSee('Admin');
    $response->assertSee('Reporter');
});

test('nav exposes permitted sections only', function () {
    $admin = adminUser();
    $reporter = User::factory()->create();
    $reporter->assignRole('Reporter');

    $adminPage = $this->actingAs($admin)->get(route('dashboard'));
    $adminPage->assertSee('Users', false);
    $adminPage->assertSee('Settings', false);
    $adminPage->assertSee('Audit log', false);

    $reporterPage = $this->actingAs($reporter)->get(route('dashboard'));
    $reporterPage->assertDontSee('Users');
    $reporterPage->assertSee('Dashboard');
});
