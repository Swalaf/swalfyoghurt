<?php

namespace App\Services\Licensing;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Payment;
use App\Notifications\LicenseIssued;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/**
 * Vendor-side licence logic: issue keys, activate/verify installs (one
 * production domain per licence), and accept Envato purchase codes.
 */
class LicenseServer
{
    public static function normalizeDomain(string $domain): string
    {
        $host = parse_url(str_contains($domain, '://') ? $domain : 'http://'.$domain, PHP_URL_HOST) ?: $domain;

        return preg_replace('/^www\./', '', strtolower(trim($host)));
    }

    /** Local/dev hosts never consume the licence's domain slot. */
    public static function isDevDomain(string $domain): bool
    {
        return in_array($domain, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($domain, '.test') || str_ends_with($domain, '.local') || str_ends_with($domain, '.localhost')
            || str_starts_with($domain, '127.') || str_starts_with($domain, 'staging.') || str_starts_with($domain, 'dev.');
    }

    public function issue(array $attrs): License
    {
        $license = License::create($attrs + [
            'key' => License::generateKey(),
            'status' => 'active',
            'supported_until' => now()->addMonths(12),
        ]);
        ActivityLog::record('License', 'Issued '.$license->type.' licence '.$license->key.($license->email ? ' to '.$license->email : ''));

        return $license;
    }

    public function issueForPayment(Payment $payment): License
    {
        $existing = License::where('purchase_ref', 'pay_'.$payment->id)->first();
        if ($existing) {
            return $existing;
        }
        $meta = $payment->meta ?? [];
        $license = $this->issue([
            'type' => $meta['type'] ?? 'regular', 'source' => 'direct', 'purchase_ref' => 'pay_'.$payment->id,
            'name' => $meta['name'] ?? null, 'email' => $meta['email'] ?? null,
        ]);
        if ($license->email) {
            rescue(fn () => Notification::route('mail', $license->email)->notify(new LicenseIssued($license)));
        }

        return $license;
    }

    /**
     * @return array{0: int, 1: array<string, mixed>} HTTP status and JSON body
     */
    public function activate(string $key, string $domain, string $instance, string $version = ''): array
    {
        $domain = self::normalizeDomain($domain);
        $license = $this->find($key);
        if (! $license) {
            return [404, ['ok' => false, 'status' => 'invalid', 'message' => 'That licence key or purchase code wasn’t found.']];
        }
        if (! $license->isActive()) {
            return [403, ['ok' => false, 'status' => 'revoked', 'message' => 'This licence has been revoked. Contact support.']];
        }
        if (! self::isDevDomain($domain)) {
            if ($license->domain && $license->domain !== $domain) {
                return [409, ['ok' => false, 'status' => 'in_use', 'message' => 'This licence is already active on '.$license->domain.'. Deactivate it there first (Admin → License & updates → Move to another domain).']];
            }
            $license->forceFill(['domain' => $domain, 'instance_id' => $instance, 'activated_at' => $license->activated_at ?? now()]);
        }
        $license->forceFill(['last_check_at' => now(), 'last_version' => $version ?: $license->last_version])->save();

        return [200, $this->payload($license, $domain)];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function verify(string $key, string $domain, string $version = ''): array
    {
        $domain = self::normalizeDomain($domain);
        $license = $this->find($key, remoteLookup: false);
        if (! $license) {
            return [404, ['ok' => false, 'status' => 'invalid', 'message' => 'Unknown licence.']];
        }
        $license->forceFill(['last_check_at' => now(), 'last_version' => $version ?: $license->last_version])->save();
        if (! $license->isActive()) {
            return [403, ['ok' => false, 'status' => 'revoked', 'message' => 'This licence has been revoked.']];
        }
        if (! self::isDevDomain($domain) && $license->domain && $license->domain !== $domain) {
            return [409, ['ok' => false, 'status' => 'in_use', 'message' => 'This licence is registered to '.$license->domain.'.']];
        }

        return [200, $this->payload($license, $domain)];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function deactivate(string $key, string $domain): array
    {
        $license = $this->find($key, remoteLookup: false);
        if (! $license || ($license->domain && $license->domain !== self::normalizeDomain($domain))) {
            return [404, ['ok' => false, 'message' => 'Licence not active on this domain.']];
        }
        $license->forceFill(['domain' => null, 'instance_id' => null])->save();
        ActivityLog::record('License', $license->key.' deactivated from '.self::normalizeDomain($domain));

        return [200, ['ok' => true, 'message' => 'Deactivated. You can now activate it on another domain.']];
    }

    public function authorizeDownload(string $key, string $domain): ?License
    {
        [$status] = $this->verify($key, $domain);
        $license = $this->find($key, remoteLookup: false);

        return $status === 200 && $license?->supportActive() ? $license : null;
    }

    protected function payload(License $l, string $domain): array
    {
        return [
            'ok' => true, 'status' => 'active', 'type' => $l->type, 'licensee' => $l->name ?: $l->email,
            'domain' => $l->domain ?: $domain,
            'supported_until' => $l->supported_until?->toIso8601String(),
            'support_active' => $l->supportActive(),
        ];
    }

    /** Own keys first; otherwise try the code as an Envato purchase code. */
    protected function find(string $key, bool $remoteLookup = true): ?License
    {
        $key = trim($key);
        $license = License::where('key', strtoupper($key))->orWhere('purchase_ref', 'envato_'.strtolower($key))->first();
        if ($license || ! $remoteLookup || ! preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $key)) {
            return $license;
        }
        $token = Settings::get('envato_token') ? rescue(fn () => decrypt(Settings::get('envato_token')), null, false) : null;
        if (! $token) {
            return null;
        }
        $res = Http::withToken($token)->timeout(20)->get('https://api.envato.com/v3/market/author/sale', ['code' => $key]);
        if (! $res->successful() || ! $res->json('item')) {
            return null;
        }

        return $this->issue([
            'type' => str_contains(strtolower((string) $res->json('license')), 'extended') ? 'extended' : 'regular',
            'source' => 'envato', 'purchase_ref' => 'envato_'.strtolower($key),
            'name' => $res->json('buyer'), 'supported_until' => $res->json('supported_until') ? now()->parse($res->json('supported_until')) : null,
        ]);
    }
}
