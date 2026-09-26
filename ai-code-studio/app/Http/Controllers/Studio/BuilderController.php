<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\PublishedAppController;
use App\Models\ChangeSet;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Services\Studio\ProjectAgent;
use Illuminate\Http\Request;

class BuilderController extends StudioController
{
    public function show(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);

        return view('studio.builder', ['project' => $project, 'state' => $this->payload($project, 'builder')]);
    }

    public function state(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);

        return response()->json($this->payload($project->fresh(), $request->query('channel', 'builder')));
    }

    public function message(Request $request, Project $project, ProjectAgent $agent)
    {
        $this->authorizeProject($request, $project);
        $data = $request->validate([
            'text' => 'required|string|max:4000',
            'channel' => 'nullable|in:builder,workspace',
            'mode' => 'nullable|in:Ask,Edit,Agent,Debug,Explain,Refactor,Generate,Review',
        ]);
        @set_time_limit(180);
        $agent->respond($project, $request->user(), $data['text'], $data['channel'] ?? 'builder', $data['mode'] ?? 'Agent');

        return response()->json($this->payload($project->fresh(), $data['channel'] ?? 'builder'));
    }

    public function approve(Request $request, Project $project, ChangeSet $changeSet, ProjectAgent $agent)
    {
        $this->authorizeProject($request, $project);
        abort_unless($changeSet->project_id === $project->id && $changeSet->status === 'pending', 404);
        $agent->apply($changeSet);
        $project->messages()->create(['role' => 'assistant', 'channel' => $request->input('channel', 'builder'), 'content' => '✓ Applied: '.$changeSet->summary]);

        return $this->respond($request, $project);
    }

    public function reject(Request $request, Project $project, ChangeSet $changeSet)
    {
        $this->authorizeProject($request, $project);
        abort_unless($changeSet->project_id === $project->id && $changeSet->status === 'pending', 404);
        $changeSet->update(['status' => 'rejected']);
        $project->messages()->create(['role' => 'assistant', 'channel' => $request->input('channel', 'builder'), 'content' => 'Okay — I discarded those changes. Nothing in your app changed.']);

        return $this->respond($request, $project);
    }

    /** Revert the most recently applied change set. */
    public function undo(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $last = $project->changeSets()->where('status', 'applied')->with('files')->first();
        if ($last) {
            foreach ($last->files as $f) {
                $f->previous === null
                    ? $project->files()->where('path', $f->path)->delete()
                    : $project->files()->updateOrCreate(['path' => $f->path], ['content' => $f->previous]);
            }
            $last->update(['status' => 'reverted']);
            $project->messages()->create(['role' => 'assistant', 'channel' => $request->input('channel', 'builder'), 'content' => '↶ Undid: '.$last->summary]);
        }

        return $this->respond($request, $project);
    }

    /** Public (token-protected) preview of the working copy, served sandboxed. */
    public function preview(string $slug, string $token, ?string $path = null)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        abort_unless(hash_equals($project->previewToken(), $token), 404);
        $files = $project->files()->pluck('content', 'path')->all();
        if (! $files) {
            return response(view('studio.partials.preview-empty', ['project' => $project]))->header('Content-Security-Policy', 'sandbox');
        }

        return PublishedAppController::serve($files, $path, $project->previewUrl().'/', view('studio.partials.preview-bridge')->render());
    }

    protected function respond(Request $request, Project $project)
    {
        return $request->expectsJson()
            ? response()->json($this->payload($project->fresh(), $request->input('channel', 'builder')))
            : back();
    }

    public function payload(Project $project, string $channel): array
    {
        $run = (int) $project->agentTasks()->max('run');
        $tasks = $run ? $project->agentTasks()->where('run', $run)->orderBy('id')->get() : collect();
        $pending = $project->pendingChangeSet()?->load('files');
        $messages = $project->messages()->where('channel', $channel)->get()->map(fn (ProjectMessage $m) => [
            'id' => $m->id, 'role' => $m->role, 'text' => $m->content, 'credits' => $m->credits, 'change_set_id' => $m->change_set_id,
        ]);

        return [
            'status' => $project->status,
            'progress' => $project->progress,
            'updated' => $project->updated_at?->timestamp,
            'files' => $project->files()->count(),
            'tasks' => $tasks->map(fn ($t) => ['agent' => $t->agent, 'task' => $t->task, 'status' => $t->status, 'progress' => $t->progress, 'model' => $t->model])->values(),
            'messages' => $messages->values(),
            'pending' => $pending ? [
                'id' => $pending->id,
                'summary' => $pending->summary,
                'additions' => $pending->files->sum('additions'),
                'deletions' => $pending->files->sum('deletions'),
                'files' => $pending->files->map(fn ($f) => ['path' => $f->path, 'action' => $f->action, 'add' => $f->additions, 'del' => $f->deletions])->values(),
                'approve' => route('studio.projects.changes.approve', [$project, $pending]),
                'reject' => route('studio.projects.changes.reject', [$project, $pending]),
            ] : null,
            'canUndo' => $project->changeSets()->where('status', 'applied')->exists(),
            'user_credits' => auth()->user()?->credits,
        ];
    }
}
