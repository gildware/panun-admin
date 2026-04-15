<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

/**
 * Conversation state (AI handled) in the WhatsApp PostgreSQL database.
 * Columns: phone, active_module, current_step, after_hours, active_booking_id, active_lead_id, ai_unclear_attempts, timestamps
 */
class WhatsAppConversation extends Model
{
    use HasSocialInboxChannelScope;

    public function getTable()
    {
        return config('whatsappmodule.tables.conversation', 'whatsapp_conversations');
    }

    protected $fillable = [
        'channel',
        'phone',
        'active_module',
        'current_step',
        'after_hours',
        'active_booking_id',
        'active_lead_id',
        'ai_unclear_attempts',
    ];

    protected $casts = [
        'after_hours' => 'boolean',
        'ai_unclear_attempts' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppConversation $model) {
            if (empty($model->channel)) {
                $model->channel = SocialInboxChannel::current();
            }
        });
    }

    /**
     * Messages for this conversation's phone (messages table uses phone, not conversation_id).
     */
    public function messagesByPhone(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'phone', 'phone')
            ->orderBy('created_at', 'asc');
    }
}
