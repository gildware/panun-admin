<?php

namespace Tests\Unit;

use Modules\BookingModule\Entities\Booking;
use Tests\TestCase;

class BookingReopenedNotificationTest extends TestCase
{
    public function test_booking_is_admin_reopen_in_place_transition_when_reopen_markers_dirty(): void
    {
        $booking = new Booking(['booking_status' => 'completed']);
        $booking->syncOriginal();
        $booking->booking_status = 'accepted';
        $booking->reopened_by = 'admin-user-id';

        $this->assertTrue(booking_is_admin_reopen_in_place_transition($booking));
    }

    public function test_booking_is_admin_reopen_in_place_transition_true_for_pending_target(): void
    {
        $booking = new Booking(['booking_status' => 'completed']);
        $booking->syncOriginal();
        $booking->booking_status = 'pending';
        $booking->reopened_by = 'admin-user-id';

        $this->assertTrue(booking_is_admin_reopen_in_place_transition($booking));
    }

    public function test_booking_is_admin_reopen_in_place_transition_false_without_reopen_markers(): void
    {
        $booking = new Booking(['booking_status' => 'completed']);
        $booking->syncOriginal();
        $booking->booking_status = 'pending';

        $this->assertFalse(booking_is_admin_reopen_in_place_transition($booking));
    }

    public function test_booking_is_admin_reopen_in_place_transition_false_for_unrelated_status_change(): void
    {
        $booking = new Booking(['booking_status' => 'accepted']);
        $booking->syncOriginal();
        $booking->booking_status = 'ongoing';
        $booking->reopened_by = 'admin-user-id';

        $this->assertFalse(booking_is_admin_reopen_in_place_transition($booking));
    }

    public function test_scenario_registry_includes_booking_admin_reopen(): void
    {
        $scenario = collect(notification_scenario_registry())->firstWhere('id', 'booking_admin_reopen');

        $this->assertNotNull($scenario);
        $this->assertSame('booking_update', $scenario['module']);

        $audiences = collect($scenario['audiences'])->pluck('audience')->all();
        $this->assertContains('customer', $audiences);
        $this->assertContains('provider', $audiences);
        $this->assertContains('admin', $audiences);

        $pushKeys = collect($scenario['audiences'])
            ->filter(fn (array $row) => ($row['channel'] ?? '') === 'push')
            ->pluck('key')
            ->unique()
            ->values()
            ->all();

        $this->assertSame(['booking_reopened'], $pushKeys);
    }

    public function test_default_templates_exist_for_booking_reopened(): void
    {
        $customer = get_notification_default_message('booking_reopened', 'customer_notification');
        $provider = get_notification_default_message('booking_reopened', 'provider_notification');

        $this->assertNotNull($customer);
        $this->assertNotNull($provider);
        $this->assertStringContainsString('Reopened', $customer['title']);
        $this->assertStringContainsString('reopened', strtolower($customer['description']));
        $this->assertStringContainsString('Reopened', $provider['title']);
        $this->assertStringContainsString('reopened', strtolower($provider['description']));
    }

    public function test_notification_definitions_include_booking_reopened(): void
    {
        $this->assertNotNull(notification_definition_for_key('booking_reopened'));

        $customerKeys = collect(NOTIFICATION_FOR_USER)->pluck('key')->all();
        $providerKeys = collect(NOTIFICATION_FOR_PROVIDER)->pluck('key')->all();

        $this->assertContains('booking_reopened', $customerKeys);
        $this->assertContains('booking_reopened', $providerKeys);
    }

    public function test_trigger_map_covers_booking_admin_reopen(): void
    {
        $map = notification_scenario_trigger_map();

        $this->assertArrayHasKey('booking_admin_reopen', $map);
        $this->assertSame('booking_update', $map['booking_admin_reopen']['module']);
    }
}
