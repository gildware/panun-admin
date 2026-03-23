<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('providers', 'app_availability')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->boolean('app_availability')->default(1)->after('service_availability');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('providers', 'app_availability')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('app_availability');
            });
        }
    }
};
