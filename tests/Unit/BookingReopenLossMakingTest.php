<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\BookingModule\Services\BookingReopenService;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class BookingReopenLossMakingTest extends TestCase
{
    public function test_booking_is_loss_making_when_settlement_is_scaled_to_payments(): void
    {
        $b = new Booking;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $this->assertTrue($b->isLossMakingFinancialSettlement());

        $b2 = new Booking;
        $b2->settlement_outcome = BookingFinancialSettlementService::OUTCOME_STANDARD;
        $this->assertFalse($b2->isLossMakingFinancialSettlement());
    }

    public function test_loss_making_false_when_scaled_customer_shortfall_is_fully_recovered(): void
    {
        $b = new Booking;
        $b->total_booking_amount = 600.0;
        $b->extra_fee = 0.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->settlement_config = ['scaled_customer_paid_amount' => 100.0];
        $b->setRelation('extra_services', collect());
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 600.0, 'received_by' => 'company'],
        ]));

        $this->assertFalse($b->isLossMakingFinancialSettlement());
        $this->assertTrue($b->isScaledSettlementLossRecovered());
    }

    public function test_reopen_in_place_rejects_completed_loss_making_booking(): void
    {
        $booking = new Booking;
        $booking->is_repeated = 0;
        $booking->booking_status = 'completed';
        $booking->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        $actor = new User;
        $actor->id = '00000000-0000-0000-0000-000000000001';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(translate('Loss_making_completed_booking_cannot_be_reopened'));

        app(BookingReopenService::class)->reopenInPlace($booking, $actor, '', 'accepted', null, null);
    }

    public function test_link_follow_up_rejects_loss_making_source(): void
    {
        $source = new Booking;
        $source->is_repeated = 0;
        $source->booking_status = 'completed';
        $source->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        $newBooking = new Booking;
        $actor = new User;
        $actor->id = '00000000-0000-0000-0000-000000000001';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(translate('Loss_making_completed_booking_cannot_be_reopened'));

        app(BookingReopenService::class)->linkNewBookingFromReopenedCompleted($source, $newBooking, $actor, '', null);
    }
}
