<?php

namespace Modules\AdminModule\Support;

use Illuminate\Support\Collection;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\UserManagement\Entities\User;

class TrainingPeople
{
    public static function name(?User $user): string
    {
        if (! $user) {
            return 'Employee';
        }

        $name = trim((string) $user->first_name.' '.(string) $user->last_name);

        return $name !== '' ? $name : (string) $user->email;
    }

    /**
     * @return Collection<int, User>
     */
    public static function staff(): Collection
    {
        return User::query()
            ->with('peopleProfile')
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public static function department(?User $user): string
    {
        return trim((string) ($user?->peopleProfile?->department ?? ''));
    }

    /**
     * @return Collection<int, PeopleDepartment>
     */
    public static function departments(): Collection
    {
        $staffIds = self::staff()->pluck('id');
        $counts = PeopleProfile::query()
            ->whereIn('user_id', $staffIds)
            ->where('department', '!=', '')
            ->pluck('department')
            ->countBy();

        return PeopleDepartment::query()
            ->orderBy('name')
            ->get()
            ->each(function (PeopleDepartment $department) use ($counts) {
                $department->setAttribute('staff_count', (int) ($counts[$department->name] ?? 0));
            });
    }

    /**
     * @return Collection<int, User>
     */
    public static function inDepartment(PeopleDepartment $department): Collection
    {
        $ids = PeopleProfile::query()
            ->where('department', $department->name)
            ->pluck('user_id');

        return self::staff()->whereIn('id', $ids->all())->values();
    }
}
