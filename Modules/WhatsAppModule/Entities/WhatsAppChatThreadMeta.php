<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WhatsAppChatThreadMeta extends Model
{
    protected $table = 'whatsapp_chat_thread_meta';

    protected $primaryKey = 'phone';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'phone',
        'whatsapp_chat_status_id',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(WhatsAppChatStatus::class, 'whatsapp_chat_status_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppChatTag::class,
            'whatsapp_chat_thread_tags',
            'phone',
            'whatsapp_chat_tag_id',
            'phone',
            'id'
        )->orderBy('whatsapp_chat_tags.sort_order')->orderBy('whatsapp_chat_tags.id');
    }

    public static function firstOrCreateForPhone(string $phone): self
    {
        return static::firstOrCreate(
            ['phone' => $phone],
            ['whatsapp_chat_status_id' => null]
        );
    }
}
