<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class Task extends Model
{
    protected $table = 'call_center_tasks';

    protected $fillable = [
        'external_id',
        'customer_profile_id',
        'user_id',
        'call_id',
        'call_external_id',
        'assigned_agent_external_id',
        'title',
        'description',
        'due_at',
        'priority',
        'status',
        'source',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }
}
