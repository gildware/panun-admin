<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Change readable_id to string for format PK-DD-MON-YY-NNN and add daily sequence table.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('readable_id', 20)->change();
        });

        Schema::create('booking_readable_id_daily', function (Blueprint $table) {
            $table->date('booking_date')->primary();
            $table->unsignedSmallInteger('next_value')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('readable_id')->change();
        });
        Schema::dropIfExists('booking_readable_id_daily');
    }
};
