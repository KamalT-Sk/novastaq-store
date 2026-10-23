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

        @set_time_limit(180);

        Artisan::call('schedule:run');
        $output = Artisan::output();

        return response()->json([
            'ok' => true,
            'ran_at' => now()->toIso8601String(),
            'output' => trim($output),
        ]);
    }
}
