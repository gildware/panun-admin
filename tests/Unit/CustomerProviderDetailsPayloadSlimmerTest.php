<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\CustomerProviderDetailsPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class CustomerProviderDetailsPayloadSlimmerTest extends TestCase
{
    public function test_it_keeps_services_on_subscribed_subcategories(): void
    {
        $slim = CustomerProviderDetailsPayloadSlimmer::slimSubCategories([
            [
                'id' => 'sub-1',
                'name' => 'Cleaning',
                'slug' => 'cleaning',
                'image' => 'image.png',
                'services' => [
                    [
                        'id' => 'svc-1',
                        'slug' => 'home-cleaning',
                        'name' => 'Home Cleaning',
                        'thumbnail' => 'thumb.png',
                        'thumbnail_full_path' => 'https://example.com/thumb.png',
                        'avg_rating' => 4.5,
                        'rating_count' => 2,
                        'variations_app_format' => [
                            'zone_id' => 'zone-1',
                            'default_price' => 500,
                            'zone_wise_variations' => [
                                [
                                    'variant_key' => 'default',
                                    'variant_name' => 'Default',
                                    'price' => 500,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $slim);
        $this->assertSame('Cleaning', $slim[0]['name']);
        $this->assertCount(1, $slim[0]['services']);
        $this->assertSame('Home Cleaning', $slim[0]['services'][0]['name']);
    }
}
