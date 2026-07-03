<?php

namespace Modules\ChattingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Entities\User;

class ChannelConversation extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'channel_id',
        'message',
        'user_id',
        'reply_to_conversation_id',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_conversation_id');
    }

    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    //relation
    public function conversationFiles(): HasMany
    {
        return $this->hasMany(ConversationFile::class, 'conversation_id', 'id');
    }
    public function conversationLastFiles(): HasMany
    {
        return $this->hasMany(ConversationFile::class, 'conversation_id', 'id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ConversationReaction::class, 'conversation_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChannelList::class);
    }

    public function channel_users(): HasMany
    {
        return $this->hasMany(ChannelUser::class, 'channel_id', 'channel_id');
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            // ... code here
        });

        self::created(function ($model) {
            try {
                dispatch_chat_message_push_notifications($model);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('Chat message push dispatch failed', [
                    'conversation_id' => $model->id,
                    'message' => $exception->getMessage(),
                ]);
            }

            if (! function_exists('admin_inbox_notify_chat_message')) {
                return;
            }

            try {
                admin_inbox_notify_chat_message($model);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('Admin inbox chat notify failed', [
                    'conversation_id' => $model->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        });

        self::updating(function ($model) {
            // ... code here
        });

        self::updated(function ($model) {
            // ... code here
        });

        self::deleting(function ($model) {
            // ... code here
        });

        self::deleted(function ($model) {
            // ... code here
        });
    }
}
