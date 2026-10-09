<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('people_timesheet_settings') || Schema::hasColumn('people_timesheet_settings', 'starts_on')) {
            return;
        }

        Schema::table('people_timesheet_settings', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->after('week_off');
        });

        DB::table('people_timesheet_settings')->whereNull('starts_on')->update([
            'starts_on' => '2026-10-01',
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('people_timesheet_settings') && Schema::hasColumn('people_timesheet_settings', 'starts_on')) {
            Schema::table('people_timesheet_settings', function (Blueprint $table) {
                $table->dropColumn('starts_on');
            });
        }
    }
};
