<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleDepartmentLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveAssignment;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveGrant;
use Modules\AdminModule\Entities\PeopleLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleStageLeavePolicy;

class PeopleLeaveAccrual
{

    public static function firstDueOn(string $accrualType, CarbonInterface $assignedOn): Carbon
    {
        return Carbon::parse($assignedOn->toDateString())->startOfDay();
    }

    public static function nextDueOn(string $accrualType, CarbonInterface $dueOn): Carbon
    {
        $on = Carbon::parse($dueOn->toDateString())->startOfDay();

        if ($accrualType === 'monthly') {
            return $on->copy()->startOfMonth()->addMonth();
        }

        $next = $on->copy()->addYear()->startOfYear();

        return $next->toDateString() === $on->toDateString() ? $on->copy()->addYear() : $next;
    }

    public static function monthsLeftInLeaveYear(CarbonInterface $on): int
    {
        $on = Carbon::parse($on->toDateString())->startOfDay();
        $start = $on->day <= 15 ? (int) $on->month : (int) $on->month + 1;

        return $start > 12 ? 0 : 13 - $start;
    }

    public static function proratedYearDays(float $annual, CarbonInterface $on): float
    {
        $share = $annual * self::monthsLeftInLeaveYear($on) / 12;

        return round(round($share * 2) / 2, 1);
    }

    public function assign(PeopleProfile $profile, PeopleLeavePolicy $policy, CarbonInterface $on, string $source = 'employee'): bool
    {
        $on = Carbon::parse($on->toDateString())->startOfDay();
        $current = PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->where('leave_policy_id', $policy->id)
            ->first();

        if ($source !== 'employee' && $current && $current->via_employee) {
            return false;
        }
        if ($source === 'department' && $current && $current->via_stage) {
            return false;
        }
        if ($this->blockedByHigherSource($profile, $policy, $source)) {
            return false;
        }

        $alreadyWaiting = $current
            && $current->next_accrual_on
            && $current->next_accrual_on->copy()->startOfDay()->gt($on);

        $this->deleteLowerAssignments($profile, $policy, $source, $current?->id);

        $assignment = $current ?? new PeopleLeaveAssignment([
            'user_id' => $profile->user_id,
            'leave_policy_id' => $policy->id,
            'via_employee' => false,
            'via_department' => false,
            'via_stage' => false,
        ]);
        if ($source === 'department') {
            $assignment->via_department = true;
        } elseif ($source === 'stage') {
            $assignment->via_stage = true;
        } else {
            $assignment->via_employee = true;
        }

        if ($policy->accrual_type === 'yearly' && ! $alreadyWaiting) {
            $assignment->next_accrual_on = self::nextDueOn('yearly', $on)->toDateString();
            $assignment->save();
            $this->creditYearlyShare($profile, $policy, $on);
            $this->applyDue($on, $profile->user_id);

            return true;
        }

        if (! $alreadyWaiting) {
            $assignment->next_accrual_on = self::firstDueOn($policy->accrual_type, $on)->toDateString();
        }
        $assignment->save();
        $this->applyDue($on, $profile->user_id);

        return true;
    }

    public function attachDepartment(PeopleDepartment $department, PeopleLeavePolicy $policy, CarbonInterface $on): int
    {
        $replaced = PeopleDepartmentLeavePolicy::query()
            ->where('department_id', $department->id)
            ->where('leave_policy_id', '!=', $policy->id)
            ->whereHas('policy', fn ($query) => $query->where('leave_type_id', $policy->leave_type_id))
            ->with('policy')
            ->get();
        foreach ($replaced as $link) {
            if ($link->policy) {
                $this->detachDepartment($department, $link->policy);
            }
        }

        PeopleDepartmentLeavePolicy::query()->firstOrCreate([
            'department_id' => $department->id,
            'leave_policy_id' => $policy->id,
        ]);

        $count = 0;
        $profiles = PeopleProfile::query()
            ->with('user')
            ->where('department', $department->name)
            ->where(function ($query) {
                $query->whereNull('employment_status')->orWhere('employment_status', '!=', 'exited');
            })
            ->get();
        foreach ($profiles as $profile) {
            if (! $profile->user || ! $profile->user->is_active) {
                continue;
            }
            $this->assign($profile, $policy, $on, 'department');
            $count++;
        }

        return $count;
    }

