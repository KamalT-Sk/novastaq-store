<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Database\Seeders\StarterCatalogueSeeder;
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
            if (Artisan::call('store:secure-digital-files') !== 0) {
                return back()->with('error', 'Download migration needs attention: '.Artisan::output());
            }
            Artisan::call('optimize:clear');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Update failed: '.$e->getMessage());
        }

        $ran = trim(Artisan::output());

        return back()->with('status', str_contains($ran, 'Nothing to migrate') || $ran === '' ? 'Already up to date.' : 'Database updated.');
    }

    /** Adds the starter catalogue (hidden, unpriced). Skips products that already exist. */
    public function catalogue(): RedirectResponse
    {
        $seeder = new StarterCatalogueSeeder;
        $seeder->run();

        return redirect()->route('admin.products.index', ['status' => 'hidden'])->with('status', $seeder->created
            ? "Added {$seeder->created} products, all hidden. Add a price and photo to each, then switch it Live."
            : 'Nothing new to add: the starter products are already in your store.');
    }
}
