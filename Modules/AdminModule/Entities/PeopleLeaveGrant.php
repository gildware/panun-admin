<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleLeaveGrant extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'year',
        'leave_type',
        'days',
        'source',
        'period',
        'note',
        'granted_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
