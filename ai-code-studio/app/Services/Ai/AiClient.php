<?php

namespace App\Services\Ai;

use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\AiUsage;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Talks to the admin-configured AI providers. Tries providers in the order
 * given by the routing strategy and falls back to the next one on failure.
 */
class AiClient
{
    public function hasProvider(): bool
    {
        return $this->candidates()->isNotEmpty();
    }

    /** @return Collection<int, AiProvider> */
    public function candidates(?string $strategy = null): Collection
    {
        $strategy ??= (string) Settings::get('routing', 'Balanced');
        $usable = AiProvider::all()->filter->isUsable();
        $quality = ['anthropic' => 0, 'openrouter' => 1, 'openai' => 2, 'gemini' => 3, 'groq' => 4, 'custom' => 5];

        $sorted = match ($strategy) {
            'Best quality' => $usable->sortBy(fn ($p) => [$quality[$p->driver] ?? 9, $p->priority]),
            'Cheapest', 'Free-first' => $usable->sortBy(fn ($p) => [$p->cost_per_million ?? 0, $p->priority]),
            'Fastest' => $usable->sortBy(fn ($p) => [$p->latency_ms ?? PHP_INT_MAX, $p->priority]),
            default => $usable->sortBy('priority'),
        };

        return $sorted->values();
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, string $system = '', ?User $user = null, int $maxTokens = 4096): AiResult
    {
        $providers = $this->candidates();
        if ($providers->isEmpty()) {
            throw new AiUnavailable('No AI provider is connected yet. An admin can add one under Admin → AI providers.');
        }
        if ($user && $user->credits <= 0 && ! $user->is_admin) {
            throw new AiUnavailable('You have run out of AI credits. Upgrade your plan or ask an admin to top you up.');
        }

        $errors = [];
        foreach ($providers as $provider) {
            try {
                $result = $this->send($provider, $messages, $system, $maxTokens);
                $this->recordUsage($result, $user);

                return $result;
            } catch (Throwable $e) {
                $errors[] = $provider->name.': '.$e->getMessage();
                ActivityLog::record('AI', $provider->name.' failed — '.($providers->count() > 1 ? 'trying next provider' : 'no fallback left'), 'WARN');
            }
        }

        throw new AiUnavailable('All AI providers failed. '.implode(' · ', $errors));
    }

    /**
     * Check that a provider's key works. Updates status + latency.
     *
     * @return array{ok: bool, message: string, models: int}
     */
    public function test(AiProvider $provider): array
    {
        $started = microtime(true);
        try {
            $count = match ($provider->driver) {
                'anthropic' => count($this->anthropic($provider)->get($provider->baseUrl().'/models')->throw()->json('data', [])),
                'gemini' => count(Http::timeout(20)->get($provider->baseUrl().'/models', ['key' => $provider->api_key])->throw()->json('models', [])),
                default => count($this->openAi($provider)->get($provider->baseUrl().'/models')->throw()->json('data', [])),
            };
            $ms = (int) round((microtime(true) - $started) * 1000);
            $provider->forceFill(['status' => $ms > 2500 ? 'slow' : 'connected', 'latency_ms' => $ms, 'checked_at' => now(), 'models' => $count ? $count.' models' : $provider->models]);
            $provider->exists && $provider->save();

            return ['ok' => true, 'message' => 'Connected! '.$count.' models available.', 'models' => $count];
        } catch (Throwable $e) {
            $provider->forceFill(['status' => 'error', 'checked_at' => now()]);
            $provider->exists && $provider->save();
            $msg = $e instanceof RequestException ? 'The provider rejected the request ('.$e->response->status().'). Check the key.' : 'Could not reach the provider: '.$e->getMessage();

            return ['ok' => false, 'message' => $msg, 'models' => 0];
        }
    }

    protected function send(AiProvider $provider, array $messages, string $system, int $maxTokens): AiResult
    {
        $started = microtime(true);
        $model = $provider->model();

        if ($provider->driver === 'anthropic') {
            $res = $this->anthropic($provider)->post($provider->baseUrl().'/messages', array_filter([
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system ?: null,
                'messages' => array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system')),
            ]))->throw();
            $text = collect($res->json('content', []))->where('type', 'text')->pluck('text')->implode('');
            $in = (int) $res->json('usage.input_tokens', 0);
            $out = (int) $res->json('usage.output_tokens', 0);
        } elseif ($provider->driver === 'gemini') {
            $res = Http::timeout(120)->post($provider->baseUrl().'/models/'.$model.':generateContent?key='.urlencode((string) $provider->api_key), array_filter([
                'systemInstruction' => $system ? ['parts' => [['text' => $system]]] : null,
                'contents' => array_map(fn ($m) => ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]]], $messages),
                'generationConfig' => ['maxOutputTokens' => $maxTokens],
            ]))->throw();
            $text = collect($res->json('candidates.0.content.parts', []))->pluck('text')->implode('');
            $in = (int) $res->json('usageMetadata.promptTokenCount', 0);
            $out = (int) $res->json('usageMetadata.candidatesTokenCount', 0);
        } else {
            $payload = ['model' => $model, 'max_tokens' => $maxTokens, 'messages' => array_merge($system ? [['role' => 'system', 'content' => $system]] : [], $messages)];
            $res = $this->openAi($provider)->post($provider->baseUrl().'/chat/completions', $payload)->throw();
            $text = (string) $res->json('choices.0.message.content', '');
            $in = (int) $res->json('usage.prompt_tokens', 0);
            $out = (int) $res->json('usage.completion_tokens', 0);
            $model = (string) ($res->json('model') ?: $model);
        }

        if (trim($text) === '') {
            throw new \RuntimeException('Empty response');
        }

        $provider->forceFill(['latency_ms' => (int) round((microtime(true) - $started) * 1000)])->save();
        $credits = max(1, (int) ceil(($in + $out) / 1000 * config('studio.credits_per_1k_tokens', 1)));

        return new AiResult($text, $provider, $model, $in, $out, $credits);
    }

    protected function recordUsage(AiResult $r, ?User $user): void
    {
        $cost = $r->provider->cost_per_million ? ($r->inputTokens + $r->outputTokens) / 1_000_000 * $r->provider->cost_per_million : 0;
        AiUsage::create([
            'user_id' => $user?->id, 'ai_provider_id' => $r->provider->id, 'model' => $r->model,
            'input_tokens' => $r->inputTokens, 'output_tokens' => $r->outputTokens, 'credits' => $r->credits, 'cost' => $cost,
        ]);
        if ($user && ! $user->is_admin) {
            $user->forceFill(['credits' => max(0, $user->credits - $r->credits)])->save();
        }
    }

    protected function openAi(AiProvider $p): PendingRequest
    {
        $req = Http::timeout(120)->acceptJson();
        if ($p->api_key) {
            $req = $req->withToken($p->api_key);
        }
        if ($p->driver === 'openrouter') {
            $req = $req->withHeaders(['HTTP-Referer' => config('app.url'), 'X-Title' => Settings::brand()]);
        }

        return $req;
    }

    protected function anthropic(AiProvider $p): PendingRequest
    {
        return Http::timeout(120)->acceptJson()->withHeaders([
            'x-api-key' => (string) $p->api_key,
            'anthropic-version' => '2023-06-01',
        ]);
    }

    /**
     * Pull the first JSON object out of a model reply (tolerates ``` fences / prose).
     */
    public static function extractJson(string $text): ?array
    {
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }
}
