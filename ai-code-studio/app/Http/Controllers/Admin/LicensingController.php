<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Release;
use App\Services\Licensing\LicenseServer;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Vendor-only admin: licences, releases and the storefront settings. */
class LicensingController extends AdminController
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return view('admin.licenses', [
            'licenses' => License::when($q, fn ($w) => $w->where(fn ($x) => $x->where('key', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('domain', 'like', "%$q%")->orWhere('name', 'like', "%$q%")))->latest('id')->paginate(30)->withQueryString(),
            'releases' => Release::latest('id')->get(),
            'settings' => Settings::all(),
        ]);
    }

    public function store(Request $request, LicenseServer $server)
    {
        $d = $request->validate(['type' => 'required|in:regular,extended', 'name' => 'nullable|string|max:120', 'email' => 'nullable|email|max:255', 'months' => 'nullable|integer|min:1|max:120']);
        $license = $server->issue(['type' => $d['type'], 'name' => $d['name'] ?? null, 'email' => $d['email'] ?? null, 'source' => 'manual', 'supported_until' => now()->addMonths((int) ($d['months'] ?? 12))]);

        return back()->with('status', 'Issued '.$license->key);
    }

    public function update(Request $request, License $license)
    {
        match ($request->input('action')) {
            'revoke' => $license->update(['status' => 'revoked']),
            'reactivate' => $license->update(['status' => 'active']),
            'reset' => $license->update(['domain' => null, 'instance_id' => null]),
            'extend' => $license->update(['supported_until' => ($license->supported_until?->isFuture() ? $license->supported_until : now())->addMonths(12)]),
            default => abort(422),
        };
        ActivityLog::record('License', ucfirst($request->input('action')).' '.$license->key, 'WARN');

        return back()->with('status', 'Licence updated.');
    }

    public function release(Request $request)
    {
        $d = $request->validate(['version' => 'required|regex:/^\d+\.\d+\.\d+$/|unique:releases,version', 'notes' => 'nullable|string|max:5000', 'file' => 'required|file|mimes:zip|max:512000']);
        $path = $request->file('file')->storeAs('releases', 'ai-code-studio-'.$d['version'].'.zip', 'local');
        Release::create(['version' => $d['version'], 'notes' => $d['notes'] ?? null, 'path' => $path, 'size' => $request->file('file')->getSize(), 'sha256' => hash_file('sha256', $request->file('file')->getRealPath())]);
        ActivityLog::record('License', 'Published release v'.$d['version']);

        return back()->with('status', 'Release v'.$d['version'].' published.');
    }

    public function settings(Request $request)
    {
        $d = $request->validate(['license_price_regular' => 'required|numeric|min:0', 'license_price_extended' => 'required|numeric|min:0', 'envato_token' => 'nullable|string|max:255']);
        $values = ['license_price_regular' => $d['license_price_regular'], 'license_price_extended' => $d['license_price_extended']];
        if (! empty($d['envato_token'])) {
            $values['envato_token'] = encrypt($d['envato_token']);
        }
        Settings::set($values);

        return back()->with('status', 'Storefront settings saved.');
    }
}
