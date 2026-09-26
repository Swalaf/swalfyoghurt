<?php

namespace App\Support;

class Installation
{
    public static function lockFile(): string
    {
        return storage_path('app/installed.json');
    }

    public static function installed(): bool
    {
        return (bool) config('studio.installed') || is_file(static::lockFile());
    }

    public static function markInstalled(array $meta = []): void
    {
        @mkdir(dirname(static::lockFile()), 0775, true);
        file_put_contents(static::lockFile(), json_encode(array_merge([
            'version' => config('studio.version'),
            'installed_at' => now()->toIso8601String(),
        ], $meta), JSON_PRETTY_PRINT));
    }

    /**
     * Server requirement checks for the installer's "System check" step.
     *
     * @return array<int, array{name: string, need: string, have: string, ok: int, help: string}>
     */
    public static function requirements(): array
    {
        $exts = ['pdo', 'mbstring', 'openssl', 'curl', 'zip', 'gd'];
        $loaded = array_filter($exts, fn ($e) => extension_loaded($e));
        $writable = is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'));
        $envWritable = is_file(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path());
        $free = @disk_free_space(base_path());
        $freeGb = $free ? round($free / 1024 / 1024 / 1024, 1) : null;
        $node = trim((string) @shell_exec('node -v 2>/dev/null'));
        $pdoDrivers = class_exists(\PDO::class) ? \PDO::getAvailableDrivers() : [];

        return [
            ['name' => 'PHP version', 'need' => '8.3+', 'have' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 1 : 0, 'help' => 'Ask your host to switch this site to PHP 8.3 or newer (cPanel → “Select PHP Version”).'],
            ['name' => 'Database driver (MySQL, PostgreSQL or SQLite)', 'need' => 'one', 'have' => $pdoDrivers ? implode(', ', $pdoDrivers) : 'none', 'ok' => array_intersect(['mysql', 'pgsql', 'sqlite'], $pdoDrivers) ? 1 : 0, 'help' => 'Enable the pdo_mysql PHP extension.'],
            ['name' => 'PHP extensions ('.implode(', ', $exts).')', 'need' => 'all', 'have' => count($loaded).' / '.count($exts), 'ok' => count($loaded) === count($exts) ? 1 : 0, 'help' => 'Missing: '.implode(', ', array_diff($exts, $loaded)).'. Enable them in your PHP settings.'],
            ['name' => 'Storage folder writable', 'need' => 'writable', 'have' => $writable ? 'writable' : 'read-only', 'ok' => $writable ? 1 : 0, 'help' => 'Run: chmod -R 775 storage bootstrap/cache — or ask your host to make these folders writable.'],
            ['name' => 'Configuration file (.env) writable', 'need' => 'writable', 'have' => $envWritable ? 'writable' : 'read-only', 'ok' => $envWritable ? 1 : 0, 'help' => 'The installer saves your settings to .env. Make it (or the app folder) writable.'],
            ['name' => 'Free disk space', 'need' => '1 GB', 'have' => $freeGb !== null ? $freeGb.' GB' : 'unknown', 'ok' => $freeGb === null ? 2 : ($freeGb >= 1 ? 1 : 0), 'help' => 'Free up space on the server.'],
            ['name' => 'Node.js (optional, for building user apps)', 'need' => 'optional', 'have' => $node ?: 'missing', 'ok' => $node ? 1 : 2, 'help' => 'Optional. Published apps are served as static files, so you can skip this.'],
        ];
    }
}
