<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Tests\TestCase;

class BookingFinancialSettlementServiceUnitTest extends TestCase
{
    private BookingFinancialSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingFinancialSettlementService;
    }

    public function test_resolve_visit_company_percent_from_config(): void
    {
        $main = new Booking;
        $this->assertSame(12.5, $this->service->resolveVisitCompanyPercent($main, ['visit_fee_company_percent' => 12.5]));
    }

    public function test_resolve_visit_company_percent_clamped(): void
    {
        $main = new Booking;
        $this->assertSame(100.0, $this->service->resolveVisitCompanyPercent($main, ['visit_fee_company_percent' => 150]));
        $this->assertSame(0.0, $this->service->resolveVisitCompanyPercent($main, ['visit_fee_company_percent' => -5]));
    }

    public function test_default_visit_percent_from_config(): void
    {
        config(['booking_financial.default_visit_fee_company_percent' => 33.0]);
        $this->assertSame(33.0, $this->service->defaultVisitCompanyPercent());
    }

    public function test_uses_non_standard_settlement_only_when_outcome_set(): void
    {
        $b = new Booking;
        $b->settlement_outcome = null;
        $this->assertFalse($this->service->usesNonStandardSettlement($b));

        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_STANDARD;
        $this->assertFalse($this->service->usesNonStandardSettlement($b));

        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $this->assertTrue($this->service->usesNonStandardSettlement($b));
    }

    public function test_provider_earning_basis_standard_uses_grand_total(): void
    {
        $b = new Booking;
        $b->settlement_outcome = null;

        $this->assertSame(200.0, $this->service->providerEarningBasisAmount($b, 200.0, 50.0));
    }

    public function test_provider_earning_basis_scaled_uses_min_of_grand_and_paid(): void
    {
        $b = new Booking;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        $this->assertSame(40.0, $this->service->providerEarningBasisAmount($b, 100.0, 40.0));
        $this->assertSame(100.0, $this->service->providerEarningBasisAmount($b, 100.0, 150.0));
    }

    public function test_resolve_scaled_loss_breakdown_uses_config_and_caps_paid_to_grand(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000096';
        [$x, $loss, $y, $z] = $this->service->resolveScaledLossBreakdown($b, [
            'scaled_customer_paid_amount' => 400.0,
            'scaled_loss_company_amount' => 250.0,
            'scaled_loss_provider_amount' => 350.0,
        ], 1000.0, 999.0);

        $this->assertSame(400.0, $x);
        $this->assertSame(600.0, $loss);
        $this->assertSame(250.0, $y);
        $this->assertSame(350.0, $z);

        [$x2, $loss2, $y2, $z2] = $this->service->resolveScaledLossBreakdown($b, [], 100.0, 40.0);
        $this->assertSame(40.0, $x2);
        $this->assertSame(60.0, $loss2);
        $this->assertSame(0.0, $y2);
        $this->assertSame(0.0, $z2);
    }

    public function test_validate_scaled_loss_split_rejects_when_company_plus_provider_not_equal_loss(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000095';
        $b->total_booking_amount = 1000.0;
        $b->extra_fee = 0.0;

        $bad = $this->service->validateScaledLossSplit($b, [
            'scaled_customer_paid_amount' => 400.0,
            'scaled_loss_company_amount' => 100.0,
            'scaled_loss_provider_amount' => 100.0,
        ]);
        $this->assertNotNull($bad);

        $good = $this->service->validateScaledLossSplit($b, [
            'scaled_customer_paid_amount' => 400.0,
            'scaled_loss_company_amount' => 300.0,
            'scaled_loss_provider_amount' => 300.0,
        ]);
        $this->assertNull($good);
    }

    public function test_resolve_retained_visit_amount_sums_visit_and_closing_capped_to_grand_total(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000099';
        $b->total_booking_amount = 400.0;
        $b->extra_fee = 50.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;

        $this->assertSame(
            200.0,
            $this->service->resolveRetainedVisitAmount($b, [
                'visit_charges_paid' => 120.0,
                'closing_amount_paid' => 80.0,
            ])
        );

        $this->assertSame(
            450.0,
            $this->service->resolveRetainedVisitAmount($b, [
                'visit_charges_paid' => 400.0,
                'closing_amount_paid' => 100.0,
            ])
        );
    }

    public function test_resolve_retained_visit_amount_visit_fee_split_not_capped_to_original_booking_total(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000097';
        $b->total_booking_amount = 149.0;
        $b->extra_fee = 0.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;

        $this->assertSame(
            150.0,
            $this->service->resolveRetainedVisitAmount($b, [
                'visit_charges_paid' => 100.0,
                'closing_amount_paid' => 50.0,
            ])
        );
    }

    public function test_provider_earning_basis_after_visit_cancel_uses_retained_total(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000098';
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $b->total_booking_amount = 300.0;
        $b->extra_fee = 0.0;
        $b->settlement_config = ['visit_charges_paid' => 50.0, 'closing_amount_paid' => 25.0];

        $this->assertSame(75.0, $this->service->providerEarningBasisAmount($b, 999.0, 0.0));
    }

    public function test_resolve_closing_shares_company_override_only(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000086';

        [$co, $pr] = $this->service->resolveClosingCompanyProviderShares($b, 100.0, [
            'closing_company_share' => 25.0,
        ], null);

        $this->assertSame(25.0, $co);
        $this->assertSame(75.0, $pr);
    }

    public function test_resolve_visit_line_defaults_from_percent_when_no_amount_overrides(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000087';

        [$co, $pr] = $this->service->resolveVisitLineCompanyProviderShares($b, 200.0, [
            'visit_fee_company_percent' => 25.0,
        ], null);

        $this->assertSame(50.0, $co);
        $this->assertSame(150.0, $pr);
    }

    public function test_resolve_visit_line_uses_monetary_overrides(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000088';

        [$co, $pr] = $this->service->resolveVisitLineCompanyProviderShares($b, 200.0, [
            'visit_fee_company_percent' => 10.0,
            'visit_company_amount' => 80.0,
            'visit_provider_amount' => 120.0,
        ], null);

        $this->assertSame(80.0, $co);
        $this->assertSame(120.0, $pr);
    }

    public function test_resolve_closing_shares_both_overrides_scaled_when_sum_exceeds_closing(): void
    {
        $b = new Booking;
        $b->id = '00000000-0000-0000-0000-000000000085';

        [$co, $pr] = $this->service->resolveClosingCompanyProviderShares($b, 100.0, [
            'closing_company_share' => 60.0,
            'closing_provider_share' => 90.0,
        ], null);

        $this->assertSame(40.0, $co);
        $this->assertSame(60.0, $pr);
    }

    public function test_save_settlement_clears_when_standard_without_calling_save(): void
    {
        $booking = $this->getMockBuilder(Booking::class)
            ->onlyMethods(['save'])
            ->getMock();
        $booking->expects($this->once())->method('save')->willReturn(true);

        $booking->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $booking->settlement_snapshot = ['x' => 1];
        $booking->allow_complete_without_full_payment = true;

        $this->service->saveSettlement($booking, BookingFinancialSettlementService::OUTCOME_STANDARD, [], null);

        $this->assertNull($booking->settlement_outcome);
        $this->assertNull($booking->settlement_config);
        $this->assertNull($booking->settlement_snapshot);
        $this->assertFalse((bool) $booking->allow_complete_without_full_payment);
    }

    public function test_save_settlement_allow_complete_true_only_for_scaled_outcome(): void
    {
        $service = new class extends BookingFinancialSettlementService {
            public function buildPreview(Booking $booking): array
            {
                return [
                    'outcome' => (string) ($booking->settlement_outcome ?? ''),
                    'grand_total' => 0.0,
                    'total_paid' => 0.0,
                    'visit_fee' => 0.0,
                    'visit_fee_company_percent' => 0.0,
                    'retained_on_cancel' => 0.0,
                    'suggested_customer_refund' => 0.0,
                    'company_commission' => 0.0,
                    'company_commission_after_promos' => 0.0,
                    'provider_earning' => 0.0,
                    'custom_admin_commission' => 0.0,
                    'allow_complete_without_full_payment' => (bool) ($booking->allow_complete_without_full_payment ?? false),
                ];
            }
        };

        $scaled = $this->getMockBuilder(Booking::class)->onlyMethods(['save'])->getMock();
        $scaled->expects($this->once())->method('save')->willReturn(true);
        $service->saveSettlement($scaled, BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS, [], null);
        $this->assertTrue((bool) $scaled->allow_complete_without_full_payment);

        $custom = $this->getMockBuilder(Booking::class)->onlyMethods(['save'])->getMock();
        $custom->expects($this->once())->method('save')->willReturn(true);
        $service->saveSettlement($custom, BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION, ['custom_admin_commission' => 5], null);
        $this->assertFalse((bool) $custom->allow_complete_without_full_payment);
    }
}

