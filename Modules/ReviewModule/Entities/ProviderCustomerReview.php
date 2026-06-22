<?php

namespace Modules\ReviewModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class ProviderCustomerReview extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'provider_customer_reviews';

    protected $casts = [
        'review_rating' => 'integer',
        'is_active' => 'integer',
        'booking_date' => 'datetime',
    ];

    protected $fillable = [];

    public function booking(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    protected function scopeOfStatus($query, $status)
    {
        return $query->where('is_active', $status);
    }
}
