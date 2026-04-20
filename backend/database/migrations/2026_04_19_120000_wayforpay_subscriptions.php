<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropForeign(['credit_package_id']);
        });

        DB::statement('ALTER TABLE payment_orders MODIFY credit_package_id BIGINT UNSIGNED NULL');

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreign('credit_package_id')->references('id')->on('credit_packages')->cascadeOnDelete();
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('credit_package_id')->constrained()->nullOnDelete();
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('wayforpay_order_reference', 64)->nullable()->after('stripe_subscription_id')->index();
            $table->string('rec_token', 191)->nullable()->after('wayforpay_order_reference')->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['wayforpay_order_reference', 'rec_token']);
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn('subscription_plan_id');
        });

        // credit_package_id stays nullable after up(); restoring NOT NULL may fail if subscription-only rows exist.
    }
};
