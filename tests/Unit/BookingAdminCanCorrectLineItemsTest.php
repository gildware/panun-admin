<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Tests\TestCase;

class BookingAdminCanCorrectLineItemsTest extends TestCase
{
    public function test_open_and_completed_bookings_can_be_corrected(): void
    {
        foreach (['pending', 'accepted', 'ongoing', 'on_hold', 'completed'] as $status) {
            $booking = new Booking;
            $booking->booking_status = $status;
            $this->assertTrue(booking_admin_can_correct_line_items($booking), $status);
        }
    }

    public function test_canceled_and_refunded_bookings_cannot_be_corrected(): void
    {
        foreach (['canceled', 'cancelled', 'refunded'] as $status) {
            $booking = new Booking;
            $booking->booking_status = $status;
            $this->assertFalse(booking_admin_can_correct_line_items($booking), $status);
        }
    }

    public function test_completed_repeat_visits_can_be_corrected(): void
    {
        $visit = new BookingRepeat;
        $visit->booking_status = 'completed';
        $this->assertTrue(booking_admin_can_correct_line_items($visit));
    }
}
