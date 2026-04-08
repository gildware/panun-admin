<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Tests\TestCase;

/**
 * Asserts admin commission and provider-side math across settlement outcomes
 * (standard, visit decided charges, custom commission, scaled) for in-memory bookings.
 */
class BookingFinancialSettlementCommissionScenariosTest extends TestCase
{
    private BookingFinancialSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingFinancialSettlementService;
    }

    private function baseInMemoryBooking(): Booking
    {
        $b = new Booking;
        $b->id = (string) Str::uuid();
        $b->total_booking_amount = 1000.0;
        $b->extra_fee = 0.0;
        $b->is_paid = 0;
        $b->setRelation('extra_services', collect());
        $b->setRelation('booking_partial_payments', collect());

        return $b;
    }

    public function test_custom_commission_uses_settlement_config_amount(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION;
        $b->settlement_config = ['custom_admin_commission' => 123.45];

        $d = $this->service->calculateAdminCommissionDetails($b, null);
        $this->assertSame(123.45, $d['adminCommission']);
        $this->assertSame(123.45, $d['adminCommissionWithoutCost']);
    }

    public function test_visit_fee_split_commission_is_visit_company_share_plus_closing_company_share(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->total_booking_amount = 800.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $b->settlement_config = [
            'visit_charges_paid' => 200.0,
            'visit_fee_company_percent' => 30.0,
            'closing_amount_paid' => 100.0,
            'closing_company_share' => 40.0,
            'closing_provider_share' => 60.0,
        ];

        $visitCo = round(200.0 * 0.30, 2);
        $closingCo = 40.0;
        $expected = round($visitCo + $closingCo, 2);

        $d = $this->service->calculateAdminCommissionDetails($b, null);
        $this->assertSame($expected, $d['adminCommission']);
    }

    public function test_visit_retained_cancel_commission_matches_decided_charge_splits(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->total_booking_amount = 800.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $b->settlement_config = [
            'visit_charges_paid' => 200.0,
            'visit_fee_company_percent' => 30.0,
            'closing_amount_paid' => 100.0,
            'closing_company_share' => 40.0,
            'closing_provider_share' => 60.0,
        ];

        $visitCo = round(200.0 * 0.30, 2);
        $expected = round($visitCo + 40.0, 2);

        $d = $this->service->calculateAdminCommissionDetails($b, null);
        $this->assertSame($expected, $d['adminCommission']);
    }

    public function test_standard_commission_matches_calculate_commission_for_booking(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_STANDARD;

        $fromHelper = calculate_commission_for_booking($b, null);
        $d = $this->service->calculateAdminCommissionDetails($b, null);

        $this->assertSame(round((float) $fromHelper['commission'], 2), $d['adminCommission']);
        $this->assertSame($d['adminCommission'], $d['adminCommissionWithoutCost']);
    }

    public function test_empty_outcome_treated_as_standard_for_commission(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->settlement_outcome = null;

        $fromHelper = calculate_commission_for_booking($b, null);
        $d = $this->service->calculateAdminCommissionDetails($b, null);

        $this->assertSame(round((float) $fromHelper['commission'], 2), $d['adminCommission']);
    }

    public function test_scaled_commission_matches_full_tier_on_booking_total(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 400.0],
        ]));

        $full = (float) calculate_commission_for_booking($b, null)['commission'];
        $d = $this->service->calculateAdminCommissionDetails($b, null);
        $this->assertEqualsWithDelta(round($full, 2), $d['adminCommission'], 0.02);
    }

    public function test_scaled_commission_caps_ratio_at_one_when_overpaid(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 2000.0],
        ]));

        $full = (float) calculate_commission_for_booking($b, null)['commission'];
        $d = $this->service->calculateAdminCommissionDetails($b, null);

        $this->assertEqualsWithDelta(round($full, 2), $d['adminCommission'], 0.02);
    }

    public function test_visit_fee_split_provider_earning_matches_line_shares_and_sync_formula(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->total_booking_amount = 800.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $config = [
            'visit_charges_paid' => 200.0,
            'visit_fee_company_percent' => 30.0,
            'closing_amount_paid' => 100.0,
            'closing_company_share' => 40.0,
            'closing_provider_share' => 60.0,
        ];
        $b->settlement_config = $config;

        $grand = get_booking_total_amount($b);
        $paid = $this->service->totalPaidForMainBooking($b);
        $basis = $this->service->providerEarningBasisAmount($b, $grand, $paid);
        $this->assertSame(
            round($this->service->resolveRetainedVisitAmount($b, $config), 2),
            $basis
        );

        $details = $this->service->calculateAdminCommissionDetails($b, null);
        $expectedFromSync = round(max(0.0, $basis - $details['adminCommissionWithoutCost']), 2);

        $visitPaid = $this->service->resolveVisitChargesPaid($b, $config);
        $closingPaid = $this->service->resolveClosingAmountPaid($b, $config);
        [, $prV] = $this->service->resolveVisitLineCompanyProviderShares($b, $visitPaid, $config, null);
        [, $prC] = $this->service->resolveClosingCompanyProviderShares($b, $closingPaid, $config, null);
        $providerFromLines = round($prV + $prC, 2);

        $this->assertSame($providerFromLines, $expectedFromSync);
    }

    public function test_scaled_provider_basis_uses_full_grand_total(): void
    {
        $b = $this->baseInMemoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 400.0],
        ]));

        $grand = get_booking_total_amount($b);
        $paid = $this->service->totalPaidForMainBooking($b);
        $basis = $this->service->providerEarningBasisAmount($b, $grand, $paid);

        $this->assertSame(1000.0, $basis);
    }
}
