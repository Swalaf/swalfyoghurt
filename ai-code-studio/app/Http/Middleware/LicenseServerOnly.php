<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseServerOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('studio.license_server'), 404);

        return $next($request);
    }
}
