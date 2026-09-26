<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in users must be active, have verified their email (when required)
 * and finished onboarding before using the studio.
 */
class EnsureAccountReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user->isSuspended() && ! $request->session()->has('impersonator_id')) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'This account is suspended. Contact support to restore access.']);
        }
        if (Settings::get('require_verification') && ! $user->email_verified_at) {
            return redirect()->route('verification.notice');
        }
        if ($user->coding_level === null) {
            return redirect()->route('onboarding');
        }
        if (! $user->last_active_at || $user->last_active_at->lt(now()->subMinutes(5))) {
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
