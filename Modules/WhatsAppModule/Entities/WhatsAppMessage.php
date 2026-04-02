<?php

namespace Modules\WhatsAppModule\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Message in the WhatsApp PostgreSQL database.
 * Columns: id, phone, message_text, direction (IN/OUT), message_type, wa_message_id,
 * reply_to_wa_message_id, reactions (JSON: customer/agent emoji), created_at
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
        'media_path',
        'wa_message_id',
        'reply_to_wa_message_id',
        'reactions',
        'status',
        'status_detail',
        'status_updated_at',
        'admin_seen_at',
        'sent_by',
        'sent_by_id',
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
        'admin_seen_at' => 'datetime',
        'reactions' => 'array',
    ];

    public function getBodyAttribute(): string
    {
        return $this->message_text ?? '';
    }

    protected static function booted(): void
    {
        static::creating(function (WhatsAppMessage $model) {
            if ($model->created_at === null) {
                $model->created_at = Carbon::now(
                    (string) config('whatsappmodule.message_timezone', config('app.timezone'))
                );
            }
        });
    }
}
