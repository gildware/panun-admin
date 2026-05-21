<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cart_service_infos')) {
            Schema::create('cart_service_infos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('customer_id');
                $table->uuid('zone_id')->nullable();
                $table->uuid('service_address_id')->nullable();
                $table->dateTime('service_schedule')->nullable();
                $table->timestamps();
                $table->unique('customer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_service_infos');
    }
};
