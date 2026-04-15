<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WhatsAppChatTag extends Model
{
    use HasSocialInboxChannelScope;

    protected $table = 'whatsapp_chat_tags';

    protected $fillable = [
        'channel',
        'name',
        'color',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppChatTag $model) {
            if (empty($model->channel)) {
                $model->channel = SocialInboxChannel::current();
            }
        });
    }

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
