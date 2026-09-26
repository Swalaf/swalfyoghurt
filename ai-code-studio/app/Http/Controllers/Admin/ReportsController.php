<?php

namespace App\Http\Controllers\Admin;

use App\Models\AbuseReport;
use App\Models\ActivityLog;
use App\Models\Deployment;
use Illuminate\Http\Request;

class ReportsController extends AdminController
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        return view('admin.reports', [
            'reports' => AbuseReport::with('deployment.project.user')->where('status', $status)->latest('id')->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => AbuseReport::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function takedown(Request $request, AbuseReport $report)
    {
        $reason = $request->validate(['reason' => 'nullable|string|max:200'])['reason'] ?? AbuseReport::REASONS[$report->reason];
        Deployment::where('subdomain', $report->subdomain)->where('status', 'live')->update(['status' => 'taken_down', 'takedown_reason' => $reason]);
        AbuseReport::where('subdomain', $report->subdomain)->where('status', 'open')->update(['status' => 'actioned']);
        ActivityLog::record('Abuse', 'Took down '.$report->subdomain.': '.$reason, 'WARN');

        if ($request->boolean('suspend') && ($owner = $report->deployment?->project?->user) && ! $owner->is_admin) {
            $owner->forceFill(['status' => 'suspended'])->save();
            ActivityLog::record('Security', 'Suspended '.$owner->email.' after abuse report', 'WARN');
        }

        return back()->with('status', $report->subdomain.' taken down.');
    }

    public function restore(AbuseReport $report)
    {
        Deployment::where('subdomain', $report->subdomain)->where('status', 'taken_down')->latest('id')->first()?->update(['status' => 'live', 'takedown_reason' => null]);
        ActivityLog::record('Abuse', 'Restored '.$report->subdomain);

        return back()->with('status', $report->subdomain.' restored.');
    }

    public function dismiss(AbuseReport $report)
    {
        $report->update(['status' => 'dismissed']);

        return back()->with('status', 'Report dismissed.');
    }
}
