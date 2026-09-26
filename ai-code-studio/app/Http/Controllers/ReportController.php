<?php

namespace App\Http\Controllers;

use App\Models\AbuseReport;
use App\Models\ActivityLog;
use App\Models\Deployment;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function create(string $subdomain)
    {
        abort_unless(Deployment::where('subdomain', $subdomain)->exists(), 404);

        return view('report', compact('subdomain'));
    }

    public function store(Request $request, string $subdomain)
    {
        $deployment = Deployment::where('subdomain', $subdomain)->latest('id')->firstOrFail();
        if ($request->filled('website')) { // honeypot
            return back()->with('reported', true);
        }
        $data = $request->validate([
            'reason' => 'required|in:'.implode(',', array_keys(AbuseReport::REASONS)),
            'details' => 'nullable|string|max:2000',
            'email' => 'nullable|email|max:255',
        ]);
        AbuseReport::create([
            'deployment_id' => $deployment->id, 'subdomain' => $subdomain, 'reason' => $data['reason'],
            'details' => $data['details'] ?? null, 'reporter_email' => $data['email'] ?? null, 'ip' => $request->ip(),
        ]);
        ActivityLog::record('Abuse', 'Report on '.$subdomain.': '.AbuseReport::REASONS[$data['reason']], 'WARN', $deployment->project?->user);
        if ($to = Settings::get('support_email')) {
            rescue(fn () => Mail::raw("New abuse report ({$data['reason']}) for ".url('/p/'.$subdomain)."\n\n".($data['details'] ?? '')."\n\nReview: ".route('admin.reports'), fn ($m) => $m->to($to)->subject('Abuse report: '.$subdomain)), report: false);
        }

        return back()->with('reported', true);
    }
}
