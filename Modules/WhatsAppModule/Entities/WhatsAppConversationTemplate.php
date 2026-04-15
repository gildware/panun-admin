<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

class WhatsAppConversationTemplate extends Model
{
    use HasSocialInboxChannelScope;

    protected $table = 'whatsapp_conversation_templates';

    protected $fillable = [
        'channel',
        'title',
        'body',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppConversationTemplate $model) {
            if (empty($model->channel)) {
                $model->channel = SocialInboxChannel::current();
            }
        });
    }

    public function scopeActive($query)
    {
        $table = $query->getModel()->getTable();
        if (! Schema::hasColumn($table, 'is_active')) {
            return $query;
        }

        return $query->where($table . '.is_active', true);
    }
}
