<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class BookingFollowup extends Model
{
    protected $fillable = [
        'booking_id',
        'date',
        'reason',
        'for',
        'status',
        'remarks',
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
