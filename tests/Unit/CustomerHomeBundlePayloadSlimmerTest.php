<?php

namespace Tests\Unit;

use Modules\CustomerModule\Services\CustomerHomeBundlePayloadSlimmer;
use PHPUnit\Framework\TestCase;

class CustomerHomeBundlePayloadSlimmerTest extends TestCase
{
    public function test_it_slims_service_and_advertisement_payloads(): void
    {
        $bundle = [
            'recommended_services' => [
                'data' => [[
                    'id' => 'svc-1',
                    'slug' => 'plumbing',
                    'name' => 'Plumbing',
                    'thumbnail_full_path' => 'https://example.test/thumb.jpg',
                    'description' => 'Long description',
                    'tax' => 18,
                    'tax_label' => 'GST',
                    'is_favorite' => 1,
                    'variations_app_format' => [
                        'zone_id' => 'zone-1',
                        'default_price' => 100,
                        'zone_wise_variations' => [[
                            'variant_key' => 'basic',
                            'variant_name' => 'Basic',
                            'price' => 100,
                            'description' => 'ignored',
                            'image_full_path' => 'ignored',
                        ]],
                    ],
                    'category' => [
                        'id' => 'cat-1',
                        'name' => 'Home',
                        'category_discount' => [['discount' => 10]],
                        'campaign_discount' => [],
                    ],
                ]],
            ],
            'advertisements' => [
                'data' => [[
                    'id' => 'ad-1',
                    'title' => 'Promo',
                    'provider' => [
                        'id' => 'provider-1',
                        'company_name' => 'Big Provider',
                        'owner' => ['id' => 'owner-1'],
                        'avg_rating' => 4.5,
                        'rating_count' => 12,
                        'is_favorite' => 1,
                        'subscribed_services' => [[
                            'sub_category' => ['id' => 'sub-1', 'name' => 'Electrician'],
                        ]],
                    ],
                ]],
            ],
        ];

        $slim = CustomerHomeBundlePayloadSlimmer::slim($bundle);

        $service = $slim['recommended_services']['data'][0];
        $this->assertSame('svc-1', $service['id']);
        $this->assertArrayNotHasKey('description', $service);
        $this->assertArrayNotHasKey('tax', $service);
        $this->assertSame('basic', $service['variations_app_format']['zone_wise_variations'][0]['variant_key']);
        $this->assertArrayNotHasKey('description', $service['variations_app_format']['zone_wise_variations'][0]);

        $provider = $slim['advertisements']['data'][0]['provider'];
        $this->assertSame('provider-1', $provider['id']);
        $this->assertSame(4.5, $provider['avg_rating']);
        $this->assertArrayNotHasKey('company_name', $provider);
        $this->assertSame('Electrician', $provider['subscribed_services'][0]['sub_category']['name']);
    }
}
