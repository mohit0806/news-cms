<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Settings\NewsSettings;
use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelSettings\Events\SettingsSaved;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::observe(UserObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                Audit::log('login.success', $event->user, [], $event->user);
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                Audit::log('logout', $event->user, [], $event->user);
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            Audit::log('login.failed', null, [
                'email' => $event->credentials['email'] ?? null,
            ]);
        });

        Event::listen(RoleAttachedEvent::class, function (RoleAttachedEvent $event): void {
            if ($event->model instanceof User) {
                Audit::log('users.role-assigned', $event->model, [
                    'roles' => $event->rolesOrIds,
                ]);
            }
        });

        Event::listen(RoleDetachedEvent::class, function (RoleDetachedEvent $event): void {
            if ($event->model instanceof User) {
                Audit::log('users.role-revoked', $event->model, [
                    'roles' => $event->rolesOrIds,
                ]);
            }
        });

        Event::listen(SettingsSaved::class, function (SettingsSaved $event): void {
            // Group name only — never setting values (defense in depth).
            $group = $event->settings instanceof NewsSettings
                ? $event->settings::group()
                : $event->settings::class;

            Audit::log('settings.changed', null, ['group' => $group]);
        });

        // Append-only enforcement: ordinary code paths cannot update or delete
        // audit history. Retention pruning bypasses the model via query builder.
        Activity::updating(function (): bool {
            throw new RuntimeException('Audit records are immutable.');
        });

        Activity::deleting(function (): bool {
            throw new RuntimeException('Audit records are immutable.');
        });
    }
}
