<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce TOTP two-factor authentication for Admin users when
 * FORCE_ADMIN_2FA is enabled (PRD §15.5).
 *
 * Redirects to the security settings screen where 2FA can be enrolled;
 * setup, challenge, and logout routes are excluded to avoid lockout loops.
 */
class EnsureTwoFactorAuthentication
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null
            || ! (bool) config('app.force_admin_2fa', false)
            || ! $user->hasRole('Admin')
            || $user->hasEnabledTwoFactorAuthentication()
            || $request->routeIs(
                'profile.edit',
                'security.edit',
                'two-factor.*',
                'password.confirm*',
                'logout',
            )
        ) {
            return $next($request);
        }

        return redirect()->route('security.edit');
    }
}
