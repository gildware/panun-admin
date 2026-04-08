<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Services\WhatsAppAiPlayground;
use Tests\TestCase;

class WhatsAppAiPlaygroundTest extends TestCase
{
    public function test_skip_cloud_api_for_ai_test_prefix(): void
    {
        $this->assertTrue(WhatsAppAiPlayground::skipCloudApi('AI_TEST_SANDBOX'));
        $this->assertTrue(WhatsAppAiPlayground::skipCloudApi('AI_TEST_919353294014'));
        $this->assertFalse(WhatsAppAiPlayground::skipCloudApi('+919353294014'));
        $this->assertFalse(WhatsAppAiPlayground::skipCloudApi(''));
    }
}
