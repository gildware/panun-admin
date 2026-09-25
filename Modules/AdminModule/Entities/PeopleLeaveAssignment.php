<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleLeaveAssignment extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'leave_policy_id',
        'next_accrual_on',
        'via_employee',
        'via_department',
    ];

    protected $casts = [
        'next_accrual_on' => 'date',
        'via_employee' => 'boolean',
        'via_department' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PeopleLeavePolicy::class, 'leave_policy_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PeopleProfile::class, 'user_id', 'user_id');
    }
}
