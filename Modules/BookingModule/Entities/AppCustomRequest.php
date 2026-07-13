<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\LeadManagement\Entities\Lead;
use Modules\UserManagement\Entities\User;

class AppCustomRequest extends Model
{
    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'reference_id',
        'customer_id',
        'name',
        'phone',
        'category_id',
        'category_name',
        'description',
        'status',
        'lead_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
