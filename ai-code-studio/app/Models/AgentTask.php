<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTask extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['files' => 'array', 'log' => 'array'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function appendLog(string $message): void
    {
        $log = $this->log ?? [];
        $log[] = ['t' => now()->format('H:i:s'), 'm' => $message];
        $this->log = $log;
        $this->save();
    }
}
