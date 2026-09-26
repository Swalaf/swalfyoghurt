<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    public static function disk(): Filesystem
    {
        return Storage::disk(config('studio.publish_disk', 'published'));
    }

    /** Read one published file (from the storage disk, or the legacy DB snapshot). */
    public function readFile(string $path): ?string
    {
        if ($this->storage_path) {
            $content = self::disk()->get($this->storage_path.'/'.$path);

            return $content === null || $content === false ? null : $content;
        }

        return $this->snapshot[$path] ?? null;
    }

    public function hasFiles(): bool
    {
        return (bool) ($this->storage_path || $this->snapshot);
    }

    public function deleteFiles(): void
    {
        if ($this->storage_path) {
            self::disk()->deleteDirectory($this->storage_path);
        }
        $this->forceFill(['storage_path' => null, 'snapshot' => null])->save();
    }

    public function appendLog(string $message, string $color = '#6E6E79'): void
    {
        $log = $this->log ?? [];
        $log[] = ['t' => now()->format('H:i:s'), 'm' => $message, 'c' => $color];
        $this->log = $log;
        $this->save();
    }
}
