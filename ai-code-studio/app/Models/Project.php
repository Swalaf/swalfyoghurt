<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['spec' => 'array', 'stack' => 'array'];
    }

    protected static function booted(): void
    {
        // Published files live outside the database; remove them with the project.
        static::deleting(fn (Project $p) => $p->deployments()->get()->each->deleteFiles());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Unguessable token for the sandboxed live preview (the iframe can't send session cookies). */
    public function previewToken(): string
    {
        return substr(hash_hmac('sha256', 'preview:'.$this->id.':'.$this->created_at?->timestamp, (string) config('app.key')), 0, 32);
    }

    public function previewUrl(): string
    {
        return route('preview', [$this->slug, $this->previewToken()]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->orderBy('path');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class)->orderBy('id');
    }

    public function changeSets(): HasMany
    {
        return $this->hasMany(ChangeSet::class)->latest('id');
    }

    public function agentTasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class)->latest('id');
    }

    public function liveDeployment(): ?Deployment
    {
        return $this->deployments()->where('status', 'live')->first();
    }

    public function pendingChangeSet(): ?ChangeSet
    {
        return $this->changeSets()->where('status', 'pending')->first();
    }

    public function stackLabel(string $key, string $fallback = ''): string
    {
        return $this->stack[$key] ?? $fallback;
    }

    public function statusBadge(): array
    {
        $live = $this->liveDeployment();
        if ($this->status === 'failed') {
            return ['Needs attention', '#E8B66B', '#2A2214', 'Failed', '#E5695E'];
        }
        if (in_array($this->status, ['building'], true)) {
            return ['Being built', '#A99BFF', '#1F1B36', 'Building', '#A99BFF'];
        }
        if ($live) {
            return ['Live', '#58C98A', '#15261C', 'Live', '#58C98A'];
        }
        if ($this->status === 'ready') {
            return ['Ready to try', '#5CC8E0', '#10222A', 'Preview', '#5CC8E0'];
        }

        return ['Draft', '#6E6E79', '#1C1C22', 'Draft', '#6E6E79'];
    }
}
