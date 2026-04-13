<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBookingAutomationMessageLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'whatsapp_booking_automation_message_logs';

    protected $fillable = [
        'message_key',
        'trigger_event',
        'template_id',
        'template_name',
        'recipient_party',
        'recipient_phone',
        'booking_id',
        'booking_repeat_id',
        'wa_message_id',
        'local_whatsapp_message_id',
        'result',
        'error_detail',
        'acting_admin_user_id',
        'context_json',
        'created_at',
    ];

    protected $casts = [
        'context_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function conversationMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'local_whatsapp_message_id');
    }
}
