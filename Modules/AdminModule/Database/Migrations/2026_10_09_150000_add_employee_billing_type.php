<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people_profiles') && ! Schema::hasColumn('people_profiles', 'billing_type')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->string('billing_type', 20)->default('billable')->after('work_location');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people_profiles') && Schema::hasColumn('people_profiles', 'billing_type')) {
            Schema::table('people_profiles', function (Blueprint $table) {
                $table->dropColumn('billing_type');
            });
        }
    }
};
