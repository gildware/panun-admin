<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Tests\TestCase;

/**
 * Extra coverage for settlement static helpers, payment totals, preview, invoice due, and revenue reporting.
 */
class BookingFinancialSettlementExtendedCoverageTest extends TestCase
{
    private BookingFinancialSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingFinancialSettlementService;
    }

    private function memoryBooking(): Booking
    {
        $b = new Booking;
        $b->id = (string) Str::uuid();
        $b->provider_id = null;
        $b->total_booking_amount = 1000.0;
        $b->extra_fee = 0.0;
        $b->is_paid = 0;
        $b->payment_method = 'online';
        $b->setRelation('extra_services', collect());
        $b->setRelation('booking_partial_payments', collect());

        return $b;
    }

    public function test_outcome_uses_decided_visit_charges_trims_whitespace(): void
    {
        $this->assertTrue(BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges('  visit_fee_split  '));
        $this->assertFalse(BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges('custom_commission'));
    }

    public function test_special_scenario_list_tab_outcomes(): void
    {
        $tabs = BookingFinancialSettlementService::specialScenarioListTabOutcomes();
        $this->assertArrayHasKey('all', $tabs);
        $this->assertNull($tabs['all']);
        $this->assertSame(BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS, $tabs['loss_making']);
        $this->assertSame(BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL, $tabs['cancelled_after_visit']);
        $this->assertSame(BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT, $tabs['little_or_no_service']);
        $this->assertSame(BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION, $tabs['custom_commission']);
    }

    public function test_outcome_options_contains_each_constant_key(): void
    {
        $opts = BookingFinancialSettlementService::outcomeOptions();
        $this->assertArrayHasKey(BookingFinancialSettlementService::OUTCOME_STANDARD, $opts);
        $this->assertArrayHasKey(BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL, $opts);
        $this->assertArrayHasKey(BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT, $opts);
        $this->assertArrayHasKey(BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION, $opts);
        $this->assertArrayHasKey(BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS, $opts);
    }

    public function test_main_booking_for_repeat_uses_loaded_parent(): void
    {
        $parent = new Booking;
        $parent->id = (string) Str::uuid();

        $repeat = new BookingRepeat;
        $repeat->booking_id = $parent->id;
        $repeat->setRelation('booking', $parent);

        $this->assertSame($parent, $this->service->mainBookingFor($repeat));
    }

    public function test_main_booking_for_plain_booking_is_identity(): void
    {
        $b = $this->memoryBooking();
        $this->assertSame($b, $this->service->mainBookingFor($b));
    }

    public function test_total_paid_for_main_booking_sums_partials(): void
    {
        $b = $this->memoryBooking();
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 100.0],
            (object) ['paid_amount' => 50.25],
        ]));

        $this->assertSame(150.25, $this->service->totalPaidForMainBooking($b));
    }

    public function test_total_paid_for_main_booking_uses_grand_when_marked_paid_and_no_partials(): void
    {
        $b = $this->memoryBooking();
        $b->is_paid = 1;
        $b->setRelation('booking_partial_payments', collect());

        $this->assertSame(1000.0, $this->service->totalPaidForMainBooking($b));
    }

    public function test_resolve_visit_provider_percent_explicit(): void
    {
        $b = new Booking;
        $this->assertSame(
            88.0,
            $this->service->resolveVisitProviderPercent($b, ['visit_fee_provider_percent' => 88.0])
        );
    }

    public function test_resolve_visit_provider_percent_derived_from_company_percent(): void
    {
        $b = new Booking;
        $this->assertSame(
            70.0,
            $this->service->resolveVisitProviderPercent($b, ['visit_fee_company_percent' => 30.0])
        );
    }

    public function test_resolve_visit_charges_paid_uses_retained_visit_amount_when_no_explicit_visit_or_closing(): void
    {
        $b = new Booking;
        $b->id = (string) Str::uuid();
        $b->total_booking_amount = 500.0;
        $b->extra_fee = 10.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $b->setRelation('extra_services', collect());

        $paid = $this->service->resolveVisitChargesPaid($b, ['retained_visit_amount' => 175.0]);
        $this->assertSame(175.0, $paid);
    }

    public function test_resolve_visit_charges_paid_visit_fee_split_not_capped_above_grand(): void
    {
        $b = new Booking;
        $b->id = (string) Str::uuid();
        $b->total_booking_amount = 100.0;
        $b->extra_fee = 0.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $b->setRelation('extra_services', collect());

        $paid = $this->service->resolveVisitChargesPaid($b, ['visit_charges_paid' => 500.0]);
        $this->assertSame(500.0, $paid);
    }

    public function test_resolve_visit_line_shares_scale_when_monetary_sum_exceeds_visit_paid(): void
    {
        $b = new Booking;
        $b->id = (string) Str::uuid();

        [$co, $pr] = $this->service->resolveVisitLineCompanyProviderShares($b, 100.0, [
            'visit_fee_company_percent' => 50.0,
            'visit_company_amount' => 70.0,
            'visit_provider_amount' => 80.0,
        ], null);

        $this->assertSame(46.67, $co);
        $this->assertSame(53.33, $pr);
    }

    public function test_custom_commission_negative_in_config_clamped_to_zero(): void
    {
        $b = $this->memoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION;
        $b->settlement_config = ['custom_admin_commission' => -20.0];

        $d = $this->service->calculateAdminCommissionDetails($b, null);
        $this->assertSame(0.0, $d['adminCommission']);
        $this->assertSame(0.0, $d['adminCommissionWithoutCost']);
    }

    public function test_scaled_commission_zero_when_grand_total_zero(): void
    {
        $b = $this->memoryBooking();
        $b->total_booking_amount = 0.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 50.0],
        ]));

        $d = $this->service->calculateAdminCommissionDetails($b, null);
        $this->assertSame(0.0, $d['adminCommission']);
    }

    public function test_build_preview_visit_fee_split_aligns_total_company_earning_with_commission(): void
    {
        $b = $this->memoryBooking();
        $b->total_booking_amount = 800.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $b->settlement_config = [
            'visit_charges_paid' => 200.0,
            'visit_fee_company_percent' => 30.0,
            'closing_amount_paid' => 100.0,
            'closing_company_share' => 40.0,
            'closing_provider_share' => 60.0,
        ];

        $preview = $this->service->buildPreview($b);
        $details = $this->service->calculateAdminCommissionDetails($b, null);

        $this->assertTrue($preview['decided_visit_charges_mode']);
        $this->assertSame(round((float) $details['adminCommission'], 2), $preview['total_company_earning_applied']);
        $this->assertSame($preview['company_commission'], $preview['total_company_earning_applied']);
        $this->assertSame(800.0, get_booking_total_amount($b));
        $this->assertSame(300.0, $preview['booking_total']);
        $this->assertSame(300.0, $preview['grand_total']);
    }

    public function test_build_preview_scaled_includes_loss_block_and_allow_complete(): void
    {
        $b = $this->memoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->settlement_config = [
            'scaled_customer_paid_amount' => 400.0,
            'scaled_loss_company_amount' => 300.0,
            'scaled_loss_provider_amount' => 300.0,
        ];
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 400.0, 'received_by' => 'company'],
        ]));

        $preview = $this->service->buildPreview($b);

        $this->assertTrue($preview['allow_complete_without_full_payment']);
        $this->assertTrue($preview['scaled_loss_mode']);
        $this->assertSame(1000.0, $preview['scaled_total_booking_amount']);
        $this->assertSame(400.0, $preview['scaled_customer_paid_amount']);
        $this->assertSame(600.0, $preview['scaled_loss_amount']);
        $this->assertSame(300.0, $preview['scaled_loss_company_share']);
        $this->assertSame(300.0, $preview['scaled_loss_provider_share']);
    }

    public function test_build_preview_suggested_refund_when_customer_overpaid_retained(): void
    {
        $b = $this->memoryBooking();
        $b->total_booking_amount = 500.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $b->settlement_config = [
            'visit_charges_paid' => 100.0,
            'closing_amount_paid' => 50.0,
        ];
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 500.0, 'received_by' => 'company'],
        ]));

        $preview = $this->service->buildPreview($b);

        $this->assertSame(150.0, $preview['retained_on_cancel']);
        $this->assertSame(350.0, $preview['refund_to_customer']);
        $this->assertSame(350.0, $preview['suggested_customer_refund']);
    }

    public function test_get_booking_invoice_due_standard_subtracts_partials_from_grand_total(): void
    {
        $b = $this->memoryBooking();
        $b->settlement_outcome = null;
        $b->settlement_config = null;
        $b->booking_status = 'ongoing';
        $b->payment_method = 'online';
        $b->additional_charge = 0.0;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 250.0],
        ]));

        $this->assertSame(750.0, get_booking_invoice_due_amount($b));
    }

    public function test_get_booking_invoice_due_is_zero_for_standard_canceled_even_if_underpaid(): void
    {
        $b = $this->memoryBooking();
        $b->settlement_outcome = null;
        $b->settlement_config = null;
        $b->booking_status = 'canceled';
        $b->payment_method = 'online';
        $b->additional_charge = 0.0;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 250.0],
        ]));

        $this->assertSame(0.0, get_booking_invoice_due_amount($b));
    }

    public function test_booking_refund_max_eligible_total_zero_when_suppressed_or_not_canceled(): void
    {
        $suppressed = $this->memoryBooking();
        $suppressed->booking_status = 'canceled';
        $suppressed->after_visit_cancel = true;
        $this->assertSame(0.0, booking_refund_max_eligible_total($suppressed));

        $outcome = $this->memoryBooking();
        $outcome->booking_status = 'canceled';
        $outcome->after_visit_cancel = false;
        $outcome->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $this->assertSame(0.0, booking_refund_max_eligible_total($outcome));

        $ongoing = $this->memoryBooking();
        $ongoing->booking_status = 'ongoing';
        $this->assertSame(0.0, booking_refund_max_eligible_total($ongoing));
    }

    public function test_received_settlement_zeroes_for_refunded_standard_outcome(): void
    {
        $b = $this->memoryBooking();
        $b->booking_status = 'refunded';
        $b->settlement_outcome = null;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 100.0, 'received_by' => 'company'],
        ]));
        $r = get_booking_received_and_settlement($b);
        $this->assertTrue($r['net_revenue_zeroed_after_refund']);
        $this->assertSame(0.0, $r['company_share']);
        $this->assertSame(0.0, $r['amount_received_by_company']);
        $this->assertSame(0.0, $r['pay_to_provider']);
        $this->assertSame(100.0, $r['total_paid']);
    }

    public function test_received_settlement_not_zeroed_for_visit_fee_split_when_refunded(): void
    {
        $b = $this->memoryBooking();
        $b->booking_status = 'refunded';
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 100.0, 'received_by' => 'company'],
        ]));
        $r = get_booking_received_and_settlement($b);
        $this->assertFalse($r['net_revenue_zeroed_after_refund']);
    }

    public function test_get_booking_invoice_due_uses_retained_total_for_decided_cancel(): void
    {
        $b = $this->memoryBooking();
        $b->total_booking_amount = 300.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $b->settlement_config = [
            'visit_charges_paid' => 50.0,
            'closing_amount_paid' => 25.0,
        ];

        $this->assertSame(75.0, get_booking_invoice_due_amount($b));

        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 75.0],
        ]));
        $this->assertSame(0.0, get_booking_invoice_due_amount($b));
    }

    public function test_get_booking_revenue_reporting_amount_after_visit_cancel(): void
    {
        $b = $this->memoryBooking();
        $b->total_booking_amount = 300.0;
        $b->booking_status = 'canceled';
        $b->after_visit_cancel = true;
        $b->settlement_config = [
            'visit_charges_paid' => 50.0,
            'closing_amount_paid' => 25.0,
        ];

        $this->assertSame(75.0, get_booking_revenue_reporting_amount($b));
    }

    public function test_get_booking_total_amount_includes_extra_services_relation(): void
    {
        $b = $this->memoryBooking();
        $b->total_booking_amount = 100.0;
        $b->extra_fee = 5.0;
        $extra = new \stdClass;
        $extra->total = 22.5;
        $b->setRelation('extra_services', collect([$extra]));

        $this->assertSame(127.5, get_booking_total_amount($b));
    }

    public function test_uses_non_standard_settlement_on_repeat_follows_parent_outcome(): void
    {
        $parent = new Booking;
        $parent->settlement_outcome = BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION;

        $repeat = new BookingRepeat;
        $repeat->setRelation('booking', $parent);

        $this->assertTrue($this->service->usesNonStandardSettlement($repeat));

        $parent->settlement_outcome = BookingFinancialSettlementService::OUTCOME_STANDARD;
        $this->assertFalse($this->service->usesNonStandardSettlement($repeat));
    }

    public function test_resolve_closing_amount_paid_capped_to_grand_for_visit_retained_cancel(): void
    {
        $b = new Booking;
        $b->id = (string) Str::uuid();
        $b->total_booking_amount = 100.0;
        $b->extra_fee = 0.0;
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $b->setRelation('extra_services', collect());

        $closing = $this->service->resolveClosingAmountPaid($b, ['closing_amount_paid' => 500.0]);
        $this->assertSame(100.0, $closing);
    }

    public function test_get_commission_breakdown_scaled_matches_provider_basis_minus_commission_without_cost(): void
    {
        $b = $this->memoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $b->setRelation('booking_partial_payments', collect([
            (object) ['paid_amount' => 400.0],
        ]));

        $breakdown = get_commission_breakdown_for_booking($b);
        $grand = get_booking_total_amount($b);
        $paid = $this->service->totalPaidForMainBooking($b);
        $basis = $this->service->providerEarningBasisAmount($b, $grand, $paid);
        $details = $this->service->calculateAdminCommissionDetails($b, null);
        $expected = round(max(0.0, $basis - (float) $details['adminCommissionWithoutCost']), 2);

        $this->assertEqualsWithDelta($expected, (float) $breakdown['booking_amount_without_commission'], 0.02);
        $this->assertEqualsWithDelta((float) $details['adminCommissionWithoutCost'], (float) $breakdown['commission_without_cost'], 0.02);
    }

    public function test_build_preview_standard_has_decided_mode_false(): void
    {
        $b = $this->memoryBooking();
        $b->settlement_outcome = BookingFinancialSettlementService::OUTCOME_STANDARD;
        $b->settlement_config = [];

        $preview = $this->service->buildPreview($b);

        $this->assertFalse($preview['decided_visit_charges_mode']);
        $this->assertSame(0.0, $preview['total_company_earning_applied']);
        $this->assertFalse($preview['allow_complete_without_full_payment']);
    }

    public function test_save_settlement_non_standard_invokes_build_preview_for_snapshot(): void
    {
        $stubPreview = [
            'outcome' => BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT,
            'decided_visit_charges_mode' => true,
            'booking_total' => 1.0,
            'grand_total' => 1.0,
            'total_paid' => 0.0,
            'visit_fee' => 0.0,
            'visit_fee_company_percent' => 0.0,
            'retained_on_cancel' => 0.0,
            'suggested_customer_refund' => 0.0,
            'company_commission' => 0.0,
            'company_commission_after_promos' => 0.0,
            'provider_earning' => 0.0,
            'custom_admin_commission' => 0.0,
        ];

        $service = new class($stubPreview) extends BookingFinancialSettlementService {
            public function __construct(private array $stubPreview) {}

            public function buildPreview(Booking $booking): array
            {
                return $this->stubPreview;
            }
        };

        $booking = $this->getMockBuilder(Booking::class)->onlyMethods(['save'])->getMock();
        $booking->expects($this->once())->method('save')->willReturn(true);

        $service->saveSettlement(
            $booking,
            BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT,
            ['visit_charges_paid' => 10.0],
            'remarks'
        );

        $this->assertSame(BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT, $booking->settlement_outcome);
        $this->assertEquals(['visit_charges_paid' => 10.0], $booking->settlement_config);
        $this->assertSame('remarks', $booking->settlement_remarks);
        $this->assertEquals($stubPreview, $booking->settlement_snapshot);
        $this->assertFalse((bool) $booking->allow_complete_without_full_payment);
    }
}
