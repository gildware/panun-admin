<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\AdminModule\Services\CategoryBusinessReportAnalyticsService;
use Modules\AdminModule\Services\GeographicBusinessReportAnalyticsService;
use Modules\LeadManagement\Entities\Lead;
use Tests\TestCase;

class CategoryBusinessReportAnalyticsServiceTest extends TestCase
{
    private CategoryBusinessReportAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CategoryBusinessReportAnalyticsService(new GeographicBusinessReportAnalyticsService);
    }

    public function test_aggregate_splits_bookings_and_provider_supply_by_category(): void
    {
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-14');

        $report = $this->service->aggregate(
            [
                $this->lead('1', Lead::TYPE_CUSTOMER, '2026-09-02', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'booked'),
                $this->lead('2', Lead::TYPE_CUSTOMER, '2026-09-03', 'plumbing', 'Plumbing', 'tap', 'Tap repair', 'pending'),
                $this->lead('3', Lead::TYPE_CUSTOMER, '2026-09-04', 'carpentry', 'Carpentry', 'wood', 'Wood work', 'cancelled'),
            ],
            [
                $this->booking('b1', '2026-09-04', 'p1', 'Ali', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'completed', 1000, ['revenue' => 1100, 'admin_commission' => 110, 'provider_earning' => 990]),
                $this->booking('b2', '2026-09-05', 'p1', 'Ali', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'canceled', 200),
                $this->booking('b3', '2026-09-06', 'p2', 'Rafi', 'plumbing', 'Plumbing', 'tap', 'Tap repair', 'ongoing', 300),
                $this->booking('b4', '2026-09-07', 'p3', 'Guest', 'carpentry', 'Carpentry', 'wood', 'Wood work', 'completed', 500, ['revenue' => 520, 'admin_commission' => 52, 'provider_earning' => 468]),
                $this->booking('b5', '2026-09-08', '', '', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'completed', 400, ['revenue' => 400, 'admin_commission' => 40, 'provider_earning' => 360]),
            ],
            [
                $this->provider('p1', 'Ali', true, true, 'plumbing', 'Plumbing', 'pipe', 'Pipe repair'),
                $this->provider('p2', 'Rafi', true, true, 'plumbing', 'Plumbing', 'tap', 'Tap repair'),
                $this->provider('p4', 'Idle Co', true, true, 'plumbing', 'Plumbing', 'pipe', 'Pipe repair'),
                $this->provider('p5', 'Closed Co', false, true, 'carpentry', 'Carpentry', 'wood', 'Wood work'),
                $this->provider('p6', 'Spark', true, true, 'electrical', 'Electrical', 'wiring', 'Wiring'),
            ],
            $from,
            $to
        );

        $this->assertSame(3, $report['summary']['leads']);
        $this->assertSame(5, $report['summary']['bookings']);
        $this->assertSame(3, $report['summary']['booking_completed']);
        $this->assertSame(1, $report['summary']['booking_cancelled']);
        $this->assertSame(1, $report['summary']['booking_pending']);
        $this->assertSame(1900.0, $report['summary']['booking_amount_completed']);
        $this->assertSame(2020.0, $report['summary']['revenue']);
        $this->assertSame(202.0, $report['summary']['admin_commission']);
        $this->assertSame(1818.0, $report['summary']['provider_earning']);
        $this->assertSame(5, $report['summary']['providers']);
        $this->assertSame(4, $report['summary']['providers_active']);
        $this->assertSame(2, $report['summary']['providers_active_with_booking']);
        $this->assertSame(2, $report['summary']['providers_active_idle']);

        $plumbing = collect($report['category']['rows'])->firstWhere('key', 'plumbing');
        $this->assertNotNull($plumbing);
        $this->assertSame(2, $plumbing['leads']);
        $this->assertSame(2, $plumbing['customer']);
        $this->assertSame(1, $plumbing['booked']);
        $this->assertSame(1, $plumbing['pending']);
        $this->assertSame(4, $plumbing['bookings']);
        $this->assertSame(2, $plumbing['status_completed']);
        $this->assertSame(1, $plumbing['status_canceled']);
        $this->assertSame(1, $plumbing['status_ongoing']);
        $this->assertSame(1400.0, $plumbing['booking_amount_completed']);
        $this->assertSame(1500.0, $plumbing['revenue']);
        $this->assertSame(150.0, $plumbing['admin_commission']);
        $this->assertSame(1350.0, $plumbing['provider_earning']);
        $this->assertSame(3, $plumbing['providers']);
        $this->assertSame(3, $plumbing['providers_active']);
        $this->assertSame(2, $plumbing['providers_active_with_booking']);
        $this->assertSame(1, $plumbing['providers_active_idle']);

        $carpentry = collect($report['category']['rows'])->firstWhere('key', 'carpentry');
        $this->assertSame(1, $carpentry['providers']);
        $this->assertSame(0, $carpentry['providers_active']);
        $this->assertSame(1, $carpentry['providers_with_booking']);
        $this->assertSame(1, $carpentry['providers_not_subscribed_with_booking']);

        $electrical = collect($report['category']['rows'])->firstWhere('key', 'electrical');
        $this->assertSame(0, $electrical['bookings']);
        $this->assertSame(1, $electrical['providers_active_idle']);

        $ali = collect($report['category']['providers'])->first(fn (array $row) => $row['provider_id'] === 'p1' && $row['category_key'] === 'plumbing');
        $this->assertNotNull($ali);
        $this->assertTrue($ali['subscribed']);
        $this->assertTrue($ali['took_booking']);
        $this->assertSame(2, $ali['bookings']);
        $this->assertSame(1, $ali['booking_completed']);
        $this->assertSame(1100.0, $ali['revenue']);
        $this->assertSame(110.0, $ali['admin_commission']);
        $this->assertSame(990.0, $ali['provider_earning']);
        $this->assertStringContainsString('Pipe repair', $ali['subcategories']);

        $guest = collect($report['category']['providers'])->first(fn (array $row) => $row['provider_id'] === 'p3');
        $this->assertFalse($guest['subscribed']);
        $this->assertTrue($guest['took_booking']);

        $pipe = collect($report['subcategory']['rows'])->firstWhere('key', 'pipe');
        $this->assertSame('Plumbing', $pipe['category_label']);
        $this->assertSame(2, $pipe['providers']);
        $this->assertSame(3, $pipe['bookings']);
        $this->assertSame(1500.0, $pipe['revenue']);
        $this->assertSame(150.0, $pipe['admin_commission']);

        $this->assertSame(3, array_sum($report['daily']['leads']));
        $this->assertSame(5, array_sum($report['daily']['bookings']));
        $this->assertSame(4, array_sum(collect($report['daily']['by_category']['booking_series'])->firstWhere('key', 'plumbing')['data']));
    }

    public function test_recommend_flags_demand_with_almost_no_working_providers(): void
    {
        $rec = $this->service->recommend([
            'label' => 'Plumbing',
            'leads' => 2,
            'customer' => 2,
            'booked' => 1,
            'bookings' => 8,
            'booking_completed' => 4,
            'booking_cancelled' => 1,
            'providers_active_with_booking' => 1,
            'providers_active_idle' => 0,
        ]);

        $this->assertNotNull($rec);
        $this->assertSame('risk', $rec['type']);
        $this->assertStringContainsString('Plumbing', $rec['text']);
    }

    public function test_recommend_flags_active_providers_with_no_recent_booking(): void
    {
        $rec = $this->service->recommend([
            'label' => 'Electrical',
            'leads' => 0,
            'customer' => 0,
            'booked' => 0,
            'bookings' => 0,
            'booking_completed' => 0,
            'booking_cancelled' => 0,
            'providers_active_with_booking' => 0,
            'providers_active_idle' => 4,
        ]);

        $this->assertNotNull($rec);
        $this->assertSame('opportunity', $rec['type']);
        $this->assertStringContainsString('Electrical', $rec['text']);
    }

    public function test_aggregate_groups_lead_and_booking_cancellation_reasons(): void
    {
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-14');

        $report = $this->service->aggregate(
            [
                $this->lead('1', Lead::TYPE_CUSTOMER, '2026-09-02', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'cancelled', [
                    'cancel_reason_key' => 'customer:price',
                    'cancel_reason_label' => 'Price too high',
                    'cancel_reason_kind' => 'customer',
                    'cancel_remarks' => 'Asked for a discount',
                ]),
                $this->lead('2', Lead::TYPE_CUSTOMER, '2026-09-03', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'cancelled'),
                $this->lead('3', Lead::TYPE_PROVIDER, '2026-09-04', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', null, [
                    'cancel_reason_key' => 'provider:zone',
                    'cancel_reason_label' => 'Out of zone',
                    'cancel_reason_kind' => 'provider',
                    'cancel_remarks' => 'Does not cover Rajbagh',
                ]),
            ],
            [
                $this->booking('b1', '2026-09-05', 'p1', 'Ali', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'canceled', 0, [
                    'cancel_reason_key' => 'admin:1',
                    'cancel_reason_label' => 'Customer not available',
                    'cancel_responsible' => 'customer',
                    'cancel_remarks' => 'No one at home',
                ]),
                $this->booking('b2', '2026-09-06', 'p1', 'Ali', 'plumbing', 'Plumbing', 'pipe', 'Pipe repair', 'refunded', 0, [
                    'cancel_reason_key' => 'admin:1',
                    'cancel_reason_label' => 'Customer not available',
                    'cancel_responsible' => 'customer',
                    'cancel_remarks' => 'Called later',
                ]),
            ],
            [],
            $from,
            $to
        );

        $plumbing = collect($report['category']['rows'])->firstWhere('key', 'plumbing');
        $this->assertSame(2, $plumbing['cancelled']);
        $this->assertSame(1, $plumbing['provider_lead_cancelled']);
        $this->assertSame(100.0, $plumbing['lead_cancel_rate']);
        $this->assertSame(2, $plumbing['booking_cancelled']);
        $this->assertSame(1, $plumbing['status_canceled']);
        $this->assertSame(1, $plumbing['status_refunded']);

        $leadReasons = collect($plumbing['lead_cancel_reasons']);
        $price = $leadReasons->firstWhere('key', 'customer:price');
        $this->assertSame(1, $price['total']);
        $this->assertSame('Asked for a discount', $price['remarks_text']);
        $this->assertSame('customer_lead', $price['source']);
        $unspecified = $leadReasons->firstWhere('key', GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY);
        $this->assertSame(1, $unspecified['total']);
        $providerReason = $leadReasons->firstWhere('key', 'provider:zone');
        $this->assertSame('provider_lead', $providerReason['source']);

        $bookingReason = $plumbing['booking_cancel_reasons'][0];
        $this->assertSame('Customer not available', $bookingReason['label']);
        $this->assertSame(2, $bookingReason['total']);
        $this->assertSame(100.0, $bookingReason['share']);
        $this->assertSame('customer', $bookingReason['responsible']);
        $this->assertStringContainsString('No one at home', $bookingReason['remarks_text']);
        $this->assertStringContainsString('Called later', $bookingReason['remarks_text']);
    }

    /**
     * @return array<string, mixed>
     */
    private function lead(
        string $id,
        string $type,
        string $receivedAt,
        string $categoryKey,
        string $categoryLabel,
        string $subcategoryKey,
        string $subcategoryLabel,
        ?string $status,
        array $extra = []
    ): array {
        return array_merge([
            'id' => $id,
            'lead_type' => $type,
            'received_at' => $receivedAt,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'subcategory_key' => $subcategoryKey,
            'subcategory_label' => $subcategoryLabel,
            'customer_status' => $status,
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function booking(
        string $id,
        string $createdAt,
        string $providerId,
        string $providerName,
        string $categoryKey,
        string $categoryLabel,
        string $subcategoryKey,
        string $subcategoryLabel,
        string $status,
        float $amount,
        array $extra = []
    ): array {
        return array_merge([
            'id' => $id,
            'created_at' => $createdAt,
            'provider_id' => $providerId,
            'provider_name' => $providerName,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'subcategory_key' => $subcategoryKey,
            'subcategory_label' => $subcategoryLabel,
            'status' => $status,
            'amount' => $amount,
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function provider(
        string $id,
        string $name,
        bool $active,
        bool $approved,
        string $categoryKey,
        string $categoryLabel,
        string $subcategoryKey,
        string $subcategoryLabel
    ): array {
        return [
            'provider_id' => $id,
            'name' => $name,
            'is_active' => $active,
            'is_approved' => $approved,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'subcategory_key' => $subcategoryKey,
            'subcategory_label' => $subcategoryLabel,
        ];
    }
}
