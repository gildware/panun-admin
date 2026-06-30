<?php

namespace Modules\PromotionManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PushNotificationDeliveryLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'device_id',
        'fcm_token_hash',
        'fcm_token_preview',
        'delivery_target',
        'topic',
        'notification_type',
        'title',
        'status',
        'http_status',
        'error_message',
        'push_notification_id',
        'booking_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
