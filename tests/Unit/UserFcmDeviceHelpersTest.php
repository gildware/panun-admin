<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserFcmDeviceHelpersTest extends TestCase
{
    public function test_user_fcm_device_helpers_are_registered(): void
    {
        $this->assertTrue(function_exists('register_user_fcm_device'));
        $this->assertTrue(function_exists('unregister_user_fcm_device'));
        $this->assertTrue(function_exists('user_fcm_device_tokens'));
        $this->assertTrue(function_exists('user_has_fcm_devices'));
        $this->assertTrue(function_exists('device_notification_for_user'));
        $this->assertTrue(function_exists('handle_user_fcm_token_request'));
    }

    public function test_is_valid_fcm_token_rejects_logout_placeholder(): void
    {
        $this->assertFalse(is_valid_fcm_token('@'));
        $this->assertFalse(is_valid_fcm_token(''));
        $this->assertTrue(is_valid_fcm_token('real-fcm-token'));
    }

    public function test_scenario_push_notification_accepts_user_recipient(): void
    {
        $reflection = new \ReflectionFunction('scenario_push_notification');
        $firstParam = $reflection->getParameters()[0];
        $type = $firstParam->getType();

        $this->assertNotNull($type);
        $this->assertStringContainsString('User', (string) $type);
    }
}
