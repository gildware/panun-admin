<?php

namespace Modules\BookingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class BookingCompensation extends Model
{
    use HasUuid;

    protected $table = 'booking_compensation';

    public const PARTY_COMPANY = 'company';
    public const PARTY_PROVIDER = 'provider';
    public const PARTY_CUSTOMER = 'customer';

    protected $fillable = [
        'booking_id',
        'customer_id',
        'provider_id',
        'from_party',
        'to_party',
        'amount',
        'transaction_id',
        'reference_note',
        'date',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

