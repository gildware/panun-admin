<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_timesheet_settings') && ! Schema::hasColumn('people_timesheet_settings', 'min_hours_full_time')) {
            Schema::table('people_timesheet_settings', function (Blueprint $table) {
                $table->decimal('min_hours_full_time', 4, 1)->default(0)->after('min_hours');
                $table->decimal('min_hours_part_time', 4, 1)->default(0)->after('min_hours_full_time');
            });
            DB::table('people_timesheet_settings')->update([
                'min_hours_full_time' => DB::raw('min_hours'),
                'min_hours_part_time' => DB::raw('min_hours'),
            ]);
        }

        if (Schema::hasTable('people_profiles') && ! Schema::hasColumn('people_profiles', 'work_schedule')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->string('work_schedule', 20)->default('full_time')->after('employment_type');
                $table->decimal('min_hours_override', 4, 1)->nullable()->after('work_schedule');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_timesheet_settings') && Schema::hasColumn('people_timesheet_settings', 'min_hours_full_time')) {
            Schema::table('people_timesheet_settings', function (Blueprint $table) {
                $table->dropColumn(['min_hours_full_time', 'min_hours_part_time']);
            });
        }

        if (Schema::hasTable('people_profiles') && Schema::hasColumn('people_profiles', 'work_schedule')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->dropColumn(['work_schedule', 'min_hours_override']);
            });
        }
    }
};
