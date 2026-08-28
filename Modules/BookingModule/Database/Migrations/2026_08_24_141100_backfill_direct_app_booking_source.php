<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\Source;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'booking_source')) {
            return;
        }

        $source = strtolower(Source::NAME_DIRECT_APP_BOOKING);

        DB::table('bookings')
            ->where(function ($query) {
                $query->whereNull('booking_source')
                    ->orWhereRaw("TRIM(booking_source) = ''")
                    ->orWhereRaw('LOWER(TRIM(booking_source)) = ?', ['app']);
            })
            ->update(['booking_source' => $source]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bookings', 'booking_source')) {
            return;
        }

        DB::table('bookings')
            ->whereRaw('LOWER(TRIM(booking_source)) = ?', [strtolower(Source::NAME_DIRECT_APP_BOOKING)])
            ->update(['booking_source' => null]);
    }
};
