<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_profiles') && ! Schema::hasColumn('people_profiles', 'week_off_override')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->json('week_off_override')->nullable()->after('min_hours_override');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_profiles') && Schema::hasColumn('people_profiles', 'week_off_override')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->dropColumn('week_off_override');
            });
        }
    }
};
