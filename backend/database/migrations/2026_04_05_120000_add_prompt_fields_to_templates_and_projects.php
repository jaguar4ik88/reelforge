<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->longText('generation_prompt')->nullable()->after('config_json');
            $table->longText('negative_prompt')->nullable()->after('generation_prompt');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->longText('final_prompt')->nullable()->after('product_meta_json');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('final_prompt');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['generation_prompt', 'negative_prompt']);
        });
    }
};
