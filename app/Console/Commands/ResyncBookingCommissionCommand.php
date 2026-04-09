<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookingModule\Entities\Booking;

class ResyncBookingCommissionCommand extends Command
{
    protected $signature = 'booking:resync-commission
                            {readable_id : Booking readable_id (e.g. PK09APR26005) or booking UUID}';

    protected $description = 'Recompute booking_details_amounts admin_commission / provider_earning from current totals (incl. extra services) and refresh settlement_snapshot when applicable.';

    public function handle(): int
    {
        $key = trim((string) $this->argument('readable_id'));
        if ($key === '') {
            $this->error('readable_id is required.');

            return self::FAILURE;
        }

        $booking = Booking::query()
            ->where('readable_id', $key)
            ->orWhere('id', $key)
            ->first();

        if (!$booking) {
            $this->error('No booking found for: '.$key);

            return self::FAILURE;
        }

        $booking->resyncStoredCommissionAndSettlementSnapshot();

        $this->info('Resynced commission rows for booking #'.($booking->readable_id ?? $booking->id));

        return self::SUCCESS;
    }
}
