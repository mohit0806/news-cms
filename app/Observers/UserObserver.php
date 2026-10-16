<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Support\Audit;

class UserObserver
{
    public function created(User $user): void
    {
        Audit::log('users.created', $user, [
            'name' => $user->name,
            'email' => $user->email,
        ], auth()->user());
    }

    public function updated(User $user): void
    {
        $changes = [];

        foreach ($user->getChanges() as $key => $value) {
            $changes[$key] = [
                'before' => $user->getOriginal($key),
                'after' => $value,
            ];
        }

        Audit::log('users.updated', $user, ['changes' => $changes], auth()->user());
    }
}
