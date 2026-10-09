<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\PeopleAttendanceMark;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveGrant;
use Modules\AdminModule\Entities\PeopleLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Entities\PeopleTimesheetSetting;
use Modules\AdminModule\Entities\PeopleTimesheetTask;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\UserManagement\Entities\User;

class PeopleWorkspace
{
    public const LEAVE_TYPES = [
        'casual' => 'Casual',
        'sick' => 'Sick',
        'earned' => 'Earned',
        'unpaid' => 'Unpaid',
    ];

    /** @var array<string, string>|null */
    private ?array $leaveLabels = null;

    /** @var array<string, array<string, true>> */
    private array $markedWeekOffDates = [];

    public const DOCUMENT_TITLES = [
        'Aadhaar',
        'PAN',
        'Cancelled cheque',
        'Address proof',
    ];

    public const WEEK_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public const DAY_LABELS = [
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday',
        'sun' => 'Sunday',
    ];

    public function boot(): void
    {
        $this->accrueLeaveAfterResponse();
    }

    /**
     * Leave catch-up used to run inside the page request, which held the
     * workspace blank until every employee had been processed. The nightly
     * command still applies credits. The first page of the day finishes
     * anything that command missed after the response has already been sent.
     */
    public function accrueLeaveAfterResponse(): void
    {
        $key = 'people-leave-accrual:'.now()->toDateString();

        try {
            if (! Cache::add($key, 1, now()->endOfDay()->addMinute())) {
                return;
            }
        } catch (\Throwable $exception) {
            report($exception);

            return;
        }

        app()->terminating(function () use ($key) {
            try {
                app(PeopleLeaveAccrual::class)->applyDue(now());
            } catch (\Throwable $exception) {
                try {
                    Cache::forget($key);
                } catch (\Throwable) {
                }
                report($exception);
            }
        });
    }

    /**
     * Create a staff file only for people who do not have one yet.
     *
     * @param  Collection<int, User>  $users
     */
    public function ensureMissingStaffFiles(Collection $users): void
    {
        if ($users->isEmpty() || ! Schema::hasTable('people_profiles')) {
            return;
        }

        $ids = $users->pluck('id')->map(fn ($id) => (string) $id)->all();
        $existing = PeopleProfile::query()
            ->whereIn('user_id', $ids)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $missing = array_fill_keys(array_diff($ids, $existing), true);
        if ($missing === []) {
            return;
        }

        foreach ($users as $user) {
            if (isset($missing[(string) $user->id])) {
                $this->ensureStaffFile($user);
            }
        }
    }

    public function isHr(User $user): bool
    {
        if ($user->user_type === 'super-admin') {
            return true;
        }

        return $user->roles()->where('role_name', 'like', '%hr%')->exists();
    }

    public function isManager(User $user): bool
    {
        return PeopleProfile::query()->where('manager_id', $user->id)->exists();
    }

    public function canReviewApprovals(User $user): bool
    {
        return $this->isManager($user) || $this->isHr($user);
    }

    /**
     * People who report to this manager.
     *
     * @return Collection<int, string>
     */
    public function approvalUserIds(User $actor): Collection
    {
        return $this->teamMembers($actor)->pluck('id');
    }

    /**
     * Pending leave and timesheet requests this person can decide.
     *
     * @return array{leave: int, timesheet: int, total: int}
     */
    public function pendingApprovalCounts(User $actor): array
    {
        $empty = ['leave' => 0, 'timesheet' => 0, 'total' => 0];
        if (! Schema::hasTable('people_leave_requests') || ! Schema::hasTable('people_timesheets')) {
            return $empty;
        }

        $ids = $this->approvalUserIds($actor);
        if ($ids->isEmpty()) {
            return $empty;
        }

        $leave = PeopleLeaveRequest::query()->whereIn('user_id', $ids)->where('status', 'pending')->count();
        $timesheet = PeopleTimesheet::query()->whereIn('user_id', $ids)->where('status', 'pending')->count();

        return [
            'leave' => $leave,
            'timesheet' => $timesheet,
            'total' => $leave + $timesheet,
        ];
    }

