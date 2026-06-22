<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Entities\User;

class Call extends Model
{
    protected $table = 'call_center_calls';

    protected $fillable = [
        'external_id',
        'customer_profile_id',
        'user_id',
        'direction',
        'status',
        'from_number',
        'to_number',
        'agent_external_id',
        'agent_name',
        'asterisk_unique_id',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'disposition',
        'outcome',
        'tags',
        'notes_summary',
        'source',
    ];

    protected $casts = [
        'tags' => 'array',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'call_id');
    }

    public function voicemails(): HasMany
    {
        return $this->hasMany(Voicemail::class, 'call_id');
    }
}
