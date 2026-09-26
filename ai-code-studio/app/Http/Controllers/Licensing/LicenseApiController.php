<?php

namespace App\Http\Controllers\Licensing;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Release;
use App\Services\Licensing\LicenseServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** Public JSON API that buyers' installations call (vendor installation only). */
class LicenseApiController extends Controller
{
    public function __construct(protected LicenseServer $server) {}

    protected function input(Request $request): array
    {
        return $request->validate([
            'key' => 'required|string|max:100',
            'domain' => 'required|string|max:255',
            'instance' => 'nullable|string|max:64',
            'version' => 'nullable|string|max:20',
        ]);
    }

    public function activate(Request $request)
    {
        $d = $this->input($request);
        [$status, $body] = $this->server->activate($d['key'], $d['domain'], $d['instance'] ?? '', $d['version'] ?? '');

        return response()->json($body, $status);
    }

    public function verify(Request $request)
    {
        $d = $this->input($request);
        [$status, $body] = $this->server->verify($d['key'], $d['domain'], $d['version'] ?? '');

        return response()->json($body, $status);
    }

    public function deactivate(Request $request)
    {
        $d = $this->input($request);
        [$status, $body] = $this->server->deactivate($d['key'], $d['domain']);

        return response()->json($body, $status);
    }

    public function latest(Request $request)
    {
        $d = $this->input($request);
        $release = Release::all()->sort(fn ($a, $b) => version_compare($b->version, $a->version))->first();
        if (! $release) {
            return response()->json(['ok' => false, 'message' => 'No releases published yet.'], 404);
        }
        $license = $this->server->authorizeDownload($d['key'], $d['domain']);

        return response()->json([
            'ok' => true,
            'version' => $release->version,
            'notes' => $release->notes,
            'released_at' => $release->created_at->toIso8601String(),
            'download_url' => $license ? URL::temporarySignedRoute('updates.download', now()->addHour(), ['release' => $release->id, 'license' => $license->id]) : null,
            'message' => $license ? null : 'Renew support to download updates.',
        ]);
    }

    public function download(Request $request, Release $release)
    {
        $license = License::findOrFail($request->query('license'));
        abort_unless($license->isActive() && $license->supportActive(), 403);
        abort_unless(Storage::disk('local')->exists($release->path), 404);

        return Storage::disk('local')->download($release->path, 'ai-code-studio-'.$release->version.'.zip');
    }
}
