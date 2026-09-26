<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('price_cents')->nullable(); // null = custom pricing
            $table->unsignedInteger('credits')->nullable();     // null = custom
            $table->unsignedInteger('max_projects')->nullable(); // null = unlimited
            $table->unsignedInteger('seats')->nullable();        // null = unlimited
            $table->string('support')->default('Community');
            $table->json('features')->nullable();
            $table->string('tag')->nullable();
            $table->string('cta')->nullable();
            $table->boolean('highlighted')->default(false);
            $table->string('status', 20)->default('live');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 30);
            $table->text('api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->string('default_model')->nullable();
            $table->string('models')->nullable();
            $table->string('status', 20)->default('not_configured');
            $table->unsignedInteger('priority')->default(10);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->decimal('cost_per_million', 8, 2)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('kind')->default('Web app');
            $table->text('idea')->nullable();
            $table->json('spec')->nullable();
            $table->json('stack')->nullable();
            $table->string('status', 20)->default('draft'); // draft, planned, building, ready, failed
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('branch')->default('main');
            $table->timestamps();
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->longText('content');
            $table->timestamps();
            $table->unique(['project_id', 'path']);
        });

        Schema::create('project_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20); // user, assistant, system
            $table->string('channel', 20)->default('builder'); // builder, workspace
            $table->text('content');
            $table->foreignId('change_set_id')->nullable();
            $table->unsignedInteger('credits')->default(0);
            $table->timestamps();
        });

        Schema::create('change_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->string('status', 20)->default('pending'); // pending, applied, rejected
            $table->timestamps();
        });

        Schema::create('change_set_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_set_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('action', 10); // add, modify, delete
            $table->longText('content')->nullable();
            $table->longText('previous')->nullable();
            $table->unsignedInteger('additions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
        });

        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('run');
            $table->string('agent', 30);
            $table->string('status', 20)->default('queued'); // queued, working, waiting, done, failed
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('task');
            $table->string('model')->nullable();
            $table->json('files')->nullable();
            $table->json('log')->nullable();
            $table->timestamps();
        });

        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('subdomain');
            $table->string('message');
            $table->string('commit', 12);
            $table->string('stage', 20)->default('build'); // build, test, package, deploy, verify, done
            $table->string('status', 20)->default('running'); // running, live, superseded, failed
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('snapshot')->nullable();
            $table->json('log')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 10)->default('INFO');
            $table->string('category', 30);
            $table->string('message');
            $table->foreignId('user_id')->nullable()->index();
            $table->string('actor')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('ai_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('ai_provider_id')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('credits')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('plan_id')->nullable();
            $table->string('gateway', 20);
            $table->string('reference')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payments', 'ai_usage', 'activity_logs', 'deployments', 'agent_tasks', 'change_set_files', 'change_sets', 'project_messages', 'project_files', 'projects', 'ai_providers', 'plans', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
