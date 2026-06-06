<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiTriageCopy;
use Tests\TestCase;

class MobileAppAiTriageCopyTest extends TestCase
{
    public function test_tap_leak_response_is_empathetic(): void
    {
        $reply = MobileAppAiTriageCopy::issueResponse('my tap is leaking water', [
            'Turn off the valve under the sink.',
            'Tighten the tap connector gently.',
        ]);

        $this->assertStringContainsString('Sorry to hear', $reply);
        $this->assertStringContainsString('your tap is leaking water', $reply);
        $this->assertStringContainsString('Have you tried', $reply);
        $this->assertStringContainsString('troubleshoot', $reply);
        $this->assertStringContainsString('booking', $reply);
        $this->assertStringNotContainsString('Sounds like', $reply);
    }

    public function test_follow_up_does_not_echo_yes_i_tried(): void
    {
        $this->assertTrue(\Modules\BusinessSettingsModule\Services\MobileAppAiTriageFollowUp::isFollowUp('yes I tried everything'));

        $reply = MobileAppAiTriageCopy::stillNotFixedAfterTriage('my tap is leaking water');
        $this->assertStringNotContainsString('yes I tried', $reply);
        $this->assertStringNotContainsString('Sorry to hear your yes', $reply);
        $this->assertStringContainsString('still not resolved', $reply);
        $this->assertStringContainsString('Book this service', $reply);
    }
}
