<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    protected $fillable = [
        'lead_id',
        'followup_at',
        'remarks',
        'next_followup_at',
        'created_by',
    ];

    protected $casts = [
        'followup_at' => 'datetime',
        'next_followup_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'created_by');
    }
}

