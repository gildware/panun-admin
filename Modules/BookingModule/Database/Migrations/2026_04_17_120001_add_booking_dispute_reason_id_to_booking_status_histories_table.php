<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_status_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_status_histories', 'booking_dispute_reason_id')) {
                $table->unsignedBigInteger('booking_dispute_reason_id')->nullable()->after('booking_hold_reopen_reason_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_status_histories', function (Blueprint $table) {
            if (Schema::hasColumn('booking_status_histories', 'booking_dispute_reason_id')) {
                $table->dropColumn('booking_dispute_reason_id');
            }
        });
    }
};

