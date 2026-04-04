<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pure refund cap for visit-retained cancel (no Laravel bootstrap required).
 */
class BookingVisitRetainedRefundCapTest extends TestCase
{
    public function test_caps_when_computed_exceeds_paid_minus_retained(): void
    {
        $this->assertSame(
            70.0,
            booking_cap_refund_for_visit_retained(100.0, 100.0, 30.0)
        );
    }

    public function test_unchanged_when_computed_within_cap(): void
    {
        $this->assertSame(
            50.0,
            booking_cap_refund_for_visit_retained(50.0, 100.0, 30.0)
        );
    }

    public function test_zero_when_retained_exceeds_paid(): void
    {
        $this->assertSame(
            0.0,
            booking_cap_refund_for_visit_retained(500.0, 40.0, 100.0)
        );
    }

    public function test_zero_when_no_computed_refund(): void
    {
        $this->assertSame(0.0, booking_cap_refund_for_visit_retained(0.0, 100.0, 30.0));
    }

    public function test_full_refund_when_retained_zero(): void
    {
        $this->assertSame(100.0, booking_cap_refund_for_visit_retained(100.0, 100.0, 0.0));
    }

    public function test_negative_computed_clamped_to_zero(): void
    {
        $this->assertSame(0.0, booking_cap_refund_for_visit_retained(-10.0, 100.0, 0.0));
    }
}
