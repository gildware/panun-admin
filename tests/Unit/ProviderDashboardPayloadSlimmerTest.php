<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\ProviderDashboardPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class ProviderDashboardPayloadSlimmerTest extends TestCase
{
    public function test_it_slims_recent_bookings_to_dashboard_card_fields(): void
    {
        $slim = ProviderDashboardPayloadSlimmer::slimRecentBookingItem([
            'id' => 'booking-1',
            'readable_id' => '10001',
            'created_at' => '2026-07-01 10:00:00',
            'booking_status' => 'accepted',
            'is_repeated' => 0,
            'customer_id' => 'cust-1',
            'total_booking_amount' => 500,
            'detail' => [
                [
                    'id' => 1,
                    'service_id' => 'svc-1',
                    'service_cost' => 100,
                    'service' => [
                        'id' => 'svc-1',
                        'name' => 'Home Cleaning',
                        'thumbnail' => 'thumb.png',
                        'thumbnail_full_path' => 'https://example.com/thumb.png',
                        'description' => 'ignored',
                    ],
                ],
                [
                    'id' => 2,
                    'service_id' => 'svc-2',
                    'service' => ['id' => 'svc-2', 'name' => 'Ignored'],
                ],
            ],
        ]);

        $this->assertSame('booking-1', $slim['id']);
        $this->assertArrayNotHasKey('customer_id', $slim);
        $this->assertCount(1, $slim['detail']);
        $this->assertSame('Home Cleaning', $slim['detail'][0]['service']['name']);
        $this->assertArrayNotHasKey('description', $slim['detail'][0]['service']);
        $this->assertArrayNotHasKey('service_cost', $slim['detail'][0]);
    }

    public function test_it_slims_dashboard_bundle_sections(): void
    {
        $slim = ProviderDashboardPayloadSlimmer::slimBundle([
            'dashboard' => [[
                'recent_bookings' => [[
                    'id' => 'booking-1',
                    'readable_id' => '10001',
                    'created_at' => '2026-07-01 10:00:00',
                    'booking_status' => 'accepted',
                    'is_repeated' => 0,
                    'detail' => [[
                        'id' => 1,
                        'service_id' => 'svc-1',
                        'service' => [
                            'id' => 'svc-1',
                            'name' => 'Home Cleaning',
                            'thumbnail' => 'thumb.png',
                            'thumbnail_full_path' => 'https://example.com/thumb.png',
                        ],
                    ]],
                ]],
                'subscriptions' => [[
                    'id' => 'sub-1',
                    'provider_id' => 'provider-1',
                    'category_id' => 'cat-1',
                    'sub_category_id' => 'child-1',
                    'is_subscribed' => 1,
                    'services_count' => 3,
                    'completed_booking_count' => 10,
                    'ongoing_booking_count' => 2,
                    'sub_category' => [
                        'id' => 'child-1',
                        'parent_id' => 'cat-1',
                        'name' => 'Cleaning',
                        'image' => 'image.png',
                        'image_full_path' => 'https://example.com/image.png',
                        'description' => 'ignored',
                    ],
                ]],
            ]],
            'earning' => ['total_earning' => 1000],
        ]);

        $section = $slim['dashboard'][0];
        $this->assertSame('Cleaning', $section['subscriptions'][0]['sub_category']['name']);
        $this->assertArrayNotHasKey('description', $section['subscriptions'][0]['sub_category']);
        $this->assertSame(1000, $slim['earning']['total_earning']);
    }
}
