<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleProfile extends Model
{
    use HasUuid;

    public const WORK_LOCATIONS = [
        'in_office' => 'In office',
        'hybrid' => 'Hybrid',
        'work_from_home' => 'Work from home',
    ];

    public const BILLING_TYPES = [
        'billable' => 'Billable',
        'non_billable' => 'No billable',
    ];

    public static function workLocationLabel(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return self::WORK_LOCATIONS[$value] ?? $value;
    }

    public static function billingTypeLabel(?string $value): string
    {
        return self::BILLING_TYPES[trim((string) $value)] ?? '';
    }

    public function requiresTimesheet(): bool
    {
        $attributes = $this->getAttributes();
        if (! array_key_exists('requires_timesheet', $attributes) || $attributes['requires_timesheet'] === null) {
            return true;
        }

        return (bool) $attributes['requires_timesheet'];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedWorkLocations(?string $current = null): array
    {
        $allowed = array_keys(self::WORK_LOCATIONS);
        $current = trim((string) $current);
        if ($current !== '' && ! in_array($current, $allowed, true)) {
            $allowed[] = $current;
        }

        return $allowed;
    }

    protected $fillable = [
        'user_id',
        'employee_code',
        'job_title',
        'work_location',
        'billing_type',
        'requires_timesheet',
        'address',
        'manager_id',
        'joined_on',
        'department',
        'employment_type',
        'work_schedule',
        'min_hours_override',
        'week_off_override',
        'date_of_birth',
        'emergency_contact',
        'bank_name',
        'bank_account',
        'bank_ifsc',
        'pan',
        'aadhaar',
        'uan',
        'esi_number',
        'employment_status',
        'employment_stage',
        'last_working_day',
        'leave_policy_id',
        'leave_next_accrual_on',
    ];

    protected $casts = [
        'requires_timesheet' => 'boolean',
        'min_hours_override' => 'float',
        'week_off_override' => 'array',
        'joined_on' => 'date',
        'date_of_birth' => 'date',
        'last_working_day' => 'date',
        'leave_next_accrual_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(PeopleLeavePolicy::class, 'leave_policy_id');
    }
}
