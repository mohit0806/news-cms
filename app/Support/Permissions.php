<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

/**
 * Code-owned permission catalog (PRD §4.2, TRD §6.5, §7; ADR-0002).
 *
 * Keys are fixed in code and versioned here; role assignments live in the
 * package tables. Per-role sets are cached (`role:{id}:perms`) and invalidated
 * on role/permission writes (see AuthServiceProvider) and on user role changes.
 */
final class Permissions
{
    /**
     * All 29 permission keys from PRD §4.2, verbatim.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
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
        ];
    }

    /**
     * Seeded default permission sets per role (PRD §4.2 matrix).
     *
     * Reporter keeps `tags.manage` create-only (Q-P1-01: matrix as written;
     * the create-only limitation is enforced at the P2 tags UI, not here).
     * Analytics/live scoping ("own articles only") is likewise enforced at the
     * P2 query layer; the catalog grants the keys.
     *
     * @return array<string, list<string>>
     */
    public static function defaults(): array
    {
        $editor = [
            'articles.create', 'articles.edit.own', 'articles.edit.any', 'articles.submit',
            'articles.publish', 'articles.unpublish', 'articles.delete', 'articles.set_breaking',
            'blocks.raw_html', 'categories.manage', 'tags.manage', 'authors.manage',
            'media.upload', 'media.manage', 'galleries.manage', 'polls.manage', 'live.manage',
            'pages.manage', 'menus.manage', 'homepage.manage', 'redirects.manage', 'analytics.view',
        ];

        return [
            'Admin' => self::keys(),
            'Editor' => $editor,
            'Reporter' => [
                'articles.create', 'articles.edit.own', 'articles.submit',
                'tags.manage', 'media.upload', 'live.manage', 'analytics.view',
            ],
            // Q3: Reporter minus category/tag creation.
            'Contributor' => [
                'articles.create', 'articles.edit.own', 'articles.submit',
                'media.upload', 'live.manage', 'analytics.view',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function forRole(Role $role): array
    {
        return array_values(Cache::remember(
            "role:{$role->getKey()}:perms",
            3600,
            fn (): array => $role->permissions()->pluck('name')
                ->map(fn ($name): string => (string) $name)
                ->all(),
        ));
    }

    /**
     * @return list<string>
     */
    public static function forUser(User $user): array
    {
        $keys = [];

        foreach ($user->getRoleNames() as $roleName) {
            $role = Role::query()->where('name', $roleName)->firstOrFail();

            array_push($keys, ...self::forRole($role));
        }

        return array_values(array_unique($keys));
    }

    public static function flushRoleCache(): void
    {
        $ids = Role::query()->pluck('id')->all();

        foreach ($ids as $id) {
            Cache::forget("role:{$id}:perms");
        }
    }
}