    public function attachStage(string $stage, PeopleLeavePolicy $policy, CarbonInterface $on): int
    {
        $replaced = PeopleStageLeavePolicy::query()
            ->where('employment_stage', $stage)
            ->where('leave_policy_id', '!=', $policy->id)
            ->whereHas('policy', fn ($query) => $query->where('leave_type_id', $policy->leave_type_id))
            ->with('policy')
            ->get();
        foreach ($replaced as $link) {
            if ($link->policy) {
                $this->detachStage($stage, $link->policy);
            }
        }

        PeopleStageLeavePolicy::query()->firstOrCreate([
            'employment_stage' => $stage,
            'leave_policy_id' => $policy->id,
        ]);

        $count = 0;
        $profiles = PeopleProfile::query()
            ->with('user')
            ->where('employment_stage', $stage)
            ->where(function ($query) {
                $query->whereNull('employment_status')->orWhere('employment_status', '!=', 'exited');
            })
            ->get();
        foreach ($profiles as $profile) {
            if (! $profile->user || ! $profile->user->is_active) {
                continue;
            }
            if ($this->assign($profile, $policy, $on, 'stage')) {
                $count++;
            }
        }

        return $count;
    }

    public function detachStage(string $stage, PeopleLeavePolicy $policy): void
    {
        PeopleStageLeavePolicy::query()
            ->where('employment_stage', $stage)
            ->where('leave_policy_id', $policy->id)
            ->delete();

        $userIds = PeopleProfile::query()->where('employment_stage', $stage)->pluck('user_id');
        $assignments = PeopleLeaveAssignment::query()
            ->where('leave_policy_id', $policy->id)
            ->whereIn('user_id', $userIds)
            ->get();
        foreach ($assignments as $assignment) {
            $this->releaseStageSource($assignment);
        }
    }

    public function detachDepartment(PeopleDepartment $department, PeopleLeavePolicy $policy): void
    {
        PeopleDepartmentLeavePolicy::query()
            ->where('department_id', $department->id)
            ->where('leave_policy_id', $policy->id)
            ->delete();

        $userIds = PeopleProfile::query()->where('department', $department->name)->pluck('user_id');
        $assignments = PeopleLeaveAssignment::query()
            ->where('leave_policy_id', $policy->id)
            ->whereIn('user_id', $userIds)
            ->get();
        foreach ($assignments as $assignment) {
            $this->releaseDepartmentSource($assignment);
        }
    }

    public function syncPersonDepartment(PeopleProfile $profile, string $previousName, string $nextName): void
    {
        if ($previousName === $nextName) {
            return;
        }

        $this->dropDepartmentPolicies($profile, $previousName);
        if ($profile->employment_status === 'exited' || $nextName === '') {
            return;
        }

        $department = PeopleDepartment::query()->where('name', $nextName)->first();
        if (! $department) {
            return;
        }

        $links = PeopleDepartmentLeavePolicy::query()
            ->with('policy')
            ->where('department_id', $department->id)
            ->get();
        foreach ($links as $link) {
            if ($link->policy) {
                $this->assign($profile, $link->policy, now(), 'department');
            }
        }
    }

    public function syncPersonStage(PeopleProfile $profile, string $previous, string $next): void
    {
        if ($previous === $next) {
            return;
        }

        $this->dropStagePolicies($profile, $previous);
        if ($profile->employment_status === 'exited' || $next === '') {
            return;
        }

        $this->applyStagePolicies($profile, now());
    }

    public function releaseAssignment(PeopleLeaveAssignment $assignment): void
    {
        $profile = PeopleProfile::query()->where('user_id', $assignment->user_id)->first();
        $assignment->delete();
        if (! $profile || $profile->employment_status === 'exited') {
            return;
        }

        $this->applyStagePolicies($profile, now());
        $this->applyDepartmentPolicies($profile, now());
    }

    private function dropDepartmentPolicies(PeopleProfile $profile, string $departmentName): void
    {
        if ($departmentName === '') {
            return;
        }

        $department = PeopleDepartment::query()->where('name', $departmentName)->first();
        if (! $department) {
            return;
        }

        $policyIds = PeopleDepartmentLeavePolicy::query()
            ->where('department_id', $department->id)
            ->pluck('leave_policy_id');
        $assignments = PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->whereIn('leave_policy_id', $policyIds)
            ->get();
        foreach ($assignments as $assignment) {
            $this->releaseDepartmentSource($assignment);
        }
    }

    private function releaseDepartmentSource(PeopleLeaveAssignment $assignment): void
    {
        if ($assignment->via_employee) {
            $assignment->via_department = false;
            $assignment->save();

            return;
        }

        $assignment->delete();
    }

    private function releaseStageSource(PeopleLeaveAssignment $assignment): void
    {
        if ($assignment->via_employee || $assignment->via_department) {
            $assignment->via_stage = false;
            $assignment->save();

            return;
        }

        $assignment->delete();
    }

