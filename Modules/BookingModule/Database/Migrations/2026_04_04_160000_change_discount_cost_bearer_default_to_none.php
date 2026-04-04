<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_details_amounts', function (Blueprint $table) {
            $table->string('discount_cost_bearer', 16)->default('none')->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_details_amounts', function (Blueprint $table) {
            $table->string('discount_cost_bearer', 16)->default('both')->change();
        });
    }
};
