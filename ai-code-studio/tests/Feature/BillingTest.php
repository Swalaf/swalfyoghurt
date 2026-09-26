<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\Billing;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BillingTest extends TestCase
{
    protected function gateways(): void
    {
        Settings::set('gateways', [
            'stripe' => ['public' => 'pk', 'secret' => encrypt('sk_test_stripe'), 'webhook' => encrypt('whsec_test')],
            'paystack' => ['public' => 'pk', 'secret' => encrypt('sk_test_paystack')],
        ]);
    }

    public function test_billing_page_lists_plans_and_gateways(): void
    {
        $this->gateways();
        $this->actingAs(User::factory()->create())->get('/studio/billing')->assertOk()
            ->assertSee('Professional')->assertSee('Pay with Stripe')->assertSee('Pay with Paystack');
    }

    public function test_stripe_checkout_return_activates_plan(): void
    {
        $this->gateways();
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1']),
            'api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response(['payment_status' => 'paid', 'amount_total' => 2900]),
        ]);
        $user = User::factory()->create(['credits' => 10]);
        $pro = Plan::where('slug', 'professional')->first();

        $this->actingAs($user)->post('/studio/billing/checkout', ['plan_id' => $pro->id, 'period' => 'monthly', 'gateway' => 'stripe'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_1');
        $payment = Payment::first();
        $this->assertSame(2900, $payment->amount_cents);
        $this->assertSame('cs_test_1', $payment->reference);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'checkout/sessions') && $r['line_items'][0]['price_data']['unit_amount'] === 2900 && $r->hasHeader('Authorization', 'Bearer sk_test_stripe'));

        $this->get(URL::signedRoute('billing.return', $payment))->assertRedirect(route('studio.billing'));
        $user->refresh();
        $this->assertSame($pro->id, $user->plan_id);
        $this->assertSame(12000, $user->credits);
        $this->assertTrue($user->plan_expires_at->between(now()->addDays(27), now()->addDays(32)));
        $this->assertSame('paid', $payment->fresh()->status);

        // Unsigned return URL is rejected
        $this->get(route('billing.return', $payment))->assertForbidden();
    }

    public function test_yearly_price_and_paystack_webhook_signature(): void
    {
        $this->gateways();
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['data' => ['authorization_url' => 'https://checkout.paystack.com/x']])]);
        $user = User::factory()->create();
        $biz = Plan::where('slug', 'business')->first();
        $this->actingAs($user)->post('/studio/billing/checkout', ['plan_id' => $biz->id, 'period' => 'yearly', 'gateway' => 'paystack'])->assertRedirect('https://checkout.paystack.com/x');
        $payment = Payment::first();
        $this->assertSame((int) round(79 * 12 * 0.8 * 100), $payment->amount_cents);

        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
        // Bad signature
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'nope', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);
        // Good signature
        $sig = hash_hmac('sha512', $body, 'sk_test_paystack');
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame($biz->id, $user->fresh()->plan_id);
        $this->assertSame('yearly', $user->fresh()->plan_period);
        // Replay is harmless
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame(1, Payment::where('status', 'paid')->count());
    }

    public function test_stripe_webhook_signature(): void
    {
        $this->gateways();
        $payment = Payment::create(['user_id' => User::factory()->create()->id, 'plan_id' => Plan::where('slug', 'professional')->value('id'), 'gateway' => 'stripe', 'reference' => 'cs_abc', 'amount_cents' => 2900, 'currency' => 'USD', 'period' => 'monthly']);
        $body = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_abc', 'payment_status' => 'paid']]]);
        $t = time();
        $sig = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$body, 'whsec_test');
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.$t.',v1=bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_expired_plans_drop_to_free_and_credits_refill(): void
    {
        $starter = Plan::where('slug', 'starter')->first();
        $pro = Plan::where('slug', 'professional')->first();
        $expired = User::factory()->create(['plan_id' => $pro->id, 'plan_expires_at' => now()->subDay(), 'credits' => 9000]);
        $refill = User::factory()->create(['plan_id' => $starter->id, 'credits' => 3, 'credits_reset_at' => now()->subMinute()]);
        $fresh = User::factory()->create(['plan_id' => $starter->id, 'credits' => 3, 'credits_reset_at' => now()->addDays(10)]);

        Billing::maintain();

        $this->assertSame($starter->id, $expired->fresh()->plan_id);
        $this->assertSame(500, $expired->fresh()->credits);
        $this->assertSame(500, $refill->fresh()->credits);
        $this->assertSame(3, $fresh->fresh()->credits);
    }
}
