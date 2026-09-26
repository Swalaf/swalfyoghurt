<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value platform settings stored in the `settings` table.
 * Values are JSON encoded so booleans and arrays round-trip.
 */
class Settings
{
    public const DEFAULTS = [
        'brand_name' => 'AI Code Studio',
        'brand_color' => '#7C6CF0',
        'brand_domain' => '',
        'company_name' => 'Your Company Ltd.',
        'support_email' => '',
        'default_language' => 'English',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'landing_headline' => 'build',
        'show_pricing' => true,
        'allow_signups' => true,
        'social_login' => false,
        'require_verification' => true,
        'require_2fa' => false,
        'admin_2fa' => false,
        'maintenance' => false,
        'maintenance_message' => 'We’re upgrading the platform. Your projects and deployed apps are unaffected.',
        'routing' => 'Balanced',
        'storage_driver' => 'Local disk',
        'max_upload_mb' => '50',
        'session_timeout' => '7 days',
        'mail_host' => '',
        'mail_username' => '',
        'mail_password' => '',
        'mail_from' => '',
        'gateways' => [],
        'license_code' => '',
        'license_domain' => '',
        'license_verified_at' => null,
        'cron_heartbeat' => null,
        'backup_before_update' => true,
    ];

    protected static ?array $cache = null;

    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }
        $stored = [];
        if (Installation::installed()) {
            try {
                $stored = Cache::rememberForever('studio.settings', fn () => Setting::all()
                    ->mapWithKeys(fn ($s) => [$s->key => json_decode((string) $s->value, true)])->all());
            } catch (\Throwable) {
                $stored = [];
            }
        }

        return static::$cache = array_merge(self::DEFAULTS, $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all()[$key] ?? $default;
    }

    public static function set(string|array $key, mixed $value = null): void
    {
        $pairs = is_array($key) ? $key : [$key => $value];
        foreach ($pairs as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => json_encode($v)]);
        }
        static::flush();
    }

    public static function flush(): void
    {
        static::$cache = null;
        try {
            Cache::forget('studio.settings');
        } catch (\Throwable) {
        }
    }

    public static function brand(): string
    {
        return (string) static::get('brand_name', 'AI Code Studio');
    }

    public static function accent(): string
    {
        $c = (string) static::get('brand_color', '#7C6CF0');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '#7C6CF0';
    }
}
