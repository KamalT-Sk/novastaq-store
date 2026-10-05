<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.subscriptions', [
            'subscriptions' => Subscription::with('product', 'user')
                ->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))
                ->when($q !== '', fn ($w) => $w->where('email', 'like', "%{$q}%"))
                ->latest()->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }
}
