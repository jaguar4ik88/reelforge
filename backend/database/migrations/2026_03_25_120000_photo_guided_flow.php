<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('creation_flow', 32)->default('template')->after('user_id');
        });

        Schema::create('generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 64)->default('photo_guided');
            $table->string('status', 32)->default('pending');
            $table->json('settings_json');
            $table->text('image_caption')->nullable();
            $table->text('final_prompt');
            $table->string('provider', 64)->nullable();
            $table->unsignedInteger('credits_cost')->nullable();
            $table->foreignId('credits_transaction_id')
                ->nullable()
                ->constrained('credit_transactions')
                ->nullOnDelete();
            $table->string('result_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_jobs');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('creation_flow');
        });
    }
};
