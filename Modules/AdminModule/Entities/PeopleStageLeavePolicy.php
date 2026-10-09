<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleStageLeavePolicy extends Model
{
    use HasUuid;

    protected $fillable = [
        'employment_stage',
        'leave_policy_id',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PeopleLeavePolicy::class, 'leave_policy_id');
    }
}