    private function dropStagePolicies(PeopleProfile $profile, string $stage): void
    {
        if ($stage === '') {
            return;
        }

        $policyIds = PeopleStageLeavePolicy::query()
            ->where('employment_stage', $stage)
            ->pluck('leave_policy_id');
        $assignments = PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->whereIn('leave_policy_id', $policyIds)
            ->get();
        foreach ($assignments as $assignment) {
            $this->releaseStageSource($assignment);
        }
    }

    private function applyStagePolicies(PeopleProfile $profile, CarbonInterface $on): void
    {
        $stage = $profile->employment_stage ?: 'permanent';
        $links = PeopleStageLeavePolicy::query()
            ->with('policy')
            ->where('employment_stage', $stage)
            ->get();
        foreach ($links as $link) {
            if ($link->policy) {
                $this->assign($profile, $link->policy, $on, 'stage');
            }
        }
    }

    private function applyDepartmentPolicies(PeopleProfile $profile, CarbonInterface $on): void
    {
        if ($profile->department === null || $profile->department === '') {
            return;
        }

        $department = PeopleDepartment::query()->where('name', $profile->department)->first();
        if (! $department) {
            return;
        }

        $links = PeopleDepartmentLeavePolicy::query()
            ->with('policy')
            ->where('department_id', $department->id)
            ->get();
        foreach ($links as $link) {
            if ($link->policy) {
                $this->assign($profile, $link->policy, $on, 'department');
            }
        }
    }

    private function blockedByHigherSource(PeopleProfile $profile, PeopleLeavePolicy $policy, string $source): bool
    {
        if ($source === 'employee') {
            return false;
        }

        $query = PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->where('leave_policy_id', '!=', $policy->id)
            ->whereHas('policy', fn ($builder) => $builder->where('leave_type_id', $policy->leave_type_id));

        if ($source === 'stage') {
            return $query->where('via_employee', true)->exists();
        }

        return $query->where(function ($builder) {
            $builder->where('via_employee', true)->orWhere('via_stage', true);
        })->exists();
    }

    private function deleteLowerAssignments(PeopleProfile $profile, PeopleLeavePolicy $policy, string $source, ?string $keepId): void
    {
        $query = PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->when($keepId, fn ($builder) => $builder->where('id', '!=', $keepId))
            ->whereHas('policy', fn ($builder) => $builder->where('leave_type_id', $policy->leave_type_id));

        if ($source === 'stage') {
            $query->where('via_employee', false);
        } elseif ($source === 'department') {
            $query->where('via_employee', false)->where('via_stage', false);
        }

        $query->delete();
    }

    private function creditYearlyShare(PeopleProfile $profile, PeopleLeavePolicy $policy, CarbonInterface $on): void
    {
        $type = $policy->leaveType;
        $days = self::proratedYearDays((float) $policy->days, $on);
        if (! $type || ! $type->tracks_balance || $days <= 0) {
            return;
        }

        $this->addDays($profile->user_id, (int) $on->year, $type->code, $days, 'yearly', $on->format('Y'), null, $policy->name);
    }

    public function grant(string $userId, string $type, float $days, ?string $grantedBy, ?string $note, ?CarbonInterface $on = null): void
    {
        $this->adjust($userId, $type, $days, 'add', $grantedBy, $note, $on);
    }

    public function adjust(string $userId, string $type, float $days, string $direction, ?string $grantedBy, ?string $note, ?CarbonInterface $on = null): void
    {
        $on = Carbon::parse(($on ?? now())->toDateString())->startOfDay();
        $days = round($days, 1);
        $tracks = PeopleLeaveType::query()->where('code', $type)->where('tracks_balance', true)->exists();
        if (! in_array($direction, ['add', 'remove'], true)) {
            throw new \InvalidArgumentException('Choose whether to add or remove leave.');
        }
        if ($days <= 0 || ! $tracks) {
            throw new \InvalidArgumentException('Choose a leave type and a number of days above zero.');
        }

        if ($direction === 'remove') {
            $this->removeDays($userId, (int) $on->year, $type, $days, $grantedBy, $note);

            return;
        }

        $this->addDays($userId, (int) $on->year, $type, $days, 'manual', (string) Str::uuid(), $grantedBy, $note);
    }

