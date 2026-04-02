<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->text('description');
            $table->enum('status', ['draft', 'processing', 'done', 'failed'])->default('draft');
            $table->string('video_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
