<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ChatMessagePushDispatchTest extends TestCase
{
    public function test_chat_push_helpers_include_message_payload_fields(): void
    {
        $promotion = (string) file_get_contents(dirname(__DIR__, 2).'/Modules/PromotionManagement/Lib/Promotion.php');
        $helpers = (string) file_get_contents(dirname(__DIR__, 2).'/app/Lib/NotificationMessageHelpers.php');
        $fcmHelpers = (string) file_get_contents(dirname(__DIR__, 2).'/app/Lib/UserFcmDeviceHelpers.php');

        $this->assertStringContainsString('conversation_id', $promotion);
        $this->assertStringContainsString('$extraData', $promotion);
        $this->assertStringContainsString("'message' => (string) (\$conversation->message ?? '')", $helpers);
        $this->assertStringContainsString('provider_org_member_user_ids', $helpers);
        $this->assertStringContainsString('where(\'device_id\', $deviceId)', $fcmHelpers);
    }

    public function test_org_fan_out_only_when_channel_member_has_no_fcm(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Lib/NotificationMessageHelpers.php');

        $this->assertStringContainsString('if (user_has_fcm_devices($channelMember)) {', $source);
        $this->assertStringContainsString('return [$channelMember];', $source);
        $this->assertStringContainsString('Chat org push fan-out failed', $source);
    }

    public function test_dispatch_is_wrapped_and_does_not_use_users_provider_id_column(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Lib/NotificationMessageHelpers.php');

        $this->assertStringContainsString('catch (\\Throwable $exception)', $source);
        $this->assertStringContainsString('Chat message push dispatch failed', $source);
        $this->assertStringNotContainsString("->where('provider_id', \$providerOrgId)", $source);
    }
}
