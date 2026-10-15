<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\AdminModule\Services\PeopleLeaveAccrual;
use PHPUnit\Framework\TestCase;

class PeopleLeaveAccrualTest extends TestCase
{
    public function test_the_first_credit_is_added_on_the_day_the_policy_is_assigned(): void
    {
        $this->assertSame('2026-09-25', PeopleLeaveAccrual::firstDueOn('monthly', Carbon::parse('2026-09-25'))->toDateString());
        $this->assertSame('2026-09-25', PeopleLeaveAccrual::firstDueOn('yearly', Carbon::parse('2026-09-25'))->toDateString());
    }

    public function test_the_following_credit_is_one_period_later(): void
    {
        $this->assertSame('2026-11-01', PeopleLeaveAccrual::nextDueOn('monthly', Carbon::parse('2026-10-25'))->toDateString());
        $this->assertSame('2026-12-01', PeopleLeaveAccrual::nextDueOn('monthly', Carbon::parse('2026-11-01'))->toDateString());
        $this->assertSame('2027-01-01', PeopleLeaveAccrual::nextDueOn('yearly', Carbon::parse('2026-09-25'))->toDateString());
        $this->assertSame('2027-01-01', PeopleLeaveAccrual::nextDueOn('yearly', Carbon::parse('2026-01-01'))->toDateString());
    }

    public function test_a_yearly_policy_counts_the_months_left_in_the_leave_year(): void
    {
        $this->assertSame(12, PeopleLeaveAccrual::monthsLeftInLeaveYear(Carbon::parse('2026-01-15')));
        $this->assertSame(11, PeopleLeaveAccrual::monthsLeftInLeaveYear(Carbon::parse('2026-01-16')));
        $this->assertSame(6, PeopleLeaveAccrual::monthsLeftInLeaveYear(Carbon::parse('2026-07-10')));
        $this->assertSame(0, PeopleLeaveAccrual::monthsLeftInLeaveYear(Carbon::parse('2026-12-20')));
        $this->assertSame(6.0, PeopleLeaveAccrual::proratedYearDays(12, Carbon::parse('2026-07-10')));
        $this->assertSame(0.0, PeopleLeaveAccrual::proratedYearDays(12, Carbon::parse('2026-12-20')));
    }

    public function test_a_monthly_credit_always_lands_on_the_first(): void
    {
        $due = PeopleLeaveAccrual::nextDueOn('monthly', Carbon::parse('2026-01-31'));

        $this->assertSame('2026-02-01', $due->toDateString());
    }
}
