<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotificationScenarioMessagesTest extends TestCase
{
    public function test_all_scenario_message_keys_have_default_templates(): void
    {
        foreach (notification_scenario_message_keys() as $entry) {
            $template = get_notification_default_message($entry['key'], $entry['settings_type']);
            $this->assertNotNull(
                $template,
                "Missing default template for {$entry['settings_type']} / {$entry['key']}"
            );
            $this->assertNotEmpty(trim($template['title']));
            $this->assertNotEmpty(trim($template['description']));
        }
    }

    public function test_scenario_module_counts(): void
    {
        $grouped = group_notification_scenarios_by_module();

        $this->assertCount(3, $grouped['booking_creation']);
        $this->assertCount(18, $grouped['booking_update']);
        $this->assertCount(4, $grouped['payments']);
        $this->assertCount(6, $grouped['provider_payments']);
        $this->assertCount(2, $grouped['review']);
        $this->assertCount(5, $grouped['loyalty_points']);
        $this->assertCount(2, $grouped['wallet']);
        $this->assertCount(2, $grouped['refund']);
        $this->assertCount(1, $grouped['communication']);
        $this->assertCount(2, $grouped['service_requests']);
        $this->assertCount(2, $grouped['provider_account']);
        $this->assertCount(5, $grouped['advertisement']);
        $this->assertCount(5, $grouped['admin_alerts']);
        $this->assertCount(57, notification_scenario_registry());
    }

    public function test_all_config_keys_are_covered_by_scenarios(): void
    {
        $missing = notification_config_keys_missing_from_scenarios();
        $this->assertSame([], $missing, 'Config keys missing from scenario registry: ' . json_encode($missing));
    }

    public function test_newly_added_scenarios_exist(): void
    {
        $ids = array_column(notification_scenario_registry(), 'id');

        foreach ([
            'booking_provider_accept',
            'booking_on_hold',
            'booking_pending_cancellation',
            'booking_refund_request_status',
            'booking_ignored_by_provider',
            'booking_service_location_updated',
            'referral_code_used',
            'provider_suspended',
            'advertisement_approved',
            'admin_alert_provider_registration',
        ] as $scenarioId) {
            $this->assertContains($scenarioId, $ids, "Missing scenario: {$scenarioId}");
        }
    }

    public function test_provider_accept_scenario_exists(): void
    {
        $scenario = collect(notification_scenario_registry())->firstWhere('id', 'booking_provider_accept');
        $this->assertNotNull($scenario);

        $keys = collect($scenario['audiences'])->pluck('key', 'audience')->all();
        $this->assertSame('booking_accepted', $keys['customer']);
        $this->assertSame('booking_accepted', $keys['provider']);
    }

    public function test_all_config_keys_have_default_templates(): void
    {
        foreach (NOTIFICATION_FOR_USER as $notification) {
            $template = get_notification_default_message($notification['key'], 'customer_notification');
            $this->assertNotNull($template, "Missing customer template: {$notification['key']}");
        }

        foreach (NOTIFICATION_FOR_PROVIDER as $notification) {
            $template = get_notification_default_message($notification['key'], 'provider_notification');
            $this->assertNotNull($template, "Missing provider template: {$notification['key']}");
        }
    }

    public function test_default_templates_use_valid_variables(): void
    {
        foreach (notification_default_message_templates() as $settingsType => $templates) {
            foreach ($templates as $key => $template) {
                $allowed = notification_message_variables_for_key($key);
                preg_match_all('/\{\{[a-zA-Z0-9_]+\}\}/', $template['title'] . $template['description'], $matches);
                foreach ($matches[0] as $var) {
                    $this->assertContains(
                        $var,
                        $allowed,
                        "Unknown variable {$var} in {$settingsType}/{$key}"
                    );
                }
            }
        }
    }

    public function test_scenario_send_helper_map(): void
    {
        $helperMap = [
            'send_admin_booking_created_notifications',
            'send_booking_new_service_request_to_assigned_provider',
            'send_booking_edit_service_add_notifications',
            'send_booking_payment_collected_notifications',
            'send_customer_payment_failed_notification',
            'send_customer_wallet_deducted_notification',
            'send_customer_refund_notification',
            'send_customer_loyalty_point_notification',
            'send_review_approved_to_provider_notification',
            'send_review_approved_to_customer_notification',
            'send_provider_withdraw_request_submitted_notification',
            'send_provider_removed_from_booking_notification',
            'send_provider_settlement_received_notification',
            'send_booking_reminder_notification',
            'send_chat_message_push_notification',
        ];

        foreach ($helperMap as $helper) {
            $this->assertTrue(function_exists($helper), "Missing helper: {$helper}");
        }
    }
}
