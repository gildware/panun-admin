<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    public const URGENCY_HIGH = 'high';
    public const URGENCY_MEDIUM = 'medium';
    public const URGENCY_LOW = 'low';

    public const URGENCIES = [
        self::URGENCY_HIGH,
        self::URGENCY_MEDIUM,
        self::URGENCY_LOW,
    ];

    protected $attributes = [
        'urgency' => self::URGENCY_MEDIUM,
    ];

    protected $fillable = [
        'lead_id',
        'followup_at',
        'remarks',
        'urgency',
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

