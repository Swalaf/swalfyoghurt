<?php

namespace App\Http\Controllers\Studio;

use App\Models\Project;
use Illuminate\Http\Request;

class AgentsController extends StudioController
{
    public function show(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $run = (int) $project->agentTasks()->max('run');
        $tasks = $project->agentTasks()->where('run', $run)->orderBy('id')->get()->keyBy('agent');
        $selected = $tasks->get($request->query('agent')) ?? $tasks->firstWhere('status', 'working') ?? $tasks->first();

        return view('studio.agents', compact('project', 'run', 'tasks', 'selected'));
    }
}
