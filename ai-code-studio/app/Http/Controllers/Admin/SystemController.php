<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\Deployment;
use App\Support\Installation;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use ZipArchive;

class SystemController extends AdminController
{
    public static function cronStatus(): array
    {
        $f = storage_path('app/cron-heartbeat');
        if (! is_file($f)) {
            return ['Not running', '#E8B66B', 'No heartbeat yet — add the cron line from the installer.'];
        }
        $ago = time() - filemtime($f);

        return $ago < 180 ? ['Running', '#58C98A', 'Last ran '.max(1, intdiv($ago, 60)).' min ago.'] : ['Stopped', '#E5695E', 'Last ran '.now()->subSeconds($ago)->diffForHumans().'.'];
    }

    public static function queueStatus(): array
    {
        try {
            $conn = config('queue.default');
            if ($conn === 'sync') {
                return ['Running (inline)', '#58C98A', 'Jobs run immediately inside the request.'];
            }
            $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count() : 0;
            $stale = Schema::hasTable('jobs') ? DB::table('jobs')->where('created_at', '<', now()->subMinutes(5)->timestamp)->count() : 0;
            if ($failed || $stale) {
                return ['Needs attention', '#E8B66B', ($stale ? "$stale job(s) waiting over 5 min. " : '').($failed ? "$failed failed today." : '')];
            }

            return ['Running', '#58C98A', $pending ? "$pending job(s) in the queue." : 'Queue is empty.'];
        } catch (Throwable) {
            return ['Unknown', '#6E6E79', 'Could not read the queue.'];
        }
    }

    public static function disk(): array
    {
        $total = @disk_total_space(base_path()) ?: 0;
        $free = @disk_free_space(base_path()) ?: 0;

        return [$total ? (int) round(($total - $free) / $total * 100) : 0, $free, $total];
    }

    public static function issueCount(): int
    {
        $n = 0;
        $n += self::cronStatus()[1] !== '#58C98A' ? 1 : 0;
        $n += self::queueStatus()[1] === '#E8B66B' ? 1 : 0;
        $n += self::disk()[0] >= 80 ? 1 : 0;
        $n += AiProvider::whereIn('status', ['slow', 'error'])->count();

        return $n;
    }

    public function health()
    {
        $load = function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? 0) : 0;
        $cores = (int) (@shell_exec('nproc 2>/dev/null') ?: 1);
        $cpu = min(100, (int) round($load / max(1, $cores) * 100));
        $mem = null;
        if (is_readable('/proc/meminfo')) {
            preg_match('/MemTotal:\s+(\d+)/', $info = file_get_contents('/proc/meminfo'), $t);
            preg_match('/MemAvailable:\s+(\d+)/', $info, $a);
            if ($t && $a) {
                $mem = [(int) round(($t[1] - $a[1]) / $t[1] * 100), round(($t[1] - $a[1]) / 1048576, 1), round($t[1] / 1048576, 1)];
            }
        }
        [$diskPct, $free] = self::disk();
        $total24 = ActivityLog::where('created_at', '>=', now()->subDay())->count();
        $errors24 = ActivityLog::where('created_at', '>=', now()->subDay())->where('level', 'ERROR')->count();
        $errRate = $total24 ? round($errors24 / $total24 * 100, 1) : 0;
        $col = fn ($v, $warn = 75, $bad = 90) => $v >= $bad ? '#E5695E' : ($v >= $warn ? '#E8B66B' : '#58C98A');

        try {
            $pdo = DB::connection()->getPdo();
            $db = ['Running', '#58C98A', 'Stores users, projects and settings. '.DB::connection()->getDriverName().' '.$pdo->getAttribute(\PDO::ATTR_SERVER_VERSION).'.'];
        } catch (Throwable $e) {
            $db = ['Down', '#E5695E', $e->getMessage()];
        }
        $queue = self::queueStatus();
        $cron = self::cronStatus();

        $services = [
            ['Web server', 'Serves the website and app to your users. PHP '.PHP_VERSION.'.', 'Running', '#58C98A', null],
            ['Database', $db[2], $db[0], $db[1], null],
            ['Background workers', 'Run AI jobs and builds behind the scenes. '.$queue[2], $queue[0], $queue[1], null],
            ['Scheduled tasks (cron)', 'Handles the queue and housekeeping. '.$cron[2], $cron[0], $cron[1], null],
        ];
        foreach (AiProvider::whereIn('status', ['slow', 'error'])->get() as $p) {
            $services[] = [$p->name.' (AI provider)', $p->status === 'slow' ? 'Responding slowly. Requests are automatically sent to other providers.' : 'Key rejected or unreachable. Requests go to other providers.', ucfirst($p->status), $p->status === 'slow' ? '#E8B66B' : '#E5695E', ['View provider', route('admin.providers', ['connect' => $p->id])]];
        }
        if ($diskPct >= 80) {
            $services[] = ['Disk space', $diskPct.'% full. Old published versions can be removed safely.', 'Warning', '#E8B66B', ['Free up space', route('admin.health.clear')]];
        }

