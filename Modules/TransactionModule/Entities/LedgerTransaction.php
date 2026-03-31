<?php

namespace Modules\TransactionModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class LedgerTransaction extends Model
{
    use HasUuid;

    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';

    public const REASON_REFUND = 'refund';
    public const REASON_PROVIDER_PAYOUT = 'provider_payout';

    public const RECEIVED_BY_COMPANY = 'company';
    public const RECEIVED_BY_PROVIDER = 'provider';

    protected $fillable = [
        'amount',
        'type',
        'transaction_id',
        'booking_id',
        'booking_repeat_id',
        'booking_partial_payment_id',
        'provider_id',
        'payment_method',
        'reason',
        'date',
        'received_by',
        'reference_note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function repeat(): BelongsTo
    {
        return $this->belongsTo(BookingRepeat::class, 'booking_repeat_id');
    }

    public function bookingPartialPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPartialPayment::class, 'booking_partial_payment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function scopeIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }
}
