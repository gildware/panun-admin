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
        $this->assertSame('2026-11-25', PeopleLeaveAccrual::nextDueOn('monthly', Carbon::parse('2026-10-25'))->toDateString());
        $this->assertSame('2027-09-25', PeopleLeaveAccrual::nextDueOn('yearly', Carbon::parse('2026-09-25'))->toDateString());
    }

    public function test_a_month_end_date_does_not_skip_february(): void
    {
        $due = PeopleLeaveAccrual::nextDueOn('monthly', Carbon::parse('2026-01-31'));

        $this->assertSame('2026-02-28', $due->toDateString());
    }
}
