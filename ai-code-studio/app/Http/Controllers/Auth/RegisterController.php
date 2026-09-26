<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show(Request $request)
    {
        if ($idea = trim((string) $request->query('idea'))) {
            $request->session()->put('pending_idea', mb_substr($idea, 0, 1000));
        }

        return view('auth.register', ['closed' => ! Settings::get('allow_signups')]);
    }

    public function store(Request $request)
    {
        abort_unless(Settings::get('allow_signups'), 403, 'Sign-ups are closed.');

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)],
        ], ['email.unique' => 'An account with this email already exists. Try signing in.']);

        $plan = Plan::where('price_cents', 0)->orderBy('sort')->first();
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'plan_id' => $plan?->id,
            'credits' => $plan?->credits ?? 0,
            'status' => 'active',
        ]);
        ActivityLog::record('Account', 'New sign-up'.($plan ? ' ('.$plan->name.')' : ''), 'INFO', $user);

        Auth::login($user);
        $request->session()->regenerate();

        if (Settings::get('require_verification')) {
            VerifyEmailController::sendCode($user);

            return redirect()->route('verification.notice');
        }

        return redirect()->route('onboarding');
    }
}
