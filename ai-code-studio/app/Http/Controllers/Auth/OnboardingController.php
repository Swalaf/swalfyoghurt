<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public const LEVELS = [
        ['I don’t code', 'Describe ideas in plain language. AI handles the code.'],
        ['A little', 'Mostly AI, with a peek at the code now and then.'],
        ['I’m a developer', 'Full IDE, terminal and Git front and centre.'],
    ];

    public function show(Request $request)
    {
        return view('auth.onboarding', ['level' => $request->user()->coding_level ?? 1]);
    }

    public function store(Request $request)
    {
        $level = (int) $request->validate(['level' => 'required|integer|between:0,2'])['level'];
        $request->user()->forceFill(['coding_level' => $level, 'experience' => $level === 2 ? 'developer' : 'simple'])->save();

        if ($idea = $request->session()->pull('pending_idea')) {
            return redirect()->route('studio.new', ['idea' => $idea]);
        }

        return redirect()->route('studio.dashboard');
    }
}
