<?php

namespace Tests\Unit;

use Modules\TransactionModule\Entities\LedgerTransaction;
use Tests\TestCase;

class ProviderSettlementLedgerRevertTest extends TestCase
{
    public function test_manual_payout_and_collect_are_revertible(): void
    {
        $payout = new LedgerTransaction([
            'type' => LedgerTransaction::TYPE_OUT,
            'reason' => LedgerTransaction::REASON_PROVIDER_PAYOUT,
            'provider_id' => 'provider-1',
            'reference_note' => 'Bank transfer',
        ]);
        $collect = new LedgerTransaction([
            'type' => LedgerTransaction::TYPE_IN,
            'provider_id' => 'provider-1',
            'payment_method' => 'collect_from_provider',
        ]);

        $this->assertTrue(provider_settlement_ledger_is_revertible($payout));
        $this->assertTrue(provider_settlement_ledger_is_revertible($collect));
    }

    public function test_withdrawal_payout_and_booking_rows_are_not_revertible(): void
    {
        $withdrawal = new LedgerTransaction([
            'type' => LedgerTransaction::TYPE_OUT,
            'reason' => LedgerTransaction::REASON_PROVIDER_PAYOUT,
            'provider_id' => 'provider-1',
            'reference_note' => 'Provider withdrawal request abc',
        ]);
        $bookingCollect = new LedgerTransaction([
            'type' => LedgerTransaction::TYPE_IN,
            'provider_id' => 'provider-1',
            'booking_id' => 'booking-1',
        ]);

        $this->assertFalse(provider_settlement_ledger_is_revertible($withdrawal));
        $this->assertFalse(provider_settlement_ledger_is_revertible($bookingCollect));
    }

    public function test_trx_reference_keeps_advance_top_up(): void
    {
        $ref = provider_settlement_ledger_trx_reference('11111111-1111-1111-1111-111111111111', 60.5, 10);

        $this->assertSame('psl:11111111-1111-1111-1111-111111111111|r:60.50|p:10.00', $ref);
        $this->assertSame([60.5, 10.0], provider_settlement_ledger_topups_from_reference($ref));
        $this->assertSame(
            [0.0, 0.0],
            provider_settlement_ledger_topups_from_reference('psl:11111111-1111-1111-1111-111111111111')
        );
    }
}
