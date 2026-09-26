<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Default plans (from the pricing design) and the AI provider slots.
 * Safe to run more than once.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['Starter', 'starter', 'Try it on a real project.', 0, 500, 1, 1, 'Community', ['1 project', '500 AI credits / month', 'Live preview', 'Community support'], null, 'Start free', false, 'live'],
            ['Professional', 'professional', 'For solo developers shipping regularly.', 2900, 12000, 10, 1, 'Email', ['10 projects', '12,000 AI credits / month', 'All AI agents', 'One-click deploy'], 'POPULAR', 'Get started', true, 'live'],
            ['Business', 'business', 'For teams and agencies.', 7900, 40000, null, 5, 'Priority', ['Unlimited projects', '40,000 AI credits / month', '5 team seats', 'Workflows & API access'], null, 'Get started', false, 'live'],
            ['Enterprise', 'enterprise', 'Custom limits, SSO and support.', null, null, null, null, 'Dedicated', ['Custom credits', 'SSO & audit logs', 'Dedicated support', 'SLA'], null, 'Contact sales', false, 'hidden'],
        ];
        foreach ($plans as $i => [$name, $slug, $desc, $price, $credits, $projects, $seats, $support, $features, $tag, $cta, $hi, $status]) {
            Plan::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'description' => $desc, 'price_cents' => $price, 'credits' => $credits,
                'max_projects' => $projects, 'seats' => $seats, 'support' => $support, 'features' => $features,
                'tag' => $tag, 'cta' => $cta, 'highlighted' => $hi, 'status' => $status, 'sort' => $i,
            ]);
        }

        $costs = ['openrouter' => 3.00, 'anthropic' => 3.00, 'gemini' => 1.25, 'groq' => 0.59, 'openai' => 2.50, 'custom' => null];
        $priority = 1;
        foreach (AiProvider::DRIVERS as $driver => $meta) {
            AiProvider::firstOrCreate(['driver' => $driver], [
                'name' => $meta['name'], 'status' => 'not_configured', 'priority' => $priority++, 'cost_per_million' => $costs[$driver],
            ]);
        }
    }
}
