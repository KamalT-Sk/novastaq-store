<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if (Schema::hasTable('product_digital_keys')) {
            $keys = DB::table('product_digital_keys')->get();
            foreach ($keys as $k) {
                $raw = trim((string) $k->key_content);
                $cleaned = preg_replace('/(?:[\s\-_]*(?:\d+[\.\)\:\-]|\[\d+\]))$/u', '', $raw);
                $cleaned = preg_replace('/^\s*(?:\[?\d+[\.\)\:\-\]]+|[\*\-\•])\s+/u', '', (string) $cleaned);
                $cleaned = trim((string) $cleaned);

                if ($cleaned !== '' && $cleaned !== $raw) {
                    DB::table('product_digital_keys')->where('id', $k->id)->update([
                        'key_content' => $cleaned,
                    ]);
                }
            }
        }

        if (Schema::hasTable('order_items')) {
            $items = DB::table('order_items')->whereNotNull('digital_content')->get();
            foreach ($items as $item) {
                $raw = trim((string) $item->digital_content);
                $cleaned = preg_replace('/(?:[\s\-_]*(?:\d+[\.\)\:\-]|\[\d+\]))$/u', '', $raw);
                $cleaned = preg_replace('/^\s*(?:\[?\d+[\.\)\:\-\]]+|[\*\-\•])\s+/u', '', (string) $cleaned);
                $cleaned = trim((string) $cleaned);

                if ($cleaned !== '' && $cleaned !== $raw) {
                    DB::table('order_items')->where('id', $item->id)->update([
                        'digital_content' => $cleaned,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback
    }
};

