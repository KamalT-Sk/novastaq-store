<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * One-time setup for hosts without shell access: creates the tables, the starter
 * categories and the first admin. Needs ?token=INSTALL_TOKEN from .env, and switches
 * itself off for good once an admin exists.
 */
class InstallController extends Controller
{
    public function show(Request $request): View
    {
        $this->guard($request);

        return view('install', ['token' => $request->query('token')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);

        // Shared hosts often cap requests at 30s; a cold first run can need longer.
        @set_time_limit(300);

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Database setup failed: '.$e->getMessage());
        }

        $admin = User::firstOrNew(['email' => $data['email']]);
        $admin->fill(['name' => $data['name'], 'password' => $data['password']]);
        $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        Auth::login($admin);

        return redirect()->route('admin.dashboard')->with('status', 'Store installed. Remove INSTALL_TOKEN from .env when you can.');
    }

    private function guard(Request $request): void
    {
        $token = (string) config('store.install_token');

        abort_if($token === '' || ! hash_equals($token, (string) $request->input('token', $request->query('token'))), 404);
        abort_if($this->installed(), 404);
    }

    private function installed(): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasColumn('users', 'is_admin') && User::where('is_admin', true)->exists();
        } catch (Throwable) {
            return false; // no database tables yet
        }
    }
}
