<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Tests\TestCase;

class BookingCanBeCompletedSettlementTest extends TestCase
{
    public function test_allows_completion_when_parent_booking_waives_payment_check(): void
    {
        $booking = new Booking;
        $booking->allow_complete_without_full_payment = true;

        $this->assertTrue(booking_can_be_completed($booking));
    }

    public function test_allows_completion_when_settlement_is_loss_making_scaled(): void
    {
        $booking = new Booking;
        $booking->allow_complete_without_full_payment = false;
        $booking->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        $this->assertTrue(booking_can_be_completed($booking));
    }

    public function test_allows_completion_when_repeat_parent_waives_payment_check(): void
    {
        $parent = new Booking;
        $parent->allow_complete_without_full_payment = true;

        $repeat = new BookingRepeat;
        $repeat->setRelation('booking', $parent);

        $this->assertTrue(booking_can_be_completed($repeat));
    }

    public function test_allows_completion_when_repeat_parent_is_loss_making_scaled(): void
    {
        $parent = new Booking;
        $parent->allow_complete_without_full_payment = false;
        $parent->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        $repeat = new BookingRepeat;
        $repeat->setRelation('booking', $parent);

        $this->assertTrue(booking_can_be_completed($repeat));
    }

    public function test_repeat_checks_parent_not_repeat_attribute(): void
    {
        $parent = new Booking;
        $parent->allow_complete_without_full_payment = false;

        $repeat = new BookingRepeat;
        $repeat->allow_complete_without_full_payment = true;
        $repeat->setRelation('booking', $parent);
        $repeat->is_paid = false;
        $repeat->total_booking_amount = 100;
        $repeat->booking_id = '00000000-0000-0000-0000-000000000001';
        $repeat->extra_fee = 0;

        $this->assertFalse(booking_can_be_completed($repeat));
    }
}
