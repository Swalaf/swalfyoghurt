<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Support\Settings;

class LandingController extends Controller
{
    public const HEADLINES = [
        'build' => 'Build anything with AI.',
        'team' => 'Your AI coding team. Your infrastructure. Your brand.',
        'studio' => 'The AI software development studio.',
    ];

    public function __invoke()
    {
        return view('landing', [
            'headline' => __(self::HEADLINES[Settings::get('landing_headline')] ?? self::HEADLINES['build']),
            'showPricing' => (bool) Settings::get('show_pricing', true),
            'plans' => Plan::where('status', 'live')->orderBy('sort')->get(),
            'company' => Settings::get('company_name'),
        ]);
    }
}
