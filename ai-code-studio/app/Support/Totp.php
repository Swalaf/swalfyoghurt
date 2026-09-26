<?php

namespace App\Support;

/**
 * Minimal RFC 6238 TOTP (30s, 6 digits, SHA1) — compatible with Google
 * Authenticator, 1Password, Authy etc.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    public static function code(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), 30);
        $key = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, time() + $i * 30), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $secret, string $email, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$email).'?secret='.$secret.'&issuer='.rawurlencode($issuer);
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));
        $bits = '';
        foreach (str_split($b32) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
