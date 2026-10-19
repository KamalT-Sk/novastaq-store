<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('service')->nullable();
            $table->string('budget')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->json('fields')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 20)->default('new'); // 'new', 'replied', 'archived'
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reply_subject')->nullable();
            $table->text('reply_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};

