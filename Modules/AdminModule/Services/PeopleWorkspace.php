<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleTimesheet;
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

    public const DOCUMENT_TITLES = [
        'Aadhaar',
        'PAN',
        'Cancelled cheque',
        'Address proof',
    ];

    public const WEEK_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public function boot(): void
    {
        $this->staffUsers()->each(fn (User $user) => $this->ensureStaffFile($user));
        app(PeopleLeaveAccrual::class)->applyDue(now());
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
     */
    public static function countWorkingDays(CarbonInterface $from, CarbonInterface $to, array $holidayDates): int
    {
        $holidays = array_flip($holidayDates);
        $days = 0;
        $cursor = Carbon::parse($from->toDateString())->startOfDay();
        $end = Carbon::parse($to->toDateString())->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            if (! $cursor->isSunday() && ! isset($holidays[$key])) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    public function workingDays(CarbonInterface $from, CarbonInterface $to): int
    {
        $holidays = PeopleHoliday::query()
            ->whereDate('holiday_on', '>=', $from->toDateString())
            ->whereDate('holiday_on', '<=', $to->toDateString())
            ->pluck('holiday_on')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        return self::countWorkingDays($from, $to, $holidays);
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
        return PeopleTimesheet::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'week_starts_on' => $weekStart->toDateString(),
            ],
            [
                'hours' => array_fill_keys(self::WEEK_DAYS, 0),
                'status' => 'draft',
            ]
        );
    }

    public function balance(User $user): PeopleLeaveBalance
    {
        return PeopleLeaveBalance::query()->firstOrCreate(
            ['user_id' => $user->id, 'year' => (int) now()->year],
            [
                'casual_allowance' => 0,
                'sick_allowance' => 0,
                'earned_allowance' => 0,
            ]
        );
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
        if ($this->isHr($actor)) {
            return true;
        }

        $managerId = PeopleProfile::query()->where('user_id', $employee->id)->value('manager_id');

        return $managerId && (string) $managerId === (string) $actor->id;
    }

    public function canReadFile(User $actor, User $employee): bool
    {
        if ((string) $actor->id === (string) $employee->id) {
            return true;
        }

        return $this->canDecideFor($actor, $employee);
    }

    public function submitLeave(User $user, string $type, CarbonInterface $from, CarbonInterface $to, string $reason, bool $halfDay = false): PeopleLeaveRequest
    {
        if ($halfDay && $from->toDateString() !== $to->toDateString()) {
            throw new \InvalidArgumentException('A half day is a single date.');
        }

        $days = $halfDay ? 0.5 : (float) $this->workingDays($from, $to);
        if ($days <= 0) {
            throw new \InvalidArgumentException('Those dates are already off. Pick a working day.');
        }

        if ($this->leaveTracksBalance($type)) {
            $balance = $this->balance($user);
            if ($balance->remaining($type) < $days) {
                throw new \InvalidArgumentException('You do not have enough '.$this->leaveLabel($type).' leave for those dates.');
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

        return PeopleLeaveRequest::query()->create([
            'user_id' => $user->id,
            'leave_type' => $type,
            'starts_on' => $from->toDateString(),
            'ends_on' => $to->toDateString(),
            'days' => $days,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function decideLeave(User $actor, PeopleLeaveRequest $request, string $decision): void
    {
        $employee = $request->user;
        if (! $employee || ! $this->canDecideFor($actor, $employee)) {
            throw new \InvalidArgumentException('You cannot decide this leave request.');
        }

        if ($request->status !== 'pending') {
            throw new \InvalidArgumentException('This request is already decided.');
        }

        if ($decision === 'sent_back') {
            $request->forceFill([
                'status' => 'sent_back',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ])->save();

            return;
        }

        DB::transaction(function () use ($actor, $request, $employee) {
            $balance = PeopleLeaveBalance::query()
                ->where('user_id', $employee->id)
                ->where('year', (int) $request->starts_on->year)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw new \InvalidArgumentException('This person has no leave balance for that year.');
            }

            $type = $request->leave_type;
            if ($this->leaveTracksBalance($type)) {
                if ($balance->remaining($type) < (float) $request->days) {
                    throw new \InvalidArgumentException('There is not enough '.$this->leaveLabel($type).' leave left to approve this.');
                }

                $balance->addUsed($type, (float) $request->days);
                $balance->save();
            }

            $request->forceFill([
                'status' => 'approved',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ])->save();
        });
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
                    $balance = PeopleLeaveBalance::query()
                        ->where('user_id', $request->user_id)
                        ->where('year', (int) $request->starts_on->year)
                        ->lockForUpdate()
                        ->first();
                    if ($balance) {
                        $balance->restoreUsed($request->leave_type, (float) $request->days);
                        $balance->save();
                    }
                }

                $request->forceFill([
                    'status' => 'cancelled',
                    'decided_by' => $actor->id,
                    'decided_at' => now(),
                ])->save();
            });

            return;
        }

        if ((string) $actor->id !== (string) $request->user_id || $request->status !== 'pending') {
            throw new \InvalidArgumentException('You can only cancel a request that is still waiting.');
        }

        $request->forceFill([
            'status' => 'cancelled',
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ])->save();
    }

    public function decideTimesheet(User $actor, PeopleTimesheet $sheet, string $decision): void
    {
        $employee = $sheet->user;
        if (! $employee || ! $this->canDecideFor($actor, $employee)) {
            throw new \InvalidArgumentException('You cannot decide this timesheet.');
        }

        if ($sheet->status !== 'pending') {
            throw new \InvalidArgumentException('This timesheet is not waiting for a decision.');
        }

        $sheet->forceFill([
            'status' => $decision === 'approve' ? 'approved' : 'sent_back',
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ])->save();
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
            'draft' => 'Draft',
            'published' => 'Published',
            'verified' => 'On file',
            'missing' => 'Missing',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'held' => 'Held',
            'locked' => 'Locked',
            'notice' => 'On notice',
            'exited' => 'Exited',
            'active' => 'Active',
            default => ucfirst(str_replace('_', ' ', $status)),
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

        return 'PK-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
