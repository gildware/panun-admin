<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class PeopleApprovalNotificationTest extends TestCase
{
    private PeopleWorkspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema();
        $this->workspace = app(PeopleWorkspace::class);
    }

    public function test_a_leave_request_notifies_the_manager_and_the_decision_notifies_the_person(): void
    {
        $this->type('Casual', 'casual');
        [$employee, $manager] = $this->pair();
        $this->give($employee, 2026, 'casual', 2);
        $user = User::query()->findOrFail($employee->user_id);
        $actor = User::query()->findOrFail($manager->user_id);

        $leave = $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-02'), Carbon::parse('2026-03-03'), 'Trip', false);

        $managerNotice = $this->notices($manager->user_id, UserNotification::TYPE_LEAVE_REQUEST);
        $this->assertCount(1, $managerNotice);
        $this->assertSame(UserNotification::CATEGORY_INTERNAL, $managerNotice->first()->category);
        $this->assertStringContainsString('Asha', (string) $managerNotice->first()->title);
        $this->assertSame([], $this->notices($employee->user_id, UserNotification::TYPE_LEAVE_REQUEST)->all());

        $this->workspace->decideLeave($actor, $leave->fresh(), 'sent_back', 'Please pick different dates.');

        $personNotice = $this->notices($employee->user_id, UserNotification::TYPE_LEAVE_DECIDED);
        $this->assertCount(1, $personNotice);
        $this->assertStringContainsString('Please pick different dates.', (string) $personNotice->first()->body);
        $this->assertSame([], $this->notices($manager->user_id, UserNotification::TYPE_LEAVE_DECIDED)->all());
    }

    public function test_a_timesheet_notifies_the_manager_and_the_decision_notifies_the_person(): void
    {
        [$employee, $manager] = $this->pair();
        $user = User::query()->findOrFail($employee->user_id);
        $actor = User::query()->findOrFail($manager->user_id);
        $sheet = PeopleTimesheet::query()->create([
            'user_id' => $employee->user_id,
            'week_starts_on' => '2026-03-02',
            'hours' => ['mon' => 8, 'tue' => 8, 'wed' => 8, 'thu' => 8, 'fri' => 8, 'sat' => 0, 'sun' => 0],
            'entries' => [],
            'status' => 'pending',
        ]);

        $this->workspace->notifyTimesheetSubmitted($user, $sheet);

        $this->assertCount(1, $this->notices($manager->user_id, UserNotification::TYPE_TIMESHEET_SUBMITTED));
        $this->assertSame([], $this->notices($employee->user_id, UserNotification::TYPE_TIMESHEET_SUBMITTED)->all());

        $this->workspace->decideTimesheet($actor, $sheet->fresh(), 'approve');

        $personNotice = $this->notices($employee->user_id, UserNotification::TYPE_TIMESHEET_DECIDED);
        $this->assertCount(1, $personNotice);
        $this->assertStringContainsString('Approved', (string) $personNotice->first()->title);
        $this->assertSame([], $this->notices($manager->user_id, UserNotification::TYPE_TIMESHEET_DECIDED)->all());
    }

    public function test_hr_is_notified_when_the_person_has_no_manager(): void
    {
        $this->type('Casual', 'casual');
        $employee = $this->person('Asha', 'admin-employee');
        $hr = $this->person('Neelam', 'admin-employee');
        $this->assignHr($hr);
        $this->give($employee, 2026, 'casual', 1);
        $user = User::query()->findOrFail($employee->user_id);

        $this->workspace->submitLeave($user, 'casual', Carbon::parse('2026-03-02'), Carbon::parse('2026-03-02'), 'Clinic', false);

        $this->assertCount(1, $this->notices($hr->user_id, UserNotification::TYPE_LEAVE_REQUEST));
        $this->assertSame([], $this->notices($employee->user_id, UserNotification::TYPE_LEAVE_REQUEST)->all());
    }

    public function test_a_waiting_request_is_notified_once(): void
    {
        [$employee, $manager] = $this->pair();
        PeopleLeaveRequest::query()->create([
            'user_id' => $employee->user_id,
            'leave_type' => 'casual',
            'starts_on' => '2026-03-04',
            'ends_on' => '2026-03-04',
            'days' => 1,
            'year_split' => [2026 => 1],
            'reason' => 'Already waiting',
            'status' => 'pending',
        ]);
        PeopleTimesheet::query()->create([
            'user_id' => $employee->user_id,
            'week_starts_on' => '2026-03-09',
            'hours' => ['mon' => 8],
            'status' => 'pending',
        ]);

        $this->workspace->notifyOutstandingApprovals();
        $this->workspace->notifyOutstandingApprovals();

        $this->assertCount(1, $this->notices($manager->user_id, UserNotification::TYPE_LEAVE_REQUEST));
        $this->assertCount(1, $this->notices($manager->user_id, UserNotification::TYPE_TIMESHEET_SUBMITTED));
    }

    public function test_a_document_notifies_hr_and_the_decision_notifies_the_person(): void
    {
        $employee = $this->person('Asha', 'admin-employee');
        $hr = $this->person('Neelam', 'admin-employee');
        $this->assignHr($hr);
        $user = User::query()->findOrFail($employee->user_id);
        $document = PeopleDocument::query()->create([
            'user_id' => $employee->user_id,
            'title' => 'Aadhaar',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->workspace->notifyDocumentSubmitted($user, $document);
        $this->assertCount(1, $this->notices($hr->user_id, UserNotification::TYPE_DOCUMENT_SUBMITTED));

        $document->forceFill(['status' => 'rejected', 'rejection_note' => 'The photo is unclear.'])->save();
        $this->workspace->notifyDocumentDecided($document);

        $personNotice = $this->notices($employee->user_id, UserNotification::TYPE_DOCUMENT_DECIDED);
        $this->assertCount(1, $personNotice);
        $this->assertStringContainsString('The photo is unclear.', (string) $personNotice->first()->body);
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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role_name')->nullable();
            $table->timestamps();
        });
        Schema::create('employee_role_sections', function (Blueprint $table) {
            $table->uuid('employee_id');
            $table->uuid('role_id');
        });
        Schema::create('people_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('employee_code')->nullable();
            $table->uuid('manager_id')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('employment_stage')->default('permanent');
            $table->string('work_schedule', 20)->default('full_time');
            $table->decimal('min_hours_override', 4, 1)->nullable();
            $table->json('week_off_override')->nullable();
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
        Schema::create('people_leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedSmallInteger('year');
            $table->decimal('casual_allowance', 6, 1)->default(0);
            $table->decimal('casual_used', 6, 1)->default(0);
            $table->decimal('casual_pending', 6, 1)->default(0);
            $table->decimal('sick_allowance', 6, 1)->default(0);
            $table->decimal('sick_used', 6, 1)->default(0);
            $table->decimal('earned_allowance', 6, 1)->default(0);
            $table->decimal('earned_used', 6, 1)->default(0);
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'year']);
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
            $table->json('hours')->nullable();
            $table->json('entries')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('draft');
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });
        Schema::create('people_timesheet_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('min_hours', 4, 1)->default(0);
            $table->decimal('min_hours_full_time', 4, 1)->default(0);
            $table->decimal('min_hours_part_time', 4, 1)->default(0);
            $table->json('week_off')->nullable();
            $table->date('starts_on')->nullable();
            $table->timestamps();
        });
        Schema::create('people_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('status')->default('missing');
            $table->timestamp('uploaded_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();
        });
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type', 64);
            $table->string('category', 16)->default('external');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        DB::table('people_timesheet_settings')->insert([
            'id' => (string) Str::uuid(),
            'min_hours' => 0,
            'min_hours_full_time' => 0,
            'min_hours_part_time' => 0,
            'week_off' => json_encode(['sun']),
            'starts_on' => '2026-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0: PeopleProfile, 1: PeopleProfile}
     */
    private function pair(): array
    {
        $manager = $this->person('Rafi', 'admin-employee');
        $employee = $this->person('Asha', 'admin-employee');
        $employee->manager_id = $manager->user_id;
        $employee->save();

        return [$employee, $manager];
    }

    private function person(string $firstName, string $userType): PeopleProfile
    {
        $id = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => 'Koul',
            'email' => $id.'@example.test',
            'password' => 'secret',
            'user_type' => $userType,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PeopleProfile::query()->create([
            'user_id' => $id,
            'employee_code' => 'PK'.random_int(100, 999),
            'employment_status' => 'active',
            'employment_stage' => 'permanent',
        ]);
    }

    private function assignHr(PeopleProfile $person): void
    {
        $roleId = (string) Str::uuid();
        DB::table('roles')->insert([
            'id' => $roleId,
            'role_name' => 'HR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('employee_role_sections')->insert([
            'employee_id' => $person->user_id,
            'role_id' => $roleId,
        ]);
    }

    private function type(string $name, string $code): PeopleLeaveType
    {
        return PeopleLeaveType::query()->create([
            'name' => $name,
            'short_name' => strtoupper(substr($code, 0, 2)),
            'code' => $code,
            'tracks_balance' => true,
            'allows_future' => true,
            'sort' => 1,
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

    /**
     * @return \Illuminate\Support\Collection<int, UserNotification>
     */
    private function notices(string $userId, string $type)
    {
        return UserNotification::query()->where('user_id', $userId)->where('type', $type)->get();
    }
}
