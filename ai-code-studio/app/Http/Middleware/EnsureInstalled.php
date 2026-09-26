<?php

namespace App\Http\Middleware;

use App\Support\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installing = $request->is('install') || $request->is('install/*');
        $installed = Installation::installed();

        if (! $installed && ! $installing) {
            return redirect('/install');
        }
        if ($installed && $installing && ! $request->session()->get('install.just_finished')) {
            abort(404);
        }

        return $next($request);
    }
}
