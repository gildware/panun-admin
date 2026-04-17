<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserManagement\Entities\User;

class BookingStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'changed_by',
        'booking_status',
        'is_guest',
        'booking_repeat_id',
        'booking_cancellation_reason_id',
        'booking_hold_reopen_reason_id',
        'booking_dispute_reason_id',
        'status_change_remarks',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function cancellationReason(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BookingCancellationReason::class, 'booking_cancellation_reason_id');
    }

    public function holdReopenReason(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BookingHoldReopenReason::class, 'booking_hold_reopen_reason_id');
    }

    public function disputeReason(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BookingDisputeReason::class, 'booking_dispute_reason_id');
    }

    protected static function newFactory()
    {
        return \Modules\BookingModule\Database\factories\BookingStatusHistoryFactory::new();
    }
}
