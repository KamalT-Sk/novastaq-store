<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\NombaGateway;
use App\Support\Fulfilment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Nomba calls this webhook when a payment completes.
 * Configure in Nomba Dashboard -> Developers -> Webhook:
 * URL: https://store.novastaq.com/nomba/webhook
 */
class NombaWebhookController extends Controller
{
    public function __invoke(Request $request, NombaGateway $gateway, Fulfilment $fulfilment): Response
    {
        abort_unless($gateway->isValidWebhook($request), 401);

        $event = (string) ($request->input('event') ?? $request->input('type') ?? '');
        $data = $request->input('data') ?? [];

        Log::info('Nomba webhook received: '.$event, ['payload' => $request->all()]);

        $isSuccess = in_array(strtolower($event), ['payment_success', 'payment.success', 'checkout.success', 'order_success'], true)
            || strtoupper((string) ($data['status'] ?? $request->input('status') ?? '')) === 'SUCCESS';

        if ($isSuccess) {
            $orderRef = (string) (
                $data['orderReference']
                ?? $data['order_reference']
                ?? $data['orderId']
                ?? $data['order_id']
                ?? $request->input('orderReference')
                ?? $request->input('orderId')
                ?? ''
            );

            if ($orderRef !== '') {
                $order = Order::where('payment_reference', $orderRef)
                    ->orWhere('number', $orderRef)
                    ->first();

                if ($order) {
                    $fulfilment->markOrderPaid($order, $gateway->verify($order->payment_reference), $gateway->name());
                }
            }
        }

        return response('ok');
    }
}
