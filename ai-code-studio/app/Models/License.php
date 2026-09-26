<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A licence issued by this installation when it runs as the licence server. */
class License extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['supported_until' => 'datetime', 'activated_at' => 'datetime', 'last_check_at' => 'datetime'];
    }

    public static function generateKey(): string
    {
        do {
            $key = 'ACS-'.implode('-', str_split(strtoupper(Str::random(16)), 4));
        } while (static::where('key', $key)->exists());

        return $key;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function supportActive(): bool
    {
        return ! $this->supported_until || $this->supported_until->isFuture();
    }
}
