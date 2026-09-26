<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class UserController extends AdminController
{
    protected function query(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $filter = $request->query('filter', 'All');

        return User::with('plan')->withCount('projects')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->when($filter === 'Active', fn ($query) => $query->where('status', 'active'))
            ->when($filter === 'Trial', fn ($query) => $query->where('status', 'trial'))
            ->when($filter === 'Suspended', fn ($query) => $query->where('status', 'suspended'))
            ->when($filter === 'Admins', fn ($query) => $query->where('is_admin', true));
    }

    public function index(Request $request)
    {
        return view('admin.users', [
            'users' => $this->query($request)->latest('id')->paginate(25)->withQueryString(),
            'drawer' => $request->query('user') ? User::withCount('projects')->with('plan')->find($request->query('user')) : null,
            'plans' => Plan::orderBy('sort')->get(),
            'filter' => $request->query('filter', 'All'),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Plan', 'Credits', 'Projects', 'Status', 'Admin', 'Joined', 'Last active']);
            foreach ($rows as $u) {
                fputcsv($out, [$u->name, $u->email, $u->plan?->name, $u->credits, $u->projects_count, $u->status, $u->is_admin ? 'yes' : 'no', $u->created_at, $u->last_active_at]);
            }
            fclose($out);
        }, 'users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function credits(Request $request, User $user)
    {
        $amount = (int) $request->validate(['amount' => 'required|integer|between:-1000000,1000000'])['amount'];
        $user->forceFill(['credits' => max(0, $user->credits + $amount)])->save();
        ActivityLog::record('Billing', "Admin gave {$amount} credits to {$user->email}");

        return back()->with('status', "Added {$amount} credits to {$user->name}.");
    }

    public function plan(Request $request, User $user)
    {
        $plan = Plan::findOrFail($request->validate(['plan_id' => 'required|exists:plans,id'])['plan_id']);
        $user->forceFill(['plan_id' => $plan->id, 'credits' => max($user->credits, (int) $plan->credits)])->save();
        ActivityLog::record('Billing', "Changed {$user->email} to {$plan->name}");

        return back()->with('status', "{$user->name} is now on {$plan->name}.");
    }

    public function sendReset(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', $status === Password::RESET_LINK_SENT ? "Reset link sent to {$user->email}." : __($status));
    }

    public function impersonate(Request $request, User $user)
    {
        abort_if($user->is(auth()->user()), 422);
        ActivityLog::record('Security', "Admin logged in as {$user->email}", 'WARN');
        $request->session()->put('impersonator_id', auth()->id());
        Auth::login($user);

        return redirect()->route('studio.dashboard');
    }

    public function stopImpersonating(Request $request)
    {
        $id = $request->session()->pull('impersonator_id');
        abort_unless($id && ($admin = User::find($id))?->is_admin, 403);
        Auth::login($admin);

        return redirect()->route('admin.users');
    }

    public function status(Request $request, User $user)
    {
        $status = $request->validate(['status' => 'required|in:active,trial,suspended'])['status'];
        abort_if($user->is(auth()->user()) && $status === 'suspended', 422, 'You can’t suspend yourself.');
        $user->forceFill(['status' => $status])->save();
        ActivityLog::record('Security', ucfirst($status === 'suspended' ? 'suspended' : 'restored').' '.$user->email, $status === 'suspended' ? 'WARN' : 'INFO');

        return back()->with('status', $user->name.' is now '.$status.'.');
    }

    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'You can’t delete yourself.');
        ActivityLog::record('Account', 'Deleted user '.$user->email.' and '.$user->projects()->count().' projects', 'WARN');
        $user->delete();

        return redirect()->route('admin.users')->with('status', 'User deleted.');
    }
}
