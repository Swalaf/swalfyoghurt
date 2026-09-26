<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Payment;
use App\Models\Release;
use App\Models\User;
use App\Notifications\LicenseIssued;
use App\Services\Licensing\LicenseClient;
use App\Services\Licensing\LicenseServer;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LicensingTest extends TestCase
{
    public function test_server_routes_are_hidden_unless_enabled(): void
    {
        config(['studio.license_server' => false]);
        $this->postJson('/api/license/activate', ['key' => 'x', 'domain' => 'a.com'])->assertNotFound();
        $this->get('/buy')->assertNotFound();
    }

    public function test_activation_binds_one_production_domain(): void
    {
        config(['studio.license_server' => true]);
        $license = app(LicenseServer::class)->issue(['type' => 'extended', 'email' => 'b@x.com']);

        $this->postJson('/api/license/activate', ['key' => $license->key, 'domain' => 'https://www.shop.com/', 'instance' => 'i1'])
            ->assertOk()->assertJson(['ok' => true, 'type' => 'extended', 'domain' => 'shop.com']);
        $this->postJson('/api/license/activate', ['key' => $license->key, 'domain' => 'localhost'])->assertOk(); // dev is free
        $this->postJson('/api/license/activate', ['key' => $license->key, 'domain' => 'other.com'])->assertStatus(409)->assertJson(['status' => 'in_use']);
        $this->postJson('/api/license/verify', ['key' => $license->key, 'domain' => 'shop.com'])->assertOk();
        $this->postJson('/api/license/deactivate', ['key' => $license->key, 'domain' => 'shop.com'])->assertOk();
        $this->postJson('/api/license/activate', ['key' => $license->key, 'domain' => 'other.com'])->assertOk();

        $license->update(['status' => 'revoked']);
        $this->postJson('/api/license/verify', ['key' => $license->key, 'domain' => 'other.com'])->assertForbidden()->assertJson(['status' => 'revoked']);
        $this->postJson('/api/license/activate', ['key' => 'ACS-NOPE-NOPE-NOPE-NOPE', 'domain' => 'x.com'])->assertNotFound();
    }

    public function test_envato_purchase_codes_are_accepted(): void
    {
        config(['studio.license_server' => true]);
        Settings::set('envato_token', encrypt('envato-token'));
        Http::fake(['api.envato.com/*' => Http::response(['item' => ['id' => 1], 'license' => 'Extended License', 'buyer' => 'jane', 'supported_until' => now()->addMonths(6)->toIso8601String()])]);
        $code = '12345678-abcd-abcd-abcd-1234567890ab';
        $this->postJson('/api/license/activate', ['key' => $code, 'domain' => 'jane.com'])->assertOk()->assertJson(['type' => 'extended']);
        $this->assertSame('envato', License::first()->source);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer envato-token'));
    }

    public function test_updates_require_support_and_download_is_signed(): void
    {
        config(['studio.license_server' => true]);
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.releases.store'), ['version' => '1.4.0', 'notes' => 'New stuff', 'file' => UploadedFile::fake()->create('r.zip', 10, 'application/zip')])->assertSessionHasNoErrors();
        $this->assertSame(1, Release::count());

        $license = app(LicenseServer::class)->issue(['type' => 'regular']);
        $url = $this->getJson('/api/updates/latest?'.http_build_query(['key' => $license->key, 'domain' => 'a.com']))->assertOk()->assertJson(['version' => '1.4.0'])->json('download_url');
        $this->assertNotNull($url);
        $this->get($url)->assertOk()->assertDownload('ai-code-studio-1.4.0.zip');
        $this->get(preg_replace('/signature=[^&]+/', 'signature=x', $url))->assertForbidden();

        $license->update(['supported_until' => now()->subDay()]);
        $this->getJson('/api/updates/latest?'.http_build_query(['key' => $license->key, 'domain' => 'a.com']))->assertJson(['download_url' => null]);
    }

    public function test_admin_can_issue_and_revoke_licences(): void
    {
        config(['studio.license_server' => true]);
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/licenses')->assertOk();
        $this->post(route('admin.licenses.store'), ['type' => 'regular', 'email' => 'c@x.com', 'months' => 6])->assertSessionHasNoErrors();
        $l = License::first();
        $this->assertMatchesRegularExpression('/^ACS(-[A-Z0-9]{4}){4}$/', $l->key);
        $this->post(route('admin.licenses.update', $l), ['action' => 'revoke']);
        $this->assertSame('revoked', $l->fresh()->status);
    }

    public function test_client_activation_against_remote_server(): void
    {
        config(['studio.license_url' => 'https://lic.example.com']);
        Http::fake(['lic.example.com/api/license/activate' => Http::response(['ok' => true, 'status' => 'active', 'type' => 'regular', 'supported_until' => now()->addYear()->toIso8601String()])]);
        $res = app(LicenseClient::class)->activate('ACS-AAAA-BBBB-CCCC-DDDD', 'mysite.com');
        $this->assertTrue($res['ok']);
        $this->assertSame('active', Settings::get('license_status'));
        $this->assertSame('regular', Settings::get('license_type'));
        Http::assertSent(fn ($r) => $r['domain'] === 'mysite.com' && $r['key'] === 'ACS-AAAA-BBBB-CCCC-DDDD');

        Http::fake(['lic.example.com/*' => Http::response(['ok' => false, 'status' => 'revoked', 'message' => 'Revoked'], 403)]);
        app(LicenseClient::class)->verify();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSee('licence has been revoked');
    }

    public function test_license_storefront_purchase_issues_key(): void
    {
        config(['studio.license_server' => true]);
        Settings::set('gateways', ['stripe' => ['secret' => encrypt('sk'), 'webhook' => encrypt('wh')]]);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_l', 'url' => 'https://checkout.stripe.com/l']),
            'api.stripe.com/v1/checkout/sessions/cs_l' => Http::response(['payment_status' => 'paid', 'amount_total' => 5900]),
        ]);
        Notification::fake();
        $this->get('/buy')->assertOk()->assertSee('Regular licence');
        $this->post('/buy', ['type' => 'regular', 'name' => 'Buyer', 'email' => 'buyer@x.com', 'gateway' => 'stripe', 'agree' => '1'])->assertRedirect('https://checkout.stripe.com/l');
        $payment = Payment::first();
        $this->followingRedirects()->get(URL::signedRoute('billing.return', $payment))->assertOk()->assertSee(License::first()->key);
        Notification::assertSentOnDemand(LicenseIssued::class);
    }
}
