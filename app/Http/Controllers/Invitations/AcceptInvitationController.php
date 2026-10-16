<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invitations;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class AcceptInvitationController extends Controller
{
    public function show(Invitation $invitation): View
    {
        abort_unless($invitation->isUsable(), 410);

        return view('invitations.accept', ['invitation' => $invitation]);
    }

    public function accept(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->isUsable(), 410);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(10), 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated) {
            // Atomic single-use claim: concurrent accepts cannot both succeed.
            $claimed = Invitation::query()
                ->whereKey($invitation->getKey())
                ->whereNull('accepted_at')
                ->update(['accepted_at' => now()]);

            abort_unless($claimed !== 0, 410);

            $created = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => $validated['password'],
            ]);

            $created->assignRole($invitation->role);

            return $created;
        });

        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
