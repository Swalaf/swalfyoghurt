<?php

namespace Tests\Feature;

use App\Models\AbuseReport;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaunchFeaturesTest extends TestCase
{
    protected function published(User $user, string $sub = 'shop'): Project
    {
        $p = Project::create(['user_id' => $user->id, 'name' => 'Shop', 'slug' => 'shop-'.$sub, 'kind' => 'Website', 'idea' => 'shop']);
        $p->files()->create(['path' => 'index.html', 'content' => '<html><head><title>Shop</title></head><body>Hello shop</body></html>']);
        $this->actingAs($user)->post(route('studio.projects.deploy.store', $p), ['subdomain' => $sub])->assertSessionHasNoErrors();

        return $p;
    }

    public function test_published_files_live_on_the_storage_disk_with_report_badge(): void
    {
        Storage::fake('published');
        $user = User::factory()->create();
        $p = $this->published($user);
        $d = Deployment::first();
        $this->assertSame('deployments/'.$d->id, $d->storage_path);
        $this->assertNull($d->snapshot);
        Storage::disk('published')->assertExists('deployments/'.$d->id.'/index.html');
        $this->get('/p/shop')->assertOk()->assertSee('Hello shop')->assertSee('/report/shop', false);

        Settings::set('publish_badge', false);
        $this->get('/p/shop')->assertDontSee('/report/shop', false);

        $p->delete();
        Storage::disk('published')->assertMissing('deployments/'.$d->id.'/index.html');
    }

    public function test_report_takedown_and_restore(): void
    {
        Storage::fake('published');
        $owner = User::factory()->create();
        $this->published($owner);
        auth()->logout();

        $this->get('/report/shop')->assertOk();
        $this->post('/report/shop', ['reason' => 'phishing', 'details' => 'Fake bank login'])->assertSessionHas('reported');
        $report = AbuseReport::first();
        $this->assertSame('phishing', $report->reason);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Fake bank login');
        $this->post(route('admin.reports.takedown', $report), ['reason' => 'Phishing', 'suspend' => '1']);
        $this->assertTrue($owner->fresh()->isSuspended());
        auth()->logout();
        $this->get('/p/shop')->assertStatus(451)->assertSee('removed');

        $this->actingAs($admin)->post(route('admin.reports.restore', $report));
        $this->get('/p/shop')->assertOk();
    }

    public function test_publish_limits_and_reserved_names(): void
    {
        Storage::fake('published');
        Settings::set('publish_daily_limit', '1');
        $user = User::factory()->create();
        $p = $this->published($user);
        $this->post(route('studio.projects.deploy.store', $p), ['subdomain' => 'shop'])->assertSessionHasErrors('subdomain');

        $u2 = User::factory()->create();
        $p2 = Project::create(['user_id' => $u2->id, 'name' => 'X', 'slug' => 'x', 'kind' => 'Website']);
        $p2->files()->create(['path' => 'index.html', 'content' => 'x']);
        foreach (['admin', 'paypal-verify', 'secure-bank'] as $bad) {
            $this->actingAs($u2)->post(route('studio.projects.deploy.store', $p2), ['subdomain' => $bad])->assertSessionHasErrors('subdomain');
        }
    }

    public function test_legal_pages_consent_and_cookie_notice(): void
    {
        Settings::set(['company_name' => 'Acme Ltd', 'legal_address' => '1 Main St']);
        $this->get('/terms')->assertOk()->assertSee('Terms of Service')->assertSee('Acme Ltd')->assertSee('1 Main St');
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/login')->assertSee('no tracking');

        Settings::set('legal_terms', "# Our rules\n\n<script>alert(1)</script> Be nice to {company}.");
        $this->get('/terms')->assertSee('Our rules')->assertSee('Be nice to Acme Ltd')->assertDontSee('<script>alert(1)</script>', false);

        $this->post('/register', ['name' => 'N', 'email' => 'n@x.com', 'password' => 'Secret123!'])->assertSessionHasErrors('terms');
        Settings::set('require_verification', false);
        $this->post('/register', ['name' => 'N', 'email' => 'n@x.com', 'password' => 'Secret123!', 'terms' => '1']);
        $this->assertNotNull(User::whereEmail('n@x.com')->first()->terms_accepted_at);
    }

    public function test_data_export_and_account_deletion(): void
    {
        Storage::fake('published');
        $user = User::factory()->create();
        $p = $this->published($user);
        $this->get(route('studio.account.export'))->assertOk()->assertDownload();

        $this->delete(route('studio.account.destroy'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->delete(route('studio.account.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertNull($p->fresh());
        $this->assertSame(0, Deployment::count());
    }

    public function test_language_detection_and_switching(): void
    {
        $this->get('/login', ['Accept-Language' => 'es-ES,es;q=0.9'])->assertSee('Iniciar sesión')->assertSee('lang="es"', false);
        $this->get('/login?lang=fr')->assertSee('Se connecter');
        $this->get('/login')->assertSee('Se connecter'); // remembered in session
        $this->get('/?lang=pt')->assertSee('Crie qualquer coisa com IA.');

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('studio.account.locale'), ['locale' => 'es']);
        $this->assertSame('es', $user->fresh()->locale);
        $this->get('/studio')->assertSee('Mis apps');
    }

    public function test_security_headers(): void
    {
        $this->get('/login')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
