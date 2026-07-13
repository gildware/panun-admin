<?php

namespace Tests\Unit;

use Modules\CustomerModule\Services\CustomerProviderPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class CustomerProviderPayloadSlimmerTest extends TestCase
{
    public function test_it_slims_summary_and_list_provider_payloads(): void
    {
        $raw = [
            'id' => 'provider-1',
            'company_name' => 'Acme',
            'company_address' => 'Main St',
            'logo_full_path' => 'https://example.test/logo.png',
            'list_avatar_full_path' => 'https://example.test/avatar.png',
            'cover_image_full_path' => 'https://example.test/cover.png',
            'avg_rating' => 4.5,
            'rating_count' => 12,
            'is_favorite' => 1,
            'is_active' => 1,
            'service_availability' => 1,
            'time_schedule' => ['start_time' => '09:00', 'end_time' => '18:00'],
            'weekends' => ['sunday'],
            'nextBookingEligibility' => true,
            'scheduleBookingEligibility' => true,
            'total_service_served' => 20,
            'subscribed_services_count' => 5,
            'owner' => ['id' => 'owner-1', 'account' => ['balance' => 1]],
            'storage' => ['id' => 'storage-1'],
            'commission_percentage' => 10,
        ];

        $list = CustomerProviderPayloadSlimmer::slimListItem($raw);
        $this->assertSame('Acme', $list['company_name']);
        $this->assertSame('https://example.test/avatar.png', $list['list_avatar_full_path']);
        $this->assertSame(20, $list['total_service_served']);
        $this->assertSame(5, $list['subscribed_services_count']);
        $this->assertArrayNotHasKey('owner', $list);
        $this->assertArrayNotHasKey('cover_image_full_path', $list);

        $summary = CustomerProviderPayloadSlimmer::slimSummaryItem($raw);
        $this->assertSame('https://example.test/avatar.png', $summary['list_avatar_full_path']);
        $this->assertSame('https://example.test/cover.png', $summary['cover_image_full_path']);
        $this->assertTrue($summary['nextBookingEligibility']);
        $this->assertArrayNotHasKey('owner', $summary);
        $this->assertArrayNotHasKey('storage', $summary);
    }
}
