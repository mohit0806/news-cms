<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('generated allow/deny matrix matches the seeded defaults', function () {
    foreach (Permissions::defaults() as $roleName => $allowed) {
        $user = User::factory()->create();
        $user->assignRole($roleName);

        foreach (Permissions::keys() as $key) {
            $expected = in_array($key, $allowed, true);

            expect($user->can($key))->toBe($expected, "{$roleName} {$key} should be ".($expected ? 'allowed' : 'denied'));
        }
    }
})->group('matrix');

test('guests are denied every catalog ability', function () {
    foreach (Permissions::keys() as $key) {
        expect(Gate::denies($key))->toBeTrue("guest {$key} should be denied");
    }
});
