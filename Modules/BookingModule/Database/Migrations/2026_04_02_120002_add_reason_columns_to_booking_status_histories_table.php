<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_status_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_status_histories', 'booking_cancellation_reason_id')) {
                $table->unsignedBigInteger('booking_cancellation_reason_id')->nullable()->after('booking_status');
            }
            if (! Schema::hasColumn('booking_status_histories', 'booking_hold_reopen_reason_id')) {
                $table->unsignedBigInteger('booking_hold_reopen_reason_id')->nullable()->after('booking_cancellation_reason_id');
            }
            if (! Schema::hasColumn('booking_status_histories', 'status_change_remarks')) {
                $table->text('status_change_remarks')->nullable()->after('booking_hold_reopen_reason_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_status_histories', function (Blueprint $table) {
            $table->dropColumn([
                'booking_cancellation_reason_id',
                'booking_hold_reopen_reason_id',
                'status_change_remarks',
            ]);
        });
    }
};
