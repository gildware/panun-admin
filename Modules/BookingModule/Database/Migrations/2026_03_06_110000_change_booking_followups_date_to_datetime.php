<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            $table->dateTime('date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            $table->date('date')->change();
        });
    }
};
