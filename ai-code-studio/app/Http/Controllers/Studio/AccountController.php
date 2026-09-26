<?php

namespace App\Http\Controllers\Studio;

use App\Models\ActivityLog;
use App\Support\Settings;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AccountController extends StudioController
{
    public function show(Request $request)
    {
        $user = $request->user();
        $pending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;

        return view('studio.account', [
            'user' => $user,
            'pendingSecret' => $pending ? $user->two_factor_secret : null,
            'otpUri' => $pending ? Totp::uri($user->two_factor_secret, $user->email, Settings::brand()) : null,
        ]);
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $request->user()->forceFill(['password' => $data['password']])->save();
        ActivityLog::record('Security', 'Password changed', 'INFO', $request->user());

        return back()->with('status', 'Password updated.');
    }

    public function enableTwoFactor(Request $request)
    {
        $request->user()->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => null])->save();

        return back();
    }

    public function confirmTwoFactor(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => 'required|string']);
        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code didn’t match. Try the newest code from your app.']);
        }
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        ActivityLog::record('Security', 'Two-step verification turned on', 'INFO', $user);

        return back()->with('status', 'Two-step verification is on.');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        ActivityLog::record('Security', 'Two-step verification turned off', 'WARN', $request->user());

        return back()->with('status', 'Two-step verification is off.');
    }
}
