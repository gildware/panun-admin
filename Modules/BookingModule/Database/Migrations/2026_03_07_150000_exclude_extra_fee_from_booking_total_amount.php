<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * total_booking_amount is now stored excluding extra_fee; get_booking_total_amount() adds it.
 * Subtract extra_fee from existing total_booking_amount so old data matches the new logic.
 */
class ExcludeExtraFeeFromBookingTotalAmount extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->where('extra_fee', '>', 0)
            ->update([
                'total_booking_amount' => DB::raw('GREATEST(0, total_booking_amount - extra_fee)'),
            ]);

        if (Schema::hasTable('booking_repeats')) {
            DB::table('booking_repeats')
                ->where('extra_fee', '>', 0)
                ->update([
                    'total_booking_amount' => DB::raw('GREATEST(0, total_booking_amount - extra_fee)'),
                ]);
        }
    }

    public function down(): void
    {
        // Restore by adding extra_fee back (best-effort; cannot distinguish rows we changed)
        DB::table('bookings')
            ->where('extra_fee', '>', 0)
            ->update([
                'total_booking_amount' => DB::raw('total_booking_amount + extra_fee'),
            ]);

        if (Schema::hasTable('booking_repeats')) {
            DB::table('booking_repeats')
                ->where('extra_fee', '>', 0)
                ->update([
                    'total_booking_amount' => DB::raw('total_booking_amount + extra_fee'),
                ]);
        }
    }
}
