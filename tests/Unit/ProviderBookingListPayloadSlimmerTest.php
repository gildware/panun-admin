<?php

namespace Tests\Unit;

use Modules\ProviderManagement\Services\ProviderBookingListPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class ProviderBookingListPayloadSlimmerTest extends TestCase
{
    public function test_it_slims_provider_booking_list_rows(): void
    {
        $slim = ProviderBookingListPayloadSlimmer::slimItem([
            'id' => 'booking-1',
            'readable_id' => '10001',
            'booking_status' => 'accepted',
            'created_at' => '2026-07-01 10:00:00',
            'service_schedule' => '2026-07-02 12:00:00',
            'is_repeated' => 1,
            'repeats' => [[
                'id' => 'repeat-1',
                'readable_id' => '10001-1',
                'booking_status' => 'ongoing',
                'service_schedule' => '2026-07-02 12:00:00',
                'customer' => ['id' => 'cust-1'],
            ]],
            'list_display_total' => 500,
            'payable_grand_total' => 500,
            'total_booking_amount' => 500,
            'sub_category_id' => 'sub-1',
            'service_location' => 'customer',
            'booking_status_display_key' => 'accepted',
            'booking_status_badge_variant' => 'success',
            'booking_status_tags' => [['key' => 'accepted', 'label' => 'Accepted', 'variant' => 'success']],
            'sub_category' => [
                'id' => 'sub-1',
                'name' => 'Cleaning',
                'parent_id' => 'cat-1',
                'description' => 'ignored',
            ],
            'customer' => ['id' => 'cust-1', 'first_name' => 'Jane'],
            'booking_offline_payments' => [['id' => 'pay-1']],
        ]);

        $this->assertSame('booking-1', $slim['id']);
        $this->assertSame('customer', $slim['service_location']);
        $this->assertSame('Cleaning', $slim['sub_category']['name']);
        $this->assertArrayNotHasKey('description', $slim['sub_category']);
        $this->assertArrayNotHasKey('customer', $slim);
        $this->assertSame('ongoing', $slim['repeats'][0]['booking_status']);
        $this->assertArrayNotHasKey('customer', $slim['repeats'][0]);
    }
}
