<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Tests\TestCase;

/**
 * Backend contracts for admin multi-line cart commission preview and booking commission basis.
 *
 * Full HTTP/UI flows (preview round-trip, nested forms, modals) need manual or browser automation;
 * this suite locks the numeric rules used by preview, ajax cart summary, and persisted bookings.
 */
class AdminBookingCreateCommissionMathTest extends TestCase
{
    /**
     * @return array{service: array, spare_parts: array}
     */
    private function tierServiceTenPercentSpareTwentyPercent(): array
    {
        return [
            'service' => [
                'mode' => 'tiered',
                'fixed_amount' => 0.0,
                'tiers' => [
                    ['from' => 0.0, 'to' => null, 'amount_type' => 'percentage', 'amount' => 10.0],
                ],
            ],
            'spare_parts' => [
                'mode' => 'tiered',
                'fixed_amount' => 0.0,
                'tiers' => [
                    ['from' => 0.0, 'to' => null, 'amount_type' => 'percentage', 'amount' => 20.0],
                ],
            ],
        ];
    }

    public function test_calculate_commission_for_admin_booking_create_preview_single_service_basis(): void
    {
        $out = calculate_commission_for_admin_booking_create_preview(
            500.0,
            0.0,
            0.0,
            $this->tierServiceTenPercentSpareTwentyPercent()
        );

        $this->assertSame(50.0, $out['company_commission']);
        $this->assertSame(450.0, $out['provider_commission']);
    }

    public function test_calculate_commission_for_admin_booking_create_preview_splits_spare_from_service_basis(): void
    {
        // Payable grand 600 = 500 (service-side) + 100 spare; 10% on 500 + 20% on 100 = 70 company.
        $out = calculate_commission_for_admin_booking_create_preview(
            600.0,
            100.0,
            0.0,
            $this->tierServiceTenPercentSpareTwentyPercent()
        );

        $this->assertSame(70.0, $out['company_commission']);
        $this->assertSame(530.0, $out['provider_commission']);
    }

    public function test_calculate_commission_for_admin_booking_create_preview_non_commissionable_ac_reduces_service_tier_basis(): void
    {
        // Grand 530; spare 0; 30 excluded from service commission basis -> 500 @ 10% = 50 company.
        $out = calculate_commission_for_admin_booking_create_preview(
            530.0,
            0.0,
            30.0,
            $this->tierServiceTenPercentSpareTwentyPercent()
        );

        $this->assertSame(50.0, $out['company_commission']);
        $this->assertSame(480.0, $out['provider_commission']);
    }

    public function test_booking_line_commission_uplift_for_none_bearer_is_zero(): void
    {
        $row = new \stdClass();
        $row->service_id = null;
        $row->service_cost = 500;
        $row->quantity = 1;
        $row->discount_amount = 100;
        $row->campaign_discount_amount = 0;
        $row->total_cost = 450;

        $this->assertSame(0.0, booking_line_row_commission_uplift_when_no_bearer($row));
    }

    public function test_get_booking_commission_basis_uplift_for_none_bearer_is_always_zero(): void
    {
        $b = new Booking();
        $b->id = (string) \Illuminate\Support\Str::uuid();

        $this->assertSame(0.0, get_booking_commission_basis_uplift_for_none_bearer($b));
    }

    public function test_get_booking_commissionable_amount_matches_grand_minus_spare_minus_non_commissionable(): void
    {
        $b = new Booking();
        $b->id = (string) \Illuminate\Support\Str::uuid();
        $b->total_booking_amount = 500.0;
        $b->extra_fee = 0.0;
        $b->additional_charges_breakdown = null;
        $b->setRelation('extra_services', collect());

        $grand = get_booking_total_amount($b);
        $spare = get_booking_spare_parts_amount($b);
        $nonComm = get_booking_non_commissionable_additional_charges_total($b);
        $comm = get_booking_commissionable_amount($b);

        $this->assertSame(500.0, $grand);
        $this->assertSame(0.0, $spare);
        $this->assertSame(0.0, $nonComm);
        $this->assertSame(round(max(0, $grand - $spare - $nonComm), 2), $comm);
    }

    public function test_get_booking_commissionable_amount_subtracts_non_commissionable_additional_charges(): void
    {
        $b = new Booking();
        $b->id = (string) \Illuminate\Support\Str::uuid();
        $b->total_booking_amount = 500.0;
        $b->extra_fee = 35.0;
        $b->additional_charges_breakdown = [
            ['amount' => 25.0, 'commissionable' => false],
            ['amount' => 10.0, 'commissionable' => true],
        ];
        $b->setRelation('extra_services', collect());

        $this->assertSame(535.0, get_booking_total_amount($b));
        $this->assertSame(25.0, get_booking_non_commissionable_additional_charges_total($b));
        $this->assertSame(510.0, get_booking_commissionable_amount($b));
    }

    public function test_discount_cost_bearer_none_splits_no_promotional_discount_to_admin_or_provider(): void
    {
        $split = \App\Lib\DiscountCostBearer::splitBasicAndCampaign(100.0, 50.0, \App\Lib\DiscountCostBearer::NONE);

        $this->assertSame(0.0, $split['discount_by_admin']);
        $this->assertSame(0.0, $split['discount_by_provider']);
        $this->assertSame(0.0, $split['campaign_discount_by_admin']);
        $this->assertSame(0.0, $split['campaign_discount_by_provider']);
    }

    public function test_get_booking_commissionable_amount_never_exceeds_grand_total(): void
    {
        $b = new Booking();
        $b->id = (string) \Illuminate\Support\Str::uuid();
        $b->total_booking_amount = 800.0;
        $b->extra_fee = 40.0;
        $b->additional_charges_breakdown = [
            ['amount' => 15.0, 'commissionable' => false],
        ];
        $b->setRelation('extra_services', collect());

        $grand = get_booking_total_amount($b);
        $comm = get_booking_commissionable_amount($b);

        $this->assertTrue($comm <= $grand + 1e-6, 'Commissionable service basis should not exceed payable grand total.');
    }
}
