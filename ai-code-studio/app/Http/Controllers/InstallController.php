<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Licensing\LicenseClient;
use App\Support\EnvWriter;
use App\Support\Installation;
use App\Support\Settings;
use Database\Seeders\PlatformSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallController extends Controller
{
    public function show()
    {
        return view('install.wizard', [
            'requirements' => Installation::requirements(),
            'defaults' => [
                'url' => rtrim(request()->root(), '/'),
                'domain' => request()->getHost(),
                'storage' => storage_path('app'),
                'base' => base_path(),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    public function requirements(): JsonResponse
    {
        return response()->json(Installation::requirements());
    }

    public function test(Request $request, string $kind): JsonResponse
    {
        return match ($kind) {
            'db' => $this->testDatabase($request->input('db', [])),
            'ai' => $this->testAi($request->input('ai', [])),
            'cron' => $this->testCron(),
            'lic' => $this->testLicense($request->input('license', [])),
        };
    }

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'db.driver' => 'required|in:MySQL,MariaDB,PostgreSQL,SQLite',
            'db.host' => 'nullable|string|max:255',
            'db.port' => 'nullable|numeric',
            'db.database' => 'required_unless:db.driver,SQLite|nullable|string|max:255',
            'db.username' => 'nullable|string|max:255',
            'db.password' => 'nullable|string|max:255',
            'app.name' => 'required|string|max:60',
            'app.url' => 'required|url|max:255',
            'app.timezone' => 'required|timezone',
            'app.currency' => 'required|string|size:3',
            'admin.name' => 'required|string|max:120',
            'admin.email' => 'required|email|max:255',
            'admin.password' => 'required|string|min:10|confirmed',
            'ai.driver' => 'required|in:OpenRouter,Anthropic,Google Gemini,Skip for now',
            'ai.key' => 'nullable|string|max:500',
            'storage.driver' => 'required|in:Local disk,Amazon S3,S3-compatible',
            'storage.bucket' => 'required_unless:storage.driver,Local disk|nullable|string|max:255',
            'storage.region' => 'nullable|string|max:60',
            'storage.key' => 'required_unless:storage.driver,Local disk|nullable|string|max:255',
            'storage.secret' => 'required_unless:storage.driver,Local disk|nullable|string|max:255',
            'storage.endpoint' => 'required_if:storage.driver,S3-compatible|nullable|url|max:255',
            'queue.driver' => 'required|in:Database,Redis,Sync',
            'queue.redis_host' => 'nullable|string|max:255',
            'queue.redis_port' => 'nullable|numeric',
            'license.code' => 'nullable|string|max:120',
            'license.domain' => 'nullable|string|max:255',
        ]);

        $log = [];
        try {
            $conn = $this->configureDatabase($data['db']);
            DB::connection($conn)->getPdo();

            if ($data['storage']['driver'] !== 'Local disk') {
                config(['filesystems.disks.s3' => array_merge(config('filesystems.disks.s3'), [
                    'key' => $data['storage']['key'], 'secret' => $data['storage']['secret'], 'region' => $data['storage']['region'] ?: 'us-east-1',
                    'bucket' => $data['storage']['bucket'], 'endpoint' => $data['storage']['endpoint'] ?? null,
                    'use_path_style_endpoint' => $data['storage']['driver'] === 'S3-compatible', 'throw' => true,
                ])]);
                Storage::disk('s3')->put('.install-test', 'ok');
                Storage::disk('s3')->delete('.install-test');
                $log[] = 'Connected to file storage (S3)';
            }

            Artisan::call('migrate', ['--force' => true, '--database' => $conn]);
            $log[] = 'Creating database tables ('.count(DB::connection($conn)->getSchemaBuilder()->getTables()).')';

            DB::setDefaultConnection($conn);
            (new PlatformSeeder)->run();
            $log[] = 'Seeding default plans & AI providers';

            $admin = User::updateOrCreate(['email' => strtolower($data['admin']['email'])], [
                'name' => $data['admin']['name'], 'password' => $data['admin']['password'], 'is_admin' => true,
                'email_verified_at' => now(), 'coding_level' => 2, 'experience' => 'developer', 'credits' => 0,
            ]);
            $log[] = 'Setting up admin account';

            $drivers = ['OpenRouter' => 'openrouter', 'Anthropic' => 'anthropic', 'Google Gemini' => 'gemini'];
            if (($driver = $drivers[$data['ai']['driver']] ?? null) && ! empty($data['ai']['key'])) {
                $provider = AiProvider::where('driver', $driver)->first();
                $provider->update(['api_key' => trim($data['ai']['key']), 'status' => 'connected', 'priority' => 1]);
                rescue(fn () => app(AiClient::class)->test($provider), report: false);
            }
            $log[] = 'Encrypting API keys';

            Settings::set([
                'brand_name' => $data['app']['name'],
                'brand_domain' => parse_url($data['app']['url'], PHP_URL_HOST),
                'timezone' => $data['app']['timezone'],
                'currency' => strtoupper($data['app']['currency']),
                'storage_driver' => $data['storage']['driver'],
                'support_email' => $data['admin']['email'],
                'mail_from' => 'no-reply@'.parse_url($data['app']['url'], PHP_URL_HOST),
                // Email sending is not configured yet, so don't lock new users out.
                'require_verification' => false,
            ]);
            $log[] = 'Registering background jobs';
            if (! empty($data['license']['code'])) {
                $lic = app(LicenseClient::class)->activate($data['license']['code'], $data['license']['domain'] ?? null);
                $log[] = 'Licence: '.$lic['message'];
            } else {
                $log[] = 'Licence: skipped (add it later in Admin → License & updates)';
            }

            // Written last so a failed install never leaves a half-configured .env behind.
            $env = [
                'APP_NAME' => $data['app']['name'],
                'APP_URL' => rtrim($data['app']['url'], '/'),
                'APP_ENV' => 'production',
                'APP_DEBUG' => false,
                'APP_TIMEZONE' => $data['app']['timezone'],
                'DB_CONNECTION' => $conn,
                'QUEUE_CONNECTION' => ['Database' => 'database', 'Redis' => 'redis', 'Sync' => 'sync'][$data['queue']['driver']],
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'file',
            ];
            if ($conn === 'sqlite') {
                $env['DB_DATABASE'] = config('database.connections.sqlite.database');
            } else {
                $env += ['DB_HOST' => $data['db']['host'] ?: '127.0.0.1', 'DB_PORT' => $data['db']['port'] ?: ($conn === 'pgsql' ? 5432 : 3306), 'DB_DATABASE' => $data['db']['database'], 'DB_USERNAME' => $data['db']['username'] ?? '', 'DB_PASSWORD' => $data['db']['password'] ?? ''];
            }
            if ($data['storage']['driver'] !== 'Local disk') {
                $env += [
                    'STUDIO_PUBLISH_DISK' => 's3',
                    'AWS_ACCESS_KEY_ID' => $data['storage']['key'], 'AWS_SECRET_ACCESS_KEY' => $data['storage']['secret'],
                    'AWS_DEFAULT_REGION' => $data['storage']['region'] ?: 'us-east-1', 'AWS_BUCKET' => $data['storage']['bucket'],
                    'AWS_ENDPOINT' => $data['storage']['endpoint'] ?? '', 'AWS_USE_PATH_STYLE_ENDPOINT' => $data['storage']['driver'] === 'S3-compatible',
                ];
            }
            if (str_starts_with($data['app']['url'], 'https://')) {
                $env['SESSION_SECURE_COOKIE'] = true;
            }
            if ($data['queue']['driver'] === 'Redis') {
                $env += ['REDIS_HOST' => $data['queue']['redis_host'] ?: '127.0.0.1', 'REDIS_PORT' => $data['queue']['redis_port'] ?: 6379];
            }

            EnvWriter::write($env);
            array_unshift($log, 'Writing configuration file');

            Installation::markInstalled(['admin' => $admin->email]);
            Artisan::call('config:clear');
            $log[] = 'Warming caches';

            Auth::login($admin, true);
            $request->session()->put('install.just_finished', true);
            ActivityLog::record('System', 'Platform installed (v'.config('studio.version').')', 'INFO', $admin);

            return response()->json(['ok' => true, 'log' => $log, 'url' => $data['app']['url'], 'admin' => route('admin.overview')]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'log' => $log, 'message' => $e->getMessage()], 422);
        }
    }

    protected function configureDatabase(array $db): string
    {
        $conn = ['MySQL' => 'mysql', 'MariaDB' => 'mariadb', 'PostgreSQL' => 'pgsql', 'SQLite' => 'sqlite'][$db['driver']];
        if ($conn === 'sqlite') {
            $path = database_path('database.sqlite');
            if (! is_file($path)) {
                touch($path);
            }
            config(['database.connections.sqlite.database' => $path]);
        } else {
            config(["database.connections.$conn" => array_merge(config("database.connections.$conn"), [
                'host' => $db['host'] ?: '127.0.0.1',
                'port' => $db['port'] ?: ($conn === 'pgsql' ? 5432 : 3306),
                'database' => $db['database'],
                'username' => $db['username'] ?? '',
                'password' => $db['password'] ?? '',
            ])]);
        }
        DB::purge($conn);
        config(['database.default' => $conn]);

        return $conn;
    }

    protected function testDatabase(array $db): JsonResponse
    {
        try {
            $db['driver'] ??= 'MySQL';
            $conn = $this->configureDatabase($db);
            $pdo = DB::connection($conn)->getPdo();
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

            return response()->json(['ok' => true, 'message' => 'Connected to '.$db['driver'].' '.Str::before((string) $version, '-').' ✓']);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not connect: '.Str::limit($e->getMessage(), 160)]);
        }
    }

    protected function testAi(array $ai): JsonResponse
    {
        $driver = ['OpenRouter' => 'openrouter', 'Anthropic' => 'anthropic', 'Google Gemini' => 'gemini'][$ai['driver'] ?? ''] ?? null;
        if (! $driver || empty($ai['key'])) {
            return response()->json(['ok' => false, 'message' => 'Paste an API key first.']);
        }
        $result = app(AiClient::class)->test(new AiProvider(['name' => $ai['driver'], 'driver' => $driver, 'api_key' => trim($ai['key'])]));

        return response()->json(['ok' => $result['ok'], 'message' => $result['ok'] ? 'Key works — '.$result['models'].' models available ✓' : $result['message']]);
    }

    protected function testCron(): JsonResponse
    {
        $file = storage_path('app/cron-heartbeat');
        $ok = is_file($file) && filemtime($file) > time() - 180;

        return response()->json([
            'ok' => $ok,
            'message' => $ok ? 'Cron heartbeat received ✓' : 'No heartbeat yet. Add the cron line, wait a minute, then test again (you can continue and fix this later).',
        ]);
    }

    protected function testLicense(array $license): JsonResponse
    {
        $code = trim($license['code'] ?? '');
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Enter your licence key or purchase code, or continue and add it later.']);
        }
        // Before installation there is no settings table, so only check — don't store.
        $client = app(LicenseClient::class);
        if (! $client->configured()) {
            $ok = (bool) preg_match('/^(ACS(-[A-Z0-9]{4}){4}|[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12})$/i', $code);

            return response()->json(['ok' => $ok, 'message' => $ok ? 'Format looks right ✓ (it will be activated after installation)' : 'That doesn’t look like a licence key (ACS-XXXX-XXXX-XXXX-XXXX) or purchase code.']);
        }
        try {
            $res = Http::acceptJson()->timeout(20)->post(config('studio.license_url').'/api/license/verify', ['key' => $code, 'domain' => $license['domain'] ?? request()->getHost()]);
            $ok = $res->successful() && $res->json('ok');
            $notFoundButMaybeEnvato = $res->status() === 404;

            return response()->json(['ok' => $ok || $notFoundButMaybeEnvato, 'message' => $ok ? ucfirst((string) $res->json('type')).' licence found ✓' : ($notFoundButMaybeEnvato ? 'Will be checked when you install ✓' : (string) $res->json('message', 'Licence check failed.'))]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not reach the licence server. You can continue and activate later.']);
        }
    }
}
