<?php

namespace App\Http\Controllers\Studio;

use App\Models\ActivityLog;
use App\Models\AgentTask;
use App\Models\Deployment;
use Illuminate\Http\Request;

class DashboardController extends StudioController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = $user->projects()->with(['deployments' => fn ($q) => $q->where('status', 'live')])->latest('updated_at')->get();
        $ids = $projects->pluck('id');

        if ($this->simple($request)) {
            return view('studio.home-simple', compact('projects'));
        }

        $running = AgentTask::whereIn('project_id', $ids)->whereIn('status', ['working', 'waiting'])->with('project')->latest('updated_at')->take(4)->get();
        $deploys = Deployment::whereIn('project_id', $ids)->where('created_at', '>=', now()->subDays(7))->get();
        $failedBuilds = $projects->where('status', 'failed')->count();
        $failedDeploys = $deploys->where('status', 'failed')->count();

        return view('studio.dashboard', [
            'projects' => $projects,
            'running' => $running,
            'stats' => [
                ['AI credits left', $user->is_admin ? '∞' : number_format($user->credits), $user->is_admin ? 'Admins aren’t charged' : 'of '.number_format($user->creditAllowance()).' / month', '#6E6E79'],
                ['Agents running', (string) $running->where('status', 'working')->count(), $running->where('status', 'working')->pluck('agent')->unique()->take(2)->implode(' · ') ?: 'None right now', '#A99BFF'],
                ['Active builds', (string) $projects->where('status', 'building')->count(), optional($projects->firstWhere('status', 'building'), fn ($p) => $p->slug.' · '.$p->progress.'%') ?? 'Idle', '#5CC8E0'],
                ['Deployments (7d)', (string) $deploys->count(), $deploys->where('status', 'live')->count() + $deploys->where('status', 'superseded')->count().' succeeded', '#58C98A'],
                ['Open errors', (string) ($failedBuilds + $failedDeploys), $failedBuilds ? $failedBuilds.' build failure'.($failedBuilds > 1 ? 's' : '') : 'All clear', '#E5695E'],
            ],
            'activity' => ActivityLog::where('user_id', $user->id)->latest('id')->take(6)->get(),
        ]);
    }

    public function experience(Request $request)
    {
        $mode = $request->validate(['experience' => 'required|in:simple,developer'])['experience'];
        $request->user()->forceFill(['experience' => $mode])->save();

        return back();
    }
}
