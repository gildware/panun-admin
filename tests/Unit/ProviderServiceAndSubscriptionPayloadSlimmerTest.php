<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\ProviderServicePayloadSlimmer;
use Modules\ProviderManagement\Services\ProviderSubscriptionPayloadSlimmer;
use Modules\ProviderManagement\Services\ProviderSubCategoryPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class ProviderServiceAndSubscriptionPayloadSlimmerTest extends TestCase
{
    public function test_it_slims_provider_service_list_rows(): void
    {
        $slim = ProviderServicePayloadSlimmer::slimItem([
            'id' => 'svc-1',
            'name' => 'Home Cleaning',
            'thumbnail' => 'thumb.png',
            'thumbnail_full_path' => 'https://example.com/thumb.png',
            'description' => 'ignored',
            'variations' => [[
                'id' => 'var-1',
                'variant_key' => 'default',
                'price' => 500,
                'zone_id' => 'zone-1',
            ]],
            'service_discount' => [['discount' => ['discount_amount' => 10]]],
            'category' => ['id' => 'cat-1', 'zones' => []],
        ]);

        $this->assertSame('Home Cleaning', $slim['name']);
        $this->assertSame(500, $slim['variations'][0]['price']);
        $this->assertArrayNotHasKey('zone_id', $slim['variations'][0]);
        $this->assertArrayNotHasKey('description', $slim);
        $this->assertArrayNotHasKey('category', $slim);
    }

    public function test_it_slims_subscription_rows_with_active_service_counts(): void
    {
        $slim = ProviderSubscriptionPayloadSlimmer::slimItem([
            'id' => 'sub-1',
            'provider_id' => 'provider-1',
            'category_id' => 'cat-1',
            'sub_category_id' => 'child-1',
            'is_subscribed' => 1,
            'created_at' => '2026-07-01',
            'services_count' => 3,
            'ongoing_booking_count' => 1,
            'completed_booking_count' => 10,
            'canceled_booking_count' => 2,
            'category' => ['id' => 'cat-1', 'name' => 'Home', 'description' => 'ignored'],
            'sub_category' => [
                'id' => 'child-1',
                'parent_id' => 'cat-1',
                'name' => 'Cleaning',
                'image' => 'image.png',
                'image_full_path' => 'https://example.com/image.png',
                'description' => 'ignored',
                'services' => [
                    ['id' => 'svc-1', 'is_active' => 1, 'name' => 'Ignored'],
                    ['id' => 'svc-2', 'is_active' => 0, 'name' => 'Ignored'],
                ],
            ],
        ]);

        $this->assertSame('Home', $slim['category']['name']);
        $this->assertArrayNotHasKey('description', $slim['category']);
        $this->assertCount(2, $slim['sub_category']['services']);
        $this->assertSame(1, $slim['sub_category']['services'][0]['is_active']);
        $this->assertArrayNotHasKey('name', $slim['sub_category']['services'][0]);
    }

    public function test_it_slims_provider_sub_category_browse_rows(): void
    {
        $slim = ProviderSubCategoryPayloadSlimmer::slimItem([
            'id' => 'child-1',
            'parent_id' => 'cat-1',
            'name' => 'Cleaning',
            'image' => 'image.png',
            'image_full_path' => 'https://example.com/image.png',
            'description' => 'Deep cleaning',
            'is_active' => 1,
            'is_subscribed' => 0,
            'services_count' => 2,
            'services' => [
                ['id' => 'svc-1', 'is_active' => 1, 'name' => 'Ignored'],
            ],
            'parent' => ['id' => 'cat-1'],
        ]);

        $this->assertSame('Deep cleaning', $slim['description']);
        $this->assertArrayNotHasKey('parent', $slim);
        $this->assertArrayNotHasKey('name', $slim['services'][0]);
    }
}
