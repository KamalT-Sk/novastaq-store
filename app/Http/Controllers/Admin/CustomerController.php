<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailBroadcast;
use App\Models\Order;
use App\Models\User;
use App\Support\Mailhive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = strtolower(trim((string) $request->query('q')));
        $tab = $request->query('tab', 'all'); // all, registered, guests, paying

        // 1. Fetch registered users
        $users = User::latest()->get();
        $userEmails = $users->pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->all();

        // 2. Fetch all orders grouped by lowercase email
        $allOrders = Order::latest()->get();
        $ordersByEmail = $allOrders->groupBy(fn ($o) => strtolower(trim((string) $o->email)));

        $customers = collect();

        foreach ($users as $u) {
            $email = strtolower(trim((string) $u->email));
            $userOrders = $ordersByEmail->get($email, collect());

            $customers->push((object) [
                'id' => 'u-'.$u->id,
                'user_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone ?: $userOrders->first()?->phone,
                'is_registered' => true,
                'role' => $u->role,
                'role_label' => $u->roleLabel() ?: 'Customer',
                'orders_count' => $userOrders->count(),
                'paid_orders_count' => $userOrders->where('payment_status', 'paid')->count(),
                'total_spent' => (int) $userOrders->where('payment_status', 'paid')->sum('total'),
                'latest_order_at' => $userOrders->max('created_at'),
                'created_at' => $u->created_at,
            ]);
        }

        // Add guest buyers who placed orders but haven't registered
        foreach ($ordersByEmail as $email => $orders) {
            if (in_array($email, $userEmails, true) || empty($email)) {
                continue;
            }

            $latestOrder = $orders->first();
            $customers->push((object) [
                'id' => 'g-'.md5($email),
                'user_id' => null,
                'name' => $latestOrder->customer_name ?: 'Guest Buyer',
                'email' => $email,
                'phone' => $latestOrder->phone,
                'is_registered' => false,
                'role' => null,
                'role_label' => 'Guest Buyer',
                'orders_count' => $orders->count(),
                'paid_orders_count' => $orders->where('payment_status', 'paid')->count(),
                'total_spent' => (int) $orders->where('payment_status', 'paid')->sum('total'),
                'latest_order_at' => $orders->max('created_at'),
                'created_at' => $orders->min('created_at'),
            ]);
        }

        // Global stats
        $totalCustomersCount = $customers->count();
        $registeredCount = $customers->where('is_registered', true)->count();
        $guestsCount = $customers->where('is_registered', false)->count();
        $payingCount = $customers->where('paid_orders_count', '>', 0)->count();

        // Filter by tab
        $filtered = match ($tab) {
            'registered' => $customers->where('is_registered', true),
            'guests' => $customers->where('is_registered', false),
            'paying' => $customers->where('paid_orders_count', '>', 0),
            default => $customers,
        };

        // Filter by search query
        if ($q !== '') {
            $filtered = $filtered->filter(function ($c) use ($q) {
                return str_contains(strtolower((string) $c->name), $q)
                    || str_contains(strtolower((string) $c->email), $q)
                    || str_contains(strtolower((string) $c->phone), $q);
            });
        }

        // Sort by latest activity (latest order or join date)
        $sorted = $filtered->sortByDesc(fn ($c) => $c->latest_order_at ?? $c->created_at)->values();

        // Paginate manually with LengthAwarePaginator
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $paginated = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.customers.index', [
            'customers' => $paginated,
            'totalCount' => $totalCustomersCount,
            'registeredCount' => $registeredCount,
            'guestsCount' => $guestsCount,
            'payingCount' => $payingCount,
            'tab' => $tab,
            'q' => $q,
        ]);
    }

    public function broadcastForm(): View
    {
        $users = User::pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique();
        $payingOrders = Order::where('payment_status', 'paid')->pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique();
        $allOrders = Order::pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique();

        $allEmails = $users->concat($allOrders)->unique()->values();

        $audienceCounts = [
            'all' => $allEmails->count(),
            'registered' => $users->count(),
            'paying' => $payingOrders->count(),
        ];

        $history = EmailBroadcast::with('sender')->latest()->take(20)->get();

        return view('admin.customers.broadcast', [
            'audienceCounts' => $audienceCounts,
            'history' => $history,
        ]);
    }

    public function sendTest(Request $request, Mailhive $mailhive): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:20000'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'url', 'max:500'],
        ]);

        $adminEmail = auth()->user()->email;

        $html = view('emails.broadcast', [
            'subject' => '[TEST PREVIEW] '.$data['subject'],
            'eyebrow' => $data['eyebrow'] ?: 'Test Preview',
            'headline' => $data['headline'],
            'content' => $data['content'],
            'buttonLabel' => $data['button_label'] ?? null,
            'buttonUrl' => $data['button_url'] ?? null,
        ])->render();

        $sent = $mailhive->send(
            to: $adminEmail,
            subject: '[TEST PREVIEW] '.$data['subject'],
            html: $html,
            replyTo: config('store.support_email'),
            tags: ['broadcast-test']
        );

        if ($sent) {
            return back()->withInput()->with('status', "Test email sent to {$adminEmail}. Check your inbox!");
        }

        return back()->withInput()->with('error', "Could not send test email to {$adminEmail}. Please check mail settings.");
    }

    public function sendBroadcast(Request $request, Mailhive $mailhive): RedirectResponse
    {
        @set_time_limit(300);

        $data = $request->validate([
            'audience' => ['required', Rule::in(['all', 'registered', 'paying'])],
            'subject' => ['required', 'string', 'max:190'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:20000'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'url', 'max:500'],
        ]);

        // Get recipients based on audience
        $recipients = match ($data['audience']) {
            'registered' => User::pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values(),
            'paying' => Order::where('payment_status', 'paid')->pluck('email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values(),
            default => User::pluck('email')->concat(Order::pluck('email'))->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values(),
        };

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'No recipients found for the selected audience.');
        }

        $html = view('emails.broadcast', [
            'subject' => $data['subject'],
            'eyebrow' => $data['eyebrow'],
            'headline' => $data['headline'],
            'content' => $data['content'],
            'buttonLabel' => $data['button_label'] ?? null,
            'buttonUrl' => $data['button_url'] ?? null,
        ])->render();

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            $ok = $mailhive->send(
                to: $recipient,
                subject: $data['subject'],
                html: $html,
                replyTo: config('store.support_email'),
                tags: ['bulk-broadcast']
            );
            if ($ok) {
                $sentCount++;
            }
        }

        // Record in history
        EmailBroadcast::create([
            'subject' => $data['subject'],
            'eyebrow' => $data['eyebrow'],
            'headline' => $data['headline'],
            'content' => $data['content'],
            'button_label' => $data['button_label'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'audience' => $data['audience'],
            'recipients_count' => $sentCount,
            'sent_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.customers.broadcast')
            ->with('status', "Broadcast successfully sent to {$sentCount} recipient(s)!");
    }
}
