<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('people_timesheet_settings')) {
            return;
        }

        Schema::table('people_timesheet_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('people_timesheet_settings', 'popup_enabled')) {
                $table->boolean('popup_enabled')->default(true)->after('starts_on');
            }
            if (! Schema::hasColumn('people_timesheet_settings', 'popup_interval_seconds')) {
                $table->unsignedInteger('popup_interval_seconds')->default(1800)->after('popup_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('people_timesheet_settings')) {
            return;
        }

        Schema::table('people_timesheet_settings', function (Blueprint $table) {
            if (Schema::hasColumn('people_timesheet_settings', 'popup_interval_seconds')) {
                $table->dropColumn('popup_interval_seconds');
            }
            if (Schema::hasColumn('people_timesheet_settings', 'popup_enabled')) {
                $table->dropColumn('popup_enabled');
            }
        });
    }
};
