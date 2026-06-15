<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class BookingFollowup extends Model
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
        'booking_id',
        'date',
        'reason',
        'for',
        'status',
        'remarks',
        'urgency',
        'reschedule_reason',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
