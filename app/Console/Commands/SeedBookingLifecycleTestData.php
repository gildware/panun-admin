<?php

namespace App\Console\Commands;

use App\Services\BookingTestMatrix\BookingTestMatrixOrchestrator;
use Illuminate\Console\Command;

class SeedBookingLifecycleTestData extends Command
{
    protected $signature = 'booking:seed-lifecycle-matrix
                            {--fresh : Remove prior [LIFECYCLE-TEST-MATRIX] bookings first}
                            {--customer-phone= : Customer phone (defaults to orchestrator constant)}
                            {--provider-phone= : Provider phone (defaults to orchestrator constant)}';

    protected $description = 'Seed all booking lifecycle scenarios via real checkout flows (one booking per admin list tab).';

    public function handle(BookingTestMatrixOrchestrator $orchestrator): int
    {
        $customerPhone = $this->option('customer-phone') ?: null;
        $providerPhone = $this->option('provider-phone') ?: null;
        $ctx = $orchestrator->resolveContext($customerPhone, $providerPhone);
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
