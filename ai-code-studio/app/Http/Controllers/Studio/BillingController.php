<?php

namespace App\Http\Controllers\Studio;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Billing\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Throwable;

class BillingController extends StudioController
{
    public function show(Request $request)
    {
        return view('studio.billing', [
            'user' => $request->user()->load('plan'),
            'plans' => Plan::where('status', 'live')->orderBy('sort')->get(),
            'gateways' => Billing::enabled(),
            'payments' => Payment::where('user_id', $request->user()->id)->where('kind', 'plan')->with('plan')->latest('id')->take(20)->get(),
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'period' => 'required|in:monthly,yearly',
            'gateway' => 'required|in:'.implode(',', array_keys(Billing::enabled()) ?: ['none']),
        ]);
        $plan = Plan::where('status', 'live')->findOrFail($data['plan_id']);
        abort_if($plan->isCustom() || $plan->price_cents === 0, 422, 'This plan can’t be bought online.');

        $payment = Payment::create([
            'user_id' => $request->user()->id, 'plan_id' => $plan->id, 'kind' => 'plan', 'gateway' => $data['gateway'],
            'period' => $data['period'], 'amount_cents' => Billing::amountFor($plan, $data['period']), 'currency' => Billing::currency(),
            'reference' => 'tmp_'.bin2hex(random_bytes(8)),
        ]);

        try {
            $url = Billing::gateway($data['gateway'])->checkout(
                $payment, $request->user()->email,
                $plan->name.' · '.($data['period'] === 'yearly' ? __('1 year') : __('1 month')),
                URL::signedRoute('billing.return', $payment),
                route('studio.billing'),
            );
        } catch (Throwable $e) {
            $payment->update(['status' => 'failed']);
            ActivityLog::record('Billing', 'Checkout failed ('.$data['gateway'].'): '.$e->getMessage(), 'ERROR');

            return back()->with('error', __('We couldn’t start the payment. Please try again or contact support.'));
        }

        return redirect()->away($url);
    }

    /** Customer comes back from the gateway (signed URL, so no session is needed). */
    public function return(Request $request, Payment $payment)
    {
        if ($payment->status !== 'paid' && rescue(fn () => Billing::gateway($payment->gateway)->isPaid($payment, $request), false)) {
            Billing::markPaid($payment);
        }
        $payment->refresh();

        if ($payment->kind === 'license') {
            return redirect()->to(URL::signedRoute('license.thanks', $payment));
        }

        return redirect()->route('studio.billing')->with(
            $payment->status === 'paid' ? 'status' : 'error',
            $payment->status === 'paid' ? __('Payment received — you’re on :plan.', ['plan' => $payment->plan?->name]) : __('We haven’t received confirmation of this payment yet. If you were charged, it will appear shortly.')
        );
    }

    public function webhook(Request $request, string $gateway)
    {
        $reference = Billing::gateway($gateway)->paidReferenceFromWebhook($request);
        if ($reference && ($payment = Payment::where('gateway', $gateway)->where('reference', $reference)->first())) {
            Billing::markPaid($payment);
        }

        return response()->json(['received' => true]);
    }
}
