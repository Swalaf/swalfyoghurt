<?php

namespace App\Services\Licensing;

use App\Models\ActivityLog;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Buyer-side: activates this installation against the vendor's licence
 * server and checks for updates. Never locks the platform — an invalid
 * licence only shows a warning to admins.
 */
class LicenseClient
{
    public function configured(): bool
    {
        return config('studio.license_url') !== '';
    }

    public static function instanceId(): string
    {
        $id = Settings::get('instance_id');
        if (! $id) {
            $id = 'inst_'.Str::lower(Str::random(20));
            rescue(fn () => Settings::set('instance_id', $id), report: false);
        }

        return $id;
    }

    public static function domain(): string
    {
        return LicenseServer::normalizeDomain((string) (Settings::get('license_domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST)));
    }

    /** @return array{ok: bool, status: string, message: string} */
    public function activate(string $code, ?string $domain = null): array
    {
        $domain = LicenseServer::normalizeDomain($domain ?: self::domain());

        return $this->call('activate', ['key' => trim($code), 'domain' => $domain, 'instance' => self::instanceId()], $code, $domain);
    }

    /** @return array{ok: bool, status: string, message: string} */
    public function verify(): array
    {
        $code = (string) Settings::get('license_code');
        if ($code === '') {
            return ['ok' => false, 'status' => 'missing', 'message' => 'No licence added yet.'];
        }

        return $this->call('verify', ['key' => $code, 'domain' => self::domain()], $code, self::domain());
    }

    /** @return array{ok: bool, status: string, message: string} */
    public function deactivate(): array
    {
        $code = (string) Settings::get('license_code');
        $res = $this->call('deactivate', ['key' => $code, 'domain' => self::domain()], $code, self::domain(), store: false);
        if ($res['ok']) {
            Settings::set(['license_status' => 'inactive', 'license_domain' => '']);
        }

        return $res;
    }

    /** @return array{ok: bool, latest?: string, newer?: bool, notes?: string, download?: string|null, message: string} */
    public function checkForUpdates(): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'message' => 'No licence server is configured (STUDIO_LICENSE_URL).'];
        }
        try {
            $res = Http::acceptJson()->timeout(20)->get(config('studio.license_url').'/api/updates/latest', [
                'key' => Settings::get('license_code'), 'domain' => self::domain(), 'version' => config('studio.version'),
            ]);
            if (! $res->successful()) {
                return ['ok' => false, 'message' => $res->json('message') ?: 'Update server returned '.$res->status().'.'];
            }
            $latest = (string) $res->json('version');

            return [
                'ok' => true, 'latest' => $latest, 'newer' => version_compare($latest, config('studio.version'), '>'),
                'notes' => (string) $res->json('notes'), 'download' => $res->json('download_url'),
                'message' => version_compare($latest, config('studio.version'), '>') ? 'Version '.$latest.' is available.' : 'You’re on the latest version.',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach the update server: '.$e->getMessage()];
        }
    }

    /** @return array{ok: bool, status: string, message: string} */
    protected function call(string $action, array $payload, string $code, string $domain, bool $store = true): array
    {
        if (! $this->configured()) {
            $ok = (bool) preg_match('/^(ACS(-[A-Z0-9]{4}){4}|[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12})$/i', trim($code));
            $result = ['ok' => $ok, 'status' => $ok ? 'unverified' : 'invalid', 'message' => $ok ? 'Saved. No licence server is configured, so it couldn’t be checked online.' : 'That doesn’t look like a licence key or purchase code.'];
        } else {
            try {
                $res = Http::acceptJson()->timeout(20)->post(config('studio.license_url').'/api/license/'.$action, $payload + ['version' => config('studio.version')]);
                $result = [
                    'ok' => $res->successful() && $res->json('ok'),
                    'status' => (string) ($res->json('status') ?? ($res->successful() ? 'active' : 'error')),
                    'message' => (string) ($res->json('message') ?? ($res->successful() ? 'Licence active.' : 'Licence server returned '.$res->status().'.')),
                ] + ['type' => $res->json('type'), 'supported_until' => $res->json('supported_until')];
                if ($result['ok'] && $action !== 'deactivate') {
                    $result['message'] = ucfirst((string) $res->json('type', 'regular')).' licence active'.($res->json('supported_until') ? ' · support until '.now()->parse($res->json('supported_until'))->toFormattedDateString() : '').'.';
                }
            } catch (Throwable $e) {
                // Network problems never invalidate an existing licence.
                return ['ok' => false, 'status' => 'unreachable', 'message' => 'Could not reach the licence server: '.$e->getMessage()];
            }
        }

        if ($store) {
            Settings::set(array_filter([
                'license_code' => trim($code),
                'license_domain' => $domain,
                'license_status' => $result['status'],
                'license_type' => $result['type'] ?? null,
                'license_supported_until' => $result['supported_until'] ?? null,
                'license_verified_at' => now()->toIso8601String(),
            ], fn ($v) => $v !== null));
            if (in_array($result['status'], ['revoked', 'in_use', 'invalid'], true)) {
                ActivityLog::record('License', 'Licence check: '.$result['message'], 'WARN');
            }
        }

        return $result;
    }
}
