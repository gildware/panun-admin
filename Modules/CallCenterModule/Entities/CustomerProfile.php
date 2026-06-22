<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Entities\User;

class CustomerProfile extends Model
{
    protected $table = 'call_center_customer_profiles';

    protected $fillable = [
        'user_id',
        'customer_ref',
        'customer_type',
        'tags',
        'alternate_phones',
        'priority',
        'assigned_agent_id',
        'assigned_agent_name',
        'ai_summary',
        'total_calls',
        'last_call_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'alternate_phones' => 'array',
        'last_call_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'customer_profile_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'customer_profile_id');
    }
}
