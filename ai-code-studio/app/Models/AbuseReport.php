<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbuseReport extends Model
{
    public const REASONS = [
        'phishing' => 'Phishing or stealing passwords',
        'malware' => 'Malware or harmful downloads',
        'scam' => 'Scam or fraud',
        'illegal' => 'Illegal content',
        'copyright' => 'Copyright or trademark infringement',
        'spam' => 'Spam',
        'other' => 'Something else',
    ];

    protected $guarded = [];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
