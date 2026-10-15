<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Support\Notifier;
use Illuminate\Console\Command;

class SendUnpaidOrderReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:send-reminders 
                            {--hours=2 : Minimum hours elapsed since order creation} 
                            {--max-hours=48 : Maximum hours elapsed since order creation}
                            {--limit=50 : Maximum number of reminders to send} 
                            {--dry-run : Preview matching orders without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends friendly recovery reminder emails to customers who attempted checkout but have not completed payment';

    /**
     * Execute the console command.
     */
    public function handle(Notifier $notifier): int
    {
        $hours = (int) $this->option('hours');
        $maxHours = (int) $this->option('max-hours');
        $limit = (int) $this->option('limit');
        $isDryRun = (bool) $this->option('dry-run');

        $threshold = now()->subHours($hours);
        $oldest = now()->subHours($maxHours);

        $orders = Order::with('items')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereNull('reminded_at')
            ->where('created_at', '<=', $threshold)
            ->where('created_at', '>=', $oldest)
            ->latest()
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info("No unpaid orders due for a reminder (created between {$hours}h and {$maxHours}h ago).");

            return self::SUCCESS;
        }

        $this->info("Found {$orders->count()} unpaid order(s) eligible for payment reminders.");

        $sent = 0;
        foreach ($orders as $order) {
            if ($isDryRun) {
                $this->line("[DRY RUN] Would send reminder to {$order->customer_name} <{$order->email}> for {$order->number} (created {$order->created_at->diffForHumans()})");
                continue;
            }

            $ok = $notifier->orderReminder($order);
            if ($ok) {
                $sent++;
                $this->info("✓ Sent reminder to {$order->email} for {$order->number}");
            } else {
                $this->warn("✕ Failed to send reminder to {$order->email} for {$order->number}");
            }
        }

        if (! $isDryRun) {
            $this->info("Successfully sent {$sent} / {$orders->count()} reminder(s).");
        }

        return self::SUCCESS;
    }
}
