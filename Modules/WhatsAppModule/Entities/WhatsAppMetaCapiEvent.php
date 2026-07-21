<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMetaCapiEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'whatsapp_meta_capi_events';

    protected $fillable = [
        'channel',
        'phone',
        'event_name',
        'event_id',
        'ctwa_clid',
        'lead_id',
        'booking_id',
        'status',
        'request_payload',
        'response_json',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_json' => 'array',
        'sent_at' => 'datetime',
    ];
}
