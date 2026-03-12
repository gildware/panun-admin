<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Booking in the WhatsApp PostgreSQL database.
 * Columns: id, booking_id, phone, name, alt_phone, address, service, prefered_datetime, status, created_at, updated_at, location_hint
 */
class WhatsAppBooking extends Model
{
    public function getTable()
    {
        return config('whatsappmodule.tables.bookings', 'whatsapp_bookings');
    }

    protected $fillable = [
        'booking_id',
        'phone',
        'name',
        'alt_phone',
        'address',
        'service',
        'prefered_datetime',
        'status',
        'location_hint',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
