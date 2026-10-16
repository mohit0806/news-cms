<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Support\Permissions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (Permissions::keys() as $key) {
            Gate::define($key, fn (User $user): bool => in_array($key, Permissions::forUser($user), true));
        }

        Gate::policy(Article::class, ArticlePolicy::class);

        // Spatie domain events cover every attach/detach path (give/revoke/sync);
        // saved/deleted cover attribute changes. There are no Eloquent pivot model
        // events in this Laravel version, so pivot listeners would never fire.
        foreach ([
            PermissionAttachedEvent::class,
            PermissionDetachedEvent::class,
            RoleAttachedEvent::class,
            RoleDetachedEvent::class,
        ] as $event) {
            Event::listen($event, function (): void {
                Permissions::flushRoleCache();
            });
        }

        foreach ([Role::class, Permission::class] as $model) {
            Event::listen("eloquent.saved: {$model}", function (): void {
                Permissions::flushRoleCache();
            });
            Event::listen("eloquent.deleted: {$model}", function (): void {
                Permissions::flushRoleCache();
            });
        }
    }
}
