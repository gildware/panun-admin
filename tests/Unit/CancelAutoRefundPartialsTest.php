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

        $walletByPaymentMethod = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $walletByPaymentMethod->transaction_id = 'should-not-matter';
        $walletByPaymentMethod->payment_method = 'wallet';
        $this->assertSame('wallet', booking_refund_ledger_method_key($walletByPaymentMethod));
    }

    public function test_wallet_refund_is_revertible_only_for_customer_wallet_out_rows(): void
    {
        if (! function_exists('booking_wallet_refund_is_revertible')) {
            $this->markTestSkipped('Helper not loaded');
        }

        $wallet = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $wallet->reason = \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND;
        $wallet->type = \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT;
        $wallet->amount = 100;
        $wallet->transaction_id = null;
        $this->assertTrue(booking_wallet_refund_is_revertible($wallet));

        $transfer = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $transfer->reason = \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND;
        $transfer->type = \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT;
        $transfer->amount = 100;
        $transfer->transaction_id = 'BANK-1';
        $this->assertFalse(booking_wallet_refund_is_revertible($transfer));

        $disputed = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $disputed->reason = \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND;
        $disputed->type = \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT;
        $disputed->amount = 100;
        $disputed->received_by = \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_PROVIDER;
        $this->assertFalse(booking_wallet_refund_is_revertible($disputed));

        $companyPool = new \Modules\TransactionModule\Entities\LedgerTransaction;
        $companyPool->reason = \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND;
        $companyPool->type = \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT;
        $companyPool->amount = 100;
        $companyPool->received_by = \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_COMPANY;
        $this->assertFalse(booking_wallet_refund_is_revertible($companyPool));
    }

    public function test_wallet_refund_ledger_trx_reference_round_trips(): void
    {
        if (! function_exists('booking_wallet_refund_ledger_trx_reference')) {
            $this->markTestSkipped('Helper not loaded');
        }

        $ref = booking_wallet_refund_ledger_trx_reference('abc-123');
        $this->assertTrue(is_wallet_refund_ledger_trx_reference($ref));
        $this->assertSame('', sanitize_wallet_transaction_reference_note($ref));
        $this->assertSame('', sanitize_wallet_transaction_reference_note('wallet_refund'));
        $this->assertSame('keep this', sanitize_wallet_transaction_reference_note('keep this'));
    }
}
