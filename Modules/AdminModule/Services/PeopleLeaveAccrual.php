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

class PeopleLeaveAccrual
{

    public static function firstDueOn(string $accrualType, CarbonInterface $assignedOn): Carbon
    {
        return Carbon::parse($assignedOn->toDateString())->startOfDay();
    }

    public static function nextDueOn(string $accrualType, CarbonInterface $dueOn): Carbon
    {
        $on = Carbon::parse($dueOn->toDateString())->startOfDay();

        return $accrualType === 'monthly' ? $on->copy()->addMonthNoOverflow() : $on->copy()->addYearNoOverflow();
    }

    public function assign(PeopleProfile $profile, PeopleLeavePolicy $policy, CarbonInterface $on, string $source = 'employee'): void
    {
        $on = Carbon::parse($on->toDateString())->startOfDay();
        $current = PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->where('leave_policy_id', $policy->id)
            ->first();
        $alreadyWaiting = $current
            && $current->next_accrual_on
            && $current->next_accrual_on->copy()->startOfDay()->gt($on);

        PeopleLeaveAssignment::query()
            ->where('user_id', $profile->user_id)
            ->when($current, fn ($query) => $query->where('id', '!=', $current->id))
            ->whereHas('policy', fn ($query) => $query->where('leave_type_id', $policy->leave_type_id))
            ->delete();

        $assignment = $current ?? new PeopleLeaveAssignment([
            'user_id' => $profile->user_id,
            'leave_policy_id' => $policy->id,
            'via_employee' => false,
            'via_department' => false,
        ]);
        if ($source === 'department') {
            $assignment->via_department = true;
        } else {
            $assignment->via_employee = true;
        }
        if (! $alreadyWaiting) {
            $assignment->next_accrual_on = self::firstDueOn($policy->accrual_type, $on)->toDateString();
        }
        $assignment->save();

        $this->applyDue($on, $profile->user_id);
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

    public function grant(string $userId, string $type, float $days, ?string $grantedBy, ?string $note, ?CarbonInterface $on = null): void
    {
        $on = Carbon::parse(($on ?? now())->toDateString())->startOfDay();
        $days = round($days, 1);
        $tracks = PeopleLeaveType::query()->where('code', $type)->where('tracks_balance', true)->exists();
        if ($days <= 0 || ! $tracks) {
            throw new \InvalidArgumentException('Choose a leave type and a number of days above zero.');
        }

        $this->addDays($userId, (int) $on->year, $type, $days, 'manual', (string) Str::uuid(), $grantedBy, $note);
    }

    public function applyDue(CarbonInterface $today, ?string $onlyUserId = null): int
    {
        $today = Carbon::parse($today->toDateString())->startOfDay();
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