        return view('admin.health', [
            'gauges' => [
                ['Processor (CPU)', $cpu, $col($cpu), $cpu < 75 ? 'Plenty of headroom.' : 'Busy — builds may be slower.'],
                ['Memory (RAM)', $mem[0] ?? 0, $col($mem[0] ?? 0), $mem ? "{$mem[1]} of {$mem[2]} GB used." : 'Not available on this host.'],
                ['Disk space', $diskPct, $col($diskPct, 80, 92), round($free / 1073741824, 1).' GB free.'],
                ['Error rate', $errRate, $col($errRate, 2, 5), $errors24.' errors in the last 24 hours.'],
            ],
            'services' => $services,
        ]);
    }

    public function clearCache()
    {
        Artisan::call('optimize:clear');
        Settings::flush();
        // Keep the last 3 superseded versions per project for rollback; drop older snapshots.
        $freed = 0;
        Deployment::where('status', 'superseded')->whereNotNull('snapshot')->orderByDesc('id')->get()->groupBy('project_id')
            ->each(function ($list) use (&$freed) {
                $list->slice(3)->each(function ($d) use (&$freed) {
                    $d->update(['snapshot' => null]);
                    $freed++;
                });
            });

        return back()->with('status', "Caches cleared and $freed old published version(s) removed.");
    }

    public function logs(Request $request)
    {
        $f = $request->query('level', 'All');
        $logs = ActivityLog::query()
            ->when($f === 'Errors', fn ($q) => $q->where('level', 'ERROR'))
            ->when($f === 'Warnings', fn ($q) => $q->where('level', 'WARN'))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('message', 'like', "%$s%")->orWhere('actor', 'like', "%$s%")->orWhere('category', 'like', "%$s%")))
            ->when($request->query('range', '24h') !== 'all', fn ($q) => $q->where('created_at', '>=', now()->sub(['24h' => '1 day', '7d' => '7 days', '30d' => '30 days'][$request->query('range', '24h')] ?? '1 day')))
            ->latest('id')->paginate(50)->withQueryString();

        return view('admin.logs', ['logs' => $logs, 'filter' => $f]);
    }

    public function downloadLogs()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Time', 'Level', 'Category', 'Message', 'User', 'IP']);
            ActivityLog::latest('id')->limit(10000)->each(fn ($l) => fputcsv($out, [$l->created_at, $l->level, $l->category, $l->message, $l->actor, $l->ip]));
            fclose($out);
        }, 'activity-logs-'.now()->format('Y-m-d').'.csv');
    }

    public function license()
    {
        $meta = is_file(Installation::lockFile()) ? json_decode(file_get_contents(Installation::lockFile()), true) : [];
        $pending = [];
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));
        } catch (Throwable) {
        }

        return view('admin.license', ['settings' => Settings::all(), 'meta' => $meta, 'pending' => $pending]);
    }

    public function saveLicense(Request $request)
    {
        if ($request->input('action') === 'update') {
            $backup = null;
            if ($request->boolean('backup')) {
                $backup = $this->backup();
            }
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            ActivityLog::record('System', 'Applied update to v'.config('studio.version').($backup ? ' (backup: '.basename($backup).')' : ''));

            return back()->with('status', 'Up to date — database migrations applied'.($backup ? ' after backing up to storage/app/backups/'.basename($backup) : '').'.');
        }

        $data = $request->validate(['license_code' => 'nullable|string|max:120', 'license_domain' => 'nullable|string|max:255']);
        Settings::set($data + ['license_verified_at' => now()->toIso8601String()]);

        return back()->with('status', 'License details saved.');
    }

    protected function backup(): ?string
    {
        @mkdir(storage_path('app/backups'), 0775, true);
        $path = storage_path('app/backups/backup-'.now()->format('Ymd-His').'.zip');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            return null;
        }
        if (config('database.default') === 'sqlite' && is_file($db = config('database.connections.sqlite.database'))) {
            $zip->addFile($db, 'database.sqlite');
        } else {
            foreach (['users', 'plans', 'settings', 'ai_providers', 'projects', 'project_files', 'deployments'] as $table) {
                $zip->addFromString("$table.json", DB::table($table)->get()->toJson());
            }
        }
        $zip->addFile(base_path('.env'), '.env');
        $zip->close();

        return $path;
    }
}
