<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && !Schema::hasColumn('bookings', 'area_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                // References customer_lead_areas.id (shared area list across leads and bookings)
                $table->unsignedBigInteger('area_id')->nullable()->after('zone_id');
                $table->index('area_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'area_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex(['area_id']);
                $table->dropColumn('area_id');
            });
        }
    }
};
