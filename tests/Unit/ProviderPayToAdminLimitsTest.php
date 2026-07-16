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

    public function test_resolve_amount_defaults_to_max_when_not_requested(): void
    {
        $limits = provider_pay_to_admin_limits(-100.0, 50.0, 0.0);
        $resolved = resolve_provider_pay_to_admin_amount(null, $limits, 0.0);

        $this->assertSame(50.0, $resolved['amount']);
        $this->assertNull($resolved['error']);
    }

    public function test_resolve_amount_accepts_partial_payment(): void
    {
        $limits = provider_pay_to_admin_limits(-100.0, 50.0, 0.0);
        $resolved = resolve_provider_pay_to_admin_amount(25.0, $limits, 0.0);

        $this->assertSame(25.0, $resolved['amount']);
        $this->assertNull($resolved['error']);
    }

    public function test_resolve_amount_rejects_over_max(): void
    {
        $limits = provider_pay_to_admin_limits(-100.0, 50.0, 0.0);
        $resolved = resolve_provider_pay_to_admin_amount(75.0, $limits, 0.0);

        $this->assertSame(0.0, $resolved['amount']);
        $this->assertNotNull($resolved['error']);
    }

    public function test_resolve_amount_enforces_minimum_payable(): void
    {
        $limits = provider_pay_to_admin_limits(-100.0, 50.0, 0.0);
        $resolved = resolve_provider_pay_to_admin_amount(5.0, $limits, 10.0);

        $this->assertSame(0.0, $resolved['amount']);
        $this->assertNotNull($resolved['error']);
    }
}
