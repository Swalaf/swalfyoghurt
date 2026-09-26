<?php

namespace App\Services\Billing;

use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

interface Gateway
{
    /** Create a hosted checkout and return the URL to send the customer to. */
    public function checkout(Payment $payment, string $email, string $description, string $returnUrl, string $cancelUrl): string;

    /** Ask the gateway whether this payment has been paid (used on the return redirect). */
    public function isPaid(Payment $payment, Request $request): bool;

    /**
     * Verify a webhook's signature and return the paid payment reference, or null to ignore it.
     *
     * @throws HttpException on a bad signature
     */
    public function paidReferenceFromWebhook(Request $request): ?string;
}
