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
        $this->assertTrue(function_exists('mask_fcm_token'));
        $this->assertTrue(function_exists('log_push_notification_delivery'));
    }

    public function test_mask_fcm_token_masks_long_tokens(): void
    {
        $masked = mask_fcm_token('abcdefghijklmnopqrstuvwxyz');
        $this->assertStringContainsString('…', $masked);
        $this->assertNull(mask_fcm_token('@'));
    }

    public function test_is_valid_fcm_token_rejects_logout_placeholder(): void
    {
        $this->assertFalse(is_valid_fcm_token('@'));
        $this->assertFalse(is_valid_fcm_token(''));
        $this->assertTrue(is_valid_fcm_token('real-fcm-token'));
    }

    public function test_register_user_fcm_device_keys_rows_by_token_not_device_id(): void
    {
        if (! class_exists(\Modules\UserManagement\Entities\UserFcmDevice::class)) {
            $this->markTestSkipped('UserFcmDevice model is not available in this test harness.');
        }

        $this->assertStringContainsString(
            "'fcm_token' => \$fcmToken",
            (string) file_get_contents(dirname(__DIR__, 2).'/app/Lib/UserFcmDeviceHelpers.php')
        );
        $this->assertStringContainsString(
            'sync_user_legacy_fcm_token($userId)',
            (string) file_get_contents(dirname(__DIR__, 2).'/app/Lib/UserFcmDeviceHelpers.php')
        );
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
