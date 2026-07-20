<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Provider list / performance metrics filter heavily by provider_id.
            // Without this index MySQL full-scans bookings and admin pages hang.
            if (! Schema::hasIndex('bookings', 'bookings_provider_id_index')) {
                $table->index('provider_id', 'bookings_provider_id_index');
            }
            if (! Schema::hasIndex('bookings', 'bookings_provider_id_booking_status_index')) {
                $table->index(['provider_id', 'booking_status'], 'bookings_provider_id_booking_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasIndex('bookings', 'bookings_provider_id_booking_status_index')) {
                $table->dropIndex('bookings_provider_id_booking_status_index');
            }
            if (Schema::hasIndex('bookings', 'bookings_provider_id_index')) {
                $table->dropIndex('bookings_provider_id_index');
            }
        });
    }
};
