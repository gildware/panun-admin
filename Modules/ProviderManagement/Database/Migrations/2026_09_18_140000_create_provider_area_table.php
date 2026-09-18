<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_area')) {
            return;
        }
        if (! Schema::hasTable('providers') || ! Schema::hasTable('customer_lead_areas')) {
            return;
        }

        Schema::create('provider_area', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id');
            $table->unsignedBigInteger('area_id');
            $table->timestamps();

            $table->unique(['provider_id', 'area_id']);
            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
            $table->foreign('area_id')->references('id')->on('customer_lead_areas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_area');
    }
};
