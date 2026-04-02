<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppChatStatus extends Model
{
    protected $table = 'whatsapp_chat_statuses';

    protected $fillable = [
        'name',
        'bucket',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function threadMetas(): HasMany
    {
        return $this->hasMany(WhatsAppChatThreadMeta::class, 'whatsapp_chat_status_id');
    }
}
