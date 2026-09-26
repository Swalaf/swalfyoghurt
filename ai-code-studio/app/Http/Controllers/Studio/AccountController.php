<?php

namespace App\Http\Controllers\Studio;

use App\Models\ActivityLog;
use App\Models\AiUsage;
use App\Models\Payment;
use App\Models\User;
use App\Support\Settings;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /** GDPR: download everything we hold about the user. */
    public function export(Request $request)
    {
        $user = $request->user()->load('plan');
        $tmp = tempnam(sys_get_temp_dir(), 'exp');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('account.json', json_encode($user->makeHidden(['plan'])->toArray() + ['plan' => $user->plan?->name], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('payments.json', Payment::where('user_id', $user->id)->get()->toJson(JSON_PRETTY_PRINT));
        $zip->addFromString('activity.json', ActivityLog::where('user_id', $user->id)->get(['created_at', 'level', 'category', 'message', 'ip'])->toJson(JSON_PRETTY_PRINT));
        foreach ($user->projects()->with(['files', 'messages', 'deployments'])->get() as $p) {
            $dir = 'projects/'.$p->slug.'/';
            $zip->addFromString($dir.'project.json', json_encode($p->only(['name', 'kind', 'idea', 'spec', 'stack', 'status', 'created_at']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->addFromString($dir.'messages.json', $p->messages->map->only(['role', 'channel', 'content', 'created_at'])->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            foreach ($p->files as $f) {
                $zip->addFromString($dir.'files/'.$f->path, $f->content);
            }
        }
        $zip->close();
        ActivityLog::record('Account', 'Downloaded personal data', 'INFO', $user);

        return response()->download($tmp, 'my-data-'.now()->format('Y-m-d').'.zip')->deleteFileAfterSend();
    }

    /** GDPR: delete the account and everything in it (payment records are kept anonymised). */
    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password'], ['password.current_password' => __('That password is incorrect.')]);
        $user = $request->user();
        if ($user->is_admin && User::where('is_admin', true)->count() === 1) {
            return back()->withErrors(['password' => __('You’re the only admin. Make someone else an admin before deleting your account.')]);
        }
        // Log out first: logout() saves a new remember token, which would re-create a deleted user.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        self::purge($user);

        return redirect('/')->with('status', __('Your account and all its data have been deleted.'));
    }

    public static function purge(User $user): void
    {
        $user->projects()->get()->each->delete(); // removes published files too
        Payment::where('user_id', $user->id)->update(['user_id' => null]);
        ActivityLog::where('user_id', $user->id)->update(['user_id' => null, 'actor' => 'deleted user', 'ip' => null]);
        AiUsage::where('user_id', $user->id)->update(['user_id' => null]);
        $user->delete();
    }

    public function locale(Request $request)
    {
        $locale = $request->validate(['locale' => 'required|in:'.implode(',', array_keys(config('studio.locales')))])['locale'];
        $request->user()->forceFill(['locale' => $locale])->save();
        $request->session()->put('locale', $locale);

        return back();
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
