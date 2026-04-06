<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Legacy: projects pointed at templates.slug = photo-guided-internal.
     * After template_id became nullable, clear those references so the row can be removed.
     */
    public function up(): void
    {
        $internalIds = DB::table('templates')->where('slug', 'photo-guided-internal')->pluck('id');
        foreach ($internalIds as $tid) {
            DB::table('projects')->where('template_id', $tid)->update(['template_id' => null]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
