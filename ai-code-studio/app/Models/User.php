<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_admin', 'plan_id', 'credits', 'status',
        'experience', 'coding_level', 'email_verified_at', 'last_active_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'email_code'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'email_code_expires_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret && $this->two_factor_confirmed_at;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isDeveloper(): bool
    {
        return $this->experience === 'developer';
    }

    public function creditAllowance(): int
    {
        return (int) ($this->plan?->credits ?? 0);
    }
}
