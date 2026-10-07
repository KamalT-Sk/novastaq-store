<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Staff get a role (owner, manager, editor, orders) instead of a yes/no admin flag. Existing admins become owners. */
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('role', 20)->nullable()->after('password'));
        DB::table('users')->where('is_admin', true)->update(['role' => 'owner']);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false)->after('password'));
        DB::table('users')->whereNotNull('role')->update(['is_admin' => true]);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
