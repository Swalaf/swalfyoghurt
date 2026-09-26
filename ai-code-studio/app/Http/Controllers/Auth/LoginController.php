<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            ActivityLog::record('Security', 'Failed sign-in attempt for '.strtolower($data['email']), 'WARN', $user);

            return back()->withInput($request->only('email', 'remember'))->withErrors(['email' => 'That email and password don’t match an account.']);
        }
        if ($user->isSuspended()) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This account is suspended. Contact support to restore access.']);
        }

        if ($user->hasTwoFactor()) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            return redirect()->route('two-factor');
        }

        return self::complete($request, $user, $request->boolean('remember'));
    }

    public static function complete(Request $request, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_active_at' => now()])->saveQuietly();
        ActivityLog::record('Login', ($user->is_admin ? 'Admin' : 'User').' signed in', 'INFO', $user);

        return redirect()->intended($user->is_admin && $user->projects()->doesntExist() ? route('admin.overview') : route('studio.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
