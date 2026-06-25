<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class UserNotification extends Model
{
    use HasUuid;

    public const TYPE_BOOKING = 'booking';
    public const TYPE_CHAT_MESSAGE = 'chat_message';
    public const TYPE_PROVIDER_REQUEST = 'provider_request';
    public const TYPE_WITHDRAW_REQUEST = 'withdraw_request';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'action_url',
        'reference_type',
        'reference_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
