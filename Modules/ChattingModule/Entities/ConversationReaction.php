<?php

namespace Modules\ChattingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class ConversationReaction extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'emoji',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChannelConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
