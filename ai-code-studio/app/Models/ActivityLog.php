<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = [];

    public static function record(string $category, string $message, string $level = 'INFO', ?User $user = null): void
    {
        try {
            $user ??= auth()->user();
            static::create([
                'level' => $level,
                'category' => $category,
                'message' => mb_substr($message, 0, 250),
                'user_id' => $user?->id,
                'actor' => $user?->email ?? 'system',
                'ip' => request()?->ip(),
            ]);
        } catch (\Throwable) {
            // Logging must never break the request (e.g. before installation).
        }
    }
}
