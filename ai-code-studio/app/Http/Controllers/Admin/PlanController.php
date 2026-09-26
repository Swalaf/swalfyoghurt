<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends AdminController
{
    public const GATEWAYS = [
        'stripe' => ['S', 'Stripe', 'Cards, Apple Pay, Google Pay · 40+ countries', 'https://dashboard.stripe.com/apikeys'],
        'paystack' => ['P', 'Paystack', 'Cards, bank transfer, USSD · Africa', 'https://dashboard.paystack.com/#/settings/developers'],
        'paypal' => ['P', 'PayPal', 'PayPal balance and cards', 'https://developer.paypal.com/dashboard/applications'],
    ];

    public function index(Request $request)
    {
        return view('admin.plans', [
            'plans' => Plan::withCount('users')->orderBy('sort')->get(),
            'gateways' => Settings::get('gateways', []),
            'editing' => $request->query('edit') ? Plan::find($request->query('edit')) : null,
        ]);
    }

    protected function validated(Request $request): array
    {
        $d = $request->validate([
            'name' => 'required|string|max:60',
            'price' => 'nullable|numeric|min:0|max:100000',
            'credits' => 'nullable|integer|min:0',
            'max_projects' => 'nullable|integer|min:1',
            'seats' => 'nullable|integer|min:1',
            'support' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|in:live,hidden',
        ]);

        $d += ['price' => null, 'credits' => null, 'max_projects' => null, 'seats' => null, 'support' => null, 'description' => null, 'status' => null];
        $support = $d['support'] ?: 'Email';

        return [
            'name' => $d['name'],
            'price_cents' => $d['price'] === null ? null : (int) round($d['price'] * 100),
            'credits' => $d['credits'],
            'max_projects' => $d['max_projects'],
            'seats' => $d['seats'],
            'support' => $support,
            'description' => $d['description'],
            'status' => $d['status'] ?? 'live',
            'features' => array_values(array_filter([
                $d['max_projects'] ? $d['max_projects'].' project'.($d['max_projects'] > 1 ? 's' : '') : 'Unlimited projects',
                $d['credits'] !== null ? number_format($d['credits']).' AI credits / month' : 'Custom credits',
                $d['seats'] && $d['seats'] > 1 ? $d['seats'].' team seats' : null,
                $support.' support',
            ])),
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $plan = Plan::create($data + ['slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)), 'sort' => Plan::max('sort') + 1]);
        ActivityLog::record('Billing', 'Created plan '.$plan->name);

        return redirect()->route('admin.plans')->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request));
        ActivityLog::record('Billing', 'Updated plan '.$plan->name);

        return redirect()->route('admin.plans')->with('status', 'Plan saved.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->users()->exists()) {
            return back()->withErrors(['plan' => 'Move this plan’s customers to another plan first.']);
        }
        $plan->delete();

        return redirect()->route('admin.plans')->with('status', 'Plan deleted.');
    }

    public function gateway(Request $request, string $gateway)
    {
        $data = $request->validate(['public' => 'nullable|string|max:255', 'secret' => 'nullable|string|max:255', 'disconnect' => 'nullable|boolean']);
        $all = Settings::get('gateways', []);
        if ($request->boolean('disconnect')) {
            unset($all[$gateway]);
        } else {
            $all[$gateway] = ['public' => $data['public'] ?? '', 'secret' => encrypt($data['secret'] ?? ''), 'connected_at' => now()->toIso8601String()];
        }
        Settings::set('gateways', $all);
        ActivityLog::record('Billing', ($request->boolean('disconnect') ? 'Disconnected ' : 'Connected ').self::GATEWAYS[$gateway][1]);

        return back()->with('status', self::GATEWAYS[$gateway][1].($request->boolean('disconnect') ? ' disconnected.' : ' keys saved.'));
    }
}
