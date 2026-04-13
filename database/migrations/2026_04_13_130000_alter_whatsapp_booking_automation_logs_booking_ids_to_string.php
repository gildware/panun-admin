<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_booking_automation_message_logs')) {
            return;
        }

        Schema::table('whatsapp_booking_automation_message_logs', function (Blueprint $table) {
            // Booking IDs are UUID strings; older installs may have bigint columns.
            // Use a conservative length (64) to cover UUID + any prefixes.
            $table->string('booking_id', 64)->nullable()->change();
            $table->string('booking_repeat_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Not safe to down-migrate UUIDs to bigint.
    }
};

