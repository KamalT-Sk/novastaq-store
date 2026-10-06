<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

    public function update(Request $request, Order $order): RedirectResponse
    {
        $order->update($request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]));

        return back()->with('status', $order->status === 'completed' ? "{$order->number} marked as done." : "{$order->number} marked {$order->status}.");
    }
}