    public function carryForward(CarbonInterface $today, ?string $onlyUserId = null): int
    {
        $today = Carbon::parse($today->toDateString())->startOfDay();
        $year = (int) $today->year;
        $query = PeopleLeaveAssignment::query()
            ->whereHas('profile', function ($builder) {
                $builder->whereNull('employment_status')->orWhere('employment_status', '!=', 'exited');
            })
            ->whereHas('user', fn ($user) => $user->where('is_active', 1))
            ->with('policy.leaveType');
        if ($onlyUserId) {
            $query->where('user_id', $onlyUserId);
        }

        $credited = 0;
        foreach ($query->get() as $assignment) {
            $policy = $assignment->policy;
            $type = $policy?->leaveType;
            $limit = round((float) ($policy->carry_limit ?? 0), 1);
            if (! $policy || ! $type || ! $type->tracks_balance || $limit <= 0) {
                continue;
            }

            $previous = PeopleLeaveBalance::query()
                ->where('user_id', $assignment->user_id)
                ->where('year', $year - 1)
                ->first();
            if (! $previous) {
                continue;
            }

            $days = min($limit, max(0, $previous->remaining($type->code)));
            $days = round(round($days * 2) / 2, 1);
            if ($days <= 0) {
                continue;
            }

            if ($this->addDays($assignment->user_id, $year, $type->code, $days, 'carry', (string) $year, null, $policy->name)) {
                $credited++;
            }
        }

        return $credited;
    }

    public function applyDue(CarbonInterface $today, ?string $onlyUserId = null): int
    {
        $today = Carbon::parse($today->toDateString())->startOfDay();
        $this->carryForward($today, $onlyUserId);
        $query = PeopleLeaveAssignment::query()
            ->whereNotNull('next_accrual_on')
            ->whereDate('next_accrual_on', '<=', $today->toDateString())
            ->whereHas('profile', function ($builder) {
                $builder->whereNull('employment_status')->orWhere('employment_status', '!=', 'exited');
            })
            ->whereHas('user', fn ($user) => $user->where('is_active', 1))
            ->with('policy.leaveType');

        if ($onlyUserId) {
            $query->where('user_id', $onlyUserId);
        }

        $credited = 0;
        foreach ($query->get() as $assignment) {
            $policy = $assignment->policy;
            $type = $policy?->leaveType;
            if (! $policy || ! $type) {
                continue;
            }

            $guard = 0;
            while ($assignment->next_accrual_on && $assignment->next_accrual_on->copy()->startOfDay()->lte($today) && $guard < 36) {
                $due = $assignment->next_accrual_on->copy()->startOfDay();
                $period = $policy->accrual_type === 'monthly' ? $due->format('Y-m') : $due->format('Y');
                $days = round((float) $policy->days, 1);
                if ($type->tracks_balance && $days > 0 && $this->addDays($assignment->user_id, (int) $due->year, $type->code, $days, $policy->accrual_type, $period, null, $policy->name)) {
                    $credited++;
                }
                $assignment->next_accrual_on = self::nextDueOn($policy->accrual_type, $due);
                $guard++;
            }

            $assignment->save();
        }

        return $credited;
    }

    private function removeDays(string $userId, int $year, string $type, float $days, ?string $grantedBy, ?string $note): void
    {
        DB::transaction(function () use ($userId, $year, $type, $days, $grantedBy, $note) {
            $balance = PeopleLeaveBalance::query()
                ->where('user_id', $userId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();
            $left = $balance ? $balance->remaining($type) : 0.0;
            if ($days > $left) {
                $shown = PeopleLeavePolicy::formatDays($left);
                throw new \InvalidArgumentException($left > 0
                    ? 'Only '.$shown.($left == 1.0 ? ' day' : ' days').' can be removed.'
                    : 'There are no days left to remove.');
            }

            PeopleLeaveGrant::query()->create([
                'user_id' => $userId,
                'year' => $year,
                'leave_type' => $type,
                'days' => round(-1 * $days, 1),
                'source' => 'manual',
                'period' => (string) Str::uuid(),
                'note' => $note,
                'granted_by' => $grantedBy,
            ]);
            $balance->addAllowance($type, -1 * $days);
            $balance->save();
        });
    }

    private function addDays(string $userId, int $year, string $type, float $days, string $source, string $period, ?string $grantedBy, ?string $note): bool
    {
        return DB::transaction(function () use ($userId, $year, $type, $days, $source, $period, $grantedBy, $note) {
            $grant = PeopleLeaveGrant::query()->firstOrCreate(
                [
                    'user_id' => $userId,
                    'leave_type' => $type,
                    'source' => $source,
                    'period' => $period,
                ],
                [
                    'year' => $year,
                    'days' => $days,
                    'note' => $note,
                    'granted_by' => $grantedBy,
                ]
            );

            if (! $grant->wasRecentlyCreated) {
                return false;
            }

            $balance = PeopleLeaveBalance::query()->firstOrCreate(
                ['user_id' => $userId, 'year' => $year],
                [
                    'casual_allowance' => 0,
                    'sick_allowance' => 0,
                    'earned_allowance' => 0,
                ]
            );
            $balance->addAllowance($type, $days);
            $balance->save();

            return true;
        });
    }
}
