<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('provider', 32)->default('wayforpay')->after('credit_package_id');
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->decimal('amount_uah', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
