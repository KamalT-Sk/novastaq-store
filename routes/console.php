<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('store:make-admin {email}', function (string $email) {
    $user = User::where('email', $email)->first();
    if (! $user) {
        $password = $this->secret('Password for the new admin (min 8 characters)');
        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return 1;
        }
        $user = User::create(['name' => $this->ask('Name', 'Admin'), 'email' => $email, 'password' => $password]);
    }
    $user->forceFill(['role' => 'owner'])->save();
    $this->info("{$email} is now an owner. Sign in at /login, then open /admin. Add other staff from Admin → Team.");
})->purpose('Create an owner account, or make an existing user an owner');

\Illuminate\Support\Facades\Schedule::command('orders:send-reminders')->hourly();

