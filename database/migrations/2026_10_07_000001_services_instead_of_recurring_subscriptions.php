<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Subscriptions are sold as one-off services (e.g. "X Premium, 1 month"): the customer pays once,
 * says which account to activate, and an admin marks the order done. No recurring billing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // What to ask the customer for, e.g. "X username or profile link"
            $table->string('account_label')->nullable()->after('type');
        });
        DB::table('products')->where('type', 'subscription')->update(['type' => 'service']);
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['billing_interval', 'paystack_plan_code']));

        Schema::table('order_items', function (Blueprint $table) {
            // The account the service is for, as the customer typed it
            $table->text('account_details')->nullable()->after('name');
        });

        DB::table('categories')->where('type', 'subscription')->update(['type' => 'service']);

        Schema::dropIfExists('subscriptions');
    }

    public function down(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('email');
            $table->string('status')->default('pending');
            $table->string('provider')->default('paystack');
            $table->string('provider_subscription_code')->nullable()->unique();
            $table->string('provider_customer_code')->nullable();
            $table->string('provider_email_token')->nullable();
            $table->string('payment_reference')->nullable()->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('next_payment_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('account_details'));
        Schema::table('products', function (Blueprint $table) {
            $table->string('billing_interval')->nullable();
            $table->string('paystack_plan_code')->nullable();
            $table->dropColumn('account_label');
        });
        DB::table('products')->where('type', 'service')->update(['type' => 'subscription']);
        DB::table('categories')->where('type', 'service')->update(['type' => 'subscription']);
    }
};
