<?php

namespace App\Http\Controllers\Studio;

use App\Jobs\BuildProject;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Services\Studio\Blueprints;
use App\Services\Studio\ProjectAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends StudioController
{
    public function create(Request $request)
    {
        return view($this->simple($request) ? 'studio.new-simple' : 'studio.new', [
            'idea' => (string) $request->query('idea', ''),
            'kinds' => Blueprints::KINDS,
            'project' => null,
        ]);
    }

    public function store(Request $request, ProjectAgent $agent)
    {
        $data = $request->validate([
            'kind' => 'required|in:'.collect(Blueprints::KINDS)->pluck(1)->implode(','),
            'idea' => 'required|string|min:5|max:2000',
            'name' => 'nullable|string|max:80',
        ], ['idea.required' => 'Describe what you want to build — a sentence is enough.']);

        $user = $request->user();
        $limit = $user->plan?->max_projects;
        if (! $user->is_admin && $limit !== null && $user->projects()->count() >= $limit) {
            return back()->withInput()->withErrors(['idea' => "Your plan allows {$limit} project".($limit > 1 ? 's' : '').'. Delete one or upgrade to create more.']);
        }

        $name = ($data['name'] ?? null) ?: self::nameFromIdea($data['idea']);
        $project = $user->projects()->create([
            'name' => $name ?: 'New project',
            'slug' => Project::uniqueSlug($name ?: 'project'),
            'kind' => $data['kind'],
            'idea' => $data['idea'],
            'status' => 'planned',
        ]);
        $project->update($agent->generateSpec($project, $user));
        ActivityLog::record('Project', 'Created '.$project->slug, 'INFO', $user);

        return redirect()->route('studio.projects.plan', $project);
    }

    /** "A website for my bakery where people…" → "Website For My Bakery". */
    public static function nameFromIdea(string $idea): string
    {
        $idea = preg_replace('/^(please\s+)?(build|make|create)\s+(me\s+)?/i', '', trim($idea));
        $idea = preg_replace('/^(an?|the)\s+/i', '', $idea);
        $head = preg_split('/\s+(where|that|which|with|so|to|using|including)\s+|[,.;:—–(]|\s-\s/i', $idea)[0];
        $words = array_slice(preg_split('/\s+/', trim($head)), 0, 5);

        return Str::limit(Str::title(implode(' ', $words)), 48, '') ?: 'New project';
    }

    public function plan(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);

        return view($this->simple($request) ? 'studio.new-simple' : 'studio.new', [
            'project' => $project, 'kinds' => Blueprints::KINDS, 'idea' => $project->idea,
        ]);
    }

    public function updatePlan(Request $request, Project $project, ProjectAgent $agent)
    {
        $this->authorizeProject($request, $project);
        $spec = $project->spec ?? [];
        $stack = $project->stack ?? [];

        if ($request->input('action') === 'regenerate') {
            $project->update(['spec' => null]);
            $project->update($agent->generateSpec($project, $request->user()));

            return back()->with('status', 'Plan regenerated.');
        }

        if ($request->has('keep')) { // Simple: unticked features are dropped
            $keep = array_map('intval', (array) $request->input('keep', []));
            $spec['features'] = array_values(array_intersect_key($spec['features'] ?? [], array_flip($keep)));
        }
        if ($extra = trim((string) $request->input('extra'))) {
            $section = in_array($request->input('section'), ['features', 'roles', 'pages', 'database', 'api', 'phases'], true) ? $request->input('section') : 'features';
            $spec[$section] = array_values(array_merge($spec[$section] ?? [], [Str::limit($extra, 140)]));
        }
        foreach (Blueprints::STACK_OPTIONS as $key => $options) {
            if (in_array($request->input("stack.$key"), $options, true)) {
                $stack[$key] = $request->input("stack.$key");
            }
        }
        if ($idea = trim((string) $request->input('idea'))) {
            $project->idea = Str::limit($idea, 2000, '');
        }
        $project->update(['spec' => $spec, 'stack' => $stack]);

        if ($request->input('action') === 'build') {
            return $this->build($request, $project);
        }

        return back();
    }

    public function build(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        if ($project->status !== 'building') {
            BuildProject::start($project, $request->user());
        }

        return redirect()->route($this->simple($request) ? 'studio.projects.builder' : 'studio.projects.agents', $project);
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $project->delete();
        $request->session()->forget('studio.project');
        ActivityLog::record('Project', 'Deleted '.$project->slug, 'WARN', $request->user());

        return redirect()->route('studio.dashboard')->with('status', 'Project deleted.');
    }
}
