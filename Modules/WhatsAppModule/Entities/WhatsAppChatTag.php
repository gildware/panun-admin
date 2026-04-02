<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WhatsAppChatTag extends Model
{
    protected $table = 'whatsapp_chat_tags';

    protected $fillable = [
        'name',
        'color',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function threadMetas(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppChatThreadMeta::class,
            'whatsapp_chat_thread_tags',
            'whatsapp_chat_tag_id',
            'phone',
            'id',
            'phone',
        );
    }
}
