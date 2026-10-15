<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('eyebrow')->nullable();
            $table->string('headline');
            $table->text('content');
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->string('audience')->default('all');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcasts');
    }
};
