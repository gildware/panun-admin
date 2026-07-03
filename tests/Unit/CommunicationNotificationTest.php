<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CommunicationNotificationTest extends TestCase
{
    /** @return list<array> */
    private function communicationScenarios(): array
    {
        return array_values(array_filter(
            notification_scenario_registry(),
            fn (array $scenario) => ($scenario['module'] ?? '') === 'communication'
        ));
    }

    public function test_communication_module_has_three_scenarios(): void
    {
        $scenarios = $this->communicationScenarios();

        $this->assertCount(3, $scenarios);

        $ids = array_column($scenarios, 'id');
        $this->assertSame([
            'chat_new_message',
            'chat_admin_customer_message',
            'chat_admin_provider_message',
        ], $ids);
    }

    /**
     * @dataProvider communicationScenarioProvider
     */
    public function test_each_communication_scenario_has_required_fields(string $scenarioId): void
    {
        $scenario = collect(notification_scenario_registry())->firstWhere('id', $scenarioId);
        $this->assertNotNull($scenario, "Missing scenario: {$scenarioId}");
        $this->assertSame('communication', $scenario['module']);
        $this->assertNotEmpty($scenario['title']);
        $this->assertNotEmpty($scenario['trigger_actor']);
        $this->assertNotEmpty($scenario['trigger_action']);
        $this->assertNotEmpty($scenario['audiences']);

        foreach ($scenario['audiences'] as $audience) {
            $this->assertTrue($audience['wired'], "{$scenarioId} audience must be wired");
        }
    }

    public static function communicationScenarioProvider(): array
    {
        return [
            'booking chat' => ['chat_new_message'],
            'admin customer chat' => ['chat_admin_customer_message'],
            'admin provider chat' => ['chat_admin_provider_message'],
        ];
    }

    /**
     * @dataProvider chatMessageTemplateProvider
     */
    public function test_chat_message_templates_are_complete(string $settingsType): void
    {
        $template = get_notification_default_message('chat_message', $settingsType);

        $this->assertNotNull($template, "Missing chat_message template for {$settingsType}");
        $this->assertNotEmpty(trim($template['title']), "{$settingsType} title must not be empty");
        $this->assertNotEmpty(trim($template['description']), "{$settingsType} description must not be empty");

        $allowed = notification_message_variables_for_key('chat_message');
        preg_match_all('/\{\{[a-zA-Z0-9_]+\}\}/', $template['title'] . $template['description'], $matches);
        foreach ($matches[0] as $var) {
            $this->assertContains($var, $allowed, "Unknown variable {$var} in {$settingsType}/chat_message");
        }
    }

    public static function chatMessageTemplateProvider(): array
    {
        return [
            'customer push' => ['customer_notification'],
            'provider push' => ['provider_notification'],
        ];
    }

    public function test_customer_chat_message_template_content(): void
    {
        $template = get_notification_default_message('chat_message', 'customer_notification');

        $this->assertSame('New Message from {{senderName}}', $template['title']);
        $this->assertStringContainsString('{{userName}}', $template['description']);
        $this->assertStringContainsString('{{bookingId}}', $template['description']);
    }

    public function test_provider_chat_message_template_content(): void
    {
        $template = get_notification_default_message('chat_message', 'provider_notification');

        $this->assertSame('New Chat Message', $template['title']);
        $this->assertStringContainsString('{{providerName}}', $template['description']);
        $this->assertStringContainsString('{{bookingId}}', $template['description']);
        $this->assertStringContainsString('{{userName}}', $template['description']);
    }

    /**
     * @dataProvider chatMessagePreviewProvider
     */
    public function test_chat_message_preview_replaces_all_variables(string $settingsType, string $field): void
    {
        $template = get_notification_default_message('chat_message', $settingsType);
        $text = $template[$field];
        $preview = preview_notification_message_text($text, 'chat_message');

        preg_match_all('/\{\{[a-zA-Z0-9_]+\}\}/', $preview, $remaining);
        $this->assertSame([], $remaining[0], "Unresolved variables in {$settingsType}/{$field}: " . implode(', ', $remaining[0]));
    }

    public static function chatMessagePreviewProvider(): array
    {
        return [
            'customer title' => ['customer_notification', 'title'],
            'customer description' => ['customer_notification', 'description'],
            'provider title' => ['provider_notification', 'title'],
            'provider description' => ['provider_notification', 'description'],
        ];
    }

    public function test_chat_message_trigger_scenarios_for_customer(): void
    {
        $info = notification_trigger_scenarios_for_key('chat_message', 'customer_notification');

        $this->assertNotNull($info);
        $this->assertSame('Customer', $info['recipient']);
        $this->assertSame('Communication', $info['module']);
        $this->assertTrue($info['wired']);
        $this->assertCount(3, $info['scenarios']);
        $this->assertStringContainsString('booking chat', $info['scenarios'][0]);
        $this->assertStringContainsString('support conversation', $info['scenarios'][1]);
    }

    public function test_chat_message_trigger_scenarios_for_provider(): void
    {
        $info = notification_trigger_scenarios_for_key('chat_message', 'provider_notification');

        $this->assertNotNull($info);
        $this->assertSame('Provider', $info['recipient']);
        $this->assertSame('Communication', $info['module']);
        $this->assertTrue($info['wired']);
        $this->assertCount(3, $info['scenarios']);
        $this->assertStringContainsString('booking chat', $info['scenarios'][0]);
        $this->assertStringContainsString('support conversation', $info['scenarios'][1]);
    }

    /**
     * @dataProvider communicationScenarioProvider
     */
    public function test_each_communication_scenario_trigger_is_wired(string $scenarioId): void
    {
        $audit = audit_notification_scenario_triggers();
        $scenarioResults = array_values(array_filter(
            $audit['results'],
            fn (array $row) => $row['scenario_id'] === $scenarioId
        ));

        $this->assertNotEmpty($scenarioResults, "No trigger checks for {$scenarioId}");

        foreach ($scenarioResults as $result) {
            $this->assertTrue(
                $result['ok'],
                "{$scenarioId} — {$result['label']} missing: " . implode(', ', $result['missing'])
            );
        }
    }

    public function test_booking_chat_scenario_audiences(): void
    {
        $scenario = collect(notification_scenario_registry())->firstWhere('id', 'chat_new_message');

        $audiences = collect($scenario['audiences'])->keyBy('audience');
        $this->assertSame('push', $audiences['customer']['channel']);
        $this->assertSame('chat_message', $audiences['customer']['key']);
        $this->assertSame('push', $audiences['provider']['channel']);
        $this->assertSame('chat_message', $audiences['provider']['key']);
    }

    public function test_admin_customer_chat_scenario_audiences(): void
    {
        $scenario = collect(notification_scenario_registry())->firstWhere('id', 'chat_admin_customer_message');

        $audiences = collect($scenario['audiences'])->keyBy('audience');
        $this->assertSame('push', $audiences['customer']['channel']);
        $this->assertSame('chat_message', $audiences['customer']['key']);
        $this->assertSame('inbox', $audiences['admin']['channel']);
        $this->assertNull($audiences['admin']['key']);
    }

    public function test_admin_provider_chat_scenario_audiences(): void
    {
        $scenario = collect(notification_scenario_registry())->firstWhere('id', 'chat_admin_provider_message');

        $audiences = collect($scenario['audiences'])->keyBy('audience');
        $this->assertSame('push', $audiences['provider']['channel']);
        $this->assertSame('chat_message', $audiences['provider']['key']);
        $this->assertSame('inbox', $audiences['admin']['channel']);
        $this->assertNull($audiences['admin']['key']);
    }

    /**
     * @dataProvider chatMessageSettingsTypeProvider
     */
    public function test_chat_message_notification_settings_type(string $userType, ?string $expected): void
    {
        $user = new \Modules\UserManagement\Entities\User(['user_type' => $userType]);

        $this->assertSame($expected, chat_message_notification_settings_type($user));
    }

    public static function chatMessageSettingsTypeProvider(): array
    {
        return [
            'customer' => ['customer', 'customer_notification'],
            'provider admin' => ['provider-admin', 'provider_notification'],
            'provider employee' => ['provider-employee', 'provider_notification'],
            'super admin' => ['super-admin', null],
            'serviceman' => ['provider-serviceman', null],
        ];
    }

    public function test_communication_push_helpers_exist(): void
    {
        $this->assertTrue(function_exists('send_chat_message_push_notification'));
        $this->assertTrue(function_exists('dispatch_chat_message_push_notifications'));
        $this->assertTrue(function_exists('build_chat_message_sender_payload'));
        $this->assertTrue(function_exists('chat_message_push_recipient_users'));
        $this->assertTrue(function_exists('provider_org_member_user_ids'));
        $this->assertTrue(function_exists('chat_message_same_phone_fcm_users'));
        $this->assertTrue(function_exists('admin_inbox_notify_chat_message'));
        $this->assertTrue(function_exists('device_notification_for_chatting'));
    }

    public function test_chat_message_is_referenced_by_all_communication_push_audiences(): void
    {
        $pushKeys = [];

        foreach ($this->communicationScenarios() as $scenario) {
            foreach ($scenario['audiences'] as $audience) {
                if (($audience['channel'] ?? '') === 'push' && ($audience['key'] ?? null)) {
                    $pushKeys[] = $audience['settings_type'] . ':' . $audience['key'];
                }
            }
        }

        $this->assertSame([
            'customer_notification:chat_message',
            'provider_notification:chat_message',
            'customer_notification:chat_message',
            'provider_notification:chat_message',
        ], $pushKeys);
    }
}
