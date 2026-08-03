<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class UserNotification extends Model
{
    use HasUuid;

    public const CATEGORY_EXTERNAL = 'external';
    public const CATEGORY_INTERNAL = 'internal';

    public const TYPE_BOOKING = 'booking';
    public const TYPE_CHAT_MESSAGE = 'chat_message';
    public const TYPE_PROVIDER_REQUEST = 'provider_request';
    public const TYPE_WITHDRAW_REQUEST = 'withdraw_request';
    public const TYPE_PROVIDER_WITHDRAWAL = 'provider_withdrawal';
    public const TYPE_ADVERTISEMENT = 'advertisement';
    public const TYPE_SERVICE_REQUEST = 'service_request';
    public const TYPE_SHOWCASE = 'showcase';
    public const TYPE_PROFILE_CHANGE_REQUEST = 'profile_change_request';
    public const TYPE_WELCOME_BONUS = 'welcome_bonus';
    public const TYPE_REVIEW = 'review';
    public const TYPE_WEB_BOOKING = 'web_booking';
    public const TYPE_WEB_PROVIDER_REQUEST = 'web_provider_request';
    public const TYPE_APP_CUSTOM_REQUEST = 'app_custom_request';
    public const TYPE_LEAD = 'lead';
    public const TYPE_LEAD_COMMENT = 'lead_comment';
    public const TYPE_BOOKING_COMMENT = 'booking_comment';
    public const TYPE_TICKET_ASSIGNED = 'ticket_assigned';
    public const TYPE_TICKET_COMMENT = 'ticket_comment';
    public const TYPE_LEAD_ASSIGNED = 'lead_assigned';
    public const TYPE_BOOKING_ASSIGNED = 'booking_assigned';
    public const TYPE_WHATSAPP_ASSIGNED = 'whatsapp_assigned';
    public const TYPE_WHATSAPP_HUMAN_SUPPORT = 'whatsapp_human_support';
    public const TYPE_LEAD_FOLLOWUP_DUE = 'lead_followup_due';

    protected $fillable = [
        'user_id',
        'type',
        'category',
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

    public static function categoryForType(string $type): string
    {
        return match ($type) {
            self::TYPE_LEAD_COMMENT,
            self::TYPE_BOOKING_COMMENT,
            self::TYPE_TICKET_ASSIGNED,
            self::TYPE_TICKET_COMMENT,
            self::TYPE_LEAD_ASSIGNED,
            self::TYPE_BOOKING_ASSIGNED,
            self::TYPE_WHATSAPP_ASSIGNED,
            self::TYPE_LEAD_FOLLOWUP_DUE => self::CATEGORY_INTERNAL,
            default => self::CATEGORY_EXTERNAL,
        };
    }

    public function isInternal(): bool
    {
        return $this->category === self::CATEGORY_INTERNAL;
    }

    public function isExternal(): bool
    {
        return $this->category === self::CATEGORY_EXTERNAL;
    }

    protected function actionUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => admin_notification_normalize_action_url($value),
            set: fn (?string $value) => admin_notification_normalize_action_url($value),
        );
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function senderType(): string
    {
        return resolve_admin_notification_sender($this)['sender_type'];
    }

    public function senderAvatarUrl(): ?string
    {
        return resolve_admin_notification_sender($this)['sender_image_url'];
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
            self::TYPE_WELCOME_BONUS => 'redeem',
            self::TYPE_REVIEW => 'rate_review',
            self::TYPE_WEB_BOOKING => 'language',
            self::TYPE_WEB_PROVIDER_REQUEST => 'handshake',
            self::TYPE_APP_CUSTOM_REQUEST => 'phone_iphone',
            self::TYPE_LEAD => 'person_search',
            self::TYPE_LEAD_COMMENT => 'comment',
            self::TYPE_BOOKING_COMMENT => 'comment',
            self::TYPE_TICKET_ASSIGNED => 'confirmation_number',
            self::TYPE_TICKET_COMMENT => 'comment',
            self::TYPE_LEAD_ASSIGNED => 'assignment_ind',
            self::TYPE_BOOKING_ASSIGNED => 'assignment',
            self::TYPE_WHATSAPP_ASSIGNED => 'forum',
            self::TYPE_WHATSAPP_HUMAN_SUPPORT => 'support_agent',
            self::TYPE_LEAD_FOLLOWUP_DUE => 'event',
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
            self::TYPE_WELCOME_BONUS => translate('Welcome_Bonus'),
            self::TYPE_REVIEW => translate('Booking_Review'),
            self::TYPE_WEB_BOOKING => translate('Web_Bookings'),
            self::TYPE_WEB_PROVIDER_REQUEST => translate('Web_Provider_Requests'),
            self::TYPE_APP_CUSTOM_REQUEST => translate('App_Custom_Requests'),
            self::TYPE_LEAD => translate('Lead'),
            self::TYPE_LEAD_COMMENT => translate('Lead_Comment'),
            self::TYPE_BOOKING_COMMENT => translate('Booking_Comment'),
            self::TYPE_TICKET_ASSIGNED => translate('Ticket_Assigned'),
            self::TYPE_TICKET_COMMENT => translate('Ticket_Comment'),
            self::TYPE_LEAD_ASSIGNED => translate('Lead_Assigned'),
            self::TYPE_BOOKING_ASSIGNED => translate('Booking_Assigned'),
            self::TYPE_WHATSAPP_ASSIGNED => translate('WhatsApp_Assigned'),
            self::TYPE_WHATSAPP_HUMAN_SUPPORT => translate('Human_support'),
            self::TYPE_LEAD_FOLLOWUP_DUE => translate('Follow_up_due'),
            default => translate('Notification'),
        };
    }

    public function actionButtonLabel(): string
    {
        if (! $this->action_url) {
            return translate('View_Details');
        }

        return match ($this->type) {
            self::TYPE_BOOKING, self::TYPE_PROVIDER_WITHDRAWAL, self::TYPE_BOOKING_ASSIGNED, self::TYPE_BOOKING_COMMENT => translate('Go_to_booking'),
            self::TYPE_ADVERTISEMENT => translate('View_advertisement'),
            self::TYPE_CHAT_MESSAGE => translate('Go_to_message'),
            self::TYPE_PROVIDER_REQUEST => translate('View_provider'),
            self::TYPE_WITHDRAW_REQUEST => translate('View_Requests'),
            self::TYPE_SERVICE_REQUEST => translate('View_Requests'),
            self::TYPE_SHOWCASE => translate('View_Details'),
            self::TYPE_PROFILE_CHANGE_REQUEST => translate('View_Requests'),
            self::TYPE_WELCOME_BONUS => translate('View_Welcome_Bonus_Report'),
            self::TYPE_REVIEW => translate('View_Details'),
            self::TYPE_WEB_BOOKING => translate('View_Details'),
            self::TYPE_WEB_PROVIDER_REQUEST => translate('View_Details'),
            self::TYPE_APP_CUSTOM_REQUEST => translate('View_Details'),
            self::TYPE_LEAD, self::TYPE_LEAD_COMMENT, self::TYPE_LEAD_ASSIGNED, self::TYPE_LEAD_FOLLOWUP_DUE => translate('View_Lead'),
            self::TYPE_TICKET_ASSIGNED, self::TYPE_TICKET_COMMENT => translate('View_Ticket'),
            self::TYPE_WHATSAPP_ASSIGNED, self::TYPE_WHATSAPP_HUMAN_SUPPORT => translate('Go_to_WhatsApp'),
            default => translate('View_Details'),
        };
    }
}
