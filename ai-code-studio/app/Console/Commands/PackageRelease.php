<?php

namespace App\Console\Commands;

use App\Models\Release;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Builds the distributable zip buyers upload to their hosting.
 * Run `composer install --no-dev --optimize-autoloader` first so vendor/ is lean.
 */
class PackageRelease extends Command
{
    protected $signature = 'studio:package {--out= : Output folder (default: build/)} {--publish : Also publish it as a release on this licence server} {--notes= : Release notes for --publish} {--allow-dev : Package even if dev dependencies are installed}';

    protected $description = 'Build a versioned release zip of this platform for buyers';

    /** Never packaged. Entries ending in "/" are folders (prefix match); others are exact paths. */
    protected const EXCLUDE = [
        '.env', '.env.backup', '.env.production', '.git/', '.github/', '.idea/', '.vscode/', 'node_modules/', 'build/', 'tests/', '.phpunit.cache/', '.phpunit.result.cache', 'phpunit.xml',
        'storage/logs/', 'storage/framework/cache/data/', 'storage/framework/sessions/', 'storage/framework/views/',
        'storage/framework/testing/', 'storage/app/private/', 'storage/app/published/', 'storage/app/backups/', 'storage/app/releases/',
        'storage/app/installed.json', 'storage/app/cron-heartbeat', 'storage/pail/', 'bootstrap/cache/packages.php',
        'bootstrap/cache/services.php', 'bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php', 'bootstrap/cache/events.php',
        'database/database.sqlite', 'public/uploads/', 'public/hot', 'public/storage',
    ];

    public function handle(): int
    {
        $version = config('studio.version');
        if (is_dir(base_path('vendor/phpunit')) && ! $this->option('allow-dev')) {
            $this->error('Dev dependencies are installed. Run: composer install --no-dev --optimize-autoloader (then package again).');

            return self::FAILURE;
        }
        $outDir = rtrim($this->option('out') ?: base_path('build'), '/');
        @mkdir($outDir, 0775, true);
        $file = $outDir.'/ai-code-studio-'.$version.'.zip';
        @unlink($file);

        $zip = new ZipArchive;
        if ($zip->open($file, ZipArchive::CREATE) !== true) {
            $this->error('Cannot write '.$file);

            return self::FAILURE;
        }
        $base = base_path().'/';
        $count = 0;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $path => $info) {
            $rel = str_replace('\\', '/', substr($path, strlen($base)));
            if ($this->excluded($rel.($info->isDir() ? '/' : ''))) {
                continue;
            }
            if ($info->isDir()) {
                $zip->addEmptyDir('ai-code-studio/'.$rel);
            } else {
                $zip->addFile($path, 'ai-code-studio/'.$rel);
                $count++;
            }
        }
        // Keep empty runtime folders writable-ready.
        foreach (['storage/app/private', 'storage/app/published', 'storage/logs', 'storage/framework/sessions', 'storage/framework/views', 'storage/framework/cache/data', 'public/uploads'] as $dir) {
            $zip->addFromString('ai-code-studio/'.$dir.'/.gitignore', "*\n!.gitignore\n");
        }
        $zip->close();

        $size = round(filesize($file) / 1048576, 1);
        $this->info("Built {$file} ({$count} files, {$size} MB)");

        if ($this->option('publish')) {
            abort_unless(config('studio.license_server'), 1, 'This installation is not a licence server (STUDIO_LICENSE_SERVER=true).');
            $stored = 'releases/ai-code-studio-'.$version.'.zip';
            Storage::disk('local')->put($stored, fopen($file, 'r'));
            Release::updateOrCreate(['version' => $version], ['path' => $stored, 'size' => filesize($file), 'sha256' => hash_file('sha256', $file), 'notes' => $this->option('notes')]);
            $this->info("Published v{$version} — buyers see it under Admin → License & updates.");
        }

        return self::SUCCESS;
    }

    protected function excluded(string $rel): bool
    {
        if (str_contains($rel, '/.git/') || str_ends_with($rel, '/.git')) {
            return true;
        }
        foreach (self::EXCLUDE as $ex) {
            if ($rel === $ex || (str_ends_with($ex, '/') && str_starts_with($rel, $ex))) {
                return true;
            }
        }

        return false;
    }
}
