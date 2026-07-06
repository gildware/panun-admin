<?php

namespace Tests\Unit;

use Modules\CustomerModule\Services\CustomerBookingListPayloadSlimmer;
use PHPUnit\Framework\TestCase;

class CustomerBookingListPayloadSlimmerTest extends TestCase
{
    public function test_it_slims_booking_list_rows_and_repeat_items(): void
    {
        $slim = CustomerBookingListPayloadSlimmer::slimItem([
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
                'provider' => ['id' => 'provider-1'],
            ]],
            'list_display_total' => 500,
            'payable_grand_total' => 500,
            'sub_category_id' => 'sub-1',
            'is_customize_booking' => 0,
            'booking_status_display_key' => 'accepted',
            'booking_status_badge_variant' => 'success',
            'booking_status_tags' => [['key' => 'accepted', 'label' => 'Accepted', 'variant' => 'success']],
            'customer' => ['id' => 'cust-1'],
            'provider' => ['id' => 'provider-1'],
            'detail' => [['service_name' => 'Ignored']],
        ]);

        $this->assertSame('booking-1', $slim['id']);
        $this->assertEquals(500, $slim['list_display_total']);
        $this->assertArrayNotHasKey('customer', $slim);
        $this->assertArrayNotHasKey('provider', $slim);
        $this->assertSame('ongoing', $slim['repeats'][0]['booking_status']);
        $this->assertArrayNotHasKey('provider', $slim['repeats'][0]);
    }
}
