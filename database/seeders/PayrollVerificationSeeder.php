<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Entities\PeopleAttendanceMark;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleLeaveAssignment;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleSalaryStructure;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Services\PeoplePayroll;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;

/**
 * October 2026 records for checking people, leave, timesheets, attendance, and payroll.
 * Safe to run again. It replaces October leave, timesheets, attendance, and draft pay.
 */
class PayrollVerificationSeeder extends Seeder
{
    private const MONTH = '2026-10';

    private const TODAY = '2026-10-09';

    public function run(): void
    {
        $workspace = app(PeopleWorkspace::class);
        $settings = $workspace->timesheetSettings();
        $settings->week_off = ['sun'];
        $settings->save();

        foreach (['Operations', 'Customer support', 'Accounts', 'Human resources', 'Field service'] as $name) {
            PeopleDepartment::query()->firstOrCreate(['name' => $name]);
        }

        $roleId = (string) Role::query()->where('role_name', 'Employee')->value('id');
        $kamran = User::query()->where('email', 'admin@panunkaergar.com')->firstOrFail();
        $people = [];
        foreach ($this->roster() as $email => $row) {
            $people[$email] = $this->preparePerson($workspace, $kamran, $roleId, $email, $row);
        }

        $ids = array_map(fn (User $user) => $user->id, $people);
        PeopleLeaveRequest::query()->whereIn('user_id', $ids)->whereDate('starts_on', '>=', '2026-09-01')->whereDate('starts_on', '<=', '2026-10-31')->delete();
        PeopleTimesheet::query()->whereIn('user_id', $ids)->whereDate('week_starts_on', '>=', '2026-09-28')->whereDate('week_starts_on', '<=', '2026-10-26')->delete();
        PeopleAttendanceMark::query()->whereIn('user_id', $ids)->whereDate('marked_on', '>=', '2026-10-01')->whereDate('marked_on', '<=', '2026-10-31')->delete();
        PeoplePayslip::query()->whereIn('user_id', $ids)->where('period', self::MONTH)->where('status', '!=', 'published')->delete();

        foreach ($people as $user) {
            $this->resetBalance($user);
        }

        $this->applyLeave($workspace, $kamran, $people);
        $this->fillTimesheets($people);
        $this->approveTimesheets($workspace, $people);
        $this->markAttendance($people);

        app(PeoplePayroll::class)->buildMonth(self::MONTH, true);
        $this->report($workspace, $people);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function preparePerson(PeopleWorkspace $workspace, User $kamran, string $roleId, string $email, array $row): User
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $user = new User();
            $user->first_name = $row['first_name'];
            $user->last_name = $row['last_name'];
            $user->email = $email;
            $user->phone = $row['phone'];
            $user->profile_image = 'default.png';
            $user->identification_type = 'nid';
            $user->identification_image = [];
            $user->password = bcrypt('Verify@1234');
            $user->user_type = 'admin-employee';
            $user->is_active = 1;
            $user->is_phone_verified = 1;
            $user->is_email_verified = 1;
            $user->save();

            if ($roleId !== '' && ! DB::table('employee_role_sections')->where('employee_id', $user->id)->exists()) {
                DB::table('employee_role_sections')->insert([
                    'employee_id' => $user->id,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $user->gender = $row['gender'];
        $user->date_of_birth = $row['dob'];
        if (! $user->phone) {
            $user->phone = $row['phone'];
        }
        $user->save();

        $profile = $workspace->ensureStaffFile($user);
        $managerId = $email === 'admin@panunkaergar.com' ? null : ($row['manager'] ?? $kamran->id);
        if (($row['manager'] ?? null) === 'demo') {
            $managerId = User::query()->where('email', 'employee.demo@panunkaergar.com')->value('id');
        }
        $profile->forceFill([
            'job_title' => $row['title'],
            'department' => $row['department'],
            'work_location' => $row['location'],
            'billing_type' => $row['billing'],
            'requires_timesheet' => $row['timesheet'],
            'address' => $row['address'],
            'manager_id' => $managerId,
            'joined_on' => $row['joined'],
            'employment_type' => $row['employment_type'],
            'work_schedule' => $row['schedule'],
            'min_hours_override' => null,
            'week_off_override' => null,
            'date_of_birth' => $row['dob'],
            'emergency_contact' => $row['emergency'],
            'bank_name' => $row['bank_name'],
            'bank_account' => $row['bank_account'],
            'bank_ifsc' => $row['bank_ifsc'],
            'pan' => $row['pan'],
            'aadhaar' => $row['aadhaar'],
            'uan' => $row['uan'],
            'esi_number' => $row['esi'],
            'employment_status' => $row['status'],
            'employment_stage' => $row['stage'],
            'last_working_day' => $row['last_day'],
            'leave_next_accrual_on' => '2026-11-01',
        ])->save();

        PeopleSalaryStructure::query()->where('user_id', $user->id)->delete();
        PeopleSalaryStructure::query()->create([
            'user_id' => $user->id,
            'effective_from' => '2026-10-01',
            'basic' => $row['basic'],
            'hra' => 0,
            'special_allowance' => 0,
            'pf_employee' => 0,
            'pf_employer' => 0,
            'professional_tax' => 0,
            'tds' => 0,
            'other_deduction' => 0,
        ]);

        foreach (PeopleWorkspace::DOCUMENT_TITLES as $title) {
            $status = 'verified';
            $note = null;
            if ($email === 'tariq.lone@panunkaergar.com' && $title === 'Address proof') {
                $status = 'rejected';
                $note = 'The address on this file does not match the profile.';
            }
            if ($email === 'nusrat.shah@panunkaergar.com' && $title === 'Cancelled cheque') {
                $status = 'pending';
            }
            PeopleDocument::query()->updateOrCreate(
                ['user_id' => $user->id, 'title' => $title],
                [
                    'status' => $status,
                    'original_name' => str_replace(' ', '-', strtolower($title)).'.pdf',
                    'uploaded_at' => $status === 'pending' ? null : now(),
                    'rejection_note' => $note,
                ]
            );
        }

        $this->resetBalance($user);
        foreach (PeopleLeavePolicy::query()->pluck('id') as $policyId) {
            PeopleLeaveAssignment::query()->firstOrCreate(
                ['user_id' => $user->id, 'leave_policy_id' => $policyId],
                [
                    'next_accrual_on' => '2026-11-01',
                    'via_employee' => true,
                    'via_department' => false,
                    'via_stage' => false,
                ]
            );
        }

        return $user->fresh();
    }

    private function resetBalance(User $user): void
    {
        PeopleLeaveBalance::query()->updateOrCreate(
            ['user_id' => $user->id, 'year' => 2026],
            [
                'casual_allowance' => 8,
                'casual_used' => 0,
                'sick_allowance' => 12,
                'sick_used' => 0,
                'earned_allowance' => 15,
                'earned_used' => 0,
                'extra' => [
                    'sick' => ['allowance' => 12, 'used' => 0, 'pending' => 0],
                    'earned' => ['allowance' => 15, 'used' => 0, 'pending' => 0],
                ],
            ]
        );
    }

    /**
     * @param  array<string, User>  $people
     */
    private function applyLeave(PeopleWorkspace $workspace, User $kamran, array $people): void
    {
        $hr = $people['hr.manager@panunkaergar.com'];
        $demo = $people['employee.demo@panunkaergar.com'];

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $people['mehak.bashir@gildware.com'],
            'sick',
            Carbon::parse('2026-10-06'),
            Carbon::parse('2026-10-06'),
            'Fever. Staying home for the day.'
        ), 'approve');

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $demo,
            'sick',
            Carbon::parse('2026-10-08'),
            Carbon::parse('2026-10-08'),
            'Clinic visit in the morning.',
            true,
            4,
            '09:00',
            '13:00'
        ), 'approve');

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $demo,
            'earned',
            Carbon::parse('2026-10-13'),
            Carbon::parse('2026-10-14'),
            'Family function in Baramulla.'
        ), 'approve');

        $workspace->submitLeave(
            $demo,
            'unpaid',
            Carbon::parse('2026-10-16'),
            Carbon::parse('2026-10-16'),
            'Personal work in the afternoon. Waiting for approval.',
            true,
            4,
            '14:00',
            '18:00'
        );

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $demo,
            'earned',
            Carbon::parse('2026-10-21'),
            Carbon::parse('2026-10-21'),
            'Asked for a day off, then asked to withdraw it.'
        ), 'sent_back', 'Please work this day. Apply again if you still need it.');

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $people['accounts.manager@panunkaergar.com'],
            'unpaid',
            Carbon::parse('2026-10-05'),
            Carbon::parse('2026-10-05'),
            'No paid leave left for this errand.'
        ), 'approve');

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $people['accounts.manager@panunkaergar.com'],
            'unpaid',
            Carbon::parse('2026-10-10'),
            Carbon::parse('2026-10-10'),
            'Half day without pay for a bank visit.',
            true,
            4,
            '10:00',
            '14:00'
        ), 'approve');

        $this->decide($workspace, $kamran, $workspace->submitLeave(
            $people['imran.sofi@panunkaergar.com'],
            'unpaid',
            Carbon::parse('2026-10-07'),
            Carbon::parse('2026-10-07'),
            'Unpaid day during notice.'
        ), 'approve');

        $this->decide($workspace, $hr, $workspace->submitLeave(
            $kamran,
            'earned',
            Carbon::parse('2026-10-15'),
            Carbon::parse('2026-10-15'),
            'Out for half a day.',
            true,
            4,
            '09:00',
            '13:00'
        ), 'approve');

        $this->decide($workspace, $demo, $workspace->submitLeave(
            $people['junior.employee@panunkaergar.com'],
            'sick',
            Carbon::parse('2026-10-08'),
            Carbon::parse('2026-10-08'),
            'Not well. One paid sick day.'
        ), 'approve');

        $workspace->submitLeave(
            $people['sana.farooq@panunkaergar.com'],
            'sick',
            Carbon::parse('2026-10-09'),
            Carbon::parse('2026-10-09'),
            'Sick today. Still waiting on a decision.'
        );

        $cancelled = $workspace->submitLeave(
            $people['mariya.bhat@gildware.com'],
            'unpaid',
            Carbon::parse('2026-10-22'),
            Carbon::parse('2026-10-22'),
            'Applied, then cancelled.'
        );
        $workspace->cancelLeave($people['mariya.bhat@gildware.com'], $cancelled);
    }

    private function decide(PeopleWorkspace $workspace, User $actor, PeopleLeaveRequest $leave, string $decision, ?string $note = null): void
    {
        $leave->load('user');
        $workspace->decideLeave($actor, $leave, $decision, $note);
    }

    /**
     * @param  array<string, User>  $people
     */
    private function fillTimesheets(array $people): void
    {
        $roster = $this->roster();
        foreach ($people as $email => $user) {
            $row = $roster[$email];
            if (! $row['timesheet']) {
                continue;
            }
            $profile = PeopleProfile::query()->where('user_id', $user->id)->first();
            $user->setRelation('peopleProfile', $profile);
            $joined = $profile?->joined_on?->toDateString();
            $left = $profile?->last_working_day?->toDateString();
            foreach ($this->workingDays() as $date) {
                if ($date > now()->toDateString()) {
                    continue;
                }
                if ($joined && $date < $joined) {
                    continue;
                }
                if ($left && $date > $left) {
                    continue;
                }
                if (in_array($date, $row['skip'], true)) {
                    continue;
                }
                $half = $row['half'][$date] ?? null;
                $hours = $half ?? ($row['extra'][$date] ?? $row['hours']);
                $this->writeWork($user, $date, (float) $hours, $half !== null);
            }
        }

        $this->addWorkBeside($people['employee.demo@panunkaergar.com'], '2026-10-08', 4);
    }

    private function writeWork(User $user, string $date, float $hours, bool $half): void
    {
        $day = Carbon::parse($date)->startOfDay();
        $week = $day->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $sheet = $this->sheet($user, $week);
        $entries = $sheet->entries ?? [];
        if (isset($entries[$date])) {
            return;
        }

        $rows = $hours > 8
            ? [$this->taskRow('Meeting', 8), $this->taskRow('Training', round($hours - 8, 1))]
            : [$this->taskRow($half ? 'Meeting' : 'Meeting', $hours)];
        $entry = ['status' => 'submitted', 'rows' => $rows];
        if ($half) {
            $entry['half'] = true;
        }
        $entries[$date] = $entry;
        $sheet->forceFill([
            'entries' => $entries,
            'hours' => $this->hoursFrom($entries),
            'status' => 'draft',
        ])->save();
    }

    private function addWorkBeside(User $user, string $date, float $hours): void
    {
        $week = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();
        $sheet = PeopleTimesheet::query()->where('user_id', $user->id)->whereDate('week_starts_on', $week)->first();
        if (! $sheet) {
            return;
        }
        $entries = $sheet->entries ?? [];
        $entry = $entries[$date] ?? null;
        if (! is_array($entry)) {
            return;
        }
        $entry['rows'][] = $this->taskRow('Meeting', $hours);
        $entries[$date] = $entry;
        $sheet->forceFill([
            'entries' => $entries,
            'hours' => $this->hoursFrom($entries),
        ])->save();
    }

    /**
     * @param  array<string, User>  $people
     */
    private function approveTimesheets(PeopleWorkspace $workspace, array $people): void
    {
        $hr = $people['hr.manager@panunkaergar.com'];
        $tariqId = (string) $people['tariq.lone@panunkaergar.com']->id;
        $sheets = PeopleTimesheet::query()
            ->whereIn('user_id', array_map(fn (User $user) => $user->id, $people))
            ->whereDate('week_starts_on', '>=', '2026-09-28')
            ->get();

        foreach ($sheets as $sheet) {
            $sheet->load('user');
            if ($this->weekStillOpen($sheet)) {
                $sheet->forceFill([
                    'status' => 'draft',
                    'decided_by' => null,
                    'decided_at' => null,
                    'decision_note' => null,
                ])->save();

                continue;
            }
            if ((string) $sheet->user_id === $tariqId && $sheet->week_starts_on->toDateString() === '2026-09-28') {
                $workspace->decideTimesheet($hr, $sheet, 'sent_back', 'The hours on this week do not match the work done.');

                continue;
            }
            if ($sheet->status === 'approved') {
                continue;
            }
            $workspace->decideTimesheet($hr, $sheet, 'approve');
        }
    }

    /**
     * @param  array<string, User>  $people
     */
    private function markAttendance(array $people): void
    {
        $hrId = $people['hr.manager@panunkaergar.com']->id;
        $roster = $this->roster();
        $leaveDates = [];
        $requests = PeopleLeaveRequest::query()
            ->whereIn('user_id', array_map(fn (User $user) => $user->id, $people))
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('ends_on', '>=', '2026-10-01')
            ->whereDate('starts_on', '<=', self::TODAY)
            ->get();
        foreach ($requests as $request) {
            $cursor = $request->starts_on->copy();
            while ($cursor->lte($request->ends_on) && $cursor->toDateString() <= self::TODAY) {
                $leaveDates[(string) $request->user_id][$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }

        foreach ($people as $email => $user) {
            $row = $roster[$email];
            $profile = PeopleProfile::query()->where('user_id', $user->id)->first();
            $joined = $profile?->joined_on?->toDateString();
            $left = $profile?->last_working_day?->toDateString();
            foreach ($this->workingDays() as $date) {
                if ($date > self::TODAY) {
                    continue;
                }
                if (($joined && $date < $joined) || ($left && $date > $left)) {
                    continue;
                }
                if (isset($leaveDates[(string) $user->id][$date]) || in_array($date, $row['no_attendance'], true)) {
                    continue;
                }
                $status = $row['hand'][$date] ?? 'present';
                PeopleAttendanceMark::query()->updateOrCreate(
                    ['user_id' => $user->id, 'marked_on' => $date],
                    ['status' => $status, 'marked_by' => $hrId]
                );
            }
        }
    }

    /**
     * @param  array<string, User>  $people
     */
    private function report(PeopleWorkspace $workspace, array $people): void
    {
        $slips = PeoplePayslip::query()->with('user')->where('period', self::MONTH)->get()->keyBy('user_id');
        $hidden = PeopleProfile::query()->where('billing_type', 'non_billable')->pluck('user_id')->map(fn ($id) => (string) $id)->all();
        $lines = [];
        foreach ($this->roster() as $email => $row) {
            $user = $people[$email];
            $slip = $slips->get($user->id);
            $onPayroll = ! in_array((string) $user->id, $hidden, true);
            $lines[] = sprintf(
                '%s | %s | basic %s | lop %s | net %s | %s',
                $workspace->displayName($user),
                $onPayroll ? 'payroll' : 'hidden',
                number_format((float) $row['basic'], 0),
                $slip ? $slip->lop_days : '—',
                $slip ? number_format((float) $slip->net, 2) : '—',
                $row['check']
            );
        }
        sort($lines);
        foreach ($lines as $line) {
            $this->command?->info($line);
        }
    }

    private function weekStillOpen(PeopleTimesheet $sheet): bool
    {
        $start = Carbon::parse($sheet->week_starts_on)->startOfDay();
        $holidays = ['2026-10-02', '2026-10-20'];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            if (! $day->isFuture() || $day->dayOfWeekIso === 7 || in_array($day->toDateString(), $holidays, true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function sheet(User $user, string $week): PeopleTimesheet
    {
        $sheet = PeopleTimesheet::query()->where('user_id', $user->id)->whereDate('week_starts_on', $week)->first();
        if ($sheet) {
            return $sheet;
        }

        return PeopleTimesheet::query()->create([
            'user_id' => $user->id,
            'week_starts_on' => $week,
            'hours' => array_fill_keys(PeopleWorkspace::WEEK_DAYS, 0),
            'status' => 'draft',
        ]);
    }

    /**
     * @param  array<string, mixed>  $entries
     * @return array<string, float>
     */
    private function hoursFrom(array $entries): array
    {
        $hours = array_fill_keys(PeopleWorkspace::WEEK_DAYS, 0);
        foreach ($entries as $date => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $key = PeopleWorkspace::DAY_KEYS[Carbon::parse($date)->dayOfWeekIso - 1] ?? '';
            if (! array_key_exists($key, $hours)) {
                continue;
            }
            $total = 0.0;
            foreach ($entry['rows'] ?? [] as $task) {
                $total += is_array($task) ? (float) ($task['hours'] ?? 0) : 0;
            }
            $hours[$key] = round($total, 1);
        }

        return $hours;
    }

    /**
     * @return array{ticket_id: string, task: string, description: null, deadline: null, hours: float}
     */
    private function taskRow(string $task, float $hours): array
    {
        return [
            'ticket_id' => $task === 'Training'
                ? '548695b3-39f0-4445-8fa1-29793b601249'
                : 'fe7a7622-2848-4890-bc65-01712c54e475',
            'task' => $task,
            'description' => null,
            'deadline' => null,
            'hours' => $hours,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function workingDays(): array
    {
        $holidays = ['2026-10-02', '2026-10-20'];
        $days = [];
        $cursor = Carbon::parse('2026-10-01')->startOfDay();
        $end = Carbon::parse('2026-10-31')->startOfDay();
        while ($cursor->lte($end)) {
            if ($cursor->dayOfWeekIso !== 7 && ! in_array($cursor->toDateString(), $holidays, true)) {
                $days[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function roster(): array
    {
        $blank = [
            'skip' => [],
            'half' => [],
            'extra' => [],
            'hand' => [],
            'no_attendance' => [],
            'timesheet' => true,
            'billing' => 'billable',
            'schedule' => 'full_time',
            'employment_type' => 'full_time',
            'stage' => 'permanent',
            'status' => 'active',
            'last_day' => null,
            'location' => 'in_office',
            'hours' => 8.0,
            'manager' => null,
        ];

        return [
            'admin@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Kamran', 'last_name' => 'Maqbool', 'phone' => '+918899556555', 'gender' => 'male', 'dob' => '1990-04-12',
                'title' => 'Director', 'department' => 'Operations', 'joined' => '2026-03-01', 'basic' => 50000,
                'address' => 'Rajbagh, Srinagar, Jammu and Kashmir 190008',
                'emergency' => 'Father, +919419000001',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012345', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1001A', 'aadhaar' => '910000000001', 'uan' => '100100100101', 'esi' => '5110000001',
                'extra' => ['2026-10-01' => 11, '2026-10-03' => 10, '2026-10-09' => 12],
                'check' => 'Full pay. Extra hours on 1, 3 and 9 Oct. Half earned leave on 15 Oct.',
            ]),
            'hr.manager@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'HR', 'last_name' => 'Manager', 'phone' => '+919800000011', 'gender' => 'female', 'dob' => '1992-06-18',
                'title' => 'HR Manager', 'department' => 'Human resources', 'joined' => '2026-01-06', 'basic' => 40000,
                'address' => 'Jawahar Nagar, Srinagar, Jammu and Kashmir 190008',
                'emergency' => 'Spouse, +919419000011',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012306', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1006H', 'aadhaar' => '910000000006', 'uan' => '100100100106', 'esi' => '5110000006',
                'timesheet' => false,
                'check' => 'Timesheet not required. Missing days are not loss of pay.',
            ]),
            'accounts.manager@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Accounts', 'last_name' => 'Manager', 'phone' => '+919800000012', 'gender' => 'male', 'dob' => '1988-11-02',
                'title' => 'Accounts Manager', 'department' => 'Accounts', 'joined' => '2026-02-01', 'basic' => 35000,
                'address' => 'Karan Nagar, Srinagar, Jammu and Kashmir 190010',
                'emergency' => 'Brother, +919419000012',
                'bank_name' => 'State Bank of India', 'bank_account' => '3012345678901234', 'bank_ifsc' => 'SBIN0001234',
                'pan' => 'AFZPM1007A', 'aadhaar' => '910000000007', 'uan' => '100100100107', 'esi' => '5110000007',
                'check' => 'Loss of pay 1.5. Unpaid full day 5 Oct and unpaid half day 10 Oct.',
            ]),
            'mariya.bhat@gildware.com' => array_merge($blank, [
                'first_name' => 'Mariya', 'last_name' => 'Bhat', 'phone' => '+919103280767', 'gender' => 'female', 'dob' => '1996-01-22',
                'title' => 'Field technician', 'department' => 'Field service', 'location' => 'work_from_home', 'joined' => '2026-03-01', 'basic' => 32000,
                'address' => 'Bemina, Srinagar, Jammu and Kashmir 190018',
                'emergency' => 'Mother, +919419000004',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012304', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1004M', 'aadhaar' => '910000000004', 'uan' => '100100100104', 'esi' => '5110000004',
                'hours' => 10.0,
                'check' => 'Worked extra, 10 hours a day. Extra hours do not raise pay. One cancelled leave on 22 Oct.',
            ]),
            'mehak.bashir@gildware.com' => array_merge($blank, [
                'first_name' => 'Mehak', 'last_name' => 'Bashir', 'phone' => '+917006315157', 'gender' => 'female', 'dob' => '1997-08-09',
                'title' => 'Support lead', 'department' => 'Customer support', 'location' => 'hybrid', 'joined' => '2026-03-01', 'basic' => 28000,
                'address' => 'Hyderpora, Srinagar, Jammu and Kashmir 190014',
                'emergency' => 'Sister, +919419000003',
                'bank_name' => 'HDFC Bank', 'bank_account' => '5012345678901234', 'bank_ifsc' => 'HDFC0000123',
                'pan' => 'AFZPM1003E', 'aadhaar' => '910000000003', 'uan' => '100100100103', 'esi' => '5110000003',
                'check' => 'Paid sick day on 6 Oct. Full pay.',
            ]),
            'imran.sofi@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Imran', 'last_name' => 'Sofi', 'phone' => '+919800000102', 'gender' => 'male', 'dob' => '1991-03-14',
                'title' => 'Senior technician', 'department' => 'Field service', 'joined' => '2026-02-01', 'basic' => 26000,
                'address' => 'Nowgam, Srinagar, Jammu and Kashmir 190015',
                'emergency' => 'Wife, +919419000102',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012102', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1102I', 'aadhaar' => '910000000102', 'uan' => '100100100202', 'esi' => '5110000102',
                'status' => 'notice', 'last_day' => '2026-10-16',
                'check' => 'On notice, last day 16 Oct. Unpaid day 7 Oct is loss of pay. Days after 16 Oct are not.',
            ]),
            'aalim.hameed@gildware.com' => array_merge($blank, [
                'first_name' => 'Aalim', 'last_name' => 'Hameed', 'phone' => '+918899646359', 'gender' => 'male', 'dob' => '1994-12-05',
                'title' => 'Field supervisor', 'department' => 'Field service', 'joined' => '2026-03-01', 'basic' => 22000,
                'address' => 'Lal Bazar, Srinagar, Jammu and Kashmir 190023',
                'emergency' => 'Brother, +919419000005',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012305', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1005A', 'aadhaar' => '910000000005', 'uan' => '100100100105', 'esi' => '5110000005',
                'hours' => 6.0,
                'skip' => ['2026-10-03'],
                'half' => ['2026-10-07' => 4],
                'hand' => ['2026-10-03' => 'absent', '2026-10-07' => 'half'],
                'check' => 'Most days are 6 hours and still paid in full. Absent 3 Oct and half day 7 Oct. Loss of pay 1.5.',
            ]),
            'tariq.lone@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Tariq', 'last_name' => 'Lone', 'phone' => '+919800000106', 'gender' => 'male', 'dob' => '1993-07-19',
                'title' => 'Operations coordinator', 'department' => 'Operations', 'joined' => '2026-04-01', 'basic' => 21000,
                'address' => 'Soura, Srinagar, Jammu and Kashmir 190011',
                'emergency' => 'Father, +919419000106',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012106', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1106T', 'aadhaar' => '910000000106', 'uan' => '100100100206', 'esi' => '5110000106',
                'extra' => ['2026-10-12' => 9],
                'check' => 'Week of 28 Sep was sent back. That week is already over, so it could be reviewed. Address proof was sent back.',
            ]),
            'sana.farooq@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Sana', 'last_name' => 'Farooq', 'phone' => '+919800000103', 'gender' => 'female', 'dob' => '1998-05-30',
                'title' => 'Accounts associate', 'department' => 'Accounts', 'location' => 'work_from_home', 'joined' => '2026-05-01', 'basic' => 20000,
                'address' => 'Bagh-e-Mehtab, Srinagar, Jammu and Kashmir 190019',
                'emergency' => 'Mother, +919419000103',
                'bank_name' => 'HDFC Bank', 'bank_account' => '5012345678901103', 'bank_ifsc' => 'HDFC0000123',
                'pan' => 'AFZPM1103S', 'aadhaar' => '910000000103', 'uan' => '100100100203', 'esi' => '5110000103',
                'billing' => 'non_billable', 'timesheet' => false, 'employment_type' => 'contract',
                'check' => 'No billable. Contract. Timesheet not required. Pending sick leave on 9 Oct. Not on payroll.',
            ]),
            'bilal.ahmad@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Bilal', 'last_name' => 'Ahmad', 'phone' => '+919800000104', 'gender' => 'male', 'dob' => '1995-09-11',
                'title' => 'Technician', 'department' => 'Field service', 'joined' => '2026-04-01', 'basic' => 19000,
                'address' => 'Batamaloo, Srinagar, Jammu and Kashmir 190009',
                'emergency' => 'Father, +919419000104',
                'bank_name' => 'State Bank of India', 'bank_account' => '3012345678901104', 'bank_ifsc' => 'SBIN0001234',
                'pan' => 'AFZPM1104B', 'aadhaar' => '910000000104', 'uan' => '100100100204', 'esi' => '5110000104',
                'skip' => ['2026-10-03', '2026-10-08'],
                'half' => ['2026-10-06' => 4],
                'no_attendance' => ['2026-10-03', '2026-10-06', '2026-10-08'],
                'check' => 'No timesheet on 3 and 8 Oct, half day on 6 Oct. Loss of pay 2.5.',
            ]),
            'employee.demo@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Demo', 'last_name' => 'Employee', 'phone' => '+919876543210', 'gender' => 'male', 'dob' => '1999-02-02',
                'title' => 'Support executive', 'department' => 'Customer support', 'joined' => '2026-08-01', 'basic' => 18000,
                'address' => 'Rajbagh, Srinagar, Jammu and Kashmir 190008',
                'emergency' => 'Friend, +919419000002',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012302', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1002D', 'aadhaar' => '910000000002', 'uan' => '100100100102', 'esi' => '5110000002',
                'stage' => 'probation',
                'check' => 'Paid half sick 8 Oct, paid earned 13–14 Oct. Pending unpaid half 16 Oct does not cut pay yet. Sent-back leave on 21 Oct was worked. Full pay.',
            ]),
            'rafia.jan@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Rafia', 'last_name' => 'Jan', 'phone' => '+919800000101', 'gender' => 'female', 'dob' => '2000-10-10',
                'title' => 'Support executive', 'department' => 'Customer support', 'joined' => '2026-10-06', 'basic' => 16000,
                'address' => 'Hawal, Srinagar, Jammu and Kashmir 190011',
                'emergency' => 'Mother, +919419000101',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012101', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1101R', 'aadhaar' => '910000000101', 'uan' => '100100100201', 'esi' => '5110000101',
                'stage' => 'probation',
                'check' => 'Joined 6 Oct. Days before joining are not loss of pay. Full basic for the month.',
            ]),
            'junior.employee@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Junior', 'last_name' => 'Employee', 'phone' => '+919800000013', 'gender' => 'male', 'dob' => '2001-01-15',
                'title' => 'Trainee', 'department' => 'Operations', 'location' => 'hybrid', 'joined' => '2026-10-01', 'basic' => 15000,
                'address' => 'Lal Chowk, Srinagar, Jammu and Kashmir 190001',
                'emergency' => 'Father, +919419000013',
                'bank_name' => 'J&K Bank', 'bank_account' => '0123456789012308', 'bank_ifsc' => 'JAKA0SRIN01',
                'pan' => 'AFZPM1008J', 'aadhaar' => '910000000008', 'uan' => '100100100108', 'esi' => '5110000008',
                'billing' => 'non_billable', 'stage' => 'probation', 'manager' => 'demo',
                'check' => 'No billable. Paid sick day 8 Oct is on attendance and leave, not on payroll.',
            ]),
            'nusrat.shah@panunkaergar.com' => array_merge($blank, [
                'first_name' => 'Nusrat', 'last_name' => 'Shah', 'phone' => '+919800000105', 'gender' => 'female', 'dob' => '1999-11-28',
                'title' => 'Support executive', 'department' => 'Customer support', 'location' => 'hybrid', 'joined' => '2026-06-01', 'basic' => 12000,
                'address' => 'Nishat, Srinagar, Jammu and Kashmir 191121',
                'emergency' => 'Sister, +919419000105',
                'bank_name' => 'HDFC Bank', 'bank_account' => '5012345678901105', 'bank_ifsc' => 'HDFC0000123',
                'pan' => 'AFZPM1105N', 'aadhaar' => '910000000105', 'uan' => '100100100205', 'esi' => '5110000105',
                'schedule' => 'part_time', 'hours' => 4.0,
                'check' => 'Part time, 4 hours a day, full attendance. Cancelled cheque is still pending. Full pay.',
            ]),
        ];
    }
}
