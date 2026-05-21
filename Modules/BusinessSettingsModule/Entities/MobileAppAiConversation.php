<?php

namespace Modules\BusinessSettingsModule\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Entities\User;

class MobileAppAiConversation extends Model
{
    protected $table = 'mobile_app_ai_conversations';

    protected $fillable = [
        'user_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MobileAppAiMessage::class, 'conversation_id');
    }

    public function appMessages(): HasMany
    {
        return $this->messages()->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP);
    }

    /**
     * Conversations that have at least one customer message from the mobile app AI chat.
     */
    public function scopeWithInAppAiChats(Builder $query): Builder
    {
        return $query->whereHas('messages', function (Builder $q): void {
            $q->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
                ->where('role', 'user');
        });
    }
}
