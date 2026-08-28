<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\BookingModule\Services\AdminRepeatBookingScheduleService;
use PHPUnit\Framework\TestCase;

class AdminRepeatBookingScheduleServiceTest extends TestCase
{
    private AdminRepeatBookingScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminRepeatBookingScheduleService();
    }

    public function test_daily_visits_are_inclusive(): void
    {
        $start = Carbon::parse('2026-04-01 10:30:00');
        $end = Carbon::parse('2026-04-03 23:59:59');
        $dates = $this->service->generateDaily($start, $end);

        $this->assertCount(3, $dates);
        $this->assertSame('2026-04-01 10:30:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-03 10:30:00', $dates[2]->format('Y-m-d H:i:s'));
    }

    public function test_weekly_visits_match_selected_weekdays(): void
    {
        $start = Carbon::parse('2026-04-06 09:00:00'); // Monday
        $end = Carbon::parse('2026-04-19 23:59:59');
        $dates = $this->service->generateWeekly($start, $end, [1, 4]); // Mon, Thu

        $this->assertCount(4, $dates);
        $this->assertSame('Monday', $dates[0]->format('l'));
        $this->assertSame('Thursday', $dates[1]->format('l'));
        $this->assertSame('09:00:00', $dates[1]->format('H:i:s'));
    }

    public function test_monthly_visits_keep_day_and_time(): void
    {
        $start = Carbon::parse('2026-01-31 11:15:00');
        $dates = $this->service->generateMonthly($start, 3);

        $this->assertCount(3, $dates);
        $this->assertSame('2026-01-31 11:15:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-28 11:15:00', $dates[1]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 11:15:00', $dates[2]->format('Y-m-d H:i:s'));
    }

    public function test_monthly_multiple_days_repeat_each_month(): void
    {
        $start = Carbon::parse('2026-04-05 10:00:00');
        $dates = $this->service->generateMonthly($start, 2, [5, 20]);

        $this->assertCount(4, $dates);
        $this->assertSame('2026-04-05 10:00:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-20 10:00:00', $dates[1]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-05 10:00:00', $dates[2]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-20 10:00:00', $dates[3]->format('Y-m-d H:i:s'));
    }

    public function test_monthly_day_31_clamps_in_short_months(): void
    {
        $start = Carbon::parse('2026-01-31 09:00:00');
        $dates = $this->service->generateMonthly($start, 3, [31]);

        $this->assertCount(3, $dates);
        $this->assertSame('2026-01-31 09:00:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-28 09:00:00', $dates[1]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 09:00:00', $dates[2]->format('Y-m-d H:i:s'));
    }

    public function test_custom_visits_dedupe_and_sort(): void
    {
        $first = Carbon::parse('2026-05-10 08:00:00');
        $dates = $this->service->generateCustom($first, [
            '2026-05-20T08:00',
            '2026-05-10 08:00:00',
            '2026-05-01T08:00',
        ]);

        $this->assertCount(3, $dates);
        $this->assertSame('2026-05-01 08:00:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-20 08:00:00', $dates[2]->format('Y-m-d H:i:s'));
    }

    public function test_open_ended_daily_uses_default_window(): void
    {
        $start = Carbon::parse('2026-04-01 10:30:00');
        $dates = $this->service->generateDailyUntilCount($start, AdminRepeatBookingScheduleService::OPEN_ENDED_DAILY_VISITS);

        $this->assertCount(AdminRepeatBookingScheduleService::OPEN_ENDED_DAILY_VISITS, $dates);
        $this->assertSame('2026-04-01 10:30:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-14 10:30:00', $dates[13]->format('Y-m-d H:i:s'));
    }

    public function test_open_ended_weekly_includes_start_then_weekdays(): void
    {
        $start = Carbon::parse('2026-04-06 09:00:00'); // Monday
        $dates = $this->service->generateWeeklyUntilCount($start, [1, 4], 4);

        $this->assertCount(4, $dates);
        $this->assertSame('Monday', $dates[0]->format('l'));
        $this->assertSame('Thursday', $dates[1]->format('l'));
        $this->assertSame('Monday', $dates[2]->format('l'));
    }

    public function test_following_dates_skip_existing_days(): void
    {
        $after = Carbon::parse('2026-04-06 09:00:00');
        $dates = $this->service->generateFollowingDates($after, 'weekly', [1], 2, ['2026-04-13']);

        $this->assertCount(2, $dates);
        $this->assertSame('2026-04-20 09:00:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-27 09:00:00', $dates[1]->format('Y-m-d H:i:s'));
    }

    public function test_cadence_meta_stores_visits_per_period(): void
    {
        $meta = $this->service->cadenceMetaFromPlan([
            'type' => 'monthly',
            'visits_per_period' => 3,
            'end_date' => '2026-12-31',
        ], Carbon::parse('2026-04-06 09:00:00'));

        $this->assertSame(3, $meta['visits_per_period']);
        $this->assertSame(3, $meta['planned_visits']);
        $this->assertSame('monthly', $meta['type']);
        $this->assertFalse($meta['until_stopped']);
        $this->assertSame('2026-12-31', $meta['end_date']);
        $this->assertSame('2026-04-06', $meta['start_date']);
    }

    public function test_cadence_meta_without_end_date_is_until_stopped(): void
    {
        $meta = $this->service->cadenceMetaFromPlan([
            'type' => 'monthly',
            'visits_per_period' => 3,
        ], Carbon::parse('2026-04-01 10:00:00'));

        $this->assertSame(3, $meta['visits_per_period']);
        $this->assertTrue($meta['until_stopped']);
        $this->assertNull($meta['end_date']);
    }

    public function test_period_key_groups_monthly_visits(): void
    {
        $this->assertSame('2026-04', $this->service->periodKey('monthly', Carbon::parse('2026-04-05 08:00:00')));
        $this->assertSame('2026-04', $this->service->periodKey('monthly', Carbon::parse('2026-04-20 18:00:00')));
        $this->assertSame('2026-W15', $this->service->periodKey('weekly', Carbon::parse('2026-04-06 09:00:00')));
        $this->assertSame('2026', $this->service->periodKey('yearly', Carbon::parse('2026-11-01 09:00:00')));
    }
}
