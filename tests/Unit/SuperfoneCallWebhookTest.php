<?php

namespace Tests\Unit;

use Modules\LeadManagement\Services\SuperfoneCallService;
use Tests\TestCase;

class SuperfoneCallWebhookTest extends TestCase
{
    public function test_last_ten_digits_keep_the_indian_subscriber_number(): void
    {
        $this->assertSame('9876543210', SuperfoneCallService::lastTenDigits('+91 98765 43210'));
        $this->assertSame('9876543210', SuperfoneCallService::lastTenDigits('9876543210'));
    }

    public function test_provisional_disposition_does_not_replace_a_finished_call(): void
    {
        $this->assertFalse(SuperfoneCallService::shouldReplaceDisposition('ANSWER', 'ACTIVE'));
        $this->assertTrue(SuperfoneCallService::shouldReplaceDisposition('ACTIVE', 'NOANSWER'));
        $this->assertTrue(SuperfoneCallService::shouldReplaceDisposition(null, 'ANSWER'));
        $this->assertTrue(SuperfoneCallService::isProvisionalDisposition('RINGING'));
    }

    public function test_outbound_missed_calls_are_tagged_unanswered(): void
    {
        $this->assertSame('unanswered', SuperfoneCallService::outcomeForDirection('missed', 'OUTBOUND'));
        $this->assertSame('missed', SuperfoneCallService::outcomeForDirection('missed', 'INBOUND'));
        $this->assertSame('answered', SuperfoneCallService::outcomeForDirection('answered', 'OUTBOUND'));
    }

    public function test_a_provider_call_waits_for_the_employee_to_pick_the_lead(): void
    {
        $decision = SuperfoneCallService::decideLink(true, true, false, 'OUTBOUND', 0);

        $this->assertSame('provider', $decision['party']);
        $this->assertTrue($decision['needs_lead_link']);
        $this->assertNull($decision['booking']);
    }

    public function test_an_inbound_stranger_becomes_an_unknown_lead(): void
    {
        $decision = SuperfoneCallService::decideLink(false, false, false, 'INBOUND', 0);

        $this->assertSame('unknown', $decision['party']);
        $this->assertFalse($decision['needs_lead_link']);
    }

    public function test_an_outbound_unknown_number_is_left_for_the_employee(): void
    {
        $decision = SuperfoneCallService::decideLink(false, false, false, 'OUTBOUND', 0);

        $this->assertSame('unmatched', $decision['party']);
        $this->assertTrue($decision['needs_lead_link']);
    }

    public function test_one_open_booking_is_attached_and_several_are_left_to_choose(): void
    {
        $one = SuperfoneCallService::decideLink(false, true, true, 'OUTBOUND', 1);
        $many = SuperfoneCallService::decideLink(false, false, true, 'INBOUND', 3);

        $this->assertSame('lead', $one['party']);
        $this->assertSame('one', $one['booking']);
        $this->assertFalse($one['needs_lead_link']);
        $this->assertSame('customer', $many['party']);
        $this->assertSame('choose', $many['booking']);
        $this->assertFalse($many['needs_lead_link']);
    }

    public function test_zero_duration_does_not_wipe_a_real_duration(): void
    {
        $this->assertFalse(SuperfoneCallService::shouldReplaceDuration(42, 0));
        $this->assertTrue(SuperfoneCallService::shouldReplaceDuration(0, 42));
        $this->assertTrue(SuperfoneCallService::shouldReplaceDuration(null, 0));
        $this->assertFalse(SuperfoneCallService::shouldReplaceDuration(42, null));
    }
}
