<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyEmailController extends Controller
{
    public static function sendCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->forceFill(['email_code' => Hash::make($code), 'email_code_expires_at' => now()->addMinutes(30)])->save();
        rescue(fn () => $user->notify(new EmailVerificationCode($code)));
    }

    public function show(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('onboarding');
        }

        return view('auth.verify');
    }

    public function store(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();
        $code = preg_replace('/\D/', '', $request->input('code'));

        if (! $user->email_code || $user->email_code_expires_at?->isPast() || ! Hash::check($code, $user->email_code)) {
            return back()->withErrors(['code' => 'That code is wrong or has expired. Check your inbox or send a new one.']);
        }
        $user->forceFill(['email_verified_at' => now(), 'email_code' => null, 'email_code_expires_at' => null])->save();

        return redirect()->route('onboarding');
    }

    public function resend(Request $request)
    {
        self::sendCode($request->user());

        return back()->with('status', 'We sent a new code.');
    }
}
