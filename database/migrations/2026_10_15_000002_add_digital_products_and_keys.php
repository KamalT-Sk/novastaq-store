<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('digital_type')->default('pool')->after('account_label'); // pool | file
            $table->text('digital_url')->nullable()->after('digital_type');
            $table->string('digital_file')->nullable()->after('digital_url');
            $table->text('digital_instructions')->nullable()->after('digital_file');
        });

        Schema::create('product_digital_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('key_content');
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'order_item_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->after('account_details'); // physical | service | digital
            $table->text('digital_content')->nullable()->after('delivery_type');
            $table->string('digital_file')->nullable()->after('digital_content');
            $table->text('digital_instructions')->nullable()->after('digital_file');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'digital_content', 'digital_file', 'digital_instructions']);
        });

        Schema::dropIfExists('product_digital_keys');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['digital_type', 'digital_url', 'digital_file', 'digital_instructions']);
        });
    }
};

