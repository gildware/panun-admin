<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            $table->text('reschedule_reason')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            $table->dropColumn('reschedule_reason');
        });
    }
};
