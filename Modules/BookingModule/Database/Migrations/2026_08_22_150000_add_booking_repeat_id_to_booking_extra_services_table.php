<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingRepeatIdToBookingExtraServicesTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_extra_services')) {
            return;
        }

        Schema::table('booking_extra_services', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_extra_services', 'booking_repeat_id')) {
                $table->foreignUuid('booking_repeat_id')
                    ->nullable()
                    ->after('booking_id')
                    ->constrained('booking_repeats')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_extra_services') || ! Schema::hasColumn('booking_extra_services', 'booking_repeat_id')) {
            return;
        }

        Schema::table('booking_extra_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_repeat_id');
        });
    }
}
