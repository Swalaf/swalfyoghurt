<?php

namespace App\Services\Ai;

use App\Models\AiProvider;

class AiResult
{
    public function __construct(
        public string $text,
        public AiProvider $provider,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public int $credits = 0,
    ) {}
}
