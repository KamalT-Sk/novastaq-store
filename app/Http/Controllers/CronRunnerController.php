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

        if ($action === 'weekend-sale' || $action === 'store:weekend-sale') {
            $broadcast = filter_var($request->input('broadcast', $request->query('broadcast', true)), FILTER_VALIDATE_BOOLEAN);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            Artisan::call('store:weekend-sale', [
                '--broadcast' => $broadcast,
            ]);
            $output = Artisan::output();
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

