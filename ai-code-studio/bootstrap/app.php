<?php

use App\Http\Middleware\EnsureAccountReady;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\LicenseServerOnly;
use App\Http\Middleware\PlatformMaintenance;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class, EnsureInstalled::class, SetLocale::class, PlatformMaintenance::class]);
        $middleware->alias(['admin' => EnsureAdmin::class, 'ready' => EnsureAccountReady::class, 'license.server' => LicenseServerOnly::class]);
        // Called by payment gateways and buyers' installations, not browsers.
        $middleware->validateCsrfTokens(except: ['webhooks/*', 'api/license/*']);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/studio');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
