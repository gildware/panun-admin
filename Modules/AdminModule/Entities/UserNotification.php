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
    public const TYPE_PROVIDER_WITHDRAWAL = 'provider_withdrawal';
    public const TYPE_ADVERTISEMENT = 'advertisement';
    public const TYPE_SERVICE_REQUEST = 'service_request';
    public const TYPE_SHOWCASE = 'showcase';
    public const TYPE_PROFILE_CHANGE_REQUEST = 'profile_change_request';

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

    public function iconName(): string
    {
        return match ($this->type) {
            self::TYPE_BOOKING => 'event_available',
            self::TYPE_CHAT_MESSAGE => 'chat',
            self::TYPE_PROVIDER_REQUEST => 'person_add',
            self::TYPE_WITHDRAW_REQUEST => 'payments',
            self::TYPE_PROVIDER_WITHDRAWAL => 'person_off',
            self::TYPE_ADVERTISEMENT => 'campaign',
            self::TYPE_SERVICE_REQUEST => 'design_services',
            self::TYPE_SHOWCASE => 'photo_library',
            self::TYPE_PROFILE_CHANGE_REQUEST => 'manage_accounts',
            default => 'notifications',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_BOOKING => translate('Booking'),
            self::TYPE_CHAT_MESSAGE => translate('Message'),
            self::TYPE_PROVIDER_REQUEST => translate('Provider_Request'),
            self::TYPE_WITHDRAW_REQUEST => translate('Withdraw_Request'),
            self::TYPE_PROVIDER_WITHDRAWAL => translate('Provider_withdrawal'),
            self::TYPE_ADVERTISEMENT => translate('Advertisement'),
            self::TYPE_SERVICE_REQUEST => translate('service_requests'),
            self::TYPE_SHOWCASE => translate('Work_Showcase_Approvals'),
            self::TYPE_PROFILE_CHANGE_REQUEST => translate('Profile_Update_Requests'),
            default => translate('Notification'),
        };
    }

    public function actionButtonLabel(): string
    {
        if (! $this->action_url) {
            return translate('View_Details');
        }

        return match ($this->type) {
            self::TYPE_BOOKING, self::TYPE_PROVIDER_WITHDRAWAL => translate('Go_to_booking'),
            self::TYPE_ADVERTISEMENT => translate('View_advertisement'),
            self::TYPE_CHAT_MESSAGE => translate('Go_to_message'),
            self::TYPE_PROVIDER_REQUEST => translate('View_provider'),
            self::TYPE_WITHDRAW_REQUEST => translate('View_Requests'),
            self::TYPE_SERVICE_REQUEST => translate('View_Requests'),
            self::TYPE_SHOWCASE => translate('View_Details'),
            self::TYPE_PROFILE_CHANGE_REQUEST => translate('View_Requests'),
            default => translate('View_Details'),
        };
    }
}
