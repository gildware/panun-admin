<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Support\WhatsAppChatStatusBucket;
use PHPUnit\Framework\TestCase;

class WhatsAppChatStatusBucketTest extends TestCase
{
    public function test_null_status_is_open(): void
    {
        $this->assertSame('open', WhatsAppChatStatusBucket::fromChatStatus(null));
        $this->assertTrue(WhatsAppChatStatusBucket::matches(null, ['open']));
        $this->assertFalse(WhatsAppChatStatusBucket::matches(null, ['closed']));
    }

    public function test_closed_bucket_is_excluded_from_open_filter(): void
    {
        $closed = ['id' => 2, 'name' => 'Closed', 'bucket' => 'closed'];

        $this->assertSame('closed', WhatsAppChatStatusBucket::fromChatStatus($closed));
        $this->assertFalse(WhatsAppChatStatusBucket::matches($closed, ['open']));
        $this->assertTrue(WhatsAppChatStatusBucket::matches($closed, ['closed']));
        $this->assertTrue(WhatsAppChatStatusBucket::matches($closed, []));
    }
}
