<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Services\Studio\ProjectAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Multi-agent build: Architect → Developer → QA → Security, then the Deploy
 * agent waits for the user to publish.
 */
class BuildProject implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public const AGENTS = [
        'Architect' => 'Spec & module boundaries',
        'Developer' => 'Write the application code',
        'QA' => 'Check every file is valid',
        'Security' => 'Scan for secrets & unsafe code',
        'Deploy' => 'Publish when you’re ready',
    ];

    public function __construct(public Project $project, public User $user, public int $run) {}

    public static function start(Project $project, User $user): int
    {
        $run = (int) $project->agentTasks()->max('run') + 1;
        foreach (self::AGENTS as $agent => $task) {
            $project->agentTasks()->create(['run' => $run, 'agent' => $agent, 'task' => $task, 'status' => 'queued']);
        }
        $project->update(['status' => 'building', 'progress' => 5]);
        ActivityLog::record('Build', 'Build #'.$run.' started for '.$project->slug, 'INFO', $user);
        dispatch(new self($project, $user, $run));

        return $run;
    }

    public function handle(ProjectAgent $agent): void
    {
        $p = $this->project;
        $task = fn (string $name) => $p->agentTasks()->where('run', $this->run)->where('agent', $name)->firstOrFail();

        try {
            // Architect
            $t = $task('Architect');
            $t->update(['status' => 'working', 'progress' => 30]);
            $t->appendLog('received idea: '.mb_strimwidth((string) $p->idea, 0, 60, '…'));
            if (empty($p->spec)) {
                $plan = $agent->generateSpec($p, $this->user);
                $p->update($plan);
            }
            $t->appendLog('spec ready · '.count($p->spec['features'] ?? []).' features, '.count($p->spec['pages'] ?? []).' pages');
            $t->update(['status' => 'done', 'progress' => 100, 'files' => ['docs/spec.json']]);
            $p->files()->updateOrCreate(['path' => 'docs/spec.json'], ['content' => json_encode(['spec' => $p->spec, 'stack' => $p->stack], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            $p->update(['progress' => 25]);

            // Developer
            $t = $task('Developer');
            $t->update(['status' => 'working', 'progress' => 10]);
            $t->appendLog('reading spec & stack');
            $out = $agent->generateInitialFiles($p, $this->user);
            foreach ($out['files'] as $path => $content) {
                $p->files()->updateOrCreate(['path' => $path], ['content' => $content]);
                $t->appendLog('wrote '.$path);
            }
            $t->update(['status' => 'done', 'progress' => 100, 'model' => $out['model'], 'files' => array_keys($out['files'])]);
            $p->update(['progress' => 70]);

            // QA
            $t = $task('QA');
            $t->update(['status' => 'working', 'progress' => 20]);
            $problems = $this->qa($p);
            foreach ($problems as $msg) {
                $t->appendLog('⚠ '.$msg);
            }
            $t->appendLog(count($problems) ? count($problems).' warning(s)' : 'all files look valid');
            $t->update(['status' => 'done', 'progress' => 100]);
            $p->update(['progress' => 85]);

            // Security
            $t = $task('Security');
            $t->update(['status' => 'working', 'progress' => 20]);
            $leaks = $this->secretScan($p);
            foreach ($leaks as $msg) {
                $t->appendLog('✕ '.$msg);
            }
            $t->appendLog($leaks ? count($leaks).' possible secret(s) found' : 'no secrets or unsafe patterns found');
            $t->update(['status' => 'done', 'progress' => 100]);

            $task('Deploy')->update(['status' => 'waiting', 'progress' => 0, 'task' => 'Waiting for you to publish']);
            $p->update(['status' => 'ready', 'progress' => 100]);
            ActivityLog::record('Build', 'Build #'.$this->run.' finished for '.$p->slug, 'INFO', $this->user);
        } catch (Throwable $e) {
            $this->failed($e);
        }
    }

    public function failed(Throwable $e): void
    {
        $this->project->agentTasks()->where('run', $this->run)->whereIn('status', ['working', 'queued'])->update(['status' => 'failed']);
        $this->project->update(['status' => 'failed']);
        ActivityLog::record('Build', $this->project->slug.' build failed: '.$e->getMessage(), 'ERROR', $this->user);
    }

    /** @return array<int, string> */
    protected function qa(Project $p): array
    {
        $issues = [];
        foreach ($p->files()->get() as $f) {
            $ext = $f->extension();
            if ($ext === 'json' && json_decode($f->content) === null && trim($f->content) !== 'null') {
                $issues[] = $f->path.': invalid JSON';
            }
            if (in_array($ext, ['html', 'htm'], true) && ! preg_match('/<title>/i', $f->content)) {
                $issues[] = $f->path.': missing <title>';
            }
        }
        if (! $p->files()->where('path', 'index.html')->exists()) {
            $issues[] = 'index.html is missing — the preview has nothing to show';
        }

        return $issues;
    }

    /** @return array<int, string> */
    protected function secretScan(Project $p): array
    {
        $patterns = ['/sk-[A-Za-z0-9_-]{20,}/' => 'API key', '/AKIA[0-9A-Z]{16}/' => 'AWS key', '/-----BEGIN (RSA |EC )?PRIVATE KEY-----/' => 'private key', '/\beval\s*\(/' => 'eval() call'];
        $found = [];
        foreach ($p->files()->get() as $f) {
            foreach ($patterns as $re => $label) {
                if (preg_match($re, $f->content)) {
                    $found[] = $label.' in '.$f->path;
                }
            }
        }

        return $found;
    }
}
