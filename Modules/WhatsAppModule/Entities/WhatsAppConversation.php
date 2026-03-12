<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversation state (AI handled) in the WhatsApp PostgreSQL database.
 * Columns: phone, active_module, current_step, after_hours, active_booking_id, active_lead_id, conversation_id
 */
class WhatsAppConversation extends Model
{
    public function getTable()
    {
        return config('whatsappmodule.tables.conversation', 'whatsapp_conversations');
    }

    protected $fillable = [
        'phone',
        'active_module',
        'current_step',
        'after_hours',
        'active_booking_id',
        'active_lead_id',
    ];

    protected $casts = [
        'after_hours' => 'boolean',
    ];

    /**
     * Messages for this conversation's phone (messages table uses phone, not conversation_id).
     */
    public function messagesByPhone(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'phone', 'phone')
            ->orderBy('created_at', 'asc');
    }
}
