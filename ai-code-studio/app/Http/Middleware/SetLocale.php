<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Picks the interface language: ?lang= → user preference → session → browser → platform default. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('studio.locales'));

        if (($lang = $request->query('lang')) && in_array($lang, $available, true)) {
            $request->session()->put('locale', $lang);
            if ($request->user() && $request->user()->locale !== $lang) {
                $request->user()->forceFill(['locale' => $lang])->saveQuietly();
            }
        }

        $default = array_search(Settings::get('default_language', 'English'), config('studio.locales'), true) ?: 'en';
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $request->getPreferredLanguage($available)
            ?? $default;

        app()->setLocale(in_array($locale, $available, true) ? $locale : 'en');

        return $next($request);
    }
}
