<?php

namespace App\Services\Studio;

use App\Models\ActivityLog;
use App\Models\ChangeSet;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiUnavailable;
use App\Support\LineDiff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The "AI development agent": turns plain-language requests into proposed
 * file changes (change sets) the user approves or rejects.
 */
class ProjectAgent
{
    public function __construct(protected AiClient $ai) {}

    public const SYSTEM = <<<'TXT'
You are the AI development agent inside AI Code Studio. You build complete, working software for people who may not code.
The project is previewed in a browser, so ALWAYS keep a runnable static front end at the project root: index.html that loads relative CSS/JS files. Backend source files (Laravel, Node, etc.) may be added alongside when the stack needs them.
Reply with ONE JSON object and nothing else:
{"reply": "<short friendly explanation in plain language>", "summary": "<one-line change summary>", "files": [{"path": "relative/path.ext", "action": "add|modify|delete", "content": "<full file content>"}]}
Always send the FULL content of every file you add or modify. Use "files": [] when no change is needed.
TXT;

    public function generateSpec(Project $project, User $user): array
    {
        $fallback = ['spec' => Blueprints::spec($project->kind, (string) $project->idea), 'stack' => Blueprints::stack($project->kind)];
        if (! $this->ai->hasProvider()) {
            return $fallback;
        }
        $shape = json_encode($fallback, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        try {
            $res = $this->ai->chat([[
                'role' => 'user',
                'content' => "Plan this {$project->kind}: \"{$project->idea}\".\nReturn ONLY JSON with exactly this shape (replace the values, keep 3-8 items per list, stack values must be one of: ".json_encode(Blueprints::STACK_OPTIONS)."):\n".$shape,
            ]], 'You are a senior software architect. Answer with JSON only.', $user, 2000);
            $data = AiClient::extractJson($res->text);
            if (! isset($data['spec']['features'], $data['stack'])) {
                return $fallback;
            }

            return ['spec' => $data['spec'] + ['summary' => $project->idea], 'stack' => $data['stack'] + $fallback['stack']];
        } catch (AiUnavailable) {
            return $fallback;
        }
    }

    /**
     * Initial code generation for a freshly planned project.
     *
     * @return array{files: array<string, string>, model: string}
     */
    public function generateInitialFiles(Project $project, User $user): array
    {
        if ($this->ai->hasProvider()) {
            try {
                $res = $this->ai->chat([[
                    'role' => 'user',
                    'content' => "Build the first working version of this project.\nName: {$project->name}\nKind: {$project->kind}\nIdea: {$project->idea}\nSpec: ".json_encode($project->spec)."\nStack: ".json_encode($project->stack),
                ]], self::SYSTEM, $user, 8000);
                $data = AiClient::extractJson($res->text);
                $files = collect($data['files'] ?? [])
                    ->filter(fn ($f) => isset($f['path'], $f['content']) && ($f['action'] ?? 'add') !== 'delete' && $this->safePath($f['path']))
                    ->mapWithKeys(fn ($f) => [$this->safePath($f['path']) => (string) $f['content']])->all();
                if (isset($files['index.html'])) {
                    return ['files' => $files, 'model' => $res->provider->name.' · '.$res->model];
                }
            } catch (AiUnavailable) {
            }
        }

        return ['files' => Scaffolder::starter($project), 'model' => 'Starter template (no AI provider)'];
    }

    /**
     * Handle a chat message. Returns the assistant message (with a pending change set if code changed).
     */
    public function respond(Project $project, User $user, string $text, string $channel = 'builder', string $mode = 'Agent'): ProjectMessage
    {
        $project->messages()->create(['role' => 'user', 'channel' => $channel, 'content' => $text]);

        $history = $project->messages()->where('channel', $channel)->latest('id')->take(12)->get()->reverse()
            ->map(fn ($m) => ['role' => $m->role === 'assistant' ? 'assistant' : 'user', 'content' => $m->content])->values()->all();
        $readOnly = in_array($mode, ['Ask', 'Explain', 'Review'], true);
        $context = $this->filesContext($project);
        $history[count($history) - 1]['content'] = "Mode: {$mode}".($readOnly ? ' (answer only — return "files": [])' : '')."\nProject: {$project->name} ({$project->kind})\nIdea: {$project->idea}\n\nCurrent files:\n{$context}\n\nRequest: {$text}";

        try {
            $res = $this->ai->chat($history, self::SYSTEM, $user, 8000);
        } catch (AiUnavailable $e) {
            return $project->messages()->create(['role' => 'assistant', 'channel' => $channel, 'content' => $e->getMessage()]);
        }

        $data = AiClient::extractJson($res->text);
        $reply = trim((string) ($data['reply'] ?? '')) ?: (is_null($data) ? trim($res->text) : 'Done.');
        $changeSet = null;
        if (! $readOnly && ! empty($data['files'])) {
            $changeSet = $this->createChangeSet($project, (string) ($data['summary'] ?? Str::limit($text, 120)), $data['files']);
        }

        ActivityLog::record('AI', 'Agent answered on '.$project->slug.' via '.$res->provider->name, 'INFO', $user);

        return $project->messages()->create([
            'role' => 'assistant', 'channel' => $channel, 'content' => $reply,
            'change_set_id' => $changeSet?->id, 'credits' => $res->credits,
        ]);
    }

    public function createChangeSet(Project $project, string $summary, array $files): ?ChangeSet
    {
        $existing = $project->files()->get()->keyBy('path');
        $rows = [];
        foreach ($files as $f) {
            $path = $this->safePath((string) ($f['path'] ?? ''));
            if (! $path) {
                continue;
            }
            $action = in_array($f['action'] ?? '', ['add', 'modify', 'delete'], true) ? $f['action'] : ($existing->has($path) ? 'modify' : 'add');
            $prev = $existing->get($path)?->content;
            if ($action === 'delete' && $prev === null) {
                continue;
            }
            $content = $action === 'delete' ? null : (string) ($f['content'] ?? '');
            if ($action !== 'delete' && $prev === $content) {
                continue;
            }
            $action = $action === 'delete' ? 'delete' : ($prev === null ? 'add' : 'modify');
            [$add, $del] = LineDiff::stats($prev, $content);
            $rows[] = compact('path', 'action', 'content') + ['previous' => $prev, 'additions' => $add, 'deletions' => $del];
        }
        if (! $rows) {
            return null;
        }

        return DB::transaction(function () use ($project, $summary, $rows) {
            // Only one pending change set at a time: newer proposals replace older ones.
            $project->changeSets()->where('status', 'pending')->update(['status' => 'rejected']);
            $set = $project->changeSets()->create(['summary' => Str::limit($summary, 250)]);
            $set->files()->createMany($rows);

            return $set;
        });
    }

    public function apply(ChangeSet $set): void
    {
        DB::transaction(function () use ($set) {
            foreach ($set->files as $f) {
                if ($f->action === 'delete') {
                    $set->project->files()->where('path', $f->path)->delete();
                } else {
                    $set->project->files()->updateOrCreate(['path' => $f->path], ['content' => $f->content]);
                }
            }
            $set->update(['status' => 'applied']);
            $set->project->touch();
        });
    }

    public function safePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path), '/ ');
        if ($path === '' || str_contains($path, '..') || strlen($path) > 200 || ! preg_match('#^[A-Za-z0-9_\-./()\[\]@ ]+$#', $path)) {
            return null;
        }

        return $path;
    }

    protected function filesContext(Project $project, int $budget = 60000): string
    {
        $out = '';
        foreach ($project->files()->get() as $f) {
            $chunk = "--- {$f->path}\n{$f->content}\n";
            if (strlen($out) + strlen($chunk) > $budget) {
                $out .= "--- {$f->path} (omitted, ".strlen($f->content)." bytes)\n";

                continue;
            }
            $out .= $chunk;
        }

        return $out ?: '(no files yet)';
    }
}
