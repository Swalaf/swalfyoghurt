<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Support\Settings;
use Tests\TestCase;

class AdminTest extends TestCase
{
    public function test_non_admins_are_blocked(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertForbidden();
        $this->get('/studio/providers')->assertForbidden();
        $this->post('/admin/routing', ['routing' => 'Cheapest'])->assertForbidden();
    }

    public function test_all_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $this->actingAs($admin);
        foreach (['/admin', '/admin/users', '/admin/users?user='.$customer->id, '/admin/plans', '/admin/plans?edit=new', '/admin/providers', '/admin/providers?connect=1', '/admin/branding', '/admin/settings/Email', '/admin/settings/Security', '/admin/health', '/admin/logs', '/admin/license', '/studio/providers', '/studio/brand', '/studio/account'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/users/export')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_user_management_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $u = User::factory()->create(['credits' => 10]);
        $this->actingAs($admin);

        $this->post(route('admin.users.credits', $u), ['amount' => 500]);
        $this->assertSame(510, $u->fresh()->credits);

        $biz = Plan::where('slug', 'business')->first();
        $this->post(route('admin.users.plan', $u), ['plan_id' => $biz->id]);
        $this->assertSame($biz->id, $u->fresh()->plan_id);
        $this->assertSame(40000, $u->fresh()->credits);

        $this->post(route('admin.users.status', $u), ['status' => 'suspended']);
        $this->assertTrue($u->fresh()->isSuspended());

        // Impersonate and return
        $this->post(route('admin.users.impersonate', $u))->assertRedirect(route('studio.dashboard'));
        $this->assertAuthenticatedAs($u);
        $this->get('/studio')->assertOk()->assertSee('Return to admin');
        $this->post(route('impersonation.stop'))->assertRedirect(route('admin.users'));
        $this->assertAuthenticatedAs($admin);

        $this->delete(route('admin.users.destroy', $admin))->assertStatus(422);
        $this->delete(route('admin.users.destroy', $u))->assertRedirect();
        $this->assertNull($u->fresh());
    }

    public function test_plan_crud(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.plans.store'), ['name' => 'Agency', 'price' => '149', 'credits' => 90000, 'seats' => 10, 'support' => 'Priority'])->assertRedirect();
        $plan = Plan::where('name', 'Agency')->first();
        $this->assertSame(14900, $plan->price_cents);
        $this->assertNull($plan->max_projects);
        $this->put(route('admin.plans.update', $plan), ['name' => 'Agency Plus', 'price' => '', 'status' => 'hidden']);
        $this->assertTrue($plan->fresh()->isCustom());
        $this->delete(route('admin.plans.destroy', $plan));
        $this->assertNull($plan->fresh());
    }

    public function test_settings_branding_and_maintenance(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->post(route('admin.settings.save', 'Sign-up & login'), ['allow_signups' => '0', 'require_verification' => '1', 'require_2fa' => '0']);
        $this->assertFalse(Settings::get('allow_signups'));

        $this->post(route('admin.branding.save'), ['brand_name' => 'Buildwise', 'brand_color' => '#2F6FEB'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Buildwise')->assertSee('--accent:#2F6FEB', false);

        $this->post(route('admin.settings.save', 'Maintenance'), ['maintenance' => '1', 'maintenance_message' => 'Back at 14:30']);
        $this->get('/admin')->assertOk(); // admins still get in
        auth()->logout();
        $this->get('/')->assertStatus(503)->assertSee('Back at 14:30');
        $this->get('/login')->assertOk();
    }

    public function test_gateway_keys_are_stored_encrypted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.gateways.update', 'paystack'), ['public' => 'pk_test', 'secret' => 'sk_test_secret']);
        $g = Settings::get('gateways')['paystack'];
        $this->assertSame('sk_test_secret', decrypt($g['secret']));
        $this->assertStringNotContainsString('sk_test_secret', \DB::table('settings')->where('key', 'gateways')->value('value'));
    }
}
