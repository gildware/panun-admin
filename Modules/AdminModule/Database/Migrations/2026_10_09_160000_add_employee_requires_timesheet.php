<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_profiles') && ! Schema::hasColumn('people_profiles', 'requires_timesheet')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->boolean('requires_timesheet')->default(true)->after('billing_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_profiles') && Schema::hasColumn('people_profiles', 'requires_timesheet')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->dropColumn('requires_timesheet');
            });
        }
    }
};
