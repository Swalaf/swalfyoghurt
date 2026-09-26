<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function store(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = User::find($request->session()->get('login.id'));
        if (! $user) {
            return redirect()->route('login');
        }
        if (! Totp::verify((string) $user->two_factor_secret, $request->input('code'))) {
            ActivityLog::record('Security', 'Wrong two-step code', 'WARN', $user);

            return back()->withErrors(['code' => 'That code didn’t work. Codes change every 30 seconds — try the newest one.']);
        }
        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        return LoginController::complete($request, $user, $remember);
    }
}
