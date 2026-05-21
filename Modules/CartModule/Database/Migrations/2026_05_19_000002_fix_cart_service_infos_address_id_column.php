<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cart_service_infos')) {
            return;
        }

        if (Schema::hasColumn('cart_service_infos', 'service_address_id')) {
            Schema::table('cart_service_infos', function (Blueprint $table) {
                $table->dropColumn('service_address_id');
            });
        }

        Schema::table('cart_service_infos', function (Blueprint $table) {
            $table->unsignedBigInteger('service_address_id')->nullable()->after('zone_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cart_service_infos')) {
            return;
        }

        if (Schema::hasColumn('cart_service_infos', 'service_address_id')) {
            Schema::table('cart_service_infos', function (Blueprint $table) {
                $table->dropColumn('service_address_id');
            });
        }

        Schema::table('cart_service_infos', function (Blueprint $table) {
            $table->uuid('service_address_id')->nullable()->after('zone_id');
        });
    }
};
