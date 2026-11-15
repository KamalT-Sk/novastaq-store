<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronRunnerController extends Controller
{
    /**
     * Executes scheduled tasks via cloud timer or webhook without needing cPanel shell/cron.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('store.cron_token');
        $given = (string) $request->input('token', $request->query('token'));

        abort_if($expected === '' || ! hash_equals($expected, $given), 403, 'Unauthorized cron token');

        @set_time_limit(300);

        $action = (string) $request->input('action', $request->query('action', 'schedule:run'));

        if ($action === 'inspect') {
            $products = \App\Models\Product::with(['images', 'variants'])->whereIn('id', [54, 56, 57, 65, 72, 73, 74, 75])->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $p->price,
                'is_active' => $p->is_active,
                'images' => $p->images->map(fn($img) => ['id' => $img->id, 'path' => $img->path]),
                'variants' => $p->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->name, 'price' => $v->price, 'is_active' => $v->is_active]),
            ]);

            return response()->json([
                'ok' => true,
                'products' => $products,
            ]);
        }

        if ($action === 'weekend-sale' || $action === 'store:weekend-sale') {
            try {
                $broadcast = filter_var($request->input('broadcast', $request->query('broadcast', false)), FILTER_VALIDATE_BOOLEAN);
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('optimize:clear');
                $params = [];
                if ($broadcast) {
                    $params['--broadcast'] = true;
                }
                Artisan::call('store:weekend-sale', $params);
                $output = Artisan::output();
            } catch (\Throwable $e) {
                report($e);
                return response()->json([
                    'ok' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }
        } elseif ($action === 'weekend-sale-dry-run') {
            Artisan::call('store:weekend-sale', [
                '--dry-run' => true,
            ]);
            $output = Artisan::output();
        } elseif ($action === 'migrate') {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
        } else {
            Artisan::call('schedule:run');
            $output = Artisan::output();
        }

        return response()->json([
            'ok' => true,
            'action' => $action,
            'ran_at' => now()->toIso8601String(),
            'output' => trim($output),
        ]);
    }
}

