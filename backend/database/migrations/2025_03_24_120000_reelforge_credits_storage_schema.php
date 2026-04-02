<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('category')->nullable()->index()->after('slug');
            $table->boolean('is_active')->default(true)->after('category');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_status')->nullable()->after('avatar_path');
        });

        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('credits_amount');
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('monthly_credits');
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('delta');
            $table->unsignedInteger('balance_after');
            $table->string('kind', 64);
            $table->string('description')->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('credit_package_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('current_period_end')->nullable();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('credit_costs', function (Blueprint $table) {
            $table->id();
            $table->string('operation_key')->unique();
            $table->unsignedInteger('cost');
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('credits_cost')->nullable()->after('video_path');
            $table->foreignId('credits_transaction_id')
                ->nullable()
                ->after('credits_cost')
                ->constrained('credit_transactions')
                ->nullOnDelete();
        });

        foreach (DB::table('users')->cursor() as $row) {
            DB::table('user_credits')->insertOrIgnore([
                'user_id'    => $row->id,
                'balance'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['credits_transaction_id']);
            $table->dropColumn(['credits_cost', 'credits_transaction_id']);
        });

        Schema::dropIfExists('credit_costs');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('user_credits');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('credit_packages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subscription_status');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['slug', 'category', 'is_active', 'sort_order']);
        });
    }
};
