<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        // Same response whether or not the account exists (no account enumeration).
        rescue(fn () => Password::sendResetLink(['email' => strtolower($request->input('email'))]));

        return redirect()->route('password.sent')->with('reset_email', strtolower($request->input('email')));
    }

    public function sent(Request $request)
    {
        return view('auth.sent', ['email' => $request->session()->get('reset_email')]);
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill(['password' => $password])->setRememberToken(null);
            $user->save();
            ActivityLog::record('Security', 'Password reset', 'INFO', $user);
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password updated. Sign in with your new password.')
            : back()->withErrors(['email' => __($status)]);
    }
}
