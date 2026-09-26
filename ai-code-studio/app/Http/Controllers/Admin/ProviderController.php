<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Services\Ai\AiClient;
use App\Support\Settings;
use Illuminate\Http\Request;

class ProviderController extends AdminController
{
    public const ROUTES = [
        ['Balanced', 'Good quality at a fair price.', 'RECOMMENDED'],
        ['Best quality', 'Always the smartest model. Costs more.', ''],
        ['Cheapest', 'Lowest cost. Fine for simple projects.', ''],
        ['Fastest', 'Quickest responses.', ''],
    ];

    public function index(Request $request)
    {
        return view('admin.providers', [
            'providers' => AiProvider::orderBy('priority')->get(),
            'routing' => Settings::get('routing', 'Balanced'),
            'conn' => $request->query('connect') ? AiProvider::find($request->query('connect')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['driver' => 'required|in:'.implode(',', array_keys(AiProvider::DRIVERS)), 'name' => 'nullable|string|max:60']);
        $p = AiProvider::create(['driver' => $data['driver'], 'name' => $data['name'] ?: AiProvider::DRIVERS[$data['driver']]['name'], 'priority' => AiProvider::max('priority') + 1]);

        return redirect()->route('admin.providers', ['connect' => $p->id]);
    }

    public function update(Request $request, AiProvider $provider, AiClient $ai)
    {
        $data = $request->validate([
            'api_key' => 'nullable|string|max:500',
            'base_url' => 'nullable|url|max:255',
            'default_model' => 'nullable|string|max:120',
            'priority' => 'nullable|integer|min:1|max:99',
            'cost_per_million' => 'nullable|numeric|min:0|max:1000',
            'disconnect' => 'nullable|boolean',
        ]);
        if ($request->boolean('disconnect')) {
            $provider->update(['api_key' => null, 'status' => 'not_configured', 'latency_ms' => null]);
            ActivityLog::record('AI', 'Disconnected '.$provider->name, 'WARN');

            return redirect()->route('admin.providers')->with('status', $provider->name.' disconnected.');
        }
        $provider->fill(array_filter([
            'base_url' => $data['base_url'] ?? null,
            'default_model' => $data['default_model'] ?? null,
            'priority' => $data['priority'] ?? null,
        ], fn ($v) => $v !== null));
        if ($request->has('cost_per_million')) {
            $provider->cost_per_million = $data['cost_per_million'];
        }
        if (! empty($data['api_key'])) {
            $provider->api_key = trim($data['api_key']);
        }
        $provider->save();

        $result = ($provider->api_key || $provider->base_url) ? $ai->test($provider) : ['ok' => false, 'message' => 'Paste an API key first.'];
        ActivityLog::record('AI', ($result['ok'] ? 'Connected ' : 'Could not connect ').$provider->name, $result['ok'] ? 'INFO' : 'WARN');

        return redirect()->route('admin.providers', $result['ok'] ? [] : ['connect' => $provider->id])
            ->with($result['ok'] ? 'status' : 'conn_error', $result['ok'] ? $provider->name.': '.$result['message'] : $result['message']);
    }

    public function test(AiProvider $provider, AiClient $ai)
    {
        $r = $ai->test($provider);

        return back()->with($r['ok'] ? 'status' : 'error', $provider->name.': '.$r['message']);
    }

    public function destroy(AiProvider $provider)
    {
        $provider->delete();

        return redirect()->route('admin.providers')->with('status', 'Provider removed.');
    }

    public function routing(Request $request)
    {
        $route = $request->validate(['routing' => 'required|in:Balanced,Best quality,Cheapest,Fastest,Free-first,Manual,Automatic fallback'])['routing'];
        Settings::set('routing', $route);

        return back();
    }
}
