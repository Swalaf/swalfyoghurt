<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-controlled maintenance mode (Admin → Settings → Maintenance).
 * Admins can still sign in and use everything.
 */
class PlatformMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Settings::get('maintenance') || $request->user()?->is_admin) {
            return $next($request);
        }
        if ($request->is('login', 'logout', 'two-factor', 'install', 'install/*', 'p/*', 'preview/*', 'up')) {
            return $next($request);
        }

        return response()->view('errors.503', ['message' => Settings::get('maintenance_message')], 503);
    }
}
