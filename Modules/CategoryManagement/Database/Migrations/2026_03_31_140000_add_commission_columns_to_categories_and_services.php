<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('commission_custom')->default(false);
            $table->json('commission_tier_setup')->nullable();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('commission_custom')->default(false);
            $table->json('commission_tier_setup')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['commission_custom', 'commission_tier_setup']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['commission_custom', 'commission_tier_setup']);
        });
    }
};
