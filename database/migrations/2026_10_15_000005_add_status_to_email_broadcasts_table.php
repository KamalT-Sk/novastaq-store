<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_broadcasts', function (Blueprint $table) {
            if (! Schema::hasColumn('email_broadcasts', 'status')) {
                $table->string('status', 20)->default('sent')->after('audience');
            }
            if (! Schema::hasColumn('email_broadcasts', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('recipients_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_broadcasts', function (Blueprint $table) {
            $table->dropColumn(['status', 'sent_at']);
        });
    }
};

