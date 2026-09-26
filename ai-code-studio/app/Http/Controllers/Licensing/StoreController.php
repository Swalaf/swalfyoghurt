<?php

namespace App\Http\Controllers\Licensing;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Payment;
use App\Models\Release;
use App\Services\Billing\Billing;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Throwable;

/** Vendor storefront: sell licences directly (vendor installation only). */
class StoreController extends Controller
{
    public static function prices(): array
    {
        return [
            'regular' => (int) round((float) Settings::get('license_price_regular', 59) * 100),
            'extended' => (int) round((float) Settings::get('license_price_extended', 299) * 100),
        ];
    }

    public function show()
    {
        return view('licensing.buy', ['prices' => self::prices(), 'gateways' => Billing::enabled()]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:regular,extended',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'gateway' => 'required|in:'.implode(',', array_keys(Billing::enabled()) ?: ['none']),
            'agree' => 'accepted',
        ]);
        $payment = Payment::create([
            'kind' => 'license', 'gateway' => $data['gateway'], 'amount_cents' => self::prices()[$data['type']],
            'currency' => Billing::currency(), 'reference' => 'tmp_'.bin2hex(random_bytes(8)),
            'meta' => ['type' => $data['type'], 'name' => $data['name'], 'email' => strtolower($data['email'])],
        ]);
        try {
            $url = Billing::gateway($data['gateway'])->checkout($payment, $data['email'], 'AI Code Studio — '.ucfirst($data['type']).' licence', URL::signedRoute('billing.return', $payment), route('license.buy'));
        } catch (Throwable $e) {
            $payment->update(['status' => 'failed']);

            return back()->withInput()->with('error', __('We couldn’t start the payment. Please try again.'));
        }

        return redirect()->away($url);
    }

    public function thanks(Payment $payment)
    {
        abort_unless($payment->kind === 'license', 404);

        $license = License::where('purchase_ref', 'pay_'.$payment->id)->first();
        $release = Release::all()->sort(fn ($a, $b) => version_compare($b->version, $a->version))->first();

        return view('licensing.thanks', [
            'payment' => $payment, 'license' => $license, 'release' => $release,
            'download' => $license && $release ? URL::temporarySignedRoute('updates.download', now()->addDay(), ['release' => $release->id, 'license' => $license->id]) : null,
        ]);
    }
}
