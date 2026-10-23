<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\Notifier;
use Illuminate\Console\Command;

class CheckLowStockAlerts extends Command
{
    protected $signature = 'stock:check-alerts {--notify : Send email alerts to store admins}';

    protected $description = 'Scan active inventory for low stock items and empty digital key pools';

    public function handle(Notifier $notifier): int
    {
        $shouldNotify = (bool) $this->option('notify');

        // 1. Digital products with 0 keys available
        $emptyPools = Product::active()
            ->where('type', 'digital')
            ->where('digital_type', 'pool')
            ->whereDoesntHave('digitalKeys', fn ($k) => $k->whereNull('assigned_at')->whereNull('order_item_id'))
            ->get();

        if ($emptyPools->isNotEmpty()) {
            $this->warn("Found {$emptyPools->count()} digital products with EMPTY pools:");
            foreach ($emptyPools as $p) {
                $this->line(" - [EMPTY POOL] {$p->name} (ID: {$p->id})");
                if ($shouldNotify) {
                    $notifier->emptyPoolAlert($p);
                }
            }
        } else {
            $this->info("All active digital key pools have available inventory.");
        }

        // 2. Physical / service items with <= 3 spots or units left
        $lowStock = Product::active()
            ->whereNotNull('stock')
            ->where('stock', '<=', 3)
            ->get();

        if ($lowStock->isNotEmpty()) {
            $this->warn("Found {$lowStock->count()} products with low inventory (<= 3 units):");
            foreach ($lowStock as $p) {
                $this->line(" - [LOW STOCK] {$p->name}: {$p->stock} remaining");
                if ($shouldNotify) {
                    $notifier->lowStockAlert($p, (int) $p->stock);
                }
            }
        } else {
            $this->info("No active items are currently below low stock threshold.");
        }

        return 0;
    }
}

