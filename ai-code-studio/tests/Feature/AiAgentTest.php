<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAgentTest extends TestCase
{
    protected function connect(string $driver, int $priority = 1): AiProvider
    {
        $p = AiProvider::where('driver', $driver)->first();
        $p->update(['api_key' => 'sk-test-'.$driver, 'status' => 'connected', 'priority' => $priority]);

        return $p;
    }

    protected function reply(array $payload): array
    {
        return ['model' => 'test-model', 'choices' => [['message' => ['content' => json_encode($payload)]]], 'usage' => ['prompt_tokens' => 1500, 'completion_tokens' => 600]];
    }

    public function test_chat_proposes_change_set_that_can_be_approved_and_undone(): void
    {
        $this->connect('openrouter');
        Http::fake(['openrouter.ai/*' => Http::response($this->reply([
            'reply' => 'I made the header purple.',
            'summary' => 'Purple header',
            'files' => [['path' => 'index.html', 'action' => 'modify', 'content' => "<title>x</title>\n<h1 style=\"color:purple\">Hi</h1>"], ['path' => 'about.html', 'action' => 'add', 'content' => '<title>About</title>']],
        ]))]);

        $user = User::factory()->create(['credits' => 100]);
        $project = Project::create(['user_id' => $user->id, 'name' => 'Demo', 'slug' => 'demo', 'idea' => 'demo', 'kind' => 'Website', 'status' => 'ready']);
        $project->files()->create(['path' => 'index.html', 'content' => "<title>x</title>\n<h1>Hi</h1>"]);

        $res = $this->actingAs($user)->postJson(route('studio.projects.messages', $project), ['text' => 'Make the header purple'])->assertOk();
        $res->assertJsonPath('pending.summary', 'Purple header')
            ->assertJsonPath('pending.additions', 2)
            ->assertJsonPath('pending.deletions', 1)
            ->assertJsonPath('messages.1.text', 'I made the header purple.');

        // 2,100 tokens → 3 credits
        $this->assertSame(97, $user->fresh()->credits);
        Http::assertSent(fn ($r) => $r->url() === 'https://openrouter.ai/api/v1/chat/completions' && $r->hasHeader('Authorization', 'Bearer sk-test-openrouter'));

        $this->postJson($res->json('pending.approve'))->assertOk()->assertJsonPath('pending', null);
        $this->assertStringContainsString('purple', $project->files()->where('path', 'index.html')->value('content'));
        $this->assertTrue($project->files()->where('path', 'about.html')->exists());

        $this->postJson(route('studio.projects.undo', $project))->assertOk();
        $this->assertStringNotContainsString('purple', $project->files()->where('path', 'index.html')->value('content'));
        $this->assertFalse($project->files()->where('path', 'about.html')->exists());
    }

    public function test_falls_back_to_next_provider_when_first_fails(): void
    {
        $this->connect('openrouter', 1);
        $this->connect('anthropic', 2);
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => 'down'], 500),
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => json_encode(['reply' => 'Hello from Claude', 'files' => []])]], 'usage' => ['input_tokens' => 10, 'output_tokens' => 5]]),
        ]);
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'name' => 'Demo', 'slug' => 'demo', 'idea' => 'demo', 'kind' => 'Website']);

        $this->actingAs($user)->postJson(route('studio.projects.messages', $project), ['text' => 'hi', 'mode' => 'Ask'])
            ->assertOk()->assertJsonPath('messages.1.text', 'Hello from Claude');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'anthropic.com/v1/messages') && $r->hasHeader('x-api-key', 'sk-test-anthropic'));
    }

    public function test_without_provider_or_credits_the_agent_explains(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'name' => 'Demo', 'slug' => 'demo', 'idea' => 'demo', 'kind' => 'Website']);
        $this->actingAs($user)->postJson(route('studio.projects.messages', $project), ['text' => 'hi'])
            ->assertOk()->assertJsonPath('messages.1.text', 'No AI provider is connected yet. An admin can add one under Admin → AI providers.');

        $this->connect('openrouter');
        $user->update(['credits' => 0]);
        $this->postJson(route('studio.projects.messages', $project), ['text' => 'hi'])
            ->assertJsonPath('messages.3.text', 'You have run out of AI credits. Upgrade your plan or ask an admin to top you up.');
    }

    public function test_build_uses_ai_generated_files_when_provider_connected(): void
    {
        $this->connect('openrouter');
        Http::fake(['openrouter.ai/*' => Http::sequence()
            ->push($this->reply(['spec' => ['features' => ['Menu', 'Orders'], 'roles' => ['Owner'], 'pages' => ['/'], 'database' => ['orders'], 'api' => ['POST /orders'], 'phases' => ['UI']], 'stack' => ['frontend' => 'Vue']]))
            ->push($this->reply(['reply' => 'Built', 'files' => [['path' => 'index.html', 'content' => '<title>AI bakery</title>'], ['path' => '../evil.php', 'content' => 'x']]]))]);

        $user = User::factory()->create(['credits' => 1000]);
        $this->actingAs($user)->post('/studio/projects', ['kind' => 'Website', 'idea' => 'A bakery site'])->assertRedirect();
        $project = $user->projects()->first();
        $this->assertSame(['Menu', 'Orders'], $project->spec['features']);

        $this->post(route('studio.projects.build', $project));
        $this->assertSame('<title>AI bakery</title>', $project->files()->where('path', 'index.html')->value('content'));
        $this->assertFalse($project->files()->where('path', 'like', '%evil%')->exists());
    }

    public function test_provider_test_marks_connection_status(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['data' => [['id' => 'a'], ['id' => 'b']]])]);
        $admin = User::factory()->admin()->create();
        $groq = AiProvider::where('driver', 'groq')->first();
        $this->actingAs($admin)->put(route('admin.providers.update', $groq), ['api_key' => 'gsk_live_123'])->assertRedirect(route('admin.providers'));
        $groq->refresh();
        $this->assertSame('connected', $groq->status);
        $this->assertSame('2 models', $groq->models);
        $this->assertSame('gsk_live_123', $groq->api_key);
        $this->assertStringNotContainsString('gsk_live_123', \DB::table('ai_providers')->where('id', $groq->id)->value('api_key'));
    }
}
