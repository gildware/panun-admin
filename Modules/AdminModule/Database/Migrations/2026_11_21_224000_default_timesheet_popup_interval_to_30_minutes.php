<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('people_timesheet_settings')
            || ! Schema::hasColumn('people_timesheet_settings', 'popup_interval_seconds')) {
            return;
        }

        DB::table('people_timesheet_settings')
            ->whereIn('popup_interval_seconds', [30, null])
            ->update(['popup_interval_seconds' => 1800]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('people_timesheet_settings')
            || ! Schema::hasColumn('people_timesheet_settings', 'popup_interval_seconds')) {
            return;
        }

        DB::table('people_timesheet_settings')
            ->where('popup_interval_seconds', 1800)
            ->update(['popup_interval_seconds' => 30]);
    }
};
