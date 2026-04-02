<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider', 32)->nullable()->after('password');
            $table->string('provider_id', 255)->nullable()->after('provider');
        });

        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['provider', 'provider_id'], 'users_provider_provider_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_provider_provider_id_unique');
        });

        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
