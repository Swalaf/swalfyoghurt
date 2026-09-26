<?php

namespace App\Services\Billing;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Licensing\LicenseServer;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class Billing
{
    public const GATEWAYS = ['stripe' => 'Stripe', 'paystack' => 'Paystack'];

    /** Currencies whose smallest unit is the whole unit (no cents). */
    public const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    /** @return array<string, string> key => label of gateways with saved keys */
    public static function enabled(): array
    {
        $saved = Settings::get('gateways', []);

        return array_filter(self::GATEWAYS, fn ($label, $key) => ! empty($saved[$key]['secret']), ARRAY_FILTER_USE_BOTH);
    }

    public static function gateway(string $key): Gateway
    {
        $saved = Settings::get('gateways', [])[$key] ?? null;
        abort_unless($saved && ! empty($saved['secret']), 404, 'That payment method isn’t available.');
        $secret = (string) decrypt($saved['secret']);

        return match ($key) {
            'stripe' => new StripeGateway($secret, ! empty($saved['webhook']) ? (string) decrypt($saved['webhook']) : ''),
            'paystack' => new PaystackGateway($secret),
        };
    }

    public static function currency(): string
    {
        return strtoupper((string) Settings::get('currency', 'USD'));
    }

    /** Plan price in the smallest currency unit for a billing period. */
    public static function amountFor(Plan $plan, string $period): int
    {
        $major = $plan->price_cents / 100;
        $major = $period === 'yearly' ? round($major * 12 * 0.8, 2) : $major;

        return (int) round(in_array(self::currency(), self::ZERO_DECIMAL, true) ? $major : $major * 100);
    }

    public static function format(int $minor, ?string $currency = null): string
    {
        $currency ??= self::currency();
        $major = in_array($currency, self::ZERO_DECIMAL, true) ? $minor : $minor / 100;

        return Number::currency($major, $currency, app()->getLocale()) ?: $currency.' '.number_format($major, 2);
    }

    /**
     * Mark a payment paid and fulfil it. Safe to call more than once (return URL + webhook).
     */
    public static function markPaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === 'paid') {
                return;
            }
            $payment->update(['status' => 'paid', 'paid_at' => now()]);

            if ($payment->kind === 'license') {
                app(LicenseServer::class)->issueForPayment($payment);

                return;
            }

            $user = $payment->user;
            $plan = $payment->plan;
            if (! $user || ! $plan) {
                return;
            }
            $months = $payment->period === 'yearly' ? 12 : 1;
            $start = $user->plan_id === $plan->id && $user->plan_expires_at?->isFuture() ? $user->plan_expires_at : now();
            $user->forceFill([
                'plan_id' => $plan->id,
                'plan_period' => $payment->period,
                'plan_expires_at' => $start->copy()->addMonthsNoOverflow($months),
                'credits' => max($user->credits, (int) $plan->credits),
                'credits_reset_at' => now()->addMonthNoOverflow(),
                'status' => $user->status === 'trial' ? 'active' : $user->status,
            ])->save();
            ActivityLog::record('Billing', 'Payment received — '.$plan->name.' ('.self::format($payment->amount_cents, $payment->currency).', '.$payment->gateway.')', 'INFO', $user);
        });
    }

    /**
     * Daily housekeeping: downgrade expired plans and refill monthly credits.
     *
     * @return array{expired: int, refilled: int}
     */
    public static function maintain(): array
    {
        $free = Plan::where('price_cents', 0)->orderBy('sort')->first();
        $expired = 0;
        User::whereNotNull('plan_expires_at')->where('plan_expires_at', '<', now())->each(function (User $u) use ($free, &$expired) {
            $u->forceFill([
                'plan_id' => $free?->id, 'plan_period' => null, 'plan_expires_at' => null,
                'credits' => min($u->credits, (int) ($free?->credits ?? 0)),
            ])->save();
            ActivityLog::record('Billing', 'Plan expired — moved to '.($free?->name ?? 'no plan'), 'INFO', $u);
            $expired++;
        });

        $refilled = 0;
        User::with('plan')->where('is_admin', false)->whereNotNull('plan_id')
            ->where(fn ($q) => $q->whereNull('credits_reset_at')->orWhere('credits_reset_at', '<', now()))
            ->each(function (User $u) use (&$refilled) {
                $u->forceFill(['credits' => max($u->credits, (int) $u->plan?->credits), 'credits_reset_at' => now()->addMonthNoOverflow()])->save();
                $refilled++;
            });

        return compact('expired', 'refilled');
    }
}
