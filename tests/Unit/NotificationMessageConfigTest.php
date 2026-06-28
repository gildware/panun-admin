<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotificationMessageConfigTest extends TestCase
{
    public function test_customer_notifications_grouped_into_four_categories(): void
    {
        $grouped = group_notification_messages_by_category(NOTIFICATION_FOR_USER);

        $this->assertArrayHasKey('booking_status', $grouped);
        $this->assertArrayHasKey('service_updates', $grouped);
        $this->assertArrayHasKey('payments', $grouped);
        $this->assertArrayHasKey('wallet_and_loyalty', $grouped);
        $this->assertArrayHasKey('review', $grouped);
        $this->assertArrayNotHasKey('bidding', $grouped);

        $this->assertCount(12, $grouped['booking_status']);
        $this->assertCount(2, $grouped['service_updates']);
        $this->assertCount(5, $grouped['payments']);
        $this->assertCount(6, $grouped['wallet_and_loyalty']);
        $this->assertCount(1, $grouped['review']);
    }

    public function test_provider_notifications_exclude_bidding(): void
    {
        $grouped = group_notification_messages_by_category(NOTIFICATION_FOR_PROVIDER);
        $keys = collect($grouped)->flatten(1)->pluck('key')->all();

        $this->assertNotContains('provider_bid_request_denied', $keys);
        $this->assertNotContains('serviceman_assign', $keys);
        $this->assertContains('booking_status_change', $keys);
        $this->assertContains('payment_collected_company', $keys);
        $this->assertContains('payment_collected_provider', $keys);
    }

    public function test_booking_status_notification_key_mapping(): void
    {
        $this->assertSame('booking_place', resolve_booking_status_notification_key('pending', 'customer'));
        $this->assertSame('new_service_request_arrived', resolve_booking_status_notification_key('pending', 'provider'));
        $this->assertSame('booking_accepted', resolve_booking_status_notification_key('accepted', 'customer'));
        $this->assertSame('booking_complete', resolve_booking_status_notification_key('completed', 'provider'));
        $this->assertSame('booking_status_change', resolve_booking_status_notification_key('ongoing', 'customer'));
        $this->assertSame('booking_status_change', resolve_booking_status_notification_key('canceled', 'provider'));
        $this->assertSame('refund', resolve_booking_status_notification_key('refund_request', 'customer'));
    }

    public function test_booking_placed_notification_skipped_after_provider_withdrawal(): void
    {
        $this->assertFalse(should_notify_customer_booking_placed_on_status_change(
            'pending',
            true,
            'accepted',
            true,
            '2026-06-27 12:00:00',
        ));
    }

    public function test_booking_placed_notification_sent_for_new_pending_booking(): void
    {
        $this->assertTrue(should_notify_customer_booking_placed_on_status_change(
            'pending',
            true,
            '',
            false,
            null,
        ));
    }

    public function test_preview_notification_message_replaces_variables(): void
    {
        $text = 'Hello {{userName}}, booking {{bookingId}} is {{bookingStatus}}.';
        $preview = preview_notification_message_text($text, 'booking_status_change');

        $this->assertStringNotContainsString('{{userName}}', $preview);
        $this->assertStringContainsString('John Doe', $preview);
        $this->assertStringContainsString('BK-1024', $preview);
        $this->assertStringContainsString('Ongoing', $preview);
    }

    public function test_notification_variables_per_key(): void
    {
        $otpVars = notification_message_variables_for_key('otp');
        $this->assertContains('{{otp}}', $otpVars);

        $paymentVars = notification_message_variables_for_key('payment_collected_company');
        $this->assertContains('{{amount}}', $paymentVars);
        $this->assertContains('{{bookingId}}', $paymentVars);
    }

    public function test_category_labels_use_bookings_heading(): void
    {
        $this->assertSame('Bookings', NOTIFICATION_MESSAGE_CATEGORY_LABELS['booking_status']);
    }
}
