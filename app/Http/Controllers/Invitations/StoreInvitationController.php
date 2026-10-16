<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invitations;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class StoreInvitationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        // Resend semantics: replace any unaccepted invitation for this email.
        Invitation::query()
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->delete();

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'expires_at' => now()->addHours(48),
            'invited_by' => $request->user()->getKey(),
        ]);

        $acceptUrl = URL::signedRoute(
            'invitations.accept',
            ['invitation' => $invitation->getKey()],
            $invitation->expires_at
        );

        Mail::to($invitation->email)->send(new InvitationMail($invitation, $acceptUrl));

        return back()->with('toast', __('Invitation sent.'));
    }
}
