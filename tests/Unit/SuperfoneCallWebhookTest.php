<?php

namespace Tests\Unit;

use Modules\LeadManagement\Services\SuperfoneCallService;
use Tests\TestCase;

class SuperfoneCallWebhookTest extends TestCase
{
    public function test_utc_call_timestamps_are_converted_to_ist(): void
    {
        $parsed = SuperfoneCallService::parseSuperfoneInstant('2026-10-20T04:01:39.000Z');

        $this->assertNotNull($parsed);
        $this->assertSame('Asia/Kolkata', $parsed->timezoneName);
        $this->assertSame('2026-10-20 09:31:39', $parsed->format('Y-m-d H:i:s'));

        $alreadyIst = SuperfoneCallService::parseSuperfoneInstant('2026-10-18T14:40:51+05:30');
        $this->assertNotNull($alreadyIst);
        $this->assertSame('2026-10-18 14:40:51', $alreadyIst->format('Y-m-d H:i:s'));

        $fromPayload = SuperfoneCallService::instantFromPayloads([
            'ALL_CALLS' => ['cdr_start' => '2026-10-20T04:01:39.000Z'],
        ], 'cdr_start');
        $this->assertSame('20 Oct 2026, 09:31 AM', $fromPayload?->format('d M Y, h:i A'));
    }

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

    public function test_a_missed_call_notifies_only_when_it_newly_becomes_missed(): void
    {
        $this->assertTrue(SuperfoneCallService::shouldNotifyMissed(null, 'missed'));
        $this->assertTrue(SuperfoneCallService::shouldNotifyMissed('answered', 'missed'));
        $this->assertFalse(SuperfoneCallService::shouldNotifyMissed('missed', 'missed'));
        $this->assertFalse(SuperfoneCallService::shouldNotifyMissed(null, 'unanswered'));
        $this->assertFalse(SuperfoneCallService::shouldNotifyMissed(null, 'answered'));
    }

    public function test_call_toast_copy_matches_direction(): void
    {
        $outbound = SuperfoneCallService::adminToastCopy(
            'OUTBOUND',
            'answered',
            '+917889458498',
            'Kamran Rasool',
            null,
            '1:20',
        );

        $this->assertSame('Kamran Rasool made Outbound call to +917889458498 and +917889458498 answered', $outbound['message']);
        $this->assertSame('+917889458498', $outbound['phone']);
        $this->assertSame('', $outbound['meta']);
        $this->assertSame('outbound', $outbound['tone']);
        $this->assertSame('call_made', $outbound['icon']);

        $named = SuperfoneCallService::adminToastCopy('OUTBOUND', 'answered', '+917889458498', 'Kamran Rasool', 'Rahul');
        $this->assertSame('Kamran Rasool made Outbound call to +917889458498 and Rahul answered', $named['message']);

        $quiet = SuperfoneCallService::adminToastCopy('OUTBOUND', 'unanswered', '+917889458498', 'Kamran Rasool', 'Rahul');
        $this->assertSame('Kamran Rasool made Outbound call to +917889458498 and Rahul did not answer', $quiet['message']);
        $this->assertSame('outbound-quiet', $quiet['tone']);

        $incoming = SuperfoneCallService::adminToastCopy('INBOUND', 'answered', '+919876543210', 'Asha');
        $this->assertSame('Incoming call answered', $incoming['title']);
        $this->assertSame('inbound', $incoming['tone']);

        $missed = SuperfoneCallService::adminToastCopy('INBOUND', 'missed', '+919876543210', null);
        $this->assertSame('Missed call', $missed['title']);
        $this->assertSame('missed', $missed['tone']);
        $this->assertSame('phone_missed', $missed['icon']);
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

    public function test_a_closed_lead_starts_a_new_lead_and_an_open_lead_is_reused(): void
    {
        $this->assertTrue(SuperfoneCallService::freshLeadForClosedMatch(false, true));
        $this->assertFalse(SuperfoneCallService::freshLeadForClosedMatch(true, true));
        $this->assertFalse(SuperfoneCallService::freshLeadForClosedMatch(false, false));
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
