<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Fulfilment;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.orders.index', [
            'orders' => Order::withCount('items')
                ->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))
                ->when($request->query('payment'), fn ($w, $p) => $w->where('payment_status', $p))
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('number', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('customer_name', 'like', "%{$q}%")))
                ->latest()->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', ['order' => $order->load('items.product', 'user')]);
    }

    public function update(Request $request, Order $order, Notifier $notifier, Fulfilment $fulfilment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'paid'])],
        ]);

        $from = $order->status;
        $justPaid = false;

        if ($request->input('payment_status') === 'paid' && ! $order->isPaid()) {
            $fulfilment->markOrderPaidManually($order);
            $justPaid = true;
            $order->refresh();
        }

        $newStatus = $validated['status'] ?? null;
        if ($newStatus && $from !== $newStatus) {
            $order->update(['status' => $newStatus]);

            // Tell the customer when something they're waiting for happens (only on a real change).
            match ($newStatus) {
                'completed' => $order->isService() ? $notifier->planActivated($order) : $notifier->orderDelivered($order),
                'shipped' => $notifier->orderShipped($order),
                'delivered' => $notifier->orderDelivered($order),
                'cancelled' => $notifier->orderCancelled($order),
                default => null,
            };
        }

        $msg = match (true) {
            $justPaid && $order->status === 'completed' => "{$order->number} marked as paid and completed.",
            $justPaid => "{$order->number} marked as paid.",
            $order->status === 'completed' => "{$order->number} marked as done.",
            default => "{$order->number} marked {$order->status}.",
        };

        return back()->with('status', $msg);
    }

    public function markPaid(Order $order, Fulfilment $fulfilment): RedirectResponse
    {
        if (! $order->isPaid()) {
            $fulfilment->markOrderPaidManually($order);

            return back()->with('status', "{$order->number} marked as paid. Confirmation emails sent.");
        }

        return back()->with('status', "{$order->number} is already marked as paid.");
    }

    public function deliverDigital(Request $request, Order $order, \App\Models\OrderItem $item, Notifier $notifier): RedirectResponse
    {
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate([
            'digital_content' => ['required', 'string', 'max:5000'],
        ]);

        $item->update([
            'delivery_type' => 'digital',
            'digital_content' => trim($data['digital_content']),
        ]);

        if ($order->fresh('items')->pendingDigitalItems()->isEmpty() && ! $order->hasPhysical()) {
            $order->update(['status' => 'completed']);
        }

        $notifier->digitalDelivered($order->fresh('items'), $item);

        return back()->with('status', "Digital link delivered to {$order->customer_name} ({$order->email}).");
    }
}
