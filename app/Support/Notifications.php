<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use InvalidArgumentException;

/**
 * Notification dispatch contracts for P1/P2 emitters (PRD §15.4, P1-T08.2).
 *
 * - `send()` delivers unconditionally (database always; mail per the
 *   notification's own `via()` preference check).
 * - `sendOnce()` additionally guards against duplicate user-visible rows when
 *   a sender runs more than once (job retries, double event handling): a
 *   notification carrying the same `dedupe_key` skips delivery while an
 *   unread row with that key exists. Read rows never block — new activity
 *   re-notifies.
 *
 * Contract for P2 emitters: notifications that must not duplicate on retry
 * accept a stable dedupe key (e.g. `article:42:submitted`) and include it as
 * `dedupe_key` in `toArray()`; emitters deliver them via `sendOnce()`.
 *
 * Limitation: check-then-insert without a distributed lock. Safe for admin
 * notification volumes; concurrent duplicate sends in the same instant could
 * both pass the check. Documented, not hidden.
 */
final class Notifications
{
    public static function send(User $notifiable, Notification $notification): void
    {
        NotificationFacade::send($notifiable, $notification);
    }

    public static function sendOnce(User $notifiable, string $key, Notification $notification): void
    {
        // Fail fast on contract violation: without the key in the stored data,
        // the existence check below could never match and dedup would silently
        // do nothing. Mirrors the database channel, which also probes toArray.
        $data = method_exists($notification, 'toArray')
            ? $notification->toArray($notifiable)
            : null;

        if (! is_array($data) || ($data['dedupe_key'] ?? null) !== $key) {
            throw new InvalidArgumentException('Notifications sent via sendOnce() must include the dedupe key in toArray().');
        }

        $exists = $notifiable->notifications()
            ->where('type', $notification::class)
            ->where('data->dedupe_key', $key)
            ->whereNull('read_at')
            ->exists();

        if ($exists) {
            return;
        }

        NotificationFacade::send($notifiable, $notification);
    }
}
