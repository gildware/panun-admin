<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class BookingReopenEvent extends Model
{
    public const RESOLUTION_REOPEN_IN_PLACE = 'reopen_in_place';

    public const RESOLUTION_NEW_BOOKING = 'new_booking';

    protected $table = 'booking_reopen_events';

    protected $fillable = [
        'source_booking_id',
        'actor_user_id',
        'resolution',
        'complaint_notes',
        'child_booking_id',
        'target_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sourceBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'source_booking_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function childBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'child_booking_id');
    }
}
