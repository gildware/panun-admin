<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Tests\TestCase;

class BookingDisputeCloseEligibilityTest extends TestCase
{
    public function test_dispute_and_close_allowed_for_ongoing_with_zero_customer_paid(): void
    {
        $b = new Booking;
        $b->is_repeated = 0;
        $b->booking_status = 'ongoing';
        $b->setRelation('booking_partial_payments', collect());

        $this->assertTrue(booking_admin_can_dispute_and_close($b));
    }
}
