<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Message in the WhatsApp PostgreSQL database.
 * Columns: id, phone, message_text, direction (IN/OUT), message_type (TEXT/IMAGE), created_at
 */
class WhatsAppMessage extends Model
{
    // Local table does not have updated_at.
    public const UPDATED_AT = null;

    public function getTable()
    {
        return config('whatsappmodule.tables.messages', 'messages');
    }

    protected $fillable = [
        'phone',
        'message_text',
        'direction',
        'message_type',
        'wa_message_id',
        'status',
        'status_updated_at',
        'sent_by',
        'sent_by_id',
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
    ];

    public function getBodyAttribute(): string
    {
        return $this->message_text ?? '';
    }
}
