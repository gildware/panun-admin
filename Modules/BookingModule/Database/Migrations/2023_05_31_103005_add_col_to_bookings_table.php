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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('additional_tax_amount', 24, 2)->default(0);
            $table->decimal('additional_discount_amount', 24, 2)->default(0);
            $table->decimal('additional_campaign_discount_amount', 24, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('additional_tax_amount');
            $table->dropColumn('additional_discount_amount');
            $table->dropColumn('additional_campaign_discount_amount');
        });
    }
};
