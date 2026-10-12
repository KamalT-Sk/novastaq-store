<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function index(PaymentGatewayManager $gateways): View
    {
        $items = [
            'paystack' => [
                'name' => 'paystack',
                'label' => 'Paystack',
                'description' => 'Cards, Bank Transfer, USSD (NGN)',
                'badge' => 'Cards / NGN',
                'is_enabled' => Setting::isGatewayEnabled('paystack'),
                'is_configured' => (bool) config('services.paystack.secret_key'),
                'webhook_url' => url('/paystack/webhook'),
            ],
            'tsara' => [
                'name' => 'tsara',
                'label' => 'Tsara',
                'description' => 'Direct Transfer, Solana & BSC Crypto',
                'badge' => 'Crypto / Transfer',
                'is_enabled' => Setting::isGatewayEnabled('tsara'),
                'is_configured' => (bool) (config('services.tsara.public_key') && config('services.tsara.secret_key')),
                'webhook_url' => url('/tsara/webhook'),
            ],
            'nomba' => [
                'name' => 'nomba',
                'label' => 'Nomba',
                'description' => 'Cards, Bank Transfer, USSD & QR Codes',
                'badge' => 'Cards / Transfer',
                'is_enabled' => Setting::isGatewayEnabled('nomba'),
                'is_configured' => (bool) (config('services.nomba.client_id') && config('services.nomba.client_secret') && config('services.nomba.account_id')),
                'webhook_url' => url('/nomba/webhook'),
            ],
        ];

        return view('admin.payments.index', ['gateways' => $items]);
    }

    public function toggle(Request $request, string $gateway): RedirectResponse
    {
        $supported = ['paystack', 'tsara', 'nomba'];
        abort_unless(in_array($gateway, $supported, true), 404);

        $currentlyEnabled = Setting::isGatewayEnabled($gateway);
        $newState = ! $currentlyEnabled;

        // Ensure at least one gateway remains enabled so customers aren't left stranded
        $enabled = Setting::enabledGateways();
        if ($currentlyEnabled && count($enabled) <= 1) {
            return back()->with('error', 'You must have at least one payment gateway active for customers to checkout.');
        }

        Setting::setGatewayEnabled($gateway, $newState);

        $label = ucfirst($gateway);
        $statusText = $newState ? 'enabled' : 'disabled';

        return back()->with('status', "{$label} payment gateway has been {$statusText}.");
    }
}
