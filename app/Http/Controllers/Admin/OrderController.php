<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Fulfilment;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'completed' => $order->isService() ? $notifier->planActivated($order) : ($order->isDigital() ? null : $notifier->orderDelivered($order)),
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

    public function deliverDigital(Request $request, Order $order, OrderItem $item, Notifier $notifier): RedirectResponse
    {
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate([
            'digital_content' => ['required', 'string', 'max:5000'],
            'send_email' => ['nullable', 'boolean'],
            'mark_completed' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($item, $data) {
            $item->update(['delivery_type' => 'digital', 'digital_content' => trim($data['digital_content'])]);
            // A manual replacement supersedes the pool values for this item. Keep
            // those keys assigned for inventory history, but do not overwrite them.
        });

        $markCompleted = $request->has('mark_completed') ? $request->boolean('mark_completed') : true;
        if ($markCompleted) {
            $order->update(['status' => 'completed']);
        }

        $sendEmail = $request->has('send_email') ? $request->boolean('send_email') : true;
        if ($sendEmail) {
            $notifier->digitalDelivered($order->fresh('items'), $item);
        }

        $statusMsg = 'Digital link saved'
            .($sendEmail ? " and emailed to {$order->customer_name} ({$order->email})" : '')
            .($markCompleted ? ' (order completed)' : '')
            .'.';

        return back()->with('status', $statusMsg);
    }

    public function sendReminder(Order $order, Notifier $notifier): RedirectResponse
    {
        if ($order->isPaid()) {
            return back()->with('error', "Order {$order->number} is already paid.");
        }

        $ok = $notifier->orderReminder($order);

        return back()->with($ok ? 'status' : 'error', $ok
            ? "Payment reminder email sent to {$order->customer_name} ({$order->email})."
            : "Could not send reminder email to {$order->email}. Please check mail settings.");
    }

    public function sendBatchReminders(Request $request, Notifier $notifier): RedirectResponse
    {
        @set_time_limit(300);

        $threshold = now()->subHours(2);
        $oldest = now()->subHours(48);

        $orders = Order::with('items')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereNull('reminded_at')
            ->where('created_at', '<=', $threshold)
            ->where('created_at', '>=', $oldest)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('status', 'No unpaid orders are currently due for reminders (needs 2+ hours since checkout).');
        }

        $sent = 0;
        foreach ($orders as $order) {
            if ($notifier->orderReminder($order)) {
                $sent++;
            }
        }

        return back()->with('status', "Sent payment recovery reminders to {$sent} customer(s).");
    }
}
