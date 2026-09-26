<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingsController extends AdminController
{
    /** section => [description, action label, rows[type, key, label, help]] */
    public const SECTIONS = [
        'General' => ['Basic information about your platform.', 'Reset to defaults', [
            ['text', 'company_name', 'Company name', 'Shown in emails and the footer.'],
            ['text', 'support_email', 'Support email', 'Where customers can reach you.'],
            ['text', 'default_language', 'Default language', 'For new users.'],
            ['select', 'landing_headline', 'Landing page headline', 'The big line on your home page.'],
            ['toggle', 'show_pricing', 'Show pricing on the home page', 'Hide it if you sell by quote.'],
        ]],
        'Sign-up & login' => ['Control who can join and how they sign in.', 'Preview login page', [
            ['toggle', 'allow_signups', 'Allow new sign-ups', 'Turn off to make the platform invite-only.'],
            ['toggle', 'require_verification', 'Require email verification', 'Stops fake accounts. Needs email sending set up.'],
            ['toggle', 'require_2fa', 'Encourage two-step verification', 'Shows a reminder to users who haven’t turned it on.'],
        ]],
        'Email' => ['Needed to send sign-up confirmations and password resets.', 'Send test email', [
            ['text', 'mail_host', 'Mail server (SMTP host)', 'Your email provider gives you this, e.g. smtp.mailgun.org'],
            ['text', 'mail_port', 'Port', 'Usually 587 (TLS) or 465 (SSL).'],
            ['text', 'mail_username', 'Username', 'Usually your email address.'],
            ['password', 'mail_password', 'Password', 'Stored encrypted.'],
            ['text', 'mail_from', 'Send emails from', 'The address customers see.'],
        ]],
        'Storage' => ['Where project files and uploads are saved.', 'Test connection', [
            ['text', 'storage_driver', 'Storage type', 'Local disk is fine to start. Switch to S3 as you grow.'],
            ['text', 'max_upload_mb', 'Max upload size (MB)', 'Per file.'],
        ]],
        'Security' => ['Protect your platform and your users.', 'Run security scan', [
            ['toggle', 'admin_2fa', 'Require 2-step for admins', 'Strongly recommended. Admins without it are reminded on sign-in.'],
            ['text', 'session_timeout', 'Session timeout', 'Sign users out after inactivity.'],
        ]],
        'Maintenance' => ['Temporarily close the platform while you make changes.', 'Preview maintenance page', [
            ['toggle', 'maintenance', 'Maintenance mode', 'Visitors see a friendly “back soon” page. Admins can still sign in.'],
            ['text', 'maintenance_message', 'Message', 'Shown on the maintenance page.'],
        ]],
    ];

    public function index(Request $request, ?string $section = null)
    {
        $section = array_key_exists((string) $section, self::SECTIONS) ? $section : 'Sign-up & login';

        return view('admin.settings', ['sections' => self::SECTIONS, 'section' => $section, 'settings' => Settings::all()]);
    }

    public function save(Request $request, string $section)
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);
        $values = [];
        foreach (self::SECTIONS[$section][2] as [$type, $key]) {
            if ($type === 'toggle') {
                $values[$key] = $request->boolean($key);
            } elseif ($type === 'password') {
                if ($request->filled($key)) {
                    $values[$key] = encrypt($request->input($key));
                }
            } else {
                $values[$key] = mb_substr(trim((string) $request->input($key)), 0, 255);
            }
        }
        if ($section === 'General' && ($values['support_email'] ?? '') && ! filter_var($values['support_email'], FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['support_email' => 'Enter a valid email address.']);
        }
        Settings::set($values);
        ActivityLog::record('Settings', 'Updated '.$section.' settings');

        return back()->with('status', $section.' settings saved.');
    }

    public function action(Request $request, string $section)
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);

        switch ($section) {
            case 'General':
                Settings::set(collect(self::SECTIONS['General'][2])->mapWithKeys(fn ($r) => [$r[1] => Settings::DEFAULTS[$r[1]] ?? ''])->all());

                return back()->with('status', 'General settings reset to defaults.');
            case 'Sign-up & login':
                return redirect()->route('login');
            case 'Maintenance':
                return response()->view('errors.503', ['message' => Settings::get('maintenance_message'), 'preview' => true]);
            case 'Email':
                try {
                    Mail::raw('This is a test email from '.Settings::brand().'. Email sending works!', fn ($m) => $m->to($request->user()->email)->subject('Test email from '.Settings::brand()));

                    return back()->with('status', 'Test email sent to '.$request->user()->email.(config('mail.default') === 'log' ? ' (mail driver is “log”: check storage/logs)' : '').'.');
                } catch (Throwable $e) {
                    ActivityLog::record('Email', 'Could not send email: '.$e->getMessage(), 'ERROR');

                    return back()->with('error', 'Could not send: '.$e->getMessage());
                }
            case 'Storage':
                try {
                    Storage::put('.write-test', 'ok');
                    $ok = Storage::get('.write-test') === 'ok';
                    Storage::delete('.write-test');

                    return back()->with($ok ? 'status' : 'error', $ok ? 'Storage works: wrote and read a test file.' : 'Storage read-back failed.');
                } catch (Throwable $e) {
                    return back()->with('error', 'Storage error: '.$e->getMessage());
                }
            case 'Security':
                $issues = [];
                config('app.debug') && $issues[] = 'Debug mode is on (set APP_DEBUG=false).';
                ! str_starts_with((string) config('app.url'), 'https://') && $issues[] = 'The site URL isn’t https.';
                ($n = User::where('is_admin', true)->whereNull('two_factor_confirmed_at')->count()) && $issues[] = "$n admin(s) without two-step verification.";
                is_writable(base_path('.env')) && app()->isProduction() && $issues[] = '.env is writable by the web server — make it read-only after setup.';

                return back()->with($issues ? 'error' : 'status', $issues ? 'Security scan: '.implode(' ', $issues) : 'Security scan passed — no issues found.');
        }

        return back();
    }

    public function branding()
    {
        return view('admin.branding', ['settings' => Settings::all()]);
    }

    public function saveBranding(Request $request)
    {
        $data = $request->validate([
            'brand_name' => 'required|string|max:60',
            'brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_domain' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:png,svg,jpg,jpeg,webp|max:1024',
            'favicon' => 'nullable|image|mimes:png,ico,svg|max:256',
        ]);
        foreach (['logo', 'favicon'] as $f) {
            if ($request->hasFile($f)) {
                $name = $f.'-'.time().'.'.$request->file($f)->extension();
                $request->file($f)->move(public_path('uploads'), $name);
                $data["brand_$f"] = '/uploads/'.$name;
            }
            unset($data[$f]);
        }
        Settings::set(array_filter($data, fn ($v) => $v !== null));
        ActivityLog::record('Settings', 'Branding updated');

        return back()->with('status', 'Branding saved & published.');
    }
}
