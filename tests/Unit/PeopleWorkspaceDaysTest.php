<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\AdminModule\Services\PeopleWorkspace;
use PHPUnit\Framework\TestCase;

class PeopleWorkspaceDaysTest extends TestCase
{
    public function test_working_days_skip_sundays_and_holidays(): void
    {
        $days = PeopleWorkspace::countWorkingDays(
            Carbon::parse('2026-09-27'),
            Carbon::parse('2026-10-03'),
            ['2026-10-02']
        );

        $this->assertSame(5, $days);
    }

    public function test_a_holiday_on_its_own_is_not_a_working_day(): void
    {
        $days = PeopleWorkspace::countWorkingDays(
            Carbon::parse('2026-10-02'),
            Carbon::parse('2026-10-02'),
            ['2026-10-02']
        );

        $this->assertSame(0, $days);
    }
}
