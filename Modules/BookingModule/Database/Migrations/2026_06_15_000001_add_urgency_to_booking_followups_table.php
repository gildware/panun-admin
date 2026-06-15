<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_followups') && !Schema::hasColumn('booking_followups', 'urgency')) {
            Schema::table('booking_followups', function (Blueprint $table) {
                $table->string('urgency', 10)->default('medium')->after('remarks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_followups') && Schema::hasColumn('booking_followups', 'urgency')) {
            Schema::table('booking_followups', function (Blueprint $table) {
                $table->dropColumn('urgency');
            });
        }
    }
};
