<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('superfone_calls', function (Blueprint $table) {
            $table->boolean('manual_unmatched')->default(false)->after('booking_followup_id');
        });
    }

    public function down(): void
    {
        Schema::table('superfone_calls', function (Blueprint $table) {
            $table->dropColumn('manual_unmatched');
        });
    }
};
