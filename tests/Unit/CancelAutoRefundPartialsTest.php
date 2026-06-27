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

    public function test_refund_channel_breakdown_splits_wallet_and_digital(): void
    {
        if (! function_exists('get_booking_customer_refund_channel_breakdown')) {
            $this->markTestSkipped('Helper not loaded');
        }

        $booking = new \Modules\BookingModule\Entities\Booking;
        $booking->setRelation('booking_partial_payments', collect([
            (object) ['paid_with' => 'wallet', 'paid_amount' => 50],
            (object) ['paid_with' => 'digital', 'paid_amount' => 100],
        ]));

        $breakdown = get_booking_customer_refund_channel_breakdown($booking);

        $this->assertSame(50.0, $breakdown['wallet_paid']);
        $this->assertSame(100.0, $breakdown['digital_paid']);
        $this->assertTrue($breakdown['has_mixed_payments']);
        $this->assertTrue($breakdown['requires_digital_refund_choice']);
    }

    public function test_refund_ledger_method_key_distinguishes_wallet_and_transfer(): void
    {
        if (! function_exists('booking_refund_ledger_method_key')) {
            $this->markTestSkipped('Helper not loaded');
        }

        $walletEntry = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $walletEntry->transaction_id = null;

        $transferEntry = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $transferEntry->transaction_id = 'TXN-12345';

        $this->assertSame('wallet', booking_refund_ledger_method_key($walletEntry));
        $this->assertSame('transfer', booking_refund_ledger_method_key($transferEntry));
    }
}
