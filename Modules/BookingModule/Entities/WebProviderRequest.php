<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\LeadManagement\Entities\Lead;

class WebProviderRequest extends Model
{
    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'reference_id',
        'name',
        'phone',
        'service_category',
        'area',
        'details',
        'experience',
        'status',
        'lead_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
