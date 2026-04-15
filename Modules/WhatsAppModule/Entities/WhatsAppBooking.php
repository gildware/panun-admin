<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\LeadManagement\Entities\Lead;

/**
 * Booking in the WhatsApp PostgreSQL database.
 * Columns: id, booking_id, phone, name, alt_phone, address, service, service_description, district, prefered_datetime, status, cancellation_reason, location_hint, admin_prefill_json, system_booking_id, created_at, updated_at
 */
class WhatsAppBooking extends Model
{
    use HasSocialInboxChannelScope;

    public function getTable()
    {
        return config('whatsappmodule.tables.bookings', 'whatsapp_bookings');
    }

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_TENTATIVE_PENDING_HUMAN = 'TENTATIVE_PENDING_HUMAN';

    public const STATUS_HUMAN_CONFIRMED = 'HUMAN_CONFIRMED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'channel',
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
        'cancellation_reason',
        'location_hint',
        'admin_prefill_json',
        'system_booking_id',
        'lead_id',
    ];

    protected $casts = [
        'prefered_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'admin_prefill_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppBooking $model) {
            if (empty($model->channel)) {
                $model->channel = SocialInboxChannel::current();
            }
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
