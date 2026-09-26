<?php

namespace App\Services\Billing;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Paystack Standard (redirect) via the REST API. */
class PaystackGateway implements Gateway
{
    public function __construct(protected string $secret) {}

    public function checkout(Payment $payment, string $email, string $description, string $returnUrl, string $cancelUrl): string
    {
        $reference = 'acs_'.$payment->id.'_'.Str::lower(Str::random(10));
        $res = Http::withToken($this->secret)->acceptJson()->timeout(30)->post('https://api.paystack.co/transaction/initialize', [
            'email' => $email,
            'amount' => $payment->amount_cents,
            'currency' => strtoupper($payment->currency),
            'reference' => $reference,
            'callback_url' => $returnUrl,
            'metadata' => ['payment_id' => $payment->id, 'description' => $description, 'cancel_action' => $cancelUrl],
        ])->throw();

        $payment->update(['reference' => $reference]);

        return (string) $res->json('data.authorization_url');
    }

    public function isPaid(Payment $payment, Request $request): bool
    {
        if (! $payment->reference) {
            return false;
        }
        $res = Http::withToken($this->secret)->acceptJson()->timeout(30)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($payment->reference));

        return $res->successful() && $res->json('data.status') === 'success'
            && (int) $res->json('data.amount') === $payment->amount_cents
            && strtoupper((string) $res->json('data.currency')) === strtoupper($payment->currency);
    }

    public function paidReferenceFromWebhook(Request $request): ?string
    {
        $signature = (string) $request->header('x-paystack-signature');
        abort_unless($signature !== '' && hash_equals(hash_hmac('sha512', $request->getContent(), $this->secret), $signature), 400, 'Invalid signature.');

        $event = $request->json()->all();

        return ($event['event'] ?? '') === 'charge.success' ? ($event['data']['reference'] ?? null) : null;
    }
}
