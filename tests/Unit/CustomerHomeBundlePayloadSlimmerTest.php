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
        $this->assertSame('ignored', $service['variations_app_format']['zone_wise_variations'][0]['description']);
        $this->assertSame('ignored', $service['variations_app_format']['zone_wise_variations'][0]['image_full_path']);

        $provider = $slim['advertisements']['data'][0]['provider'];
        $this->assertSame('provider-1', $provider['id']);
        $this->assertSame(4.5, $provider['avg_rating']);
        $this->assertArrayNotHasKey('company_name', $provider);
        $this->assertSame('Electrician', $provider['subscribed_services'][0]['sub_category']['name']);
    }

    public function test_it_slims_provider_category_and_featured_sections(): void
    {
        $bundle = [
            'providers' => [
                'data' => [[
                    'id' => 'provider-1',
                    'company_name' => 'Acme',
                    'company_address' => 'Main St',
                    'logo_full_path' => 'https://example.test/logo.png',
                    'avg_rating' => 4.2,
                    'rating_count' => 8,
                    'is_favorite' => 1,
                    'owner' => ['id' => 'owner-1', 'account' => ['balance' => 99]],
                    'commission_percentage' => 10,
                ]],
            ],
            'categories' => [
                'data' => [[
                    'id' => 'cat-1',
                    'slug' => 'home',
                    'name' => 'Home',
                    'image_full_path' => 'https://example.test/cat.png',
                    'description' => 'Long',
                    'zones_basic_info' => [['id' => 'z1']],
                ]],
            ],
            'featured_categories' => [
                'data' => [[
                    'id' => 'cat-2',
                    'slug' => 'cleaning',
                    'name' => 'Cleaning',
                    'image_full_path' => 'https://example.test/clean.png',
                    'description' => 'Long',
                    'services_by_category' => [[
                        'id' => 'svc-2',
                        'slug' => 'deep-clean',
                        'name' => 'Deep Clean',
                        'thumbnail_full_path' => 'https://example.test/svc.png',
                        'description' => 'ignored',
                        'tax' => 18,
                        'variations_app_format' => [
                            'default_price' => 50,
                            'zone_wise_variations' => [['variant_key' => 'basic', 'price' => 50]],
                        ],
                    ]],
                ]],
            ],
        ];

        $slim = CustomerHomeBundlePayloadSlimmer::slim($bundle);

        $listProvider = $slim['providers']['data'][0];
        $this->assertSame('Acme', $listProvider['company_name']);
        $this->assertArrayNotHasKey('owner', $listProvider);
        $this->assertArrayNotHasKey('commission_percentage', $listProvider);

        $category = $slim['categories']['data'][0];
        $this->assertSame('home', $category['slug']);
        $this->assertArrayNotHasKey('description', $category);
        $this->assertArrayNotHasKey('zones_basic_info', $category);

        $featured = $slim['featured_categories']['data'][0];
        $this->assertSame('cleaning', $featured['slug']);
        $this->assertArrayNotHasKey('description', $featured);
        $this->assertArrayNotHasKey('tax', $featured['services_by_category'][0]);
    }
}
