<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MobileInboxNotificationHelpersTest extends TestCase
{
    public function test_device_notification_accepts_push_notification_id_parameter(): void
    {
        $reflection = new \ReflectionFunction('device_notification');
        $params = $reflection->getParameters();

        $this->assertSame('push_notification_id', end($params)->getName());
    }

    public function test_persist_transactional_push_notification_returns_nullable_string(): void
    {
        $reflection = new \ReflectionFunction('persist_transactional_push_notification');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertSame('?string', (string) $returnType);
    }
}
