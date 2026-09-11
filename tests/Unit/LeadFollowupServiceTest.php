<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadFollowupService;
use Tests\TestCase;

class LeadFollowupServiceTest extends TestCase
{
    private LeadFollowupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeadFollowupService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ai_default_followup_is_tomorrow_at_eleven(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 15:40:00'));

        $followUpAt = $this->service->defaultAiNextFollowupAt();

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-09-12 11:00:00')));
    }

    public function test_first_human_assign_pushes_past_due_by_one_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:30:00'));
        $lead = new Lead([
            'lead_type' => Lead::TYPE_UNKNOWN,
            'handled_by' => Lead::HANDLED_BY_AI,
            'next_followup_at' => Carbon::parse('2026-09-11 10:00:00'),
        ]);

        $changed = $this->service->applyFirstHumanAssignFollowupGrace(
            $lead,
            Lead::HANDLED_BY_AI,
            'employee-uuid'
        );

        $this->assertTrue($changed);
        $this->assertTrue($lead->next_followup_at->equalTo(Carbon::parse('2026-09-11 11:30:00')));
    }

    public function test_first_human_assign_keeps_due_when_more_than_one_hour_remains(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 11:00:00'));
        $originalDue = Carbon::parse('2026-09-11 16:00:00');
        $lead = new Lead([
            'lead_type' => Lead::TYPE_CUSTOMER,
            'handled_by' => Lead::HANDLED_BY_AI,
            'next_followup_at' => $originalDue->copy(),
        ]);

        $changed = $this->service->applyFirstHumanAssignFollowupGrace(
            $lead,
            Lead::HANDLED_BY_AI,
            'employee-uuid'
        );

        $this->assertFalse($changed);
        $this->assertTrue($lead->next_followup_at->equalTo($originalDue));
    }

    public function test_human_to_human_reassign_does_not_move_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:30:00'));
        $originalDue = Carbon::parse('2026-09-11 10:00:00');
        $lead = new Lead([
            'lead_type' => Lead::TYPE_CUSTOMER,
            'handled_by' => 'employee-a',
            'next_followup_at' => $originalDue->copy(),
        ]);

        $changed = $this->service->applyFirstHumanAssignFollowupGrace(
            $lead,
            'employee-a',
            'employee-b'
        );

        $this->assertFalse($changed);
        $this->assertTrue($lead->next_followup_at->equalTo($originalDue));
    }
}
