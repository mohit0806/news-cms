<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('catalog holds the exact PRD §4.2 key set', function () {
    expect(Permissions::keys())->toHaveCount(29)
        ->toContain(
            'articles.create',
            'articles.edit.own',
            'articles.edit.any',
            'articles.submit',
            'articles.publish',
            'articles.unpublish',
            'articles.delete',
            'articles.set_breaking',
            'blocks.raw_html',
            'categories.manage',
            'tags.manage',
            'authors.manage',
            'media.upload',
            'media.manage',
            'galleries.manage',
            'polls.manage',
            'live.manage',
            'pages.manage',
            'menus.manage',
            'homepage.manage',
            'redirects.manage',
            'analytics.view',
            'scripts.manage',
            'users.manage',
            'roles.manage',
            'settings.manage',
            'modules.manage',
            'audit.view',
            'system.view',
        );
});

test('every catalog key is registered as a gate ability', function () {
    foreach (Permissions::keys() as $key) {
        expect(Gate::has($key))->toBeTrue($key.' is not a registered gate ability');
    }
});

test('unknown abilities fail closed', function () {
    expect(Gate::has('nope.not-a-permission'))->toBeFalse();

    $user = User::factory()->create();

    expect($user->can('nope.not-a-permission'))->toBeFalse();
});

test('role permission cache invalidates on change', function () {
    $role = Role::create(['name' => 'CacheProbe', 'guard_name' => 'web']);
    $permission = Permission::create(['name' => 'articles.publish', 'guard_name' => 'web']);

    expect(Permissions::forRole($role))->toBeEmpty();

    $role->givePermissionTo($permission);

    expect(Permissions::forRole($role))->toBe(['articles.publish']);
});

test('role seeding is additive and never revokes administrator configuration', function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

    $editor = Role::findByName('Editor');
    $editor->revokePermissionTo('articles.delete');
    Role::findByName('Reporter')->givePermissionTo('system.view');

    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

    expect($editor->refresh()->hasPermissionTo('articles.delete'))->toBeTrue()
        ->and(Role::findByName('Reporter')->hasPermissionTo('system.view'))->toBeTrue();
});

test('single-role invariant rejects zero and multiple roles', function () {
    $user = User::factory()->create();

    expect(fn () => $user->assignRole())->toThrow(InvalidArgumentException::class);
    expect(fn () => $user->syncRoles('Admin', 'Editor'))->toThrow(InvalidArgumentException::class);
});
