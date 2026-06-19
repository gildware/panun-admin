<?php

namespace App\Console\Commands;

use App\Services\BookingTestMatrix\BookingTestMatrixOrchestrator;
use Illuminate\Console\Command;

class SeedBookingLifecycleTestData extends Command
{
    protected $signature = 'booking:seed-lifecycle-matrix
                            {--fresh : Remove prior [LIFECYCLE-TEST-MATRIX] bookings first}';

    protected $description = 'Seed all booking lifecycle scenarios for customer 7889729790 + provider 9353294014 via real checkout flows.';

    public function handle(BookingTestMatrixOrchestrator $orchestrator): int
    {
        $ctx = $orchestrator->resolveContext();
        $this->info('Customer: ' . $ctx['customer']->phone . ' (' . $ctx['customer']->id . ')');
        $this->info('Provider: ' . $ctx['provider_user']->phone . ' (' . $ctx['provider']->id . ')');

        $created = $orchestrator->seedAll(fresh: (bool) $this->option('fresh'));

        $rows = [];
        foreach ($created as $key => $booking) {
            $rows[] = [$key, $booking->readable_id, $booking->booking_status];
        }
        $this->table(['Scenario', 'Readable ID', 'Status'], $rows);
        $this->info('Ledger rows: ' . $orchestrator->ledgerCountForMatrix());

        return self::SUCCESS;
    }
}
