<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Tests\TestCase;

class ProjectFlowTest extends TestCase
{
    protected function makeProject(User $user): Project
    {
        $this->actingAs($user)->post('/studio/projects', ['kind' => 'Website', 'idea' => 'A website for my bakery where people can see the menu'])
            ->assertRedirect();

        return $user->projects()->latest('id')->first();
    }

    public function test_plan_build_preview_publish_without_ai(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);
        $this->assertSame('Website For My Bakery', $project->name);
        $this->assertNotEmpty($project->spec['features']);

        // Simple plan: drop feature 0, add one, then build (queue is sync in tests)
        $count = count($project->spec['features']);
        $keep = range(1, $count - 1);
        $this->post(route('studio.projects.plan.update', $project), ['keep' => array_merge([-1], $keep), 'extra' => 'Online cake orders', 'action' => 'build'])
            ->assertRedirect(route('studio.projects.builder', $project));

        $project->refresh();
        $this->assertSame('ready', $project->status);
        $this->assertContains('Online cake orders', $project->spec['features']);
        $this->assertCount($count, $project->spec['features']);
        $this->assertTrue($project->files()->where('path', 'index.html')->exists());
        $this->assertSame(['done', 'done', 'done', 'done', 'waiting'], $project->agentTasks()->orderBy('id')->pluck('status')->all());

        $this->get(route('studio.projects.builder', $project))->assertOk()->assertSee('Website For My Bakery');
        $this->get(route('studio.projects.code', [$project, 'file' => 'index.html']))->assertOk();
        $this->get(route('studio.projects.agents', $project))->assertOk()->assertSee('Developer Agent');

        // Token-protected, sandboxed preview works without a session
        auth()->logout();
        $this->get($project->previewUrl())->assertOk()
            ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts allow-forms allow-popups allow-modals allow-downloads')
            ->assertSee('<base href="'.$project->previewUrl().'/">', false)
            ->assertSee('studio-edit', false);
        $this->get($project->previewUrl().'/styles.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8');
        $this->get(route('preview', [$project->slug, str_repeat('0', 32)]))->assertNotFound();

        // Publish
        $this->actingAs($user)->post(route('studio.projects.deploy.store', $project), ['subdomain' => 'bakery'])->assertRedirect();
        $d = $project->deployments()->first();
        $this->assertSame('live', $d->status);
        $this->get('/p/bakery')->assertOk()->assertSee('Website For My Bakery')->assertDontSee('studio-edit', false);
        $this->get('/p/bakery/app.js')->assertOk();
        $this->get('/p/nope')->assertNotFound();

        // Edit, republish, roll back
        $this->postJson(route('studio.projects.files.save', $project), ['path' => 'index.html', 'content' => '<html><head><title>v2</title></head><body>Version two</body></html>'])->assertOk();
        $this->post(route('studio.projects.deploy.store', $project), ['subdomain' => 'bakery']);
        $this->get('/p/bakery')->assertSee('Version two');
        $this->assertSame('superseded', $d->fresh()->status);
        $this->post(route('studio.projects.deploy.rollback', [$project, $d]))->assertRedirect();
        $this->get('/p/bakery')->assertDontSee('Version two');

        // Download
        $this->get(route('studio.projects.download', $project))->assertOk()->assertDownload('website-for-my-bakery.zip');
    }

    public function test_publish_address_must_be_unique_across_projects(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $pa = $this->makeProject($a);
        $pa->files()->create(['path' => 'index.html', 'content' => '<title>a</title>']);
        $this->actingAs($a)->post(route('studio.projects.deploy.store', $pa), ['subdomain' => 'shop']);

        $pb = $this->makeProject($b);
        $pb->files()->create(['path' => 'index.html', 'content' => '<title>b</title>']);
        $this->actingAs($b)->post(route('studio.projects.deploy.store', $pb), ['subdomain' => 'shop'])->assertSessionHasErrors('subdomain');
        $this->assertSame(1, Deployment::count());
    }

    public function test_users_cannot_open_each_others_projects(): void
    {
        $owner = User::factory()->create();
        $project = $this->makeProject($owner);
        $other = User::factory()->create();

        $this->actingAs($other);
        $this->get(route('studio.projects.builder', $project))->assertNotFound();
        $this->get(route('studio.projects.code', $project))->assertNotFound();
        $this->postJson(route('studio.projects.messages', $project), ['text' => 'hi'])->assertNotFound();
        $this->delete(route('studio.projects.destroy', $project))->assertNotFound();
        $this->assertTrue($project->fresh() !== null);
    }

    public function test_file_paths_are_sanitised(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);
        $this->postJson(route('studio.projects.files.save', $project), ['path' => '../../.env', 'content' => 'x'])->assertStatus(422);
        $this->postJson(route('studio.projects.files.save', $project), ['path' => 'pages/about.html', 'content' => 'x'])->assertOk();
        $this->assertTrue($project->files()->where('path', 'pages/about.html')->exists());
    }

    public function test_plan_project_limit_is_enforced(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::where('slug', 'starter')->value('id')]);
        $this->makeProject($user);
        $this->actingAs($user)->post('/studio/projects', ['kind' => 'Website', 'idea' => 'A second website please'])
            ->assertSessionHasErrors('idea');
        $this->assertSame(1, $user->projects()->count());
    }
}
