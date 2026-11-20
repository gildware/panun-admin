<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookingModule\Services\BookingFollowupMissedRecorder;

class RecordBookingFollowupMissesCommand extends Command
{
    protected $signature = 'bookings:record-followup-misses';

    protected $description = 'Persist missed booking follow-ups for open bookings once due time has passed';

    public function handle(BookingFollowupMissedRecorder $recorder): int
    {
        $recorded = $recorder->recordOverdueMisses();
        $this->info("Booking follow-up misses recorded: {$recorded}");

        return self::SUCCESS;
    }
}
