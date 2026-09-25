<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleProfile extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'employee_code',
        'job_title',
        'work_location',
        'address',
        'manager_id',
        'joined_on',
        'department',
        'employment_type',
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
        'last_working_day',
        'leave_policy_id',
        'leave_next_accrual_on',
    ];

    protected $casts = [
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
