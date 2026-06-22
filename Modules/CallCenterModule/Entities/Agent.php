<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class Agent extends Model
{
    protected $table = 'call_center_agents';

    protected $fillable = [
        'external_id',
        'user_id',
        'name',
        'email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
