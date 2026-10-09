<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveGrant;
use Modules\AdminModule\Entities\PeopleLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleStageLeavePolicy;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Services\PeopleLeaveAccrual;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class PeopleLeaveSystemTest extends TestCase
{
    private PeopleLeaveAccrual $accrual;

    private PeopleWorkspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema();
        $this->accrual = app(PeopleLeaveAccrual::class);
        $this->workspace = app(PeopleWorkspace::class);
    }

    public function test_probation_and_permanent_use_different_policies(): void
    {
        $type = $this->type('Casual', 'casual');
        $probationPolicy = $this->policy($type, 'Probation casual', 'monthly', 1);
        $permanentPolicy = $this->policy($type, 'Permanent casual', 'monthly', 2);
        $probation = $this->person('probation');
        $permanent = $this->person('permanent');

        $this->accrual->attachStage('probation', $probationPolicy, Carbon::parse('2026-03-01'));
        $this->accrual->attachStage('permanent', $permanentPolicy, Carbon::parse('2026-03-01'));

        $this->assertSame(1.0, $this->allowance($probation, 'casual'));
        $this->assertSame(2.0, $this->allowance($permanent, 'casual'));
        $this->assertSame((string) $probationPolicy->id, $this->assignmentPolicyId($probation));
        $this->assertSame((string) $permanentPolicy->id, $this->assignmentPolicyId($permanent));
    }

    public function test_moving_to_permanent_switches_the_policy_and_keeps_a_direct_assignment(): void
    {
        $type = $this->type('Casual', 'casual');
        $probationPolicy = $this->policy($type, 'Probation casual', 'monthly', 1);
        $permanentPolicy = $this->policy($type, 'Permanent casual', 'monthly', 2);
        $personal = $this->policy($type, 'Personal casual', 'monthly', 3);
        $person = $this->person('probation');
        $kept = $this->person('probation');

        $this->accrual->attachStage('probation', $probationPolicy, Carbon::parse('2026-03-01'));
        $this->accrual->attachStage('permanent', $permanentPolicy, Carbon::parse('2026-03-01'));
        $this->assertTrue($this->accrual->assign($kept, $personal, Carbon::parse('2026-03-02')));

        $person->employment_stage = 'permanent';
        $person->save();
        $this->accrual->syncPersonStage($person->fresh(), 'probation', 'permanent');

        $this->assertSame((string) $permanentPolicy->id, $this->assignmentPolicyId($person));
        $this->assertSame((string) $personal->id, $this->assignmentPolicyId($kept));
        $this->assertFalse($this->accrual->assign($kept->fresh(), $permanentPolicy, Carbon::parse('2026-03-03'), 'stage'));
        $this->assertSame((string) $personal->id, $this->assignmentPolicyId($kept));
    }

    public function test_an_employee_type_policy_replaces_the_department_policy(): void
    {
        $type = $this->type('Sick', 'sick');
        $departmentPolicy = $this->policy($type, 'Department sick', 'monthly', 1);
        $stagePolicy = $this->policy($type, 'Permanent sick', 'monthly', 2);
        $department = PeopleDepartment::query()->create(['name' => 'Care']);
        $person = $this->person('permanent', 'Care');

        $this->accrual->attachDepartment($department, $departmentPolicy, Carbon::parse('2026-04-01'));
        $this->assertSame((string) $departmentPolicy->id, $this->assignmentPolicyId($person));

        $this->accrual->attachStage('permanent', $stagePolicy, Carbon::parse('2026-04-02'));

        $this->assertSame((string) $stagePolicy->id, $this->assignmentPolicyId($person));
        $this->assertTrue((bool) $person->fresh()->user);
    }

    public function test_a_second_request_cannot_use_days_already_waiting(): void
    {
        $type = $this->type('Casual', 'casual');
        $person = $this->person('permanent');
        $this->give($person, 2026, 'casual', 1);
        $user = User::query()->findOrFail($person->user_id);

        $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-02'), Carbon::parse('2026-03-02'), 'Clinic', false);

        $this->expectException(\InvalidArgumentException::class);
        $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-03'), Carbon::parse('2026-03-03'), 'Again', false);
    }

    public function test_approval_uses_the_reserved_days_and_cancel_puts_them_back(): void
    {
        $type = $this->type('Casual', 'casual');
        $person = $this->person('permanent');
        $manager = $this->person('permanent');
        $person->manager_id = $manager->user_id;
        $person->save();
        $this->give($person, 2026, 'casual', 2);
        $user = User::query()->findOrFail($person->user_id);
        $actor = User::query()->findOrFail($manager->user_id);

        $request = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-02'), Carbon::parse('2026-03-03'), 'Trip', false);
        $balance = $this->balance($person, 2026);
        $this->assertSame(2.0, $balance->pending('casual'));
        $this->assertSame(2.0, $balance->taken('casual'));
        $this->assertSame(0.0, $balance->remaining('casual'));

        $this->workspace->decideLeave($actor, $request->fresh(), 'approve');
        $balance = $this->balance($person, 2026);
        $this->assertSame(0.0, $balance->pending('casual'));
        $this->assertSame(2.0, $balance->used('casual'));
        $this->assertSame(2.0, $balance->taken('casual'));

        $this->workspace->cancelLeave($actor, $request->fresh(), true);
        $balance = $this->balance($person, 2026);
        $this->assertSame(0.0, $balance->used('casual'));
        $this->assertSame(0.0, $balance->taken('casual'));
        $this->assertSame(2.0, $balance->remaining('casual'));
    }

    public function test_applied_leave_shows_on_the_timesheet_and_a_half_day_uses_half_hours(): void
    {
        $this->type('Casual', 'casual');
        $person = $this->person('permanent');
        $this->give($person, 2026, 'casual', 5);
        $user = User::query()->findOrFail($person->user_id);

        $full = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-02'), Carbon::parse('2026-03-02'), 'Full day', false);
        $half = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-03'), Carbon::parse('2026-03-03'), 'Half day', true);

        $sheet = PeopleTimesheet::query()->where('user_id', $user->id)->whereDate('week_starts_on', '2026-03-02')->firstOrFail();
        $fullDay = $sheet->entries['2026-03-02'];
        $halfDay = $sheet->entries['2026-03-03'];

        $this->assertSame('submitted', $fullDay['status']);
        $this->assertSame((string) $full->id, $fullDay['leave_request_id']);
        $this->assertFalse($fullDay['half']);
        $this->assertSame('On Leave (Casual)', $fullDay['rows'][0]['task']);
        $this->assertSame('Full day', $fullDay['rows'][0]['description']);
        $this->assertEquals(8.0, $fullDay['rows'][0]['hours']);
        $this->assertEquals(8.0, $sheet->hours['mon']);

        $this->assertTrue($halfDay['half']);
        $this->assertSame('On Leave (Casual)', $halfDay['rows'][0]['task']);
        $this->assertSame('Half day', $halfDay['rows'][0]['description']);
        $this->assertEquals(4.0, $halfDay['rows'][0]['hours']);
        $this->assertEquals(4.0, $sheet->hours['tue']);

        $entries = $sheet->entries;
        $entries['2026-03-03']['rows'][] = [
            'ticket_id' => 'calls',
            'task' => 'Calls',
            'description' => 'Worked the other half',
            'deadline' => null,
            'hours' => 4,
        ];
        $sheet->entries = $entries;
        $sheet->save();
        $this->workspace->mirrorOpenLeave($user);
        $sheet = $sheet->fresh();
        $kept = $sheet->entries['2026-03-03'];
        $this->assertCount(2, $kept['rows']);
        $this->assertSame('calls', $kept['rows'][1]['ticket_id']);
        $this->assertEquals(8.0, $sheet->hours['tue']);

        $this->assertEquals(4.0, (float) $half->hours);

        $timed = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-06'), Carbon::parse('2026-03-06'), 'Morning', true, null, '09:00', '13:00');
        $sheet = $sheet->fresh();
        $this->assertSame('09:00', $timed->from_time);
        $this->assertSame('13:00', $timed->to_time);
        $this->assertEquals(4.0, (float) $timed->hours);
        $this->assertSame('09:00', $sheet->entries['2026-03-06']['rows'][0]['from_time']);
        $this->assertSame('13:00', $sheet->entries['2026-03-06']['rows'][0]['to_time']);

        $short = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-04'), Carbon::parse('2026-03-04'), 'Two hours', true, 2);
        $long = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-05'), Carbon::parse('2026-03-05'), 'Six hours', true, 6);
        $sheet = $sheet->fresh();
        $this->assertEquals(2.0, (float) $short->hours);
        $this->assertEquals(0.3, (float) $short->days);
        $this->assertEquals(2.0, $sheet->entries['2026-03-04']['rows'][0]['hours']);
        $this->assertEquals(6.0, (float) $long->hours);
        $this->assertEquals(0.8, (float) $long->days);
        $this->assertEquals(6.0, $sheet->entries['2026-03-05']['rows'][0]['hours']);

        $this->workspace->syncPartialLeaveHours($user, $short->fresh(), 6);
        $short = $short->fresh();
        $sheet = $sheet->fresh();
        $this->assertEquals(6.0, (float) $short->hours);
        $this->assertEquals(0.8, (float) $short->days);
        $this->workspace->mirrorOpenLeave($user);
        $sheet = $sheet->fresh();
        $this->assertEquals(6.0, $sheet->entries['2026-03-04']['rows'][0]['hours']);

        $this->workspace->cancelLeave($user, $half->fresh());
        $sheet = $sheet->fresh();
        $this->assertArrayNotHasKey('2026-03-03', $sheet->entries);
        $this->assertEquals(0.0, $sheet->hours['tue']);
        $this->assertSame((string) $full->id, $sheet->entries['2026-03-02']['leave_request_id']);
    }

    public function test_rejecting_leave_stores_the_reason(): void
    {
        Carbon::setTestNow('2026-03-01 09:00:00');
        $this->type('Casual', 'casual');
        $person = $this->person('permanent');
        $manager = $this->person('permanent');
        $person->manager_id = $manager->user_id;
        $person->save();
        $this->give($person, 2026, 'casual', 2);
        $user = User::query()->findOrFail($person->user_id);
        $actor = User::query()->findOrFail($manager->user_id);

        $request = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-02'), Carbon::parse('2026-03-03'), 'Trip', false);
        Carbon::setTestNow('2026-03-01 10:00:00');
        $this->workspace->decideLeave($actor, $request->fresh(), 'sent_back', 'Team is short that week');

        $request->refresh();
        $this->assertSame('sent_back', $request->status);
        $this->assertSame('Team is short that week', $request->decision_note);
        $this->assertSame(2.0, $this->balance($person, 2026)->remaining('casual'));

        $history = $this->workspace->leaveHistory($person->user_id);
        $this->assertSame('Team is short that week', $history[0]['why']);
        $this->assertSame('Sent back by Asha Koul', $history[0]['how']);
    }

    public function test_a_leave_type_can_refuse_future_dates(): void
    {
        Carbon::setTestNow('2026-10-08 09:00:00');
        $sick = $this->type('Sick', 'sick');
        $sick->allows_future = false;
        $sick->save();
        $earned = $this->type('Earned', 'earned');
        $person = $this->person('permanent');
        $this->give($person, 2026, 'sick', 2);
        $this->give($person, 2026, 'earned', 2);
        $user = User::query()->findOrFail($person->user_id);

        $this->workspace->submitLeave($user, 'sick', Carbon::parse('2026-10-08'), Carbon::parse('2026-10-08'), 'Today', false);
        $this->workspace->submitLeave($user, 'earned', Carbon::parse('2026-10-12'), Carbon::parse('2026-10-12'), 'Ahead', false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sick cannot be applied for a future date.');
        $this->workspace->submitLeave($user, 'sick', Carbon::parse('2026-10-09'), Carbon::parse('2026-10-09'), 'Tomorrow', false);
    }

    public function test_a_manual_adjustment_adds_days_and_removes_only_what_is_left(): void
    {
        Carbon::setTestNow('2026-10-08');
        $this->type('Casual', 'casual');
        $this->type('Comp off', 'comp_off');
        $person = $this->person('permanent');
        $this->give($person, 2026, 'casual', 5);
        $balance = $this->balance($person, 2026);
        $balance->addUsed('casual', 1);
        $balance->addPending('casual', 1);
        $balance->save();

        $this->accrual->adjust($person->user_id, 'casual', 2, 'add', $person->user_id, 'Extra');
        $balance = $this->balance($person, 2026);
        $this->assertSame(7.0, $balance->allowance('casual'));
        $this->assertSame(5.0, $balance->remaining('casual'));
        $this->assertSame(1.0, $balance->used('casual'));
        $this->assertSame(1.0, $balance->pending('casual'));

        $this->accrual->adjust($person->user_id, 'casual', 2, 'remove', $person->user_id, 'Correction');
        $balance = $this->balance($person, 2026);
        $this->assertSame(5.0, $balance->allowance('casual'));
        $this->assertSame(3.0, $balance->remaining('casual'));
        $this->assertSame(1.0, $balance->used('casual'));

        $this->accrual->adjust($person->user_id, 'comp_off', 1.5, 'add', null, null);
        $this->accrual->adjust($person->user_id, 'comp_off', 0.5, 'remove', null, null);
        $this->assertSame(1.0, $this->balance($person, 2026)->remaining('comp_off'));

        $grants = PeopleLeaveGrant::query()->where('user_id', $person->user_id)->orderBy('days')->pluck('days')->map(fn ($days) => (float) $days)->all();
        $this->assertEquals([-2.0, -0.5, 1.5, 2.0], $grants);

        try {
            $this->accrual->adjust($person->user_id, 'casual', 4, 'remove', $person->user_id, null);
            $this->fail('Removing more than the days left should be refused.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(3.0, $this->balance($person, 2026)->remaining('casual'));
        }

        Carbon::setTestNow();
    }

    public function test_leave_history_shows_when_leave_was_added_taken_and_put_back(): void
    {
        Carbon::setTestNow('2026-10-08 09:00:00');
        $this->type('Sick', 'sick');
        $person = $this->person('permanent');
        $manager = $this->person('permanent');
        $person->manager_id = $manager->user_id;
        $person->save();
        $this->accrual->adjust($person->user_id, 'sick', 3, 'add', $manager->user_id, 'Opening balance');

        Carbon::setTestNow('2026-10-08 10:00:00');
        PeopleLeaveGrant::query()->create([
            'user_id' => $person->user_id,
            'year' => 2026,
            'leave_type' => 'sick',
            'days' => 1,
            'source' => 'monthly',
            'period' => '2026-10',
            'note' => 'Care sick',
        ]);

        Carbon::setTestNow('2026-10-08 11:00:00');
        $user = User::query()->findOrFail($person->user_id);
        $request = $this->workspace->submitLeave($user, 'sick', Carbon::parse('2026-10-09'), Carbon::parse('2026-10-09'), 'Clinic', false);
        Carbon::setTestNow('2026-10-08 15:00:00');
        $this->workspace->decideLeave(User::query()->findOrFail($manager->user_id), $request->fresh(), 'approve');

        $history = $this->workspace->leaveHistory($person->user_id);
        $this->assertSame([
            '1 day taken for 9 Oct 2026',
            '−1 day held for 9 Oct 2026',
            '+1 day added',
            '+3 days added',
        ], $history->pluck('what')->all());
        $this->assertSame('Approved by Asha Koul', $history[0]['how']);
        $this->assertSame('Clinic', $history[0]['why']);
        $this->assertSame('Sick', $history[0]['leave']);
        $this->assertSame('Monthly credit', $history[2]['how']);
        $this->assertSame('Care sick, For October 2026', $history[2]['why']);
        $this->assertSame('Adjusted by hand by Asha Koul', $history[3]['how']);
        $this->assertSame('Opening balance', $history[3]['why']);

        Carbon::setTestNow();
    }

    public function test_a_request_across_the_new_year_uses_both_balances(): void
    {
        $this->type('Casual', 'casual');
        $person = $this->person('permanent');
        $this->give($person, 2026, 'casual', 1);
        $this->give($person, 2027, 'casual', 1);
        $user = User::query()->findOrFail($person->user_id);

        $request = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-12-31'), Carbon::parse('2027-01-01'), 'Year end', false);

        $this->assertSame(1.0, $this->balance($person, 2026)->pending('casual'));
        $this->assertSame(1.0, $this->balance($person, 2027)->pending('casual'));
        $this->assertEquals([2026 => 1.0, 2027 => 1.0], $request->daysByYear());
    }

    public function test_a_yearly_policy_joined_in_july_credits_half_the_year(): void
    {
        $type = $this->type('Earned', 'earned');
        $policy = $this->policy($type, 'Earned year', 'yearly', 12);
        $person = $this->person('permanent');

        $this->accrual->assign($person, $policy, Carbon::parse('2026-07-10'));

        $this->assertSame(6.0, $this->allowance($person, 'earned'));
        $assignment = \Modules\AdminModule\Entities\PeopleLeaveAssignment::query()->where('user_id', $person->user_id)->first();
        $this->assertSame('2027-01-01', $assignment->next_accrual_on->toDateString());
    }

    public function test_unused_days_carry_once_up_to_the_limit(): void
    {
        $type = $this->type('Earned', 'earned');
        $policy = $this->policy($type, 'Earned year', 'yearly', 12, 5);
        $person = $this->person('permanent');
        $this->accrual->assign($person, $policy, Carbon::parse('2026-01-01'));
        $balance = $this->balance($person, 2026);
        $balance->casual_allowance = 0;
        $balance->addUsed('earned', 7);
        $balance->save();

        $this->assertSame(1, $this->accrual->carryForward(Carbon::parse('2027-01-02'), $person->user_id));
        $this->assertSame(5.0, $this->allowance($person, 'earned', 2027));
        $this->assertSame(0, $this->accrual->carryForward(Carbon::parse('2027-01-03'), $person->user_id));
        $this->assertSame(5.0, $this->allowance($person, 'earned', 2027));
    }

    public function test_leave_calendar_marks_leave_attendance_holiday_and_week_off(): void
    {
        $person = $this->person('permanent');
        $this->type('Casual', 'casual');
        PeopleLeaveRequest::query()->create([
            'user_id' => $person->user_id,
            'leave_type' => 'casual',
            'starts_on' => '2026-10-05',
            'ends_on' => '2026-10-05',
            'days' => 1,
            'reason' => 'Family',
            'status' => 'approved',
            'decided_at' => now(),
        ]);
        PeopleHoliday::query()->create([
            'holiday_on' => '2026-10-02',
            'name' => 'Gandhi Jayanti',
        ]);
        PeopleTimesheet::query()->create([
            'user_id' => $person->user_id,
            'week_starts_on' => '2026-10-05',
            'hours' => ['mon' => 0, 'tue' => 8, 'wed' => 0, 'thu' => 0, 'fri' => 0, 'sat' => 0],
            'entries' => [
                '2026-10-06' => [
                    'status' => 'submitted',
                    'rows' => [['ticket_id' => 'task', 'hours' => 8]],
                ],
            ],
            'status' => 'draft',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-10-08 09:00:00'));
        try {
            $calendar = $this->workspace->leaveCalendar($person->user_id);
        } finally {
            Carbon::setTestNow();
        }

        $days = [];
        foreach ($calendar['months'] as $month) {
            if ($month['key'] !== '2026-10') {
                continue;
            }
            foreach ($month['weeks'] as $week) {
                foreach ($week as $day) {
                    if ($day['date'] !== '') {
                        $days[$day['date']] = $day;
                    }
                }
            }
        }

        $visible = array_column(array_values(array_filter($calendar['months'], fn (array $month) => $month['visible'])), 'key');
        $this->assertSame(['2026-08', '2026-09', '2026-10'], $visible);
        $this->assertSame('leave', $days['2026-10-05']['kind']);
        $this->assertSame('CA', $days['2026-10-05']['code']);
        $this->assertSame(5, $days['2026-10-05']['day']);
        $this->assertSame('present', $days['2026-10-06']['kind']);
        $this->assertSame('off', $days['2026-10-04']['kind']);
        $this->assertSame('off', $days['2026-10-02']['kind']);
        $this->assertSame('Gandhi Jayanti', Str::after($days['2026-10-02']['title'], ' · '));
        $this->assertSame('absent', $days['2026-10-07']['kind']);
        $this->assertSame('today', $days['2026-10-08']['kind']);
    }

    public function test_a_leave_type_without_a_balance_counts_as_unpaid(): void
    {
        $this->type('Unpaid', 'unpaid', false);
        $this->type('Casual', 'casual', true);

        $this->assertSame(['unpaid'], $this->workspace->unpaidLeaveTypeCodes());
    }

    private function schema(): void
    {
        $default = (string) config('database.default', '');
        $connection = config('database.connections.'.$default, []);
        $driver = (string) ($connection['driver'] ?? '');
        $database = (string) ($connection['database'] ?? '');
        if ($driver !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException('Refusing to rebuild tables on '.$driver.' database '.$database.'.');
        }

        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('user_type')->nullable();
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('people_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('employee_code')->nullable();
            $table->string('department')->nullable();
            $table->uuid('manager_id')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('employment_stage')->default('permanent');
            $table->string('work_schedule', 20)->default('full_time');
            $table->decimal('min_hours_override', 4, 1)->nullable();
            $table->json('week_off_override')->nullable();
            $table->timestamps();
        });
        Schema::create('people_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('people_leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('code')->unique();
            $table->boolean('tracks_balance')->default(true);
            $table->boolean('allows_future')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::create('people_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('leave_type_id');
            $table->string('accrual_type');
            $table->decimal('days', 6, 1)->default(0);
            $table->decimal('carry_limit', 6, 1)->default(0);
            $table->timestamps();
        });
        Schema::create('people_leave_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('leave_policy_id');
            $table->date('next_accrual_on')->nullable();
            $table->boolean('via_employee')->default(false);
            $table->boolean('via_department')->default(false);
            $table->boolean('via_stage')->default(false);
            $table->timestamps();
        });
        Schema::create('people_department_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id');
            $table->uuid('leave_policy_id');
            $table->timestamps();
        });
        Schema::create('people_stage_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employment_stage');
            $table->uuid('leave_policy_id');
            $table->timestamps();
        });
        Schema::create('people_leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedSmallInteger('year');
            $table->decimal('casual_allowance', 6, 1)->default(0);
            $table->decimal('casual_used', 6, 1)->default(0);
            $table->decimal('sick_allowance', 6, 1)->default(0);
            $table->decimal('sick_used', 6, 1)->default(0);
            $table->decimal('earned_allowance', 6, 1)->default(0);
            $table->decimal('earned_used', 6, 1)->default(0);
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'year']);
        });
        Schema::create('people_leave_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedSmallInteger('year');
            $table->string('leave_type');
            $table->decimal('days', 6, 1);
            $table->string('source');
            $table->string('period');
            $table->string('note')->nullable();
            $table->uuid('granted_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'leave_type', 'source', 'period']);
        });
        Schema::create('people_leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('leave_type');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('days', 6, 1);
            $table->decimal('hours', 4, 1)->nullable();
            $table->string('from_time', 5)->nullable();
            $table->string('to_time', 5)->nullable();
            $table->json('year_split')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });
        Schema::create('people_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('holiday_on');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('people_timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->date('week_starts_on');
            $table->json('hours');
            $table->json('entries')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('draft');
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
        Schema::create('people_timesheet_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('min_hours', 4, 1)->default(0);
            $table->decimal('min_hours_full_time', 4, 1)->default(0);
            $table->decimal('min_hours_part_time', 4, 1)->default(0);
            $table->json('week_off');
            $table->timestamps();
        });
        DB::table('people_timesheet_settings')->insert([
            'id' => (string) Str::uuid(),
            'min_hours' => 0,
            'week_off' => json_encode(['sun']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function type(string $name, string $code, bool $tracks = true): PeopleLeaveType
    {
        return PeopleLeaveType::query()->create([
            'name' => $name,
            'short_name' => strtoupper(substr($code, 0, 2)),
            'code' => $code,
            'tracks_balance' => $tracks,
            'sort' => 1,
        ]);
    }

    private function policy(PeopleLeaveType $type, string $name, string $accrual, float $days, float $carry = 0): PeopleLeavePolicy
    {
        return PeopleLeavePolicy::query()->create([
            'name' => $name,
            'leave_type_id' => $type->id,
            'accrual_type' => $accrual,
            'days' => $days,
            'carry_limit' => $carry,
        ]);
    }

    private function person(string $stage, string $department = ''): PeopleProfile
    {
        $id = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $id,
            'first_name' => 'Asha',
            'last_name' => 'Koul',
            'email' => $id.'@example.test',
            'password' => 'secret',
            'user_type' => 'super-admin',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PeopleProfile::query()->create([
            'user_id' => $id,
            'employee_code' => 'PK'.random_int(100, 999),
            'department' => $department,
            'employment_status' => 'active',
            'employment_stage' => $stage,
        ]);
    }

    private function give(PeopleProfile $person, int $year, string $type, float $days): void
    {
        $balance = PeopleLeaveBalance::query()->firstOrCreate(
            ['user_id' => $person->user_id, 'year' => $year],
            ['casual_allowance' => 0, 'sick_allowance' => 0, 'earned_allowance' => 0]
        );
        $balance->addAllowance($type, $days);
        $balance->save();
    }

    private function allowance(PeopleProfile $person, string $type, int $year = 2026): float
    {
        return $this->balance($person, $year)->allowance($type);
    }

    private function balance(PeopleProfile $person, int $year): PeopleLeaveBalance
    {
        return PeopleLeaveBalance::query()->where('user_id', $person->user_id)->where('year', $year)->firstOrFail();
    }

    private function assignmentPolicyId(PeopleProfile $person): ?string
    {
        return \Modules\AdminModule\Entities\PeopleLeaveAssignment::query()->where('user_id', $person->user_id)->value('leave_policy_id');
    }
}
