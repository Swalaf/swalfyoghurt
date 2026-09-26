<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeSetFile extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function changeSet(): BelongsTo
    {
        return $this->belongsTo(ChangeSet::class);
    }
}
