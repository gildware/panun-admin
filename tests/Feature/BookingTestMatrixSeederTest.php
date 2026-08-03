<?php

namespace Tests\Feature;

use App\Services\BookingTestMatrix\BookingTestMatrixOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Tests\TestCase;

/**
 * Seeds lifecycle test bookings through real checkout + status flows (not raw DB inserts).
 *
 * Run: php artisan test --filter=BookingTestMatrixSeederTest
 *
 * @group booking-seed
 * @group integration
 */
class BookingTestMatrixSeederTest extends TestCase
{
    public function test_seeds_full_booking_matrix_for_customer_and_provider(): void
    {
        Notification::fake();

        /** @var BookingTestMatrixOrchestrator $orchestrator */
        $orchestrator = app(BookingTestMatrixOrchestrator::class);
        $ctx = $orchestrator->resolveContext();

        $created = $orchestrator->seedAll(fresh: true);

        $this->assertCount(17, $created, 'Expected 17 lifecycle scenarios');

        foreach ($created as $key => $booking) {
            $this->assertInstanceOf(Booking::class, $booking);
            $this->assertStringContainsString(BookingTestMatrixOrchestrator::TAG, (string) $booking->service_description, $key);
            $this->assertSame($ctx['customer']->id, $booking->customer_id, $key);
        }

        $this->assertSame('pending', $created['pending']->booking_status);
        $this->assertNull($created['pending']->provider_id);
        $this->assertSame('accepted', $created['accepted']->booking_status);
        $this->assertSame('canceled', $created['canceled']->booking_status);
        $this->assertSame('ongoing', $created['ongoing']->booking_status);
        $this->assertSame('completed', $created['completed']->booking_status);
        $this->assertSame('on_hold', $created['on_hold']->booking_status);
        $this->assertNotNull($created['reopened']->last_reopen_event_at);
        $this->assertNotNull($created['resolved']->reopen_resolved_at);
        $this->assertNotEmpty($created['disputed_cancelled']->reopen_disputed_snapshot);
        $this->assertSame('canceled', $created['disputed_cancelled']->booking_status);
        $this->assertNotEmpty($created['disputed_completed']->reopen_disputed_snapshot);
        $this->assertSame('completed', $created['completed_no_or_little']->booking_status);
        $this->assertTrue($created['cancelled_after_visit']->after_visit_cancel);
        $this->assertSame('scaled_to_payments', $created['loss_making_pending']->settlement_outcome);

        $matrixIds = Booking::query()
            ->where('service_description', 'like', BookingTestMatrixOrchestrator::TAG . '%')
            ->pluck('id');

        $this->assertGreaterThanOrEqual(17, $matrixIds->count());

        $this->assertGreaterThan(0, BookingStatusHistory::query()->whereIn('booking_id', $matrixIds)->count());
        $this->assertGreaterThan(0, BookingPartialPayment::query()->whereIn('booking_id', $matrixIds)->count());
        $this->assertGreaterThan(0, $orchestrator->ledgerCountForMatrix());
        $this->assertGreaterThan(0, LedgerTransaction::query()->whereIn('booking_id', $matrixIds)->count());
        $this->assertGreaterThan(0, Transaction::query()->whereIn('booking_id', $matrixIds)->count());

        $companyLedger = LedgerTransaction::query()
            ->whereIn('booking_id', $matrixIds)
            ->where('received_by', LedgerTransaction::RECEIVED_BY_COMPANY)
            ->count();
        $providerLedger = LedgerTransaction::query()
            ->whereIn('booking_id', $matrixIds)
            ->where('received_by', LedgerTransaction::RECEIVED_BY_PROVIDER)
            ->count();
        $this->assertGreaterThan(0, $companyLedger, 'Expected company ledger rows');
        $this->assertGreaterThan(0, $providerLedger, 'Expected provider ledger rows');

        $providerPartial = BookingPartialPayment::query()
            ->whereIn('booking_id', $matrixIds)
            ->where('received_by', 'provider')
            ->count();
        $this->assertGreaterThan(0, $providerPartial, 'Expected provider-collected partial payments');

        $lossPending = Booking::query()
            ->whereIn('id', $matrixIds)
            ->lossMakingPending()
            ->count();
        $lossRecovered = Booking::query()
            ->whereIn('id', $matrixIds)
            ->lossRecovered()
            ->count();
        $lossSettled = Booking::query()
            ->whereIn('id', $matrixIds)
            ->lossSettled()
            ->count();
        $this->assertGreaterThanOrEqual(1, $lossPending);
        $this->assertGreaterThanOrEqual(1, $lossRecovered);
        $this->assertGreaterThanOrEqual(1, $lossSettled);

        $this->assertGreaterThanOrEqual(
            1,
            Booking::query()->whereIn('id', $matrixIds)->holdAfterVisit()->count(),
            'Expected hold_after_visit booking'
        );
        $this->assertGreaterThanOrEqual(
            1,
            Booking::query()->whereIn('id', $matrixIds)->cancelledAfterVisit()->count(),
            'Expected cancel_after_visit booking'
        );

        fwrite(STDERR, PHP_EOL . 'Seeded lifecycle matrix bookings:' . PHP_EOL);
        foreach ($created as $key => $booking) {
            fwrite(STDERR, sprintf(
                "  %-22s %s | %s\n",
                $key,
                $booking->readable_id,
                $booking->booking_status
            ));
        }
        fwrite(STDERR, sprintf(
            "  Ledger rows: %d | Transactions: %d\n",
            LedgerTransaction::query()->whereIn('booking_id', $matrixIds)->count(),
            Transaction::query()->whereIn('booking_id', $matrixIds)->count()
        ));
    }
}
