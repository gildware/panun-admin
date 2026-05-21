<?php

namespace Modules\BusinessSettingsModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileAppAiMessage extends Model
{
    public const SOURCE_MOBILE_APP = 'mobile_app';

    protected $table = 'mobile_app_ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'source',
        'body',
    ];

    protected $attributes = [
        'source' => self::SOURCE_MOBILE_APP,
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MobileAppAiConversation::class, 'conversation_id');
    }
}
