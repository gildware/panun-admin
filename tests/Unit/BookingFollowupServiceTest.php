<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\BookingModule\Services\BookingFollowupService;
use Tests\TestCase;

class BookingFollowupServiceTest extends TestCase
{
    private BookingFollowupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingFollowupService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_short_notice_next_day_service_uses_service_day_morning(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 17:00:00');
        $scheduledAt = Carbon::parse('2026-08-15 15:00:00');
        Carbon::setTestNow($bookedAt);

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-15 11:00:00')));
        $this->assertTrue($followUpAt->isFuture());
    }

    public function test_long_lead_uses_day_before_at_fixed_morning_time(): void
    {
        $bookedAt = Carbon::parse('2026-08-11 10:00:00');
        $scheduledAt = Carbon::parse('2026-08-13 15:00:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-12 10:00:00')));
    }

    public function test_same_day_service_uses_one_hour_before(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 17:00:00');
        $scheduledAt = Carbon::parse('2026-08-14 18:00:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-14 17:00:00')));
    }

    public function test_same_day_late_booking_floors_to_booking_time(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 17:00:00');
        $scheduledAt = Carbon::parse('2026-08-14 17:30:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo($bookedAt));
    }

    public function test_staff_suggestion_uses_default_when_that_time_is_safely_in_the_future(): void
    {
        $now = Carbon::parse('2026-08-11 10:00:00');
        Carbon::setTestNow($now);
        $scheduledAt = Carbon::parse('2026-08-13 15:00:00');

        $suggested = $this->service->suggestedFollowupAtForStaffCreate($scheduledAt, $now);

        $this->assertTrue($suggested->equalTo(Carbon::parse('2026-08-12 10:00:00')));
    }

    public function test_staff_suggestion_avoids_instant_missed_on_same_day_late_booking(): void
    {
        $now = Carbon::parse('2026-09-11 17:12:54');
        Carbon::setTestNow($now);
        $scheduledAt = Carbon::parse('2026-09-11 18:00:00');

        $suggested = $this->service->suggestedFollowupAtForStaffCreate($scheduledAt, $now);

        $this->assertTrue($suggested->equalTo(Carbon::parse('2026-09-11 17:45:00')));
        $this->assertTrue($suggested->gt($now));
    }

    public function test_staff_suggestion_falls_back_to_min_future_when_service_is_imminent(): void
    {
        $now = Carbon::parse('2026-09-11 17:50:00');
        Carbon::setTestNow($now);
        $scheduledAt = Carbon::parse('2026-09-11 18:00:00');

        $suggested = $this->service->suggestedFollowupAtForStaffCreate($scheduledAt, $now);

        $this->assertTrue($suggested->equalTo(Carbon::parse('2026-09-11 18:05:00')));
    }

    public function test_early_morning_service_caps_before_service_hour(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 22:00:00');
        $scheduledAt = Carbon::parse('2026-08-15 08:00:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-15 07:00:00')));
    }

    public function test_first_human_assign_pushes_past_due_by_one_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:30:00'));
        $currentDue = Carbon::parse('2026-09-11 10:00:00');

        $newDue = $this->service->firstHumanAssignGraceDueAt(null, 'employee-uuid', $currentDue);

        $this->assertTrue($newDue->equalTo(Carbon::parse('2026-09-11 11:30:00')));
    }

    public function test_first_human_assign_keeps_due_when_more_than_one_hour_remains(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 11:00:00'));
        $currentDue = Carbon::parse('2026-09-11 16:00:00');

        $newDue = $this->service->firstHumanAssignGraceDueAt(null, 'employee-uuid', $currentDue);

        $this->assertNull($newDue);
    }

    public function test_human_to_human_booking_reassign_does_not_move_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:30:00'));
        $currentDue = Carbon::parse('2026-09-11 10:00:00');

        $newDue = $this->service->firstHumanAssignGraceDueAt('employee-a', 'employee-b', $currentDue);

        $this->assertNull($newDue);
    }
}
