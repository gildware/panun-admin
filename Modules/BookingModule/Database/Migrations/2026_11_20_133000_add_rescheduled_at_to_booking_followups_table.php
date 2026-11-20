<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_followups', 'rescheduled_at')) {
                $table->timestamp('rescheduled_at')->nullable()->after('next_followup_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_followups', function (Blueprint $table) {
            if (Schema::hasColumn('booking_followups', 'rescheduled_at')) {
                $table->dropColumn('rescheduled_at');
            }
        });
    }
};
