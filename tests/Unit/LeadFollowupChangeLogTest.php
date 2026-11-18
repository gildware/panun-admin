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
}
