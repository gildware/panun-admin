<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFutureCustomerReasonOption extends Model
{
    protected $fillable = [
        'lead_future_customer_reason_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reason(): BelongsTo
    {
        return $this->belongsTo(LeadFutureCustomerReason::class, 'lead_future_customer_reason_id');
    }
}