    public function displayName(User $user): string
    {
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->email ?: 'Employee');
    }

    public function initials(User $user): string
    {
        $parts = preg_split('/\s+/', $this->displayName($user)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= strtoupper(substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : 'PK';
    }

    /**
     * @param  array<int, string>  $holidayDates
     * @param  array<int, string>  $weekOff
     */
    public static function countWorkingDays(CarbonInterface $from, CarbonInterface $to, array $holidayDates, array $weekOff = ['sun']): int
    {
        $holidays = array_flip($holidayDates);
        $off = array_flip($weekOff);
        $days = 0;
        $cursor = Carbon::parse($from->toDateString())->startOfDay();
        $end = Carbon::parse($to->toDateString())->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $day = self::DAY_KEYS[$cursor->dayOfWeekIso - 1] ?? '';
            if (! isset($off[$day]) && ! isset($holidays[$key])) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  array<int, string>  $holidayDates
     * @param  array<int, string>  $weekOff
     * @return array<int, float>
     */
    public static function daysByYear(CarbonInterface $from, CarbonInterface $to, array $holidayDates, array $weekOff, bool $halfDay = false): array
    {
        if ($halfDay) {
            return [(int) Carbon::parse($from->toDateString())->year => 0.5];
        }

        $holidays = array_flip($holidayDates);
        $off = array_flip($weekOff);
        $days = [];
        $cursor = Carbon::parse($from->toDateString())->startOfDay();
        $end = Carbon::parse($to->toDateString())->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $day = self::DAY_KEYS[$cursor->dayOfWeekIso - 1] ?? '';
            if (! isset($off[$day]) && ! isset($holidays[$key])) {
                $year = (int) $cursor->year;
                $days[$year] = round(($days[$year] ?? 0) + 1, 1);
            }
            $cursor->addDay();
        }

        return $days;
    }

    public static function hoursText(float $hours): string
    {
        $rounded = round($hours, 1);
        $whole = round($rounded);
        if (abs($rounded - $whole) < 0.001) {
            return (string) (int) $whole;
        }

        return number_format($rounded, 1, '.', '');
    }

    public function timesheetSettings(): PeopleTimesheetSetting
    {
        return once(fn () => PeopleTimesheetSetting::query()->firstOrCreate([], [
            'min_hours' => 0,
            'week_off' => ['sun'],
            'starts_on' => '2026-10-01',
        ]));
    }

    /**
     * First day a timesheet is required. Null means every past working day counts.
     */
    public function requiresTimesheet(?User $user): bool
    {
        if (! $user) {
            return true;
        }

        $profile = $user->relationLoaded('peopleProfile')
            ? $user->peopleProfile
            : PeopleProfile::query()->where('user_id', $user->id)->first();

        return $profile?->requiresTimesheet() ?? true;
    }

    public function timesheetStartsOn(): ?Carbon
    {
        $settings = $this->timesheetSettings();
        $attributes = $settings->getAttributes();
        if (! array_key_exists('starts_on', $attributes) || $attributes['starts_on'] === null || $attributes['starts_on'] === '') {
            return null;
        }

        return Carbon::parse($attributes['starts_on'])->startOfDay();
    }

    public function minTimesheetHours(?User $user = null): float
    {
        $settings = $this->timesheetSettings();
        $fullTime = $this->configuredHours($settings, 'min_hours_full_time');
        if (! $user) {
            return $fullTime;
        }

        $profile = $user->relationLoaded('peopleProfile')
            ? $user->peopleProfile
            : PeopleProfile::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            return $fullTime;
        }

        $attributes = $profile->getAttributes();
        if (array_key_exists('min_hours_override', $attributes) && $attributes['min_hours_override'] !== null && $attributes['min_hours_override'] !== '') {
            return round(max(0, (float) $attributes['min_hours_override']), 1);
        }

        if (($attributes['work_schedule'] ?? 'full_time') === 'part_time') {
            return $this->configuredHours($settings, 'min_hours_part_time');
        }

        return $fullTime;
    }

    /**
     * Hours a working day must reach before this employee can submit it.
     * A configured 0 uses the standard day: 8 full time, 4 part time.
     */
    public function requiredDayHours(?User $user = null): float
    {
        $configured = $this->minTimesheetHours($user);
        if ($configured > 0) {
            return $configured;
        }

        return $this->isPartTime($user) ? 4.0 : 8.0;
    }

    public function timesheetEntryHours(array $entry): float
    {
        $rows = is_array($entry['rows'] ?? null) ? $entry['rows'] : [];

        return round(array_sum(array_map(
            fn ($row) => is_array($row) ? (float) ($row['hours'] ?? 0) : 0.0,
            $rows
        )), 2);
    }

    public function dayMeetsMinimum(array $entry, ?User $user = null): bool
    {
        return $this->timesheetEntryHours($entry) + 0.001 >= $this->requiredDayHours($user);
    }

    private function isPartTime(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $profile = $user->relationLoaded('peopleProfile')
            ? $user->peopleProfile
            : PeopleProfile::query()->where('user_id', $user->id)->first();
        $schedule = is_object($profile) ? (string) ($profile->getAttributes()['work_schedule'] ?? 'full_time') : 'full_time';

        return $schedule === 'part_time';
    }

    private function configuredHours(PeopleTimesheetSetting $settings, string $column): float
    {
        $attributes = $settings->getAttributes();
        $value = array_key_exists($column, $attributes) ? $attributes[$column] : ($attributes['min_hours'] ?? 0);

        return round(max(0, (float) $value), 1);
    }

    public function leaveDayHours(?User $user = null): float
    {
        return min(24, $this->requiredDayHours($user));
    }

    public function halfLeaveHours(?User $user = null): float
    {
        return round($this->leaveDayHours($user) / 2, 1);
    }

    /**
     * Hours for a partial day. Empty uses half the employee's day.
     * The value stays below a full day so a whole day is still a full leave.
     */
    public function partialLeaveHours(?User $user, ?float $hours = null): float
    {
        $day = $this->leaveDayHours($user);
        $max = max(0.5, round($day - 0.5, 1));
        $value = $hours === null ? $this->halfLeaveHours($user) : round($hours, 1);
        if ($value + 0.001 < 0.5 || $value - 0.001 > $max) {
            throw new \InvalidArgumentException('Leave for part of a day must be between 0.5 and '.self::hoursText($max).' hours.');
        }

        return $value;
    }

    public function leavePortion(?User $user, float $hours): float
    {
        $day = $this->leaveDayHours($user);
        if ($day <= 0) {
            return 0.5;
        }

        return max(0.1, min(0.9, round($hours / $day, 1)));
    }

    public function appliedPartialHours(User $user, PeopleLeaveRequest $request): float
    {
        $stored = $request->hours;
        if ($stored !== null && (float) $stored > 0) {
            return round((float) $stored, 1);
        }

        return $this->halfLeaveHours($user);
    }

    /**
     * Copy pending and approved leave onto the timesheet so those dates show as leave.
     */
    public function mirrorOpenLeave(User $user): void
    {
        if (! Schema::hasTable('people_leave_requests') || ! Schema::hasTable('people_timesheets')) {
            return;
        }

        $requests = PeopleLeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        foreach ($requests as $request) {
            $this->syncLeaveOntoTimesheet($user, $request);
        }
    }

    public function syncLeaveOntoTimesheet(User $user, PeopleLeaveRequest $request): void
    {
        if (! in_array($request->status, ['pending', 'approved'], true)) {
            return;
        }

        $half = $this->isHalfDayLeave($request);
        $leaveHours = $half ? $this->appliedPartialHours($user, $request) : $this->leaveDayHours($user);
        foreach ($this->leaveWorkingDates($request) as $date) {
            if ($this->attendanceIsLocked($date)) {
                continue;
            }
            $workRows = $half ? $this->halfLeaveWorkRows($user, $request, $date) : [];
            $rows = array_merge([[
                'ticket_id' => 'leave',
                'task' => self::leaveTaskLabel($this->leaveLabel($request->leave_type)),
                'description' => trim((string) $request->reason) !== '' ? trim((string) $request->reason) : null,
                'deadline' => null,
                'from_time' => $half ? $request->from_time : null,
                'to_time' => $half ? $request->to_time : null,
                'hours' => $leaveHours,
            ]], $workRows);
            $amount = round($leaveHours + array_sum(array_map(fn (array $row) => (float) ($row['hours'] ?? 0), $workRows)), 2);
            $this->saveTimesheetDay($user, $date, [
                'status' => 'submitted',
                'leave_request_id' => (string) $request->id,
                'half' => $half,
                'rows' => $rows,
            ], $amount);
        }
    }

    public function clearLeaveFromTimesheet(PeopleLeaveRequest $request): void
    {
        $user = User::query()->find($request->user_id);
        if (! $user) {
            return;
        }

        foreach ($this->leaveWorkingDates($request) as $date) {
            if ($this->attendanceIsLocked($date)) {
                continue;
            }
            $sheet = PeopleTimesheet::query()
                ->where('user_id', $user->id)
                ->whereDate('week_starts_on', $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
                ->first();
            $key = $date->toDateString();
            $entry = is_array($sheet?->entries) ? ($sheet->entries[$key] ?? null) : null;
            if (! $sheet || ! is_array($entry) || (string) ($entry['leave_request_id'] ?? '') !== (string) $request->id) {
                continue;
            }
            $this->saveTimesheetDay($user, $date, null, 0);
        }
    }

    public function leaveCovering(User $user, CarbonInterface $date): ?PeopleLeaveRequest
    {
        if (! Schema::hasTable('people_leave_requests')) {
            return null;
        }

        return PeopleLeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Hours already logged beside a half-day leave. A later sync must keep them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function halfLeaveWorkRows(User $user, PeopleLeaveRequest $request, CarbonInterface $date): array
    {
        $sheet = PeopleTimesheet::query()
            ->where('user_id', $user->id)
            ->whereDate('week_starts_on', $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
            ->first();
        $entry = is_array($sheet?->entries) ? ($sheet->entries[$date->toDateString()] ?? null) : null;
        if (! is_array($entry) || (string) ($entry['leave_request_id'] ?? '') !== (string) $request->id) {
            return [];
        }

        $rows = [];
        foreach ($entry['rows'] ?? [] as $row) {
            if (! is_array($row) || ($row['ticket_id'] ?? '') === 'leave') {
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function isHalfDayLeave(PeopleLeaveRequest $request): bool
    {
        return (float) $request->days > 0
            && (float) $request->days < 1
            && $request->starts_on->toDateString() === $request->ends_on->toDateString();
    }

    /**
     * @return array<int, string>
     */
    public function weekOffDays(?User $user = null): array
    {
        if ($user) {
            $profile = $user->relationLoaded('peopleProfile')
                ? $user->peopleProfile
                : PeopleProfile::query()->where('user_id', $user->id)->first();
            $override = $this->weekOffOverride($profile);
            if ($override !== null) {
                return $override;
            }
        }

        return $this->companyWeekOff();
    }

    /**
     * @return array<int, string>
     */
    private function companyWeekOff(): array
    {
        $days = $this->timesheetSettings()->week_off;
        if (! is_array($days)) {
            return ['sun'];
        }

        return array_values(array_intersect(self::DAY_KEYS, $days));
    }

    /**
     * Null means this person uses the company week off.
     *
     * @return array<int, string>|null
     */
    private function weekOffOverride(?PeopleProfile $profile): ?array
    {
        if (! $profile) {
            return null;
        }

        $attributes = $profile->getAttributes();
        if (! array_key_exists('week_off_override', $attributes) || $attributes['week_off_override'] === null) {
            return null;
        }

        $days = $profile->week_off_override;
        if (is_string($days)) {
            $days = json_decode($days, true);
        }
        if (! is_array($days)) {
            return [];
        }

        return array_values(array_intersect(self::DAY_KEYS, $days));
    }

    /**
     * @param  array<int, string>  $days
     */
    public static function weekOffText(array $days): string
    {
        $labels = [];
        foreach (self::DAY_KEYS as $key) {
            if (in_array($key, $days, true)) {
                $labels[] = self::DAY_LABELS[$key];
            }
        }

        return $labels === [] ? 'No week off' : implode(', ', $labels);
    }

    public function isRecurringWeekOff(CarbonInterface $date, ?User $user = null): bool
    {
        $day = self::DAY_KEYS[$date->dayOfWeekIso - 1] ?? '';

        return in_array($day, $this->weekOffDays($user), true);
    }

    public function isWeekOff(CarbonInterface $date, ?User $user = null): bool
    {
        if ($this->isRecurringWeekOff($date, $user)) {
            return true;
        }

        return $user !== null && isset($this->markedWeekOffDates($user)[$date->toDateString()]);
    }

    /**
     * Dates this person marked as a week off on a timesheet. These are single days, not a weekly pattern.
     *
     * @return array<string, true>
     */
    public function markedWeekOffDates(User $user): array
    {
        $id = (string) $user->id;
        if (array_key_exists($id, $this->markedWeekOffDates)) {
            return $this->markedWeekOffDates[$id];
        }

        $dates = [];
        if (Schema::hasTable('people_timesheets')) {
            $sheets = PeopleTimesheet::query()->where('user_id', $user->id)->get(['entries']);
            foreach ($sheets as $sheet) {
                foreach ($sheet->entries ?? [] as $date => $entry) {
                    if (is_array($entry) && ($entry['status'] ?? null) === 'week_off' && is_string($date)) {
                        $dates[$date] = true;
                    }
                }
            }
        }

        return $this->markedWeekOffDates[$id] = $dates;
    }

    public function forgetMarkedWeekOffDates(User $user): void
    {
        unset($this->markedWeekOffDates[(string) $user->id]);
    }

    /**
     * @param  array<int, string>  $holidayDates
     */
    public function extraWeekOffCount(User $user, CarbonInterface $from, CarbonInterface $to, array $holidayDates): int
    {
        $holidays = array_flip($holidayDates);
        $start = Carbon::parse($from->toDateString())->startOfDay();
        $end = Carbon::parse($to->toDateString())->startOfDay();
        $count = 0;
        foreach (array_keys($this->markedWeekOffDates($user)) as $date) {
            $day = Carbon::parse($date)->startOfDay();
            if ($day->lt($start) || $day->gt($end) || isset($holidays[$date]) || $this->isRecurringWeekOff($day, $user)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    /**
     * Additional hours configured in People & HR. These are not task-board tasks.
     *
     * @return array<int, array{id: string, task: string, deadline: string}>
     */
    public function configuredTimesheetTasks(): array
    {
        return PeopleTimesheetTask::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (PeopleTimesheetTask $task) => [
                'id' => (string) $task->id,
                'task' => $task->name,
                'deadline' => '',
            ])
            ->all();
    }

    /**
     * Task-board tickets this person can log: assigned to them, created by them, or still unassigned.
     *
     * @return array<int, array{id: string, task: string, deadline: string}>
     */
    public function assignedBoardTasks(User $user): array
    {
        if (! Schema::hasTable('task_tickets')) {
            return [];
        }

        return TaskTicket::query()
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id);
                if (Schema::hasTable('task_ticket_assignees')) {
                    $query->orWhereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id))
                        ->orWhereDoesntHave('assignees');
                }
            })
            ->orderBy('title')
            ->get(['id', 'title', 'end_date'])
            ->map(function (TaskTicket $ticket): array {
                $deadline = $ticket->end_date;

                return [
                    'id' => (string) $ticket->id,
                    'task' => (string) $ticket->title,
                    'deadline' => $deadline instanceof \DateTimeInterface ? $deadline->format('Y-m-d') : '',
                ];
            })
            ->all();
    }

    public function workingDays(CarbonInterface $from, CarbonInterface $to): int
    {
        return self::countWorkingDays($from, $to, $this->holidayDates($from, $to), $this->weekOffDays());
    }

    /**
     * @return array<int, string>
     */
    private function holidayDates(CarbonInterface $from, CarbonInterface $to): array
    {
        return PeopleHoliday::query()
            ->whereDate('holiday_on', '>=', $from->toDateString())
            ->whereDate('holiday_on', '<=', $to->toDateString())
            ->pluck('holiday_on')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    public function ensureStaffFile(User $user): PeopleProfile
    {
        $profile = PeopleProfile::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            $profile = PeopleProfile::query()->create([
                'user_id' => $user->id,
                'employee_code' => $this->nextCode(),
                'job_title' => 'Employee',
                'work_location' => '',
                'joined_on' => optional($user->created_at)->toDateString(),
            ]);
        }

        $this->balance($user);

        foreach (self::DOCUMENT_TITLES as $title) {
            PeopleDocument::query()->firstOrCreate(
                ['user_id' => $user->id, 'title' => $title],
                ['status' => 'missing']
            );
        }

        return $profile;
    }

    public function currentWeekStart(): Carbon
    {
        return now()->startOfWeek(Carbon::MONDAY);
    }

    public function timesheetForWeek(User $user, CarbonInterface $weekStart): PeopleTimesheet
    {
        $date = Carbon::parse($weekStart->toDateString())->toDateString();
        $sheet = PeopleTimesheet::query()
            ->where('user_id', $user->id)
            ->whereDate('week_starts_on', $date)
            ->first();
        if ($sheet) {
            return $sheet;
        }

        return PeopleTimesheet::query()->create([
            'user_id' => $user->id,
            'week_starts_on' => $date,
            'hours' => array_fill_keys(self::WEEK_DAYS, 0),
            'status' => 'draft',
        ]);
    }

    public function balance(User $user): PeopleLeaveBalance
    {
        return $this->balanceFor($user, (int) now()->year);
    }

    public function balanceFor(User $user, int $year): PeopleLeaveBalance
    {
        return PeopleLeaveBalance::query()->firstOrCreate(
            ['user_id' => $user->id, 'year' => $year],
            [
                'casual_allowance' => 0,
                'sick_allowance' => 0,
                'earned_allowance' => 0,
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    public function unpaidLeaveTypeCodes(): array
    {
        $codes = PeopleLeaveType::query()->where('tracks_balance', false)->pluck('code')->all();

        return $codes === [] ? ['unpaid'] : $codes;
    }

    /**
     * @return Collection<int, User>
     */
    public function staffUsers(): Collection
    {
        return User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function teamMembers(User $manager): Collection
    {
        $ids = PeopleProfile::query()->where('manager_id', $manager->id)->pluck('user_id');

        return User::query()
            ->whereIn('id', $ids)
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get();
    }

    public function canDecideFor(User $actor, User $employee): bool
    {
        $managerId = PeopleProfile::query()->where('user_id', $employee->id)->value('manager_id');

        return self::mayDecide(
            (string) $actor->id,
            (string) $employee->id,
            $managerId ? (string) $managerId : null,
            $this->isHr($actor)
        );
    }

    /**
     * The direct manager decides. HR decides only when no manager is set.
     * A person never decides their own request.
     */
    public static function mayDecide(string $actorId, string $employeeId, ?string $managerId, bool $actorIsHr): bool
    {
        if ($actorId === '' || $actorId === $employeeId) {
            return false;
        }

        if ($managerId) {
            return $managerId === $actorId;
        }

        return $actorIsHr;
    }

    public function canReadFile(User $actor, User $employee): bool
    {
        if ((string) $actor->id === (string) $employee->id) {
            return true;
        }

        return $this->canDecideFor($actor, $employee);
    }

    private function normalizeClock(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^(\d{2}):(\d{2})/', $value, $match)) {
            throw new \InvalidArgumentException('Enter a from time and a to time.');
        }
        $hours = (int) $match[1];
        $minutes = (int) $match[2];
        if ($hours > 23 || $minutes > 59) {
            throw new \InvalidArgumentException('Enter a from time and a to time.');
        }

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    private function clockToMinutes(string $value): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $value));

        return ($hours * 60) + $minutes;
    }

    public function submitLeave(User $user, string $type, CarbonInterface $from, CarbonInterface $to, string $reason, bool $halfDay = false, ?float $leaveHours = null, ?string $fromTime = null, ?string $toTime = null): PeopleLeaveRequest
    {
        if ($halfDay && $from->toDateString() !== $to->toDateString()) {
            throw new \InvalidArgumentException('A half day is a single date.');
        }

        $storedFrom = null;
        $storedTo = null;
        if ($halfDay) {
            $storedFrom = $this->normalizeClock($fromTime);
            $storedTo = $this->normalizeClock($toTime);
            if ($storedFrom !== null || $storedTo !== null) {
                if ($storedFrom === null || $storedTo === null) {
                    throw new \InvalidArgumentException('A half day needs a from time and a to time.');
                }
                $start = $this->clockToMinutes($storedFrom);
                $end = $this->clockToMinutes($storedTo);
                if ($end <= $start) {
                    throw new \InvalidArgumentException('To time has to be later than from time.');
                }
                $leaveHours = round(($end - $start) / 60, 1);
            }
        }

        $leaveType = PeopleLeaveType::query()->where('code', $type)->first();
        $today = now()->toDateString();
        if ($leaveType && ! $leaveType->allows_future && ($from->toDateString() > $today || $to->toDateString() > $today)) {
            throw new \InvalidArgumentException($leaveType->name.' cannot be applied for a future date.');
        }

        $hours = null;
        if ($halfDay) {
            $hours = $this->partialLeaveHours($user, $leaveHours);
            $split = [(int) Carbon::parse($from->toDateString())->year => $this->leavePortion($user, $hours)];
        } else {
            $split = self::daysByYear($from, $to, $this->holidayDates($from, $to), $this->weekOffDays($user));
        }
        $days = round(array_sum($split), 1);
        if ($days <= 0) {
            throw new \InvalidArgumentException('Those dates are already off. Pick a working day.');
        }

        if ($this->leaveTracksBalance($type)) {
            foreach ($split as $year => $count) {
                $balance = $this->balanceFor($user, (int) $year);
                if ($balance->remaining($type) < $count) {
                    throw new \InvalidArgumentException('You do not have enough '.$this->leaveLabel($type).' leave in '.$year.' for those dates.');
                }
            }
        }

        $overlaps = PeopleLeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('starts_on', '<=', $to->toDateString())
            ->whereDate('ends_on', '>=', $from->toDateString())
            ->exists();

        if ($overlaps) {
            throw new \InvalidArgumentException('Those dates overlap a leave request you already sent.');
        }

        $leave = DB::transaction(function () use ($user, $type, $from, $to, $days, $split, $reason, $hours, $storedFrom, $storedTo) {
            if ($this->leaveTracksBalance($type)) {
                foreach ($split as $year => $count) {
                    $balance = $this->balanceFor($user, (int) $year);
                    $balance->addPending($type, (float) $count);
                    $balance->save();
                }
            }

            $leave = PeopleLeaveRequest::query()->create([
                'user_id' => $user->id,
                'leave_type' => $type,
                'starts_on' => $from->toDateString(),
                'ends_on' => $to->toDateString(),
                'days' => $days,
                'hours' => $hours,
                'from_time' => $storedFrom,
                'to_time' => $storedTo,
                'year_split' => $split,
                'reason' => $reason,
                'status' => 'pending',
            ]);
            $this->syncLeaveOntoTimesheet($user, $leave);

            return $leave;
        });

        $this->notifyLeaveSubmitted($user, $leave);

        return $leave;
    }

    /**
     * Keep a partial day's leave hours, and the days held against the balance, in step with the timesheet.
     */
    public function syncPartialLeaveHours(User $user, PeopleLeaveRequest $request, float $hours): void
    {
        $hours = $this->partialLeaveHours($user, $hours);
        $portion = $this->leavePortion($user, $hours);
        $delta = round($portion - round((float) $request->days, 1), 1);

        DB::transaction(function () use ($user, $request, $hours, $portion, $delta) {
            if (abs($delta) >= 0.05 && $this->leaveTracksBalance($request->leave_type)) {
                $balance = $this->lockedBalance($user->id, (int) $request->starts_on->year);
                $type = $request->leave_type;
                if ($delta > 0 && $balance->remaining($type) + 0.001 < $delta) {
                    throw new \InvalidArgumentException('You do not have enough '.$this->leaveLabel($type).' leave for those hours.');
                }
                if ($request->status === 'approved') {
                    if ($delta > 0) {
                        $balance->addUsed($type, $delta);
                    } else {
                        $balance->restoreUsed($type, abs($delta));
                    }
                } elseif ($delta > 0) {
                    $balance->addPending($type, $delta);
                } else {
                    $balance->releasePending($type, abs($delta));
                }
                $balance->save();
            }

            $request->forceFill([
                'hours' => $hours,
                'days' => $portion,
                'year_split' => [(int) $request->starts_on->year => $portion],
            ])->save();
        });
    }

    public function decideLeave(User $actor, PeopleLeaveRequest $request, string $decision, ?string $note = null): void
    {
        $employee = $request->user;
        if (! $employee || ! $this->canDecideFor($actor, $employee)) {
            throw new \InvalidArgumentException('You cannot decide this leave request.');
        }

        if ($request->status !== 'pending') {
            throw new \InvalidArgumentException('This request is already decided.');
        }

        $note = trim((string) $note);

        if ($decision === 'sent_back') {
            DB::transaction(function () use ($actor, $request, $note) {
                $this->releaseReservedDays($request);
                $request->forceFill([
                    'status' => 'sent_back',
                    'decided_by' => $actor->id,
                    'decided_at' => now(),
                    'decision_note' => $note !== '' ? $note : null,
                ])->save();
                $this->clearLeaveFromTimesheet($request);
            });
            $this->notifyLeaveDecided($actor, $request);

            return;
        }

        DB::transaction(function () use ($actor, $request, $employee) {
            $type = $request->leave_type;
            if ($this->leaveTracksBalance($type)) {
                foreach ($request->daysByYear() as $year => $count) {
                    $balance = $this->lockedBalance($employee->id, (int) $year);
                    if ($balance->pending($type) + 0.001 < (float) $count) {
                        throw new \InvalidArgumentException('There is not enough '.$this->leaveLabel($type).' leave left to approve this.');
                    }
                    $balance->releasePending($type, (float) $count);
                    $balance->addUsed($type, (float) $count);
                    $balance->save();
                }
            }

            $request->forceFill([
                'status' => 'approved',
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_note' => null,
            ])->save();
        });
        $this->notifyLeaveDecided($actor, $request);
    }

    public function cancelLeave(User $actor, PeopleLeaveRequest $request, bool $asHr = false): void
    {
        if ($asHr) {
            if (! $this->isHr($actor)) {
                throw new \InvalidArgumentException('Only HR can cancel an approved leave.');
            }
            if ($request->status !== 'approved') {
                throw new \InvalidArgumentException('Only an approved leave can be cancelled here.');
            }

            DB::transaction(function () use ($actor, $request) {
                if ($this->leaveTracksBalance($request->leave_type)) {
                    foreach ($request->daysByYear() as $year => $count) {
                        $balance = $this->lockedBalance($request->user_id, (int) $year);
                        $balance->restoreUsed($request->leave_type, (float) $count);
                        $balance->save();
                    }
                }

                $request->forceFill([
                    'status' => 'cancelled',
                    'decided_by' => $actor->id,
                    'decided_at' => now(),
                ])->save();
                $this->clearLeaveFromTimesheet($request);
            });
            $this->notifyLeaveDecided($actor, $request);

            return;
        }

        if ((string) $actor->id !== (string) $request->user_id || $request->status !== 'pending') {
            throw new \InvalidArgumentException('You can only cancel a request that is still waiting.');
        }

        DB::transaction(function () use ($actor, $request) {
            $this->releaseReservedDays($request);
            $request->forceFill([
                'status' => 'cancelled',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ])->save();
            $this->clearLeaveFromTimesheet($request);
        });
    }

    /**
     * @return array<int, Carbon>
     */
    private function leaveWorkingDates(PeopleLeaveRequest $request): array
    {
        $cursor = $request->starts_on->copy()->startOfDay();
        $end = $request->ends_on->copy()->startOfDay();
        $holidays = array_flip($this->holidayDates($cursor, $end));
        $person = $request->relationLoaded('user') ? $request->user : User::query()->find($request->user_id);
        $dates = [];
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            if (! $this->isWeekOff($cursor, $person) && ! isset($holidays[$key])) {
                $dates[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        return $dates;
    }

    private function attendanceIsLocked(CarbonInterface $date): bool
    {
        if (! Schema::hasTable('people_payroll_runs')) {
            return false;
        }

        return DB::table('people_payroll_runs')
            ->where('period', $date->format('Y-m'))
            ->where('attendance_locked', true)
            ->exists();
    }

    /**
     * @param  array<string, mixed>|null  $entry
     */
    private function saveTimesheetDay(User $user, CarbonInterface $date, ?array $entry, float $dayHours): void
    {
        $day = Carbon::parse($date->toDateString())->startOfDay();
        $weekStart = $day->copy()->startOfWeek(Carbon::MONDAY);
        $sheet = $this->timesheetForWeek($user, $weekStart);
        $wasPending = $sheet->status === 'pending';
        $entries = $sheet->entries ?? [];
        $key = $day->toDateString();
        $hours = $sheet->hours ?? array_fill_keys(self::WEEK_DAYS, 0);
        $dayKey = self::DAY_KEYS[$day->dayOfWeekIso - 1] ?? null;
        $sameHours = ! $dayKey || abs((float) ($hours[$dayKey] ?? 0) - $dayHours) < 0.001;
        $current = $entries[$key] ?? null;
        if ($entry === null && ! array_key_exists($key, $entries)) {
            return;
        }
        if ($entry !== null && $current == $entry && $sameHours) {
            return;
        }

        if ($entry === null) {
            unset($entries[$key]);
        } else {
            $entries[$key] = $entry;
        }
        if ($dayKey) {
            $hours[$dayKey] = round($dayHours, 1);
        }

        $status = $sheet->status;
        $decidedBy = $sheet->decided_by;
        $decidedAt = $sheet->decided_at;
        $locked = in_array($status, ['pending', 'approved'], true);
        $complete = $this->weekSubmitted($weekStart, $entries, $user);
        if ($entry === null && ! $complete && $locked) {
            $status = 'draft';
            $decidedBy = null;
            $decidedAt = null;
        } elseif ($entry !== null && ! $locked && $complete) {
            $status = 'pending';
            $decidedBy = null;
            $decidedAt = null;
        }

        $sheet->forceFill([
            'entries' => $entries,
            'hours' => $hours,
            'status' => $status,
            'decided_by' => $decidedBy,
            'decided_at' => $decidedAt,
        ])->save();

        if ($status === 'pending' && ! $wasPending) {
            $this->notifyTimesheetSubmitted($user, $sheet);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function weekSubmitted(CarbonInterface $weekStart, array $entries, ?User $user = null): bool
    {
        $start = Carbon::parse($weekStart->toDateString())->startOfDay();
        $holidays = array_flip($this->holidayDates($start, $start->copy()->addDays(6)));
        $required = 0;
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $entry = $entries[$day->toDateString()] ?? null;
            if ($this->isBeforeTimesheetStart($day) || $this->isRecurringWeekOff($day, $user) || isset($holidays[$day->toDateString()]) || (is_array($entry) && ($entry['status'] ?? null) === 'week_off')) {
                continue;
            }
            $required++;
            if ($day->isFuture()) {
                return false;
            }
            if (! is_array($entry) || ($entry['status'] ?? null) !== 'submitted' || ! $this->dayMeetsMinimum($entry, $user)) {
                return false;
            }
        }

        return $required > 0;
    }

    public function isBeforeTimesheetStart(CarbonInterface $day): bool
    {
        $starts = $this->timesheetStartsOn();

        return $starts !== null && Carbon::parse($day->toDateString())->startOfDay()->lt($starts);
    }

    private function releaseReservedDays(PeopleLeaveRequest $request): void
    {
        if (! $this->leaveTracksBalance($request->leave_type)) {
            return;
        }

        foreach ($request->daysByYear() as $year => $count) {
            $balance = $this->lockedBalance($request->user_id, (int) $year);
            $balance->releasePending($request->leave_type, (float) $count);
            $balance->save();
        }
    }

    private function lockedBalance(string $userId, int $year): PeopleLeaveBalance
    {
        $balance = PeopleLeaveBalance::query()->firstOrCreate(
            ['user_id' => $userId, 'year' => $year],
            [
                'casual_allowance' => 0,
                'sick_allowance' => 0,
                'earned_allowance' => 0,
            ]
        );

        return PeopleLeaveBalance::query()->whereKey($balance->id)->lockForUpdate()->first() ?? $balance;
    }

    public function canDecideTimesheet(User $actor, User $employee): bool
    {
        if ((string) $actor->id === '' || (string) $actor->id === (string) $employee->id) {
            return false;
        }

        return $this->isHr($actor) || $this->canDecideFor($actor, $employee);
    }

    public function decideTimesheet(User $actor, PeopleTimesheet $sheet, string $decision, ?string $note = null): void
    {
        $employee = $sheet->user;
        if (! $employee || ! $this->canDecideTimesheet($actor, $employee)) {
            throw new \InvalidArgumentException('You cannot decide this timesheet.');
        }

        if (! in_array($sheet->status, ['pending', 'draft', 'sent_back'], true)) {
            throw new \InvalidArgumentException('This timesheet is not waiting for a decision.');
        }

        $reason = trim((string) $note);
        $sheet->forceFill([
            'status' => $decision === 'approve' ? 'approved' : 'sent_back',
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_note' => $decision === 'sent_back' && $reason !== '' ? $reason : null,
        ])->save();
        $this->notifyTimesheetDecided($actor, $sheet);
    }

    /**
     * Three-month leave and attendance grid for one person.
     *
     * @return array{anchor: string, months: array<int, array<string, mixed>>}
     */
    public function leaveCalendar(string $userId): array
    {
        $today = now()->startOfDay();
        $person = User::query()->find($userId);
        $first = $today->copy()->startOfMonth()->subMonths(15);
        $last = $today->copy()->startOfMonth()->addMonths(3);
        $rangeStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $rangeEnd = $last->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $types = PeopleLeaveType::query()->get(['code', 'name', 'short_name']);
        $names = [];
        $shorts = [];
        foreach ($types as $type) {
            $names[$type->code] = $type->name;
            $short = strtoupper(trim((string) $type->short_name));
            $shorts[$type->code] = $short !== '' ? mb_substr($short, 0, 3) : '';
        }

        $holidayNames = [];
        $holidays = PeopleHoliday::query()
            ->whereDate('holiday_on', '>=', $rangeStart->toDateString())
            ->whereDate('holiday_on', '<=', $rangeEnd->toDateString())
            ->get(['holiday_on', 'name']);
        foreach ($holidays as $holiday) {
            $holidayNames[$holiday->holiday_on->toDateString()] = $holiday->name;
        }

        $leaveDays = [];
        $requests = PeopleLeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('ends_on', '>=', $rangeStart->toDateString())
            ->whereDate('starts_on', '<=', $rangeEnd->toDateString())
            ->get();
        foreach ($requests as $request) {
            $cursor = $request->starts_on->copy()->startOfDay();
            $end = $request->ends_on->copy()->startOfDay();
            $label = $names[$request->leave_type] ?? $this->leaveLabel($request->leave_type);
            $mark = $shorts[$request->leave_type] ?? '';
            $half = (float) $request->days > 0 && (float) $request->days < 1 && $cursor->equalTo($end);
            $kind = $request->status === 'approved' ? 'leave' : 'pending';
            $status = $request->status === 'approved' ? 'Approved' : 'Pending';
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $cursor->addDay();
                if ($this->isWeekOff(Carbon::parse($key), $person) || isset($holidayNames[$key])) {
                    continue;
                }
                if (($leaveDays[$key]['kind'] ?? null) === 'leave' && $kind !== 'leave') {
                    continue;
                }
                $leaveDays[$key] = [
                    'kind' => $kind,
                    'code' => $mark,
                    'half' => $half,
                    'title' => $label.($half ? ' · half day' : '').' · '.$status,
                ];
            }
        }

        $present = [];
        $timesheetLeave = [];
        $sheets = PeopleTimesheet::query()->where('user_id', $userId)->get(['entries']);
        foreach ($sheets as $sheet) {
            foreach ($sheet->entries ?? [] as $date => $entry) {
                $date = (string) $date;
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! is_array($entry) || ($entry['status'] ?? null) !== 'submitted') {
                    continue;
                }
                $rows = is_array($entry['rows'] ?? null) ? $entry['rows'] : [];
                $onlyLeave = $rows !== [] && collect($rows)->every(fn ($row) => is_array($row) && ($row['ticket_id'] ?? '') === 'leave');
                if ($onlyLeave) {
                    $timesheetLeave[$date] = true;
                } else {
                    $present[$date] = true;
                }
            }
        }

        $months = [];
        for ($cursor = $first->copy(); $cursor->lte($last); $cursor->addMonth()) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $grid = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
            $weeks = [];
            while ($grid->lte($gridEnd)) {
                $week = [];
                for ($i = 0; $i < 7; $i++) {
                    $day = $grid->copy();
                    $grid->addDay();
                    $in = $day->month === $monthStart->month && $day->year === $monthStart->year;
                    $key = $day->toDateString();
                    $cell = [
                        'in' => $in,
                        'date' => $in ? $key : '',
                        'day' => $in ? $day->day : '',
                        'kind' => 'none',
                        'code' => '',
                        'half' => false,
                        'title' => '',
                    ];
                    if ($in) {
                        $when = $day->format('D j M Y');
                        if (isset($leaveDays[$key])) {
                            $cell['kind'] = $leaveDays[$key]['kind'];
                            $cell['code'] = $leaveDays[$key]['code'];
                            $cell['half'] = $leaveDays[$key]['half'];
                            $cell['title'] = $when.' · '.$leaveDays[$key]['title'];
                        } elseif ($this->isWeekOff($day, $person)) {
                            $cell['kind'] = 'off';
                            $cell['title'] = $when.' · Week off';
                        } elseif (isset($holidayNames[$key])) {
                            $cell['kind'] = 'off';
                            $cell['title'] = $when.' · '.$holidayNames[$key];
                        } elseif (isset($present[$key])) {
                            $cell['kind'] = 'present';
                            $cell['title'] = $when.' · Present';
                        } elseif (isset($timesheetLeave[$key])) {
                            $cell['kind'] = 'leave';
                            $cell['title'] = $when.' · Leave';
                        } elseif ($day->isSameDay($today)) {
                            $cell['kind'] = 'today';
                            $cell['title'] = $when.' · Today';
                        } elseif ($day->gt($today)) {
                            $cell['kind'] = 'future';
                            $cell['title'] = $when;
                        } elseif (! $this->requiresTimesheet($person)) {
                            $cell['kind'] = 'none';
                            $cell['title'] = $when;
                        } else {
                            $cell['kind'] = 'absent';
                            $cell['title'] = $when.' · No attendance';
                        }
                    }
                    $week[] = $cell;
                }
                $weeks[] = $week;
            }
            $months[] = [
                'key' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('M Y'),
                'current' => $monthStart->isSameMonth($today),
                'weeks' => $weeks,
            ];
        }

        $anchorIndex = 0;
        foreach ($months as $index => $month) {
            if ($month['current']) {
                $anchorIndex = $index;
                break;
            }
        }
        $windowStart = max(0, $anchorIndex - 2);
        $windowEnd = min(count($months) - 1, $windowStart + 2);
        foreach ($months as $index => $month) {
            $months[$index]['visible'] = $index >= $windowStart && $index <= $windowEnd;
        }

        return [
            'anchor' => $today->format('Y-m'),
            'months' => $months,
        ];
    }

    /**
     * One row per employee for a month. Each day is present, absent, leave, half day, week off, or a holiday.
     *
     * @param  Collection<int, User>  $staff
     * @param  Collection<string, PeopleProfile>  $profiles
     * @return array{days: array<int, array<string, mixed>>, rows: array<int, array<string, mixed>>}
     */
    public function attendanceMonth(Collection $staff, Collection $profiles, string $period): array
    {
        $month = Carbon::parse($period.'-01');
        $start = $month->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = now()->startOfDay();
        $startsOn = $this->timesheetStartsOn();
        $companyOff = array_flip($this->weekOffDays());
        $letters = [1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa', 7 => 'Su'];

        $days = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $dayKey = self::DAY_KEYS[$cursor->dayOfWeekIso - 1] ?? '';
            $days[] = [
                'date' => $cursor->toDateString(),
                'day' => $cursor->day,
                'letter' => $letters[$cursor->dayOfWeekIso] ?? '',
                'off' => isset($companyOff[$dayKey]),
                'today' => $cursor->isSameDay($today),
            ];
        }

        $userIds = $staff->pluck('id')->map(fn ($id) => (string) $id)->all();
        $holidayNames = [];
        if (Schema::hasTable('people_holidays')) {
            $holidays = PeopleHoliday::query()
                ->whereDate('holiday_on', '>=', $start->toDateString())
                ->whereDate('holiday_on', '<=', $end->toDateString())
                ->get(['holiday_on', 'name']);
            foreach ($holidays as $holiday) {
                $holidayNames[$holiday->holiday_on->toDateString()] = $holiday->name;
            }
        }

        $shorts = [];
        $names = [];
        if (Schema::hasTable('people_leave_types')) {
            foreach (PeopleLeaveType::query()->get(['code', 'name', 'short_name']) as $type) {
                $names[$type->code] = $type->name;
                $short = strtoupper(trim((string) $type->short_name));
                $shorts[$type->code] = $short !== '' ? mb_substr($short, 0, 3) : strtoupper(mb_substr((string) $type->code, 0, 2));
            }
        }

        $entriesByUser = [];
        if ($userIds !== [] && Schema::hasTable('people_timesheets')) {
            $sheets = PeopleTimesheet::query()
                ->whereIn('user_id', $userIds)
                ->whereDate('week_starts_on', '>=', $start->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
                ->whereDate('week_starts_on', '<=', $end->toDateString())
                ->get(['user_id', 'entries']);
            foreach ($sheets as $sheet) {
                foreach ($sheet->entries ?? [] as $date => $entry) {
                    if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! is_array($entry)) {
                        continue;
                    }
                    $entriesByUser[(string) $sheet->user_id][$date] = $entry;
                }
            }
        }

        $leaveByUser = [];
        if ($userIds !== [] && Schema::hasTable('people_leave_requests')) {
            $requests = PeopleLeaveRequest::query()
                ->whereIn('user_id', $userIds)
                ->whereIn('status', ['approved', 'pending'])
                ->whereDate('ends_on', '>=', $start->toDateString())
                ->whereDate('starts_on', '<=', $end->toDateString())
                ->get();
            foreach ($requests as $request) {
                $cursor = $request->starts_on->copy()->startOfDay();
                $leaveEnd = $request->ends_on->copy()->startOfDay();
                $half = $this->isHalfDayLeave($request);
                $label = $names[$request->leave_type] ?? $this->leaveLabel($request->leave_type);
                $code = $shorts[$request->leave_type] ?? 'L';
                if ($code === '') {
                    $code = 'L';
                }
                while ($cursor->lte($leaveEnd)) {
                    $key = $cursor->toDateString();
                    $cursor->addDay();
                    if ($key < $start->toDateString() || $key > $end->toDateString()) {
                        continue;
                    }
                    $existing = $leaveByUser[(string) $request->user_id][$key] ?? null;
                    if ($existing && $existing['status'] === 'approved' && $request->status !== 'approved') {
                        continue;
                    }
                    $leaveByUser[(string) $request->user_id][$key] = [
                        'status' => $request->status,
                        'half' => $half,
                        'code' => $half ? 'HD' : $code,
                        'label' => $label.($half ? ' · half day' : ''),
                    ];
                }
            }
        }

        $handsByUser = [];
        if ($userIds !== [] && Schema::hasTable('people_attendance_marks')) {
            $savedMarks = PeopleAttendanceMark::query()
                ->whereIn('user_id', $userIds)
                ->whereDate('marked_on', '>=', $start->toDateString())
                ->whereDate('marked_on', '<=', $end->toDateString())
                ->get(['user_id', 'marked_on', 'status']);
            foreach ($savedMarks as $mark) {
                $handsByUser[(string) $mark->user_id][$mark->marked_on->toDateString()] = $mark->status;
            }
        }

        $rows = [];
        foreach ($staff as $user) {
            $profile = $profiles->get($user->id);
            $user->setRelation('peopleProfile', $profile);
            $joined = $profile?->joined_on?->toDateString();
            $left = $profile?->last_working_day?->toDateString();
            if (($joined && $joined > $end->toDateString()) || ($left && $left < $start->toDateString())) {
                continue;
            }
            $joinedLabel = $joined ? Carbon::parse($joined)->format('j M Y') : '';
            $weekOff = array_flip($this->weekOffDays($user));
            $userEntries = $entriesByUser[(string) $user->id] ?? [];
            $userLeave = $leaveByUser[(string) $user->id] ?? [];
            $userHands = $handsByUser[(string) $user->id] ?? [];
            $skipsTimesheet = ! $this->requiresTimesheet($user);
            $cells = [];
            $counts = ['present' => 0, 'absent' => 0, 'leave' => 0, 'half' => 0];
            foreach ($days as $day) {
                $when = Carbon::parse($day['date']);
                $dayKey = self::DAY_KEYS[$when->dayOfWeekIso - 1] ?? '';
                $cell = $this->attendanceDay(
                    $when,
                    $today,
                    $startsOn,
                    $joined,
                    $joinedLabel,
                    $left,
                    isset($weekOff[$dayKey]),
                    $holidayNames[$day['date']] ?? null,
                    $userEntries[$day['date']] ?? null,
                    $userLeave[$day['date']] ?? null,
                    $userHands[$day['date']] ?? null,
                    $skipsTimesheet,
                );
                $cells[] = $cell;
                if (isset($counts[$cell['kind']])) {
                    $counts[$cell['kind']]++;
                }
            }
            $rows[] = [
                'user_id' => (string) $user->id,
                'name' => $this->displayName($user),
                'code' => trim((string) ($profile->employee_code ?? '')),
                'joined' => ($joined && $joined > $start->toDateString()) ? $joinedLabel : '',
                'cells' => $cells,
                'counts' => $counts,
            ];
        }

        return [
            'days' => $days,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $entry
     * @param  array{status: string, half: bool, code: string, label: string}|null  $leave
     * @return array{kind: string, mark: string, title: string, editable: bool, hand: ?string}
     */
    private function attendanceDay(
        CarbonInterface $day,
        CarbonInterface $today,
        ?CarbonInterface $startsOn,
        ?string $joined,
        string $joinedLabel,
        ?string $left,
        bool $recurringOff,
        ?string $holidayName,
        ?array $entry,
        ?array $leave,
        ?string $hand,
        bool $skipsTimesheet = false,
    ): array {
        $key = $day->toDateString();
        $when = $day->format('D j M Y');
        $hand = in_array($hand, ['present', 'absent', 'half'], true) ? $hand : null;
        $cell = function (string $kind, string $mark, string $title, bool $editable, ?string $chosen = null): array {
            return [
                'kind' => $kind,
                'mark' => $mark,
                'title' => $title,
                'editable' => $editable,
                'hand' => $chosen,
            ];
        };
        $employed = ($joined === null || $key >= $joined) && ($left === null || $key <= $left);
        if (! $employed) {
            if ($joined && $key < $joined) {
                return $cell('before', '—', $when.' · Joined '.$joinedLabel, false);
            }

            return $cell('after', '—', $when.' · After last working day', false);
        }

        $markedOff = is_array($entry) && ($entry['status'] ?? null) === 'week_off';
        if ($recurringOff || $markedOff) {
            return $cell('off', 'WO', $when.' · Week off', false);
        }
        if ($holidayName) {
            return $cell('holiday', 'H', $when.' · '.$holidayName, false);
        }
        if (! $day->gt($today) && $hand === 'present') {
            return $cell('present', 'P', $when.' · Present · Set by hand', true, 'present');
        }
        if (! $day->gt($today) && $hand === 'absent') {
            return $cell('absent', 'A', $when.' · Absent · Set by hand', true, 'absent');
        }
        if (! $day->gt($today) && $hand === 'half') {
            return $cell('half', 'HD', $when.' · Half day · Set by hand', true, 'half');
        }

        $open = ! $day->gt($today);
        if ($leave && $leave['status'] === 'approved') {
            $kind = $leave['half'] ? 'half' : 'leave';

            return $cell($kind, $leave['code'], $when.' · '.$leave['label'].' · Approved', $open);
        }

        if (is_array($entry) && ($entry['status'] ?? null) === 'submitted') {
            $rows = is_array($entry['rows'] ?? null) ? $entry['rows'] : [];
            $onlyLeave = $rows !== [] && collect($rows)->every(fn ($row) => is_array($row) && ($row['ticket_id'] ?? '') === 'leave');
            if (! empty($entry['half']) || ($leave['half'] ?? false)) {
                $label = $leave['label'] ?? 'Half day';

                return $cell('half', 'HD', $when.' · '.$label, $open);
            }
            if ($onlyLeave) {
                return $cell('leave', 'L', $when.' · Leave', $open);
            }

            return $cell('present', 'P', $when.' · Present', $open);
        }

        if ($leave && $leave['status'] === 'pending') {
            return $cell('pending', $leave['code'], $when.' · '.$leave['label'].' · Pending', $open);
        }

        if ($startsOn && $day->copy()->startOfDay()->lt($startsOn)) {
            return $cell('none', '', $when, $open);
        }
        if ($day->gt($today)) {
            return $cell('future', '', $when, false);
        }
        if ($day->isSameDay($today)) {
            return $cell('today', '', $when.' · Today', $open);
        }
        if ($skipsTimesheet) {
            return $cell('none', '', $when.' · No timesheet. Mark this day by hand.', $open);
        }

        return $cell('absent', 'A', $when.' · Absent', $open);
    }

    /**
     * Hand marks for people who do not fill a timesheet. An empty status clears that day.
     *
     * @param  array<string, string|null>  $marks
     */
    public function saveAttendanceMarks(User $actor, string $userId, string $period, array $marks): void
    {
        $start = Carbon::parse($period.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        if ($this->attendanceIsLocked($start)) {
            throw new \InvalidArgumentException('Attendance for '.$start->format('F Y').' is locked.');
        }

        $employee = User::query()->find($userId);
        if (! $employee || ! in_array($employee->user_type, ADMIN_USER_TYPES, true)) {
            throw new \InvalidArgumentException('That person is not an employee.');
        }

        $profile = PeopleProfile::query()->where('user_id', $employee->id)->first();
        $employee->setRelation('peopleProfile', $profile);
        $joined = $profile?->joined_on?->toDateString();
        $left = $profile?->last_working_day?->toDateString();
        $today = now()->startOfDay();
        $holidays = [];
        if (Schema::hasTable('people_holidays')) {
            $holidays = PeopleHoliday::query()
                ->whereDate('holiday_on', '>=', $start->toDateString())
                ->whereDate('holiday_on', '<=', $end->toDateString())
                ->pluck('name', 'holiday_on')
                ->mapWithKeys(fn ($name, $date) => [Carbon::parse($date)->toDateString() => $name])
                ->all();
        }
        $weekOffEntries = [];
        if (Schema::hasTable('people_timesheets')) {
            $sheets = PeopleTimesheet::query()
                ->where('user_id', $employee->id)
                ->whereDate('week_starts_on', '>=', $start->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
                ->whereDate('week_starts_on', '<=', $end->toDateString())
                ->get(['entries']);
            foreach ($sheets as $sheet) {
                foreach ($sheet->entries ?? [] as $date => $entry) {
                    if (is_string($date) && is_array($entry) && ($entry['status'] ?? null) === 'week_off') {
                        $weekOffEntries[$date] = true;
                    }
                }
            }
        }

        foreach ($marks as $date => $status) {
            $date = (string) $date;
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $day = Carbon::parse($date)->startOfDay();
            if ($day->lt($start) || $day->gt($end) || $day->gt($today)) {
                continue;
            }
            if (($joined && $date < $joined) || ($left && $date > $left)) {
                continue;
            }
            if ($this->isRecurringWeekOff($day, $employee) || isset($weekOffEntries[$date]) || isset($holidays[$date])) {
                continue;
            }

            $status = is_string($status) ? $status : '';
            if (! in_array($status, ['present', 'absent', 'half'], true)) {
                PeopleAttendanceMark::query()->where('user_id', $employee->id)->whereDate('marked_on', $date)->delete();

                continue;
            }

            PeopleAttendanceMark::query()->updateOrCreate(
                ['user_id' => $employee->id, 'marked_on' => $date],
                ['status' => $status, 'marked_by' => $actor->id]
            );
        }
    }

    /**
     * @return Collection<int, array{at: ?CarbonInterface, leave: string, what: string, how: string, why: string}>
     */
    public function leaveHistory(string $userId): Collection
    {
        $grants = PeopleLeaveGrant::query()->where('user_id', $userId)->get();
        $requests = PeopleLeaveRequest::query()->where('user_id', $userId)->get();
        $actorIds = $grants->pluck('granted_by')->merge($requests->pluck('decided_by'))->filter()->unique()->values();
        $actors = $actorIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $actorIds)->get()->keyBy('id');

        $rows = $grants->map(fn (PeopleLeaveGrant $grant) => $this->grantHistoryRow($grant, $actors));
        foreach ($requests as $request) {
            foreach ($this->requestHistoryRows($request, $actors, $userId) as $row) {
                $rows->push($row);
            }
        }

        return $rows
            ->sortByDesc(fn (array $row) => sprintf('%010d%s', $row['at']?->getTimestamp() ?? 0, $row['what']))
            ->values();
    }

    /**
     * @param  Collection<string, User>  $actors
     * @return array{at: ?CarbonInterface, leave: string, what: string, how: string, why: string}
     */
    private function grantHistoryRow(PeopleLeaveGrant $grant, Collection $actors): array
    {
        $days = round((float) $grant->days, 1);
        $amount = $this->dayPhrase(abs($days));
        $note = trim((string) $grant->note);
        $actor = $grant->granted_by ? $actors->get($grant->granted_by) : null;
        $actorName = $actor ? $this->displayName($actor) : null;

        [$how, $why] = match ($grant->source) {
            'monthly' => ['Monthly credit', $this->grantWhy($note, $grant->period, 'month')],
            'yearly' => ['Yearly credit', $this->grantWhy($note, $grant->period, 'year')],
            'carry' => ['Carried forward from last year', $note !== '' ? $note : 'Unused days from the year before'],
            'manual' => [$actorName ? 'Adjusted by hand by '.$actorName : 'Adjusted by hand', $note !== '' ? $note : '—'],
            default => [ucfirst(str_replace('_', ' ', (string) $grant->source)) ?: 'Credit', $note !== '' ? $note : '—'],
        };

        return [
            'at' => $grant->created_at,
            'leave' => $this->leaveLabel($grant->leave_type),
            'what' => ($days < 0 ? '−' : '+').$amount.' '.($days < 0 ? 'removed' : 'added'),
            'how' => $how,
            'why' => $why,
        ];
    }

    /**
     * @param  Collection<string, User>  $actors
     * @return list<array{at: ?CarbonInterface, leave: string, what: string, how: string, why: string}>
     */
    private function requestHistoryRows(PeopleLeaveRequest $request, Collection $actors, string $userId): array
    {
        $amount = $this->dayPhrase((float) $request->days);
        $dates = $this->leaveSpan($request);
        $reason = trim((string) $request->reason);
        $why = $reason !== '' ? $reason : '—';
        $leave = $this->leaveLabel($request->leave_type);
        $tracks = $this->leaveTracksBalance($request->leave_type);
        $rows = [[
            'at' => $request->created_at,
            'leave' => $leave,
            'what' => ($tracks ? '−'.$amount.' held' : 'Requested '.$amount).' for '.$dates,
            'how' => 'Leave requested',
            'why' => $why,
        ]];

        if ($request->status === 'pending' || ! $request->decided_at) {
            return $rows;
        }

        $decider = $request->decided_by ? $actors->get($request->decided_by) : null;
        $by = $decider ? ' by '.$this->displayName($decider) : '';
        $self = (string) $request->decided_by === $userId;
        $what = match ($request->status) {
            'approved' => $amount.' taken for '.$dates,
            'sent_back' => ($tracks ? '+'.$amount.' released' : 'Sent back').' for '.$dates,
            'cancelled' => $self
                ? (($tracks ? '+'.$amount.' released' : 'Withdrawn').' for '.$dates)
                : (($tracks ? '+'.$amount.' put back' : 'Cancelled').' for '.$dates),
            default => ucfirst(str_replace('_', ' ', $request->status)).' for '.$dates,
        };
        $how = match ($request->status) {
            'approved' => 'Approved'.$by,
            'sent_back' => 'Sent back'.$by,
            'cancelled' => ($self ? 'Withdrawn' : 'Cancelled').$by,
            default => ucfirst(str_replace('_', ' ', $request->status)).$by,
        };

        $decisionWhy = trim((string) $request->decision_note);

        $rows[] = [
            'at' => $request->decided_at,
            'leave' => $leave,
            'what' => $what,
            'how' => $how,
            'why' => $request->status === 'sent_back' && $decisionWhy !== '' ? $decisionWhy : $why,
        ];

        return $rows;
    }

    private function grantWhy(string $note, ?string $period, string $kind): string
    {
        $when = '';
        if ($kind === 'month' && preg_match('/^\d{4}-\d{2}$/', (string) $period)) {
            $when = 'For '.Carbon::parse($period.'-01')->format('F Y');
        } elseif ($kind === 'year' && preg_match('/^\d{4}$/', (string) $period)) {
            $when = 'For '.$period;
        }

        if ($note !== '' && $when !== '') {
            return $note.', '.$when;
        }

        return $note !== '' ? $note : ($when !== '' ? $when : '—');
    }

    private function dayPhrase(float $days): string
    {
        $label = PeopleLeavePolicy::formatDays($days);

        return $label.($label === '1' ? ' day' : ' days');
    }

    private function leaveSpan(PeopleLeaveRequest $request): string
    {
        $from = $request->starts_on->format('j M Y');
        $to = $request->ends_on->format('j M Y');

        return $from === $to ? $from : $from.' to '.$to;
    }

    public static function leaveTaskLabel(?string $name = null): string
    {
        $name = trim((string) $name);

        return $name === '' ? 'On Leave' : 'On Leave ('.$name.')';
    }

    public function leaveLabel(string $type): string
    {
        $this->leaveLabels ??= PeopleLeaveType::query()->pluck('name', 'code')->all();

        return $this->leaveLabels[$type] ?? (self::LEAVE_TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type)));
    }

    public function leaveTracksBalance(string $type): bool
    {
        $tracks = PeopleLeaveType::query()->where('code', $type)->value('tracks_balance');

        return $tracks === null ? $type !== 'unpaid' : (bool) $tracks;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'sent_back' => 'Sent back',
            'submitted' => 'Submitted',
            'unsubmitted' => 'Unsubmitted',
            'upcoming' => 'Upcoming',
            'holiday' => 'Holiday',
            'week_off' => 'Week off',
            'before' => 'Not open',
            'draft' => 'Draft',
            'published' => 'Published',
            'verified' => 'On file',
            'missing' => 'Missing',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'held' => 'Left out',
            'locked' => 'Locked',
            'notice' => 'On notice',
            'exited' => 'Exited',
            'active' => 'Active',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function payrollRunLabel(?string $status): string
    {
        return match ($status) {
            'draft' => 'Not sent yet',
            'published' => 'Payslips sent',
            'locked' => 'Locked',
            default => 'Not calculated',
        };
    }

    public function payrollStatusLabel(string $status, bool $leftOut): string
    {
        if ($leftOut) {
            return 'Left out';
        }

        return match ($status) {
            'draft' => 'Will be paid',
            'published' => 'Payslip sent',
            default => $this->statusLabel($status),
        };
    }

    public function nextHoliday(): ?PeopleHoliday
    {
        return PeopleHoliday::query()
            ->whereDate('holiday_on', '>=', now()->toDateString())
            ->orderBy('holiday_on')
            ->first();
    }

    private function nextCode(): string
    {
        $max = PeopleProfile::query()->pluck('employee_code')->reduce(function (int $carry, $code) {
            if (preg_match('/(\d+)$/', (string) $code, $matches)) {
                return max($carry, (int) $matches[1]);
            }

            return $carry;
        }, 0);

        return self::formatCode('PK'.($max + 1));
    }

    public static function formatCode(?string $code): string
    {
        if ($code === null || trim($code) === '') {
            return '—';
        }

        if (preg_match('/(\d+)$/', $code, $matches)) {
            return 'PK'.str_pad((string) ((int) $matches[1]), 3, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    public function notifyLeaveSubmitted(User $employee, PeopleLeaveRequest $leave): void
    {
        try {
            $this->notifyApprovers(
                $employee,
                UserNotification::TYPE_LEAVE_REQUEST,
                'Leave request from '.$this->personName($employee),
                $this->leaveLabel($leave->leave_type).' · '.$this->leaveSpan($leave),
                $this->approvalUrl($employee, 'leaves'),
                'people_leave_request',
                (string) $leave->id,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyLeaveDecided(User $actor, PeopleLeaveRequest $request): void
    {
        if ((string) $actor->id === (string) $request->user_id) {
            return;
        }

        try {
            $body = $this->leaveLabel($request->leave_type).' · '.$this->leaveSpan($request);
            $note = trim((string) $request->decision_note);
            if ($note !== '') {
                $body .= ' — '.$note;
            }
            $this->notifyPerson(
                (string) $request->user_id,
                UserNotification::TYPE_LEAVE_DECIDED,
                'Your leave was '.$this->statusLabel((string) $request->status),
                $body,
                route('admin.people.index'),
                'people_leave_decided',
                (string) $request->id.':'.(string) $request->status.':'.($request->decided_at?->getTimestamp() ?? time()),
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyTimesheetSubmitted(User $employee, PeopleTimesheet $sheet): void
    {
        try {
            $week = $sheet->week_starts_on?->format('j M Y') ?? '';
            $this->notifyApprovers(
                $employee,
                UserNotification::TYPE_TIMESHEET_SUBMITTED,
                'Timesheet from '.$this->personName($employee),
                $week !== '' ? 'Week of '.$week : 'A timesheet is waiting for a decision.',
                $this->approvalUrl($employee, 'timesheet'),
                'people_timesheet',
                (string) $sheet->id.':'.($sheet->updated_at?->getTimestamp() ?? time()),
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyTimesheetDecided(User $actor, PeopleTimesheet $sheet): void
    {
        if ((string) $actor->id === (string) $sheet->user_id) {
            return;
        }

        try {
            $week = $sheet->week_starts_on?->format('j M Y') ?? '';
            $body = $week !== '' ? 'Week of '.$week : '';
            $why = trim((string) ($sheet->decision_note ?? ''));
            if ($why !== '') {
                $body = trim($body.' '.$why);
            }
            $this->notifyPerson(
                (string) $sheet->user_id,
                UserNotification::TYPE_TIMESHEET_DECIDED,
                'Your timesheet was '.$this->statusLabel((string) $sheet->status),
                $body !== '' ? $body : null,
                route('admin.people.index', ['section' => 'timesheet']),
                'people_timesheet_decided',
                (string) $sheet->id.':'.(string) $sheet->status.':'.($sheet->decided_at?->getTimestamp() ?? time()),
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyDocumentSubmitted(User $employee, PeopleDocument $document): void
    {
        try {
            foreach ($this->hrUserIds((string) $employee->id) as $userId) {
                $this->notifyPerson(
                    $userId,
                    UserNotification::TYPE_DOCUMENT_SUBMITTED,
                    'Document from '.$this->displayName($employee),
                    (string) $document->title,
                    route('admin.hr.index', ['section' => 'person', 'user' => $employee->id, 'tab' => 'documents']),
                    'people_document',
                    (string) $document->id.':'.($document->uploaded_at?->getTimestamp() ?? time()),
                );
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyDocumentDecided(PeopleDocument $document): void
    {
        try {
            $note = trim((string) $document->rejection_note);
            $body = (string) $document->title;
            if ($note !== '') {
                $body .= ' — '.$note;
            }
            $title = $document->status === 'rejected'
                ? 'Your document was sent back'
                : 'Your document was accepted';
            $this->notifyPerson(
                (string) $document->user_id,
                UserNotification::TYPE_DOCUMENT_DECIDED,
                $title,
                $body,
                route('admin.people.index', ['section' => 'documents']),
                'people_document_decided',
                (string) $document->id.':'.(string) $document->status.':'.time(),
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyDocumentRequested(PeopleDocument $document): void
    {
        try {
            $this->notifyPerson(
                (string) $document->user_id,
                UserNotification::TYPE_DOCUMENT_DECIDED,
                'HR asked for a document',
                (string) $document->title,
                route('admin.people.index', ['section' => 'documents']),
                'people_document_requested',
                (string) $document->id,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function notifyOutstandingApprovals(): void
    {
        if (! Schema::hasTable('user_notifications') || ! Schema::hasTable('people_leave_requests') || ! Schema::hasTable('people_timesheets')) {
            return;
        }

        PeopleLeaveRequest::query()
            ->with('user')
            ->where('status', 'pending')
            ->get()
            ->each(function (PeopleLeaveRequest $leave) {
                if (! $leave->user || $this->approvalAlreadySent(UserNotification::TYPE_LEAVE_REQUEST, 'people_leave_request', (string) $leave->id)) {
                    return;
                }
                $this->notifyLeaveSubmitted($leave->user, $leave);
            });

        PeopleTimesheet::query()
            ->with('user')
            ->where('status', 'pending')
            ->get()
            ->each(function (PeopleTimesheet $sheet) {
                if (! $sheet->user || $this->approvalAlreadySent(UserNotification::TYPE_TIMESHEET_SUBMITTED, 'people_timesheet', (string) $sheet->id)) {
                    return;
                }
                $this->notifyTimesheetSubmitted($sheet->user, $sheet);
            });
    }

    private function approvalUrl(User $employee, string $tab): string
    {
        $managerId = PeopleProfile::query()->where('user_id', $employee->id)->value('manager_id');
        if ($managerId && (string) $managerId !== (string) $employee->id) {
            return route('admin.people.approvals', ['tab' => $tab]);
        }

        return $tab === 'timesheet'
            ? route('admin.hr.attendance')
            : route('admin.people.records');
    }

    private function approvalAlreadySent(string $type, string $referenceType, string $referenceId): bool
    {
        return UserNotification::query()
            ->where('type', $type)
            ->where('reference_type', $referenceType)
            ->where(function ($query) use ($referenceId) {
                $query->where('reference_id', $referenceId)
                    ->orWhere('reference_id', 'like', $referenceId.':%');
            })
            ->exists();
    }

    private function notifyApprovers(User $employee, string $type, string $title, string $body, string $actionUrl, string $referenceType, string $referenceId): void
    {
        foreach ($this->approverIds($employee) as $userId) {
            $this->notifyPerson($userId, $type, $title, $body, $actionUrl, $referenceType, $referenceId);
        }
    }

    private function notifyPerson(string $userId, string $type, string $title, ?string $body, string $actionUrl, string $referenceType, string $referenceId): void
    {
        if ($userId === '') {
            return;
        }

        try {
            app(AdminInboxNotificationService::class)->notifyUser(
                $userId,
                $type,
                $title,
                $body,
                $actionUrl,
                $referenceType,
                $referenceId,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array<int, string>
     */
    private function approverIds(User $employee): array
    {
        $managerId = PeopleProfile::query()->where('user_id', $employee->id)->value('manager_id');
        if ($managerId && (string) $managerId !== (string) $employee->id) {
            $active = User::query()->whereKey($managerId)->where('is_active', 1)->exists();
            if ($active) {
                return [(string) $managerId];
            }
        }

        return $this->hrUserIds((string) $employee->id);
    }

    /**
     * @return array<int, string>
     */
    private function hrUserIds(string $exceptUserId): array
    {
        return User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->when($exceptUserId !== '', fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->where(function ($query) {
                $query->where('user_type', 'super-admin')
                    ->orWhereHas('roles', function ($roles) {
                        $roles->where('role_name', 'like', '%hr%');
                    });
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function personName(User $user): string
    {
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : (string) ($user->email ?? 'Someone');
    }
}
