<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Tests\TestCase;

class BookingCompensationEligibilityTest extends TestCase
{
    public function test_compensation_eligible_for_terminal_statuses_on_single_booking(): void
    {
        foreach (['completed', 'canceled', 'cancelled', 'refunded'] as $status) {
            $b = new Booking;
            $b->is_repeated = 0;
            $b->booking_status = $status;
            $this->assertTrue($b->adminEligibleForCompensationRecording(), "expected eligible for status {$status}");
        }
    }

    public function test_compensation_not_eligible_for_non_terminal_or_repeat(): void
    {
        $ongoing = new Booking;
        $ongoing->is_repeated = 0;
        $ongoing->booking_status = 'ongoing';
        $this->assertFalse($ongoing->adminEligibleForCompensationRecording());

        $repeat = new Booking;
        $repeat->is_repeated = 1;
        $repeat->booking_status = 'completed';
        $this->assertFalse($repeat->adminEligibleForCompensationRecording());
    }
}
