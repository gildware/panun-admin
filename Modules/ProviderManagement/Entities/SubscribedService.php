<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;

class SubscribedService extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'is_subscribed' => 'integer'
    ];

    protected $fillable = ['provider_id', 'category_id', 'sub_category_id', 'is_subscribed'];

    public function scopeOfStatus($query, $status)
    {
        $query->where('is_subscribed', $status);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sub_category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'sub_category_id', 'sub_category_id');
    }

    public function ongoing_booking(): HasMany
    {
        return $this->providerScopedBookings('ongoing');
    }

    public function completed_booking(): HasMany
    {
        return $this->providerScopedBookings('completed');
    }

    public function canceled_booking(): HasMany
    {
        return $this->providerScopedBookings('canceled');
    }

    private function providerScopedBookings(string $status): HasMany
    {
        return $this->hasMany(Booking::class, 'sub_category_id', 'sub_category_id')
            ->whereColumn('bookings.provider_id', 'subscribed_services.provider_id')
            ->where('booking_status', $status);
    }

    protected function scopeOfSubscription($query, $status)
    {
        $query->where('is_subscribed', $status);
    }
}
