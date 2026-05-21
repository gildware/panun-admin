<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'zone_id')) {
                $table->uuid('zone_id')->nullable()->after('provider_id');
            }
            if (!Schema::hasColumn('carts', 'service_address_id')) {
                $table->unsignedBigInteger('service_address_id')->nullable()->after('zone_id');
            }
            if (!Schema::hasColumn('carts', 'service_schedule')) {
                $table->dateTime('service_schedule')->nullable()->after('service_address_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'service_schedule')) {
                $table->dropColumn('service_schedule');
            }
            if (Schema::hasColumn('carts', 'service_address_id')) {
                $table->dropColumn('service_address_id');
            }
            if (Schema::hasColumn('carts', 'zone_id')) {
                $table->dropColumn('zone_id');
            }
        });
    }
};
