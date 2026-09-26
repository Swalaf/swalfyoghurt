<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\Plan;
use App\Models\User;
use App\Support\Settings;

abstract class AdminController extends Controller
{
    /** Setup checklist shown on the overview and as the nav badge. */
    public static function checklist(): array
    {
        $s = Settings::all();
        $gateways = collect($s['gateways'] ?? [])->filter(fn ($g) => ! empty($g['secret']));

        return [
            ['Install the platform', 'Done during setup.', true, 'admin.overview'],
            ['Connect an AI provider', 'Gives your platform its AI brain.', AiProvider::whereIn('status', ['connected', 'slow'])->exists(), 'admin.providers'],
            ['Add your brand', 'Name, logo and colors.', ($s['brand_name'] ?? '') !== 'AI Code Studio' || ($s['brand_color'] ?? '') !== '#7C6CF0', 'admin.branding'],
            ['Create your plans', 'Decide what customers pay.', Plan::whereColumn('updated_at', '>', 'created_at')->exists() || Plan::count() !== 4, 'admin.plans'],
            ['Set up email sending', 'For sign-up confirmations and password resets.', ! empty($s['mail_host']), 'admin.settings'],
            ['Connect a payment gateway', 'So customers can pay you.', $gateways->isNotEmpty(), 'admin.plans'],
        ];
    }

    public static function navBadges(): array
    {
        $done = collect(self::checklist())->where(2, true)->count();

        return [
            'overview' => $done < 6 ? $done.'/6' : '',
            'users' => number_format(User::count()),
            'health' => (string) (SystemController::issueCount() ?: ''),
        ];
    }
}
