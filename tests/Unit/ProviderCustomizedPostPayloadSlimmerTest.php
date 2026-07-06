<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\ProviderCustomizedPostPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class ProviderCustomizedPostPayloadSlimmerTest extends TestCase
{
    public function test_it_slims_dashboard_customized_post_rows(): void
    {
        $slim = ProviderCustomizedPostPayloadSlimmer::slimItem([
            'id' => 'post-1',
            'service_description' => 'Need deep cleaning',
            'booking_schedule' => '2026-07-02 12:00:00',
            'created_at' => '2026-07-01 10:00:00',
            'distance' => '2.50 km',
            'customer_user_id' => 'cust-1',
            'booking_id' => 'booking-1',
            'service' => [
                'id' => 'svc-1',
                'name' => 'Home Cleaning',
                'thumbnail' => 'thumb.png',
                'thumbnail_full_path' => 'https://example.com/thumb.png',
                'description' => 'ignored',
            ],
            'sub_category' => [
                'id' => 'sub-1',
                'name' => 'Cleaning',
                'description' => 'ignored',
            ],
            'customer' => [
                'id' => 'cust-1',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'profile_image' => 'profile.png',
                'profile_image_full_path' => 'https://example.com/profile.png',
                'email' => 'ignored@example.com',
            ],
            'category' => ['id' => 'cat-1'],
            'booking' => ['id' => 'booking-1'],
            'addition_instructions' => [[
                'id' => 'ins-1',
                'details' => 'Bring ladder',
                'post_id' => 'post-1',
            ]],
        ]);

        $this->assertSame('post-1', $slim['id']);
        $this->assertSame('2.50 km', $slim['distance']);
        $this->assertArrayNotHasKey('booking', $slim);
        $this->assertSame('Bring ladder', $slim['addition_instructions'][0]['details']);
        $this->assertArrayNotHasKey('post_id', $slim['addition_instructions'][0]);
        $this->assertArrayNotHasKey('email', $slim['customer']);
    }
}
