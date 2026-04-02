<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('order')->default(1);
            $table->timestamps();

            $table->index(['project_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_images');
    }
};
