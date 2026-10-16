<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Code-defined admin navigation registry (P1-T06.1).
 *
 * The sidebar renders ONLY these entries: unimplemented (P2+) sections can
 * never appear as dead links. Entries may carry an optional `permission` key;
 * permission keys are wired by P1-T01.6 once the catalog lands (until then all
 * entries are permissionless and visible).
 */
final class AdminNav
{
    /**
     * @return list<array{route: string, label: string, icon: string, description: string, permission?: string}>
     */
    public static function items(): array
    {
        return [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'description' => 'Start here.'],
            ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => 'bell', 'description' => 'Your editorial alerts.'],
            ['route' => 'users.index', 'label' => 'Users', 'icon' => 'users', 'description' => 'Staff accounts and roles.', 'permission' => 'users.manage'],
            ['route' => 'roles.index', 'label' => 'Roles', 'icon' => 'key', 'description' => 'Role definitions and assignments.', 'permission' => 'roles.manage'],
            ['route' => 'categories.index', 'label' => 'Categories', 'icon' => 'folder', 'description' => 'Section hierarchy.', 'permission' => 'categories.manage'],
            ['route' => 'tags.index', 'label' => 'Tags', 'icon' => 'tag', 'description' => 'Flat labels and merges.', 'permission' => 'tags.manage'],
            ['route' => 'authors.index', 'label' => 'Authors', 'icon' => 'identification', 'description' => 'Public bylines.', 'permission' => 'authors.manage'],
            ['route' => 'pages.index', 'label' => 'Pages', 'icon' => 'document-text', 'description' => 'Standalone content.', 'permission' => 'pages.manage'],
            ['route' => 'menus.index', 'label' => 'Menus', 'icon' => 'bars-3', 'description' => 'Site navigation.', 'permission' => 'menus.manage'],
            ['route' => 'homepage.index', 'label' => 'Homepage', 'icon' => 'squares-2x2', 'description' => 'Homepage blocks.', 'permission' => 'homepage.manage'],
            ['route' => 'articles.index', 'label' => 'Articles', 'icon' => 'newspaper', 'description' => 'Drafts to published.', 'permission' => 'articles.create'],
            ['route' => 'settings.index', 'label' => 'Settings', 'icon' => 'cog', 'description' => 'Product controls by group.', 'permission' => 'settings.manage'],
            ['route' => 'audit.index', 'label' => 'Audit log', 'icon' => 'clipboard-document-list', 'description' => 'Append-only history.', 'permission' => 'audit.view'],
            ['route' => 'system.status', 'label' => 'System status', 'icon' => 'server-stack', 'description' => 'Live probes.', 'permission' => 'system.view'],
        ];
    }

    /**
     * @return list<array{route: string, label: string, icon: string, description: string, permission?: string}>
     */
    public static function visibleItems(): array
    {
        return array_values(array_filter(
            self::items(),
            fn (array $item) => Route::has($item['route'])
                && (! isset($item['permission']) || Gate::allows($item['permission'])),
        ));
    }
}
