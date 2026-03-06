<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations. Ensures booking readable_id is unique and always increasing (never reused after delete).
     */
    public function up(): void
    {
        Schema::create('booking_readable_id_sequence', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('next_value')->default(100000);
        });

        $max = DB::table('bookings')->max('readable_id');
        DB::table('booking_readable_id_sequence')->insert([
            'id' => 1,
            'next_value' => $max ? (int) $max + 1 : 100000,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_readable_id_sequence');
    }
};
