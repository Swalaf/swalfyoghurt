<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $guarded = [];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'checked_at' => 'datetime', 'cost_per_million' => 'float'];
    }

    public const DRIVERS = [
        'openrouter' => ['name' => 'OpenRouter', 'base' => 'https://openrouter.ai/api/v1', 'model' => 'openrouter/auto', 'kind' => 'Recommended for beginners', 'about' => 'One key, hundreds of models from many companies.', 'ini' => 'OR'],
        'anthropic' => ['name' => 'Anthropic', 'base' => 'https://api.anthropic.com/v1', 'model' => 'claude-sonnet-5', 'kind' => 'Coding models', 'about' => 'Strong at writing and fixing code.', 'ini' => 'A'],
        'gemini' => ['name' => 'Google Gemini', 'base' => 'https://generativelanguage.googleapis.com/v1beta', 'model' => 'gemini-2.5-pro', 'kind' => 'Coding & images', 'about' => 'Fast and affordable, with a free tier.', 'ini' => 'G'],
        'groq' => ['name' => 'Groq', 'base' => 'https://api.groq.com/openai/v1', 'model' => 'llama-3.3-70b-versatile', 'kind' => 'Very fast', 'about' => 'Great for quick answers.', 'ini' => 'Gq'],
        'openai' => ['name' => 'OpenAI', 'base' => 'https://api.openai.com/v1', 'model' => 'gpt-5', 'kind' => 'Coding & images', 'about' => 'Popular models for chat, code and images.', 'ini' => 'O'],
        'custom' => ['name' => 'Custom API', 'base' => '', 'model' => '', 'kind' => 'Advanced', 'about' => 'Any OpenAI-compatible endpoint, including self-hosted models (Ollama, vLLM).', 'ini' => '+'],
    ];

    public function meta(string $key): string
    {
        return self::DRIVERS[$this->driver][$key] ?? '';
    }

    public function isUsable(): bool
    {
        return in_array($this->status, ['connected', 'slow'], true)
            && ($this->api_key || $this->driver === 'custom')
            && $this->baseUrl();
    }

    public function baseUrl(): string
    {
        return rtrim($this->base_url ?: $this->meta('base'), '/');
    }

    public function model(): string
    {
        return $this->default_model ?: $this->meta('model');
    }

    public function maskedKey(): string
    {
        if ($this->driver === 'custom' && ! $this->api_key) {
            return $this->base_url ?: 'Not configured';
        }
        if (! $this->api_key) {
            return 'Not configured';
        }
        $k = $this->api_key;

        return substr($k, 0, min(6, strlen($k) - 4)).'••••••••'.substr($k, -4);
    }

    public function statusLabel(): string
    {
        return ['connected' => 'Connected', 'slow' => 'Slow', 'error' => 'Error', 'not_configured' => 'Not connected'][$this->status] ?? 'Not connected';
    }
}
