<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        // Each role sees only its part: money and orders need "orders", stock and catalogue need "catalogue".
        if (! $request->user()->hasAccess('orders')) {
            return view('admin.dashboard', [
                'catalogue' => [
                    'Live products' => Product::where('is_active', true)->count(),
                    'Hidden' => Product::where('is_active', false)->count(),
                    'Needs a price' => Product::where('price', 0)->count(),
                    'Without photos' => Product::doesntHave('images')->count(),
                ],
                'recentProducts' => Product::with('category')->latest('updated_at')->take(8)->get(),
            ]);
        }

        $paid = Order::where('payment_status', 'paid');
        $open = (clone $paid)->whereIn('status', ['processing', 'shipped']);

        $enquiriesAvailable = Schema::hasTable('enquiries');
        $newEnquiriesCount = $enquiriesAvailable ? Enquiry::where('status', 'new')->count() : 0;
        $recentEnquiries = $enquiriesAvailable ? Enquiry::latest()->take(5)->get() : collect();

        // Count digital pool products with zero keys available
        $emptyPoolCount = Product::active()
            ->where('type', 'digital')
            ->where('digital_type', 'pool')
            ->whereDoesntHave('digitalKeys', fn ($k) => $k->whereNull('assigned_at'))
            ->count();

        // Self-healing automated maintenance: runs order recovery reminders and stock alerts at most once every 30 mins
        if (cache()->add('novastaq_auto_scheduler_tick', true, 1800)) {
            try {
                \Illuminate\Support\Facades\Artisan::call('orders:send-reminders');
                \Illuminate\Support\Facades\Artisan::call('stock:check-alerts', ['--notify' => true]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('admin.dashboard', [
            'revenueMonth' => (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            'ordersToday' => (clone $paid)->where('paid_at', '>=', now()->startOfDay())->count(),
            'servicesToDo' => (clone $open)->whereNull('address')->count(),
            'devicesToShip' => (clone $open)->whereNotNull('address')->count(),
            'serviceQueue' => (clone $open)->whereNull('address')->with('items')->oldest('paid_at')->take(10)->get(),
            'lowStock' => Product::active()->whereNotNull('stock')->where('stock', '<=', 3)->orderBy('stock')->take(8)->get(),
            'recentOrders' => Order::with('items')->latest()->take(8)->get(),
            'newEnquiriesCount' => $newEnquiriesCount,
            'recentEnquiries' => $recentEnquiries,
            'emptyPoolCount' => $emptyPoolCount,
        ]);
    }
}
