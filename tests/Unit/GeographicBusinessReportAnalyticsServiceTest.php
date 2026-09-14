<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\AdminModule\Services\GeographicBusinessReportAnalyticsService;
use Modules\LeadManagement\Entities\Lead;
use Tests\TestCase;

class GeographicBusinessReportAnalyticsServiceTest extends TestCase
{
    private GeographicBusinessReportAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GeographicBusinessReportAnalyticsService;
    }

    public function test_aggregate_splits_leads_and_bookings_by_area_and_zone(): void
    {
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-14');

        $report = $this->service->aggregate(
            [
                $this->lead('1', Lead::TYPE_UNKNOWN, '2026-09-02', 'rajbagh', 'Rajbagh', 'srinagar', 'Srinagar'),
                $this->lead('2', Lead::TYPE_CUSTOMER, '2026-09-03', 'rajbagh', 'Rajbagh', 'srinagar', 'Srinagar', 'plumbing', 'Plumbing', 'pending'),
                $this->lead('3', Lead::TYPE_CUSTOMER, '2026-09-04', 'rajbagh', 'Rajbagh', 'srinagar', 'Srinagar', 'plumbing', 'Plumbing', 'booked'),
                $this->lead('4', Lead::TYPE_INVALID, '2026-09-05', 'lalchowk', 'Lalchowk', 'srinagar', 'Srinagar'),
                $this->lead('5', Lead::TYPE_CUSTOMER, '2026-09-06', 'lalchowk', 'Lalchowk', 'srinagar', 'Srinagar', 'carpentry', 'Carpentry', 'cancelled'),
            ],
            [
                $this->booking('b1', '2026-09-04', 'rajbagh', 'Rajbagh', 'srinagar', 'Srinagar', 'plumbing', 'Plumbing', 'completed', 1200),
                $this->booking('b2', '2026-09-07', 'lalchowk', 'Lalchowk', 'srinagar', 'Srinagar', 'carpentry', 'Carpentry', 'canceled', 800),
                $this->booking('b3', '2026-09-08', 'rajbagh', 'Rajbagh', 'srinagar', 'Srinagar', 'plumbing', 'Plumbing', 'ongoing', 500),
            ],
            $from,
            $to
        );

        $this->assertSame(5, $report['summary']['leads']);
        $this->assertSame(1, $report['summary']['unknown']);
        $this->assertSame(3, $report['summary']['customer']);
        $this->assertSame(1, $report['summary']['invalid']);
        $this->assertSame(1, $report['summary']['pending']);
        $this->assertSame(1, $report['summary']['booked']);
        $this->assertSame(1, $report['summary']['cancelled_leads']);
        $this->assertSame(3, $report['summary']['bookings']);
        $this->assertSame(1, $report['summary']['booking_completed']);
        $this->assertSame(1, $report['summary']['booking_cancelled']);
        $this->assertSame(1, $report['summary']['booking_pending']);

        $rajbagh = collect($report['area']['rows'])->firstWhere('key', 'rajbagh');
        $this->assertNotNull($rajbagh);
        $this->assertSame(3, $rajbagh['leads']);
        $this->assertSame(1, $rajbagh['unknown']);
        $this->assertSame(1, $rajbagh['pending']);
        $this->assertSame(1, $rajbagh['booked']);
        $this->assertSame(2, $rajbagh['bookings']);
        $this->assertSame(1, $rajbagh['booking_completed']);

        $srinagar = collect($report['zone']['rows'])->firstWhere('key', 'srinagar');
        $this->assertSame(5, $srinagar['leads']);
        $this->assertSame(3, $srinagar['bookings']);
        $this->assertNotEmpty($report['daily']['leads']);
        $this->assertSame(5, array_sum($report['daily']['leads']));
        $this->assertSame(3, array_sum($report['daily']['bookings']));

        $areaLeadSeries = collect($report['daily']['by_area']['lead_series']);
        $rajbaghLeads = $areaLeadSeries->firstWhere('key', 'rajbagh');
        $lalchowkLeads = $areaLeadSeries->firstWhere('key', 'lalchowk');
        $this->assertNotNull($rajbaghLeads);
        $this->assertNotNull($lalchowkLeads);
        $this->assertSame(3, array_sum($rajbaghLeads['data']));
        $this->assertSame(2, array_sum($lalchowkLeads['data']));
        $this->assertSame(2, array_sum(collect($report['daily']['by_area']['booking_series'])->firstWhere('key', 'rajbagh')['data']));
        $this->assertSame(1, array_sum(collect($report['daily']['by_area']['booking_series'])->firstWhere('key', 'lalchowk')['data']));
    }

    public function test_recommend_flags_weak_conversion_as_opportunity(): void
    {
        $rec = $this->service->recommend([
            'label' => 'Rajbagh',
            'category_label' => 'Plumbing',
            'leads' => 10,
            'customer' => 10,
            'booked' => 1,
            'unknown' => 0,
            'invalid' => 0,
            'bookings' => 1,
            'booking_completed' => 1,
            'booking_cancelled' => 0,
        ]);

        $this->assertNotNull($rec);
        $this->assertSame('opportunity', $rec['type']);
        $this->assertStringContainsString('Rajbagh', $rec['text']);
        $this->assertStringContainsString('Plumbing', $rec['text']);
    }

    public function test_recommend_flags_strong_completion_to_grow(): void
    {
        $rec = $this->service->recommend([
            'label' => 'Lalchowk',
            'category_label' => 'Carpentry',
            'leads' => 8,
            'customer' => 8,
            'booked' => 6,
            'unknown' => 0,
            'invalid' => 0,
            'bookings' => 8,
            'booking_completed' => 7,
            'booking_cancelled' => 1,
        ]);

        $this->assertNotNull($rec);
        $this->assertSame('grow', $rec['type']);
    }

    public function test_classify_customer_status_covers_pipeline(): void
    {
        $this->assertNull($this->service->classifyCustomerStatus(Lead::TYPE_UNKNOWN, '', '', false, '', null));
        $this->assertSame('cancelled', $this->service->classifyCustomerStatus(Lead::TYPE_CUSTOMER, 'cancel', '', false, 'Cancelled', null));
        $this->assertSame('booked', $this->service->classifyCustomerStatus(Lead::TYPE_CUSTOMER, 'pending', '', true, 'Pending', null));
        $this->assertSame('hold', $this->service->classifyCustomerStatus(Lead::TYPE_CUSTOMER, 'pending', '', false, 'On Hold', null));
        $this->assertSame('pending', $this->service->classifyCustomerStatus(Lead::TYPE_CUSTOMER, 'pending', '', false, 'Pending', null));
    }

    /**
     * @return array<string, mixed>
     */
    private function lead(
        string $id,
        string $type,
        string $receivedAt,
        string $areaKey,
        string $areaLabel,
        string $zoneKey,
        string $zoneLabel,
        string $categoryKey = '__unspecified__',
        string $categoryLabel = 'Not specified',
        ?string $status = null
    ): array {
        return [
            'id' => $id,
            'lead_type' => $type,
            'received_at' => $receivedAt,
            'area_key' => $areaKey,
            'area_label' => $areaLabel,
            'zone_key' => $zoneKey,
            'zone_label' => $zoneLabel,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'customer_status' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function booking(
        string $id,
        string $createdAt,
        string $areaKey,
        string $areaLabel,
        string $zoneKey,
        string $zoneLabel,
        string $categoryKey,
        string $categoryLabel,
        string $status,
        float $amount
    ): array {
        return [
            'id' => $id,
            'created_at' => $createdAt,
            'area_key' => $areaKey,
            'area_label' => $areaLabel,
            'zone_key' => $zoneKey,
            'zone_label' => $zoneLabel,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'status' => $status,
            'amount' => $amount,
        ];
    }
}
