<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;

class BookingSource extends Model
{
    public const NAME_DIRECT_APP_BOOKING = 'Direct App Booking';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function ensureDirectAppBookingSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_DIRECT_APP_BOOKING)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_DIRECT_APP_BOOKING,
            'description' => 'Bookings placed directly by the customer in the mobile app.',
            'is_active' => true,
        ]);
    }
}
