<?php

namespace Tests\Unit;

use Modules\AdminModule\Services\PeopleWorkspace;
use PHPUnit\Framework\TestCase;

class PeopleApprovalTest extends TestCase
{
    public function test_the_direct_manager_decides_leave_and_timesheets(): void
    {
        $this->assertTrue(PeopleWorkspace::mayDecide('manager', 'employee', 'manager', false));
        $this->assertFalse(PeopleWorkspace::mayDecide('hr', 'employee', 'manager', true));
    }

    public function test_hr_decides_only_when_no_manager_is_set(): void
    {
        $this->assertTrue(PeopleWorkspace::mayDecide('hr', 'employee', null, true));
        $this->assertFalse(PeopleWorkspace::mayDecide('colleague', 'employee', null, false));
    }

    public function test_a_person_cannot_decide_their_own_request(): void
    {
        $this->assertFalse(PeopleWorkspace::mayDecide('hr', 'hr', null, true));
        $this->assertFalse(PeopleWorkspace::mayDecide('manager', 'manager', 'manager', false));
    }

    public function test_time_text_formats_stored_clock_values(): void
    {
        $this->assertSame('9:00 AM', PeopleWorkspace::timeText('09:00'));
        $this->assertSame('12:30 PM', PeopleWorkspace::timeText('12:30:00'));
        $this->assertSame('5:05 PM', PeopleWorkspace::timeText('17:05'));
        $this->assertSame('', PeopleWorkspace::timeText(null));
        $this->assertSame('', PeopleWorkspace::timeText('bad'));
    }
}
