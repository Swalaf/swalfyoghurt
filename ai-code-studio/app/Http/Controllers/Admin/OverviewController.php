<?php

namespace App\Http\Controllers\Admin;

use App\Models\AiProvider;
use App\Models\AiUsage;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class OverviewController extends AdminController
{
    public function index()
    {
        $monthStart = now()->startOfMonth();
        $revenue = Payment::where('created_at', '>=', $monthStart)->sum('amount_cents') / 100;
        $lastRevenue = Payment::whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), $monthStart])->sum('amount_cents') / 100;
        $aiCost = (float) AiUsage::where('created_at', '>=', $monthStart)->sum('cost');
        $users = User::count();
        $activeToday = User::where('last_active_at', '>=', now()->subDay())->count();

        // 14-day revenue vs AI cost, scaled to the tallest bar.
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
        $rev = Payment::where('created_at', '>=', now()->subDays(13)->startOfDay())->get()->groupBy(fn ($p) => $p->created_at->toDateString())->map->sum('amount_cents');
        $cost = AiUsage::where('created_at', '>=', now()->subDays(13)->startOfDay())->get()->groupBy(fn ($u) => $u->created_at->toDateString())->map->sum('cost');
        $max = max(1, $rev->max() / 100, $cost->max() ?? 0);
        $chart = $days->map(fn ($d) => ['r' => round(($rev[$d] ?? 0) / 100 / $max * 100), 'c' => round(($cost[$d] ?? 0) / $max * 100), 'day' => $d]);

        $providers = AiProvider::where('status', '!=', 'not_configured')->get();
        $online = $providers->whereIn('status', ['connected', 'slow'])->count();

        return view('admin.overview', [
            'checklist' => self::checklist(),
            'kpis' => [
                ['Total users', number_format($users), '+'.User::where('created_at', '>=', now()->subWeek())->count().' this week', '#58C98A', 'Everyone with an account.'],
                ['Active today', number_format($activeToday), ($users ? round($activeToday / $users * 100) : 0).'% of users', '#6E6E79', 'Signed in within 24 hours.'],
                ['Revenue this month', '$'.number_format($revenue), $lastRevenue ? (($revenue >= $lastRevenue ? '+' : '').round(($revenue - $lastRevenue) / $lastRevenue * 100).'% vs last month') : 'No payments last month', '#58C98A', 'Money from paid plans.'],
                ['AI cost this month', '$'.number_format($aiCost, 2), AiUsage::where('created_at', '>=', $monthStart)->sum('credits').' credits used', '#6E6E79', 'What you pay AI providers.'],
                ['Projects built', number_format(Project::count()), '+'.Project::where('created_at', '>=', now()->subWeek())->count().' this week', '#58C98A', 'Apps your users created.'],
            ],
            'chart' => $chart,
            'margin' => $revenue > 0 ? max(0, round(($revenue - $aiCost) / $revenue * 100)) : null,
            'statusMini' => [
                ['Website & app', 'Running', '#58C98A'],
                ['AI providers', $providers->isEmpty() ? 'None connected' : "$online of {$providers->count()} online", $providers->isEmpty() ? '#E8B66B' : ($online === $providers->count() ? '#58C98A' : '#E8B66B')],
                ['Background jobs', SystemController::queueStatus()[0], SystemController::queueStatus()[1]],
                ['Scheduled tasks', SystemController::cronStatus()[0], SystemController::cronStatus()[1]],
            ],
            'recentUsers' => User::latest('id')->take(4)->with('plan')->get(),
        ]);
    }

    public function toggleTips(Request $request)
    {
        $request->session()->put('admin_tips', ! $request->session()->get('admin_tips', true));

        return back();
    }
}
