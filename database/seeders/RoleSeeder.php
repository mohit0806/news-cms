<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed roles and their default permission sets (PRD §4.1, §4.2; TRD §6.5).
     *
     * Additive only: existing roles keep administrator-configured permissions;
     * seeding only attaches missing catalog keys and never revokes anything.
     * Safe to re-run on every deployment.
     */
    public function run(): void
    {
        foreach (Permissions::keys() as $key) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        }

        foreach (Permissions::defaults() as $roleName => $keys) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $existing = $role->permissions()->pluck('name')->all();
            $missing = array_diff($keys, $existing);

            if ($missing !== []) {
                $role->givePermissionTo(array_values($missing));
            }
        }

        Permissions::flushRoleCache();
    }
}
