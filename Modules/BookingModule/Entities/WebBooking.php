<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\LeadManagement\Entities\Lead;

/**
 * Website booking intake row. Lifecycle / open-closed status lives on the linked {@see Lead}.
 */
class WebBooking extends Model
{
    protected $fillable = [
        'reference_id',
        'name',
        'phone',
        'service_category',
        'area',
        'details',
        'preferred_date',
        'lead_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
