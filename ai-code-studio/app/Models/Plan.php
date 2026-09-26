<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['features' => 'array', 'highlighted' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isCustom(): bool
    {
        return $this->price_cents === null;
    }

    public function priceLabel(bool $yearly = false): string
    {
        if ($this->isCustom()) {
            return 'Custom';
        }
        $dollars = $this->price_cents / 100;

        return '$'.number_format($yearly ? round($dollars * 0.8) : $dollars);
    }

    public function limit(?int $value, string $unlimited = 'Unlimited'): string
    {
        return $value === null ? $unlimited : number_format($value);
    }
}
