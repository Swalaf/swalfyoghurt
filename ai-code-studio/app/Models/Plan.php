<?php

namespace App\Models;

use App\Services\Billing\Billing;
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

        return Billing::format(Billing::amountFor($this, 'monthly'));
    }

    public function limit(?int $value, string $unlimited = 'Unlimited'): string
    {
        return $value === null ? $unlimited : number_format($value);
    }
}
