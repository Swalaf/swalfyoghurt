<?php

namespace App\Http\Controllers\Studio;

use App\Jobs\DeployProject;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeployController extends StudioController
{
    public function show(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $deployments = $project->deployments()->take(20)->get();
        $current = $request->query('d') ? $deployments->firstWhere('id', (int) $request->query('d')) : $deployments->first();

        return view($this->simple($request) ? 'studio.publish-simple' : 'studio.deploy', [
            'project' => $project,
            'deployments' => $deployments,
            'current' => $current,
            'live' => $deployments->firstWhere('status', 'live'),
            'subdomain' => $deployments->first()?->subdomain ?? $project->slug,
            'domain' => config('studio.publish_domain') ?: request()->getHost().'/p',
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $sub = Str::lower($request->validate(['subdomain' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/i'], 'message' => 'nullable|string|max:120'])['subdomain']);
        if (Deployment::where('subdomain', $sub)->where('project_id', '!=', $project->id)->exists()) {
            return back()->withErrors(['subdomain' => 'That address is taken. Try another.'])->withInput();
        }
        if ($project->files()->doesntExist()) {
            return back()->withErrors(['subdomain' => 'Build the app first — there’s nothing to publish yet.']);
        }

        $deployment = $project->deployments()->create([
            'subdomain' => $sub,
            'message' => $request->input('message') ?: ($project->changeSets()->where('status', 'applied')->value('summary') ?? 'Publish '.$project->name),
            'commit' => substr(sha1($project->id.microtime()), 0, 7),
        ]);
        dispatch(new DeployProject($deployment));

        return redirect()->route('studio.projects.deploy', [$project, 'd' => $deployment->id]);
    }

    public function rollback(Request $request, Project $project, Deployment $deployment)
    {
        $this->authorizeProject($request, $project);
        abort_unless($deployment->project_id === $project->id && $deployment->snapshot, 404);
        $project->deployments()->where('status', 'live')->update(['status' => 'superseded']);
        $deployment->update(['status' => 'live']);
        $deployment->appendLog('↶ Rolled back to this version', '#E8B66B');

        return back()->with('status', 'Rolled back to '.$deployment->commit.'.');
    }
}
