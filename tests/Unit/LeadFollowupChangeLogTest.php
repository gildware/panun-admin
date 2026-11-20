<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Services\LeadChangeLogService;
use Tests\TestCase;

class LeadFollowupChangeLogTest extends TestCase
{
    private LeadChangeLogService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lead_change_logs');
        Schema::create('lead_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('changed_by', 64)->nullable();
            $table->json('changes');
            $table->timestamps();
        });

        $this->service = new LeadChangeLogService;
    }

    public function test_reschedule_after_due_logs_missed_and_the_move(): void
    {
        $leadId = 3960;
        $due = Carbon::parse('2026-11-18 10:00:00');
        $movedTo = Carbon::parse('2026-11-19 12:00:00');

        $this->assertTrue($this->service->recordFollowupMissedIfPast($leadId, $due, Carbon::parse('2026-11-18 10:02:32')));
        $this->service->recordFollowupRescheduled($leadId, $due, $movedTo, 'call');

        $this->assertFalse($this->service->recordFollowupMissedIfPast($leadId, $due, Carbon::parse('2026-11-18 10:06:00')));

        $logs = LeadChangeLog::query()->where('lead_id', $leadId)->orderBy('id')->get();
        $this->assertCount(2, $logs);

        $missed = $logs[0]->changes['followup_missed'];
        $this->assertSame('Follow_up_missed', $missed['label']);
        $this->assertSame('18 November 2026 10:00 am', $missed['old']);
        $this->assertSame('Missed', $missed['new']);

        $moved = $logs[1]->changes['followup_rescheduled'];
        $this->assertSame('18 November 2026 10:00 am', $moved['old']);
        $this->assertSame('19 November 2026 12:00 pm', $moved['new']);
        $this->assertSame('call', $moved['reason']);
    }

    public function test_taken_on_time_logs_due_and_taken_without_a_miss(): void
    {
        $due = Carbon::parse('2026-11-18 10:00:00');
        $taken = Carbon::parse('2026-11-17 21:19:47');

        $this->assertFalse($this->service->recordFollowupMissedIfPast(3960, $due, $taken));
        $this->service->recordFollowupTaken(3960, $due, $taken, 'Spoke to the customer');

        $log = LeadChangeLog::query()->where('lead_id', 3960)->first();
        $this->assertArrayNotHasKey('followup_missed', $log->changes);
        $takenChange = $log->changes['followup_taken'];
        $this->assertSame('18 November 2026 10:00 am', $takenChange['old']);
        $this->assertSame('17 November 2026 09:19 pm', $takenChange['new']);
        $this->assertSame('Spoke to the customer', $takenChange['reason']);
    }

    public function test_taken_after_due_logs_missed_then_taken(): void
    {
        $due = Carbon::parse('2026-11-18 10:00:00');
        $taken = Carbon::parse('2026-11-18 11:15:00');

        $this->service->recordFollowupMissedIfPast(12, $due, $taken);
        $this->service->recordFollowupTaken(12, $due, $taken);

        $logs = LeadChangeLog::query()->where('lead_id', 12)->orderBy('id')->get();
        $this->assertSame('Follow_up_missed', $logs[0]->changes['followup_missed']['label']);
        $this->assertSame('18 November 2026 11:15 am', $logs[1]->changes['followup_taken']['new']);
    }

    public function test_remove_followup_taken_events_clears_take_and_matching_miss(): void
    {
        $leadId = 3933;
        $due = Carbon::parse('2026-11-20 23:00:00');
        $lateTaken = Carbon::parse('2026-11-21 00:15:00');
        $otherDue = Carbon::parse('2026-11-21 10:00:00');
        $otherTaken = Carbon::parse('2026-11-18 09:00:00');

        $this->service->recordFollowupMissedIfPast($leadId, $due, $lateTaken);
        $this->service->recordFollowupTaken($leadId, $due, $lateTaken);
        $this->service->recordFollowupTaken($leadId, $otherDue, $otherTaken);

        $this->assertSame(2, $this->service->removeFollowupTakenEvents($leadId, $due, $lateTaken));

        $logs = LeadChangeLog::query()->where('lead_id', $leadId)->get();
        $this->assertCount(1, $logs);
        $this->assertSame('21 November 2026 10:00 am', $logs[0]->changes['followup_taken']['old']);
        $this->assertSame('18 November 2026 09:00 am', $logs[0]->changes['followup_taken']['new']);
    }

    public function test_remove_early_take_does_not_clear_unrelated_miss_for_same_due(): void
    {
        $leadId = 3933;
        $due = Carbon::parse('2026-11-20 23:00:00');
        $earlyTaken = Carbon::parse('2026-11-17 13:32:31');

        $this->service->recordFollowupMissedIfPast($leadId, $due, Carbon::parse('2026-11-21 00:00:00'));
        $this->service->recordFollowupTaken($leadId, $due, $earlyTaken);

        $this->assertSame(1, $this->service->removeFollowupTakenEvents($leadId, $due, $earlyTaken));
        $this->assertTrue($this->service->followupMissedAlreadyLogged($leadId, $due->format('Y-m-d H:i:s')));
    }
}
