<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Deployment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Publishes a snapshot of the project's files. Published apps are served
 * (sandboxed) from /p/{subdomain} or {subdomain}.{publish_domain}.
 */
class DeployProject implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Deployment $deployment) {}

    public function handle(): void
    {
        $d = $this->deployment;
        $p = $d->project;
        $G = '#58C98A';
        try {
            $d->update(['stage' => 'build', 'progress' => 10]);
            $d->appendLog('Collecting files from '.$p->branch);
            $files = $p->files()->get()->mapWithKeys(fn ($f) => [$f->path => $f->content])->all();
            $d->appendLog('✓ Build complete  ('.count($files).' files)', $G);

            $d->update(['stage' => 'test', 'progress' => 35]);
            if (! isset($files['index.html'])) {
                throw new \RuntimeException('index.html is missing — nothing to serve');
            }
            $d->appendLog('✓ Checks passed: entry point index.html found', $G);

            $d->update(['stage' => 'package', 'progress' => 60]);
            $size = array_sum(array_map('strlen', $files));
            $prefix = 'deployments/'.$d->id;
            foreach ($files as $path => $content) {
                if (! Deployment::disk()->put($prefix.'/'.$path, $content)) {
                    throw new \RuntimeException('Could not write '.$path.' to storage');
                }
            }
            $d->update(['storage_path' => $prefix]);
            $d->appendLog('✓ Packaged snapshot · '.number_format($size / 1024, 1).' KB', $G);

            $d->update(['stage' => 'deploy', 'progress' => 85]);
            $p->deployments()->where('id', '!=', $d->id)->where('status', 'live')->update(['status' => 'superseded']);
            $d->appendLog('Switching traffic to '.$d->commit, '#A99BFF');

            $d->update(['stage' => 'done', 'status' => 'live', 'progress' => 100, 'finished_at' => now()]);
            $d->appendLog('✓ Live at '.self::url($d->subdomain), $G);
            $p->agentTasks()->where('agent', 'Deploy')->where('status', 'waiting')->update(['status' => 'done', 'progress' => 100, 'task' => 'Published']);
            ActivityLog::record('Deploy', $p->slug.' deployed ('.$d->commit.')', 'INFO', $p->user);
        } catch (Throwable $e) {
            $d->appendLog('✕ '.$e->getMessage(), '#E5695E');
            $d->update(['status' => 'failed', 'finished_at' => now()]);
            ActivityLog::record('Deploy', $p->slug.' deploy failed: '.$e->getMessage(), 'ERROR', $p->user);
        }
    }

    public static function url(string $subdomain): string
    {
        $domain = config('studio.publish_domain');

        return $domain ? 'https://'.$subdomain.'.'.$domain : url('/p/'.$subdomain);
    }
}
