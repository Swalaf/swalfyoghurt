<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use Symfony\Component\HttpFoundation\Response;

class PublishedAppController extends Controller
{
    public const TYPES = [
        'html' => 'text/html', 'htm' => 'text/html', 'css' => 'text/css', 'js' => 'text/javascript', 'mjs' => 'text/javascript',
        'json' => 'application/json', 'svg' => 'image/svg+xml', 'txt' => 'text/plain', 'md' => 'text/plain', 'xml' => 'application/xml',
    ];

    public function __invoke(string $subdomain, ?string $path = null): Response
    {
        $deployment = Deployment::where('subdomain', $subdomain)->where('status', 'live')->latest('id')->firstOrFail();

        return self::serve($deployment->snapshot ?? [], $path, url('/p/'.$subdomain).'/');
    }

    /**
     * Serve a file out of a {path: content} map. User-generated HTML is sandboxed
     * (opaque origin) so it can never read this app's cookies or call its routes.
     */
    public static function serve(array $files, ?string $path, string $baseHref, string $inject = ''): Response
    {
        $path = trim((string) $path, '/');
        if ($path === '') {
            $path = 'index.html';
        } elseif (! isset($files[$path]) && isset($files[$path.'/index.html'])) {
            $path .= '/index.html';
        }
        abort_unless(isset($files[$path]), 404);

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $body = $files[$path];
        if (in_array($ext, ['html', 'htm'], true) && ! preg_match('/<base\s/i', $body)) {
            // Relative links resolve against the app root regardless of trailing slashes.
            $dir = str_contains($path, '/') ? dirname($path).'/' : '';
            $tag = '<base href="'.e($baseHref.$dir).'">';
            $body = preg_match('/<head[^>]*>/i', $body) ? preg_replace('/<head[^>]*>/i', '$0'.$tag, $body, 1) : $tag.$body;
            if ($inject !== '') {
                $body = stripos($body, '</body>') !== false ? substr_replace($body, $inject, strripos($body, '</body>'), 0) : $body.$inject;
            }
        }

        return response($body, 200, [
            'Content-Type' => (self::TYPES[$ext] ?? 'text/plain').'; charset=utf-8',
            'Content-Security-Policy' => 'sandbox allow-scripts allow-forms allow-popups allow-modals allow-downloads',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
