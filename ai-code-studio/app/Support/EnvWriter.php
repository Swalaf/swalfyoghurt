<?php

namespace App\Support;

/**
 * Writes KEY=value pairs into the .env file, replacing existing keys.
 */
class EnvWriter
{
    public static function write(array $values, ?string $path = null): void
    {
        $path ??= base_path('.env');
        if (! is_file($path)) {
            $example = base_path('.env.example');
            is_file($example) ? copy($example, $path) : touch($path);
        }
        $env = file_get_contents($path);
        foreach ($values as $key => $value) {
            $line = $key.'='.static::quote($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $env = preg_match($pattern, $env)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env)
                : rtrim($env)."\n".$line."\n";
        }
        file_put_contents($path, $env);
    }

    protected static function quote(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        $value = (string) $value;
        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+-]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }
}
