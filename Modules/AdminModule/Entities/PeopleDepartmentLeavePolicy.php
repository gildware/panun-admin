<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleDepartmentLeavePolicy extends Model
{
    use HasUuid;

    protected $fillable = [
        'department_id',
        'leave_policy_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(PeopleDepartment::class, 'department_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PeopleLeavePolicy::class, 'leave_policy_id');
    }
}
