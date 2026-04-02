<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->json('additional_charge_overrides')->nullable()->after('commission_tier_setup');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->json('additional_charge_overrides')->nullable()->after('commission_tier_setup');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('additional_charge_overrides');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('additional_charge_overrides');
        });
    }
};
