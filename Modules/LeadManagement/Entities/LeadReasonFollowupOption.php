<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadReasonFollowupOption extends Model
{
    protected $fillable = [
        'reason_type',
        'reason_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reason(): BelongsTo
    {
        return $this->belongsTo(LeadInvalidReason::class, 'reason_id');
    }
}
