<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

abstract class StudioController extends Controller
{
    protected function simple(Request $request): bool
    {
        return ! $request->user()->isDeveloper();
    }

    /** Owners (and admins, for support) can open a project. */
    protected function authorizeProject(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id || $request->user()->is_admin, 404);
        $request->session()->put('studio.project', $project->slug);
    }
}
