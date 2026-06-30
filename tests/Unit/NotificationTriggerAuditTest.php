<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotificationTriggerAuditTest extends TestCase
{
    public function test_all_customer_notifications_have_trigger_scenarios(): void
    {
        foreach (NOTIFICATION_FOR_USER as $notification) {
            $info = notification_trigger_scenarios_for_key($notification['key'], 'customer_notification');
            $this->assertNotNull($info, "Missing trigger info for customer key: {$notification['key']}");
            $this->assertNotEmpty($info['scenarios']);
            $this->assertTrue($info['wired'], "Customer notification not marked wired: {$notification['key']}");
        }
    }

    public function test_all_provider_notifications_have_trigger_scenarios(): void
    {
        foreach (NOTIFICATION_FOR_PROVIDER as $notification) {
            $info = notification_trigger_scenarios_for_key($notification['key'], 'provider_notification');
            $this->assertNotNull($info, "Missing trigger info for provider key: {$notification['key']}");
            $this->assertNotEmpty($info['scenarios']);
            $this->assertTrue($info['wired'], "Provider notification not marked wired: {$notification['key']}");
        }
    }

    public function test_customer_only_keys_return_null_for_provider(): void
    {
        $customerOnly = ['booking_place', 'admin_booking_created', 'otp', 'provider_assign', 'refund', 'refund_bank_transfer', 'payment_failed', 'add_fund_wallet', 'wallet_deducted', 'referral_earning', 'loyalty_point', 'loyalty_point_convert', 'customer_review_approved', 'review_published', 'booking_reminder'];
        foreach ($customerOnly as $key) {
            $this->assertNull(notification_trigger_scenarios_for_key($key, 'provider_notification'));
        }
    }

    public function test_provider_only_keys_return_null_for_customer(): void
    {
        $providerOnly = ['new_service_request_arrived', 'admin_booking_assigned', 'booking_assigned_to_provider', 'service_request_approve', 'service_request_deny', 'showcase_submitted', 'showcase_approve', 'showcase_deny', 'widthdraw_request_approve', 'widthdraw_request_deny', 'withdraw_request_submitted', 'admin_payable', 'settlement_received', 'review_approved', 'provider_review_published', 'provider_removed_from_booking'];
        foreach ($providerOnly as $key) {
            $this->assertNull(notification_trigger_scenarios_for_key($key, 'customer_notification'));
        }
    }

    public function test_shared_payment_keys_have_both_audiences(): void
    {
        foreach (['payment_collected_company', 'payment_collected_provider'] as $key) {
            $customer = notification_trigger_scenarios_for_key($key, 'customer_notification');
            $provider = notification_trigger_scenarios_for_key($key, 'provider_notification');
            $this->assertNotNull($customer);
            $this->assertNotNull($provider);
        }
    }

    public function test_recommendations_list_is_empty(): void
    {
        $this->assertSame([], notification_trigger_recommendations());
    }

    public function test_booking_status_mapping_covers_main_statuses(): void
    {
        $this->assertSame('booking_place', resolve_booking_status_notification_key('pending', 'customer'));
        $this->assertSame('new_service_request_arrived', resolve_booking_status_notification_key('pending', 'provider'));
        $this->assertSame('booking_status_change', resolve_booking_status_notification_key('ongoing', 'customer'));
        $this->assertSame('booking_status_change', resolve_booking_status_notification_key('canceled', 'provider'));
        $this->assertSame('booking_status_change', resolve_booking_status_notification_key('on_hold', 'customer'));
    }
}
