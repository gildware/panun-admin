<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CancelAutoRefundPartialsTest extends TestCase
{
    public function test_admin_entry_partials_are_excluded_from_cancel_auto_refund_sum(): void
    {
        $partials = collect([
            (object) ['paid_with' => 'admin_entry', 'paid_amount' => 1505.64],
        ]);
        $this->assertSame(0.0, booking_sum_partials_for_cancel_platform_auto_refund($partials));
    }

    public function test_digital_and_wallet_partials_are_included(): void
    {
        $partials = collect([
            (object) ['paid_with' => 'digital', 'paid_amount' => 100],
            (object) ['paid_with' => 'wallet', 'paid_amount' => 50],
        ]);
        $this->assertSame(150.0, booking_sum_partials_for_cancel_platform_auto_refund($partials));
    }

    public function test_cash_after_service_and_offline_partials_are_excluded(): void
    {
        $partials = collect([
            (object) ['paid_with' => 'cash_after_service', 'paid_amount' => 200],
            (object) ['paid_with' => 'offline', 'paid_amount' => 300],
            (object) ['paid_with' => 'digital', 'paid_amount' => 40],
        ]);
        $this->assertSame(40.0, booking_sum_partials_for_cancel_platform_auto_refund($partials));
    }
}
