<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Tests\TestCase;

class BookingStatusRestoreCanceledTest extends TestCase
{
    public function test_canceled_status_allows_pending_in_transition_map(): void
    {
        $this->assertSame(['pending'], booking_admin_allowed_next_statuses('canceled'));
        $this->assertSame(['pending'], booking_admin_allowed_next_statuses('cancelled'));
    }

    public function test_canceled_booking_cannot_restore_when_settlement_applied(): void
    {
        $booking = new Booking([
            'booking_status' => 'canceled',
            'settlement_outcome' => 'scaled_to_payments',
        ]);

        $this->assertFalse(booking_admin_can_restore_canceled_to_pending($booking));
    }

    public function test_canceled_booking_can_restore_when_no_settlement(): void
    {
        $booking = new Booking([
            'booking_status' => 'canceled',
            'settlement_outcome' => null,
        ]);

        $this->assertTrue(booking_admin_can_restore_canceled_to_pending($booking));
    }

    public function test_completed_booking_still_has_no_status_transitions(): void
    {
        $this->assertSame([], booking_admin_allowed_next_statuses('completed'));
    }
}
