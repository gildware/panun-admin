<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Booking in the WhatsApp PostgreSQL database.
 * Columns: id, booking_id, phone, name, alt_phone, address, service, service_description, district, prefered_datetime, status, location_hint, admin_prefill_json, system_booking_id, created_at, updated_at
 */
class WhatsAppBooking extends Model
{
    public function getTable()
    {
        return config('whatsappmodule.tables.bookings', 'whatsapp_bookings');
    }

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_TENTATIVE_PENDING_HUMAN = 'TENTATIVE_PENDING_HUMAN';

    public const STATUS_HUMAN_CONFIRMED = 'HUMAN_CONFIRMED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'booking_id',
        'phone',
        'name',
        'alt_phone',
        'address',
        'district',
        'service',
        'service_description',
        'prefered_datetime',
        'status',
        'location_hint',
        'admin_prefill_json',
        'system_booking_id',
    ];

    protected $casts = [
        'prefered_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'admin_prefill_json' => 'array',
    ];
}
