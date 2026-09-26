<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'log' => 'array', 'finished_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function appendLog(string $message, string $color = '#6E6E79'): void
    {
        $log = $this->log ?? [];
        $log[] = ['t' => now()->format('H:i:s'), 'm' => $message, 'c' => $color];
        $this->log = $log;
        $this->save();
    }
}
