<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppChatStatus extends Model
{
    use HasSocialInboxChannelScope;

    protected $table = 'whatsapp_chat_statuses';

    protected $fillable = [
        'channel',
        'name',
        'bucket',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppChatStatus $model) {
            if (empty($model->channel)) {
                $model->channel = SocialInboxChannel::current();
            }
        });
    }

    public function threadMetas(): HasMany
    {
        return $this->hasMany(WhatsAppChatThreadMeta::class, 'whatsapp_chat_status_id');
    }
}
