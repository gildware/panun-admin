<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_reopen_events')) {
            return;
        }
        Schema::table('booking_reopen_events', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_reopen_events', 'booking_hold_reopen_reason_id')) {
                $table->unsignedBigInteger('booking_hold_reopen_reason_id')->nullable()->after('target_status');
                $table->foreign('booking_hold_reopen_reason_id')
                    ->references('id')
                    ->on('booking_hold_reopen_reasons')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_reopen_events')) {
            return;
        }
        Schema::table('booking_reopen_events', function (Blueprint $table) {
            if (Schema::hasColumn('booking_reopen_events', 'booking_hold_reopen_reason_id')) {
                $table->dropForeign(['booking_hold_reopen_reason_id']);
                $table->dropColumn('booking_hold_reopen_reason_id');
            }
        });
    }
};
