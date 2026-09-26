<?php

namespace App\Services\Billing;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Stripe Checkout (one-time payments) via the REST API — no SDK needed. */
class StripeGateway implements Gateway
{
    public function __construct(protected string $secret, protected string $webhookSecret = '') {}

    public function checkout(Payment $payment, string $email, string $description, string $returnUrl, string $cancelUrl): string
    {
        $res = Http::asForm()->withToken($this->secret)->timeout(30)->post('https://api.stripe.com/v1/checkout/sessions', [
            'mode' => 'payment',
            'customer_email' => $email,
            'client_reference_id' => (string) $payment->id,
            'success_url' => $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'unit_amount' => $payment->amount_cents,
                    'product_data' => ['name' => $description],
                ],
            ]],
            'metadata' => ['payment_id' => $payment->id],
        ])->throw();

        $payment->update(['reference' => $res->json('id')]);

        return (string) $res->json('url');
    }

    public function isPaid(Payment $payment, Request $request): bool
    {
        if (! $payment->reference) {
            return false;
        }
        $res = Http::withToken($this->secret)->timeout(30)->get('https://api.stripe.com/v1/checkout/sessions/'.$payment->reference);

        return $res->successful() && $res->json('payment_status') === 'paid'
            && (int) $res->json('amount_total') === $payment->amount_cents;
    }

    public function paidReferenceFromWebhook(Request $request): ?string
    {
        abort_unless($this->webhookSecret !== '', 400, 'Stripe webhook secret is not configured.');
        $header = (string) $request->header('Stripe-Signature');
        parse_str(str_replace(',', '&', $header), $parts);
        $timestamp = (int) ($parts['t'] ?? 0);
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $this->webhookSecret);
        $signatures = array_filter(array_map(fn ($p) => str_starts_with($p, 'v1=') ? substr($p, 3) : null, explode(',', $header)));
        $valid = collect($signatures)->contains(fn ($sig) => hash_equals($expected, $sig));
        abort_unless($valid && abs(time() - $timestamp) <= 300, 400, 'Invalid signature.');

        $event = $request->json()->all();
        if (($event['type'] ?? '') === 'checkout.session.completed' && ($event['data']['object']['payment_status'] ?? '') === 'paid') {
            return $event['data']['object']['id'] ?? null;
        }

        return null;
    }
}
