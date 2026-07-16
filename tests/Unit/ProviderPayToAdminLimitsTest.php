<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProviderPayToAdminLimitsTest extends TestCase
{
    public function test_ledger_due_capped_by_settlement_debt(): void
    {
        $limits = provider_pay_to_admin_limits(-14.0, 15.0, 0.0);

        $this->assertSame(14.0, $limits['max']);
        $this->assertFalse($limits['is_advance']);
    }

    public function test_settlement_debt_when_ledger_payable_is_zero(): void
    {
        $limits = provider_pay_to_admin_limits(-14.0, 0.0, 0.0);

        $this->assertSame(14.0, $limits['max']);
        $this->assertTrue($limits['is_advance']);
    }

    public function test_no_pay_when_settled(): void
    {
        $limits = provider_pay_to_admin_limits(0.0, 0.0, 0.0);

        $this->assertSame(0.0, $limits['max']);
        $this->assertFalse($limits['is_advance']);
    }

    public function test_ledger_due_without_settlement_debt(): void
    {
        $limits = provider_pay_to_admin_limits(5.0, 20.0, 5.0);

        $this->assertSame(15.0, $limits['max']);
        $this->assertFalse($limits['is_advance']);
    }
}
