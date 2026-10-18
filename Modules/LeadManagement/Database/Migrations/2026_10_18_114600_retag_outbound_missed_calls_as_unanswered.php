<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('superfone_calls') || ! Schema::hasColumn('superfone_calls', 'outcome')) {
            return;
        }

        DB::table('superfone_calls')
            ->where('direction', 'OUTBOUND')
            ->where('outcome', 'missed')
            ->update(['outcome' => 'unanswered']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('superfone_calls') || ! Schema::hasColumn('superfone_calls', 'outcome')) {
            return;
        }

        DB::table('superfone_calls')
            ->where('direction', 'OUTBOUND')
            ->where('outcome', 'unanswered')
            ->update(['outcome' => 'missed']);
    }
};
