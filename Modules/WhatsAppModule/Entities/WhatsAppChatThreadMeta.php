<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WhatsAppChatThreadMeta extends Model
{
    use HasSocialInboxChannelScope;

    protected $table = 'whatsapp_chat_thread_meta';

    protected $primaryKey = 'phone';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'channel',
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

    protected static function booted(): void
    {
        static::creating(function (WhatsAppChatThreadMeta $model) {
            if (empty($model->channel)) {
                $model->channel = SocialInboxChannel::current();
            }
        });
    }

    public static function firstOrCreateForPhone(string $phone): self
    {
        return static::firstOrCreate(
            [
                'phone' => $phone,
                'channel' => SocialInboxChannel::current(),
            ],
            ['whatsapp_chat_status_id' => null]
        );
    }
}
