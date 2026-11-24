<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_profiles') && ! Schema::hasColumn('people_profiles', 'timesheet_start_date')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->date('timesheet_start_date')->nullable()->after('requires_timesheet');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_profiles') && Schema::hasColumn('people_profiles', 'timesheet_start_date')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->dropColumn('timesheet_start_date');
            });
        }
    }
};
