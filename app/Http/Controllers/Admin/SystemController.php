<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Applies database updates after a deploy, for hosts without shell access
 * (the equivalent of `php artisan migrate --force`). Admins only.
 */
class SystemController extends Controller
{
    public function migrate(): RedirectResponse
    {
        @set_time_limit(300);

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Update failed: '.$e->getMessage());
        }

        $ran = trim(Artisan::output());

        return back()->with('status', str_contains($ran, 'Nothing to migrate') || $ran === '' ? 'Already up to date.' : 'Database updated.');
    }
}
