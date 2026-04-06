<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('template_id')
                ->references('id')
                ->on('templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $fallbackId = DB::table('templates')->orderBy('id')->value('id');
        if ($fallbackId) {
            DB::table('projects')->whereNull('template_id')->update(['template_id' => $fallbackId]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable(false)->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('template_id')
                ->references('id')
                ->on('templates')
                ->restrictOnDelete();
        });
    }
};
