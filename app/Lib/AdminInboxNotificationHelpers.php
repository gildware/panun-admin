<?php

use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\WithdrawRequest;
use Modules\UserManagement\Entities\User;

if (! function_exists('admin_inbox_notify_all')) {
    function admin_inbox_notify_all(
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        app(AdminInboxNotificationService::class)->notifyAllAdmins(
            $type,
            $title,
            $body,
            $actionUrl,
            $referenceType,
            $referenceId,
        );
    }
}

if (! function_exists('admin_inbox_notify_chat_message')) {
    function admin_inbox_notify_chat_message(ChannelConversation $conversation): void
    {
        $sender = User::query()->with(['provider'])->find($conversation->user_id);
        if (!$sender) {
            return;
        }

        $senderIsCustomer = $sender->user_type === USER_TYPES[4]['value'];
        $senderIsProvider = $sender->user_type === USER_TYPES[2]['value'];

        if (! $senderIsCustomer && ! $senderIsProvider) {
            return;
        }

        $service = app(AdminInboxNotificationService::class);
        $channelId = (string) $conversation->channel_id;
        $messagePreview = \Illuminate\Support\Str::limit(strip_tags((string) $conversation->message), 120);

        if ($senderIsCustomer) {
            $senderName = trim($sender->first_name . ' ' . $sender->last_name);
            $title = translate('New_message_from_customer');
            $userType = 'customer';
        } else {
            $senderName = $sender->provider?->company_name ?? trim($sender->first_name . ' ' . $sender->last_name);
            $title = translate('New_message_from_provider');
            $userType = 'provider';
        }

        $body = $senderName !== ''
            ? $senderName . ': ' . $messagePreview
            : $messagePreview;

        $actionUrl = route('admin.chat.index', ['user_type' => $userType]);

        $conversation->channel_users
            ->where('user_id', '!=', $conversation->user_id)
            ->pluck('user_id')
            ->each(function ($recipientId) use ($service, $title, $body, $actionUrl, $channelId, $conversation) {
                $recipient = User::query()->find($recipientId);
                if (!$recipient || ! in_array($recipient->user_type, ADMIN_USER_TYPES, true)) {
                    return;
                }

                $service->notifyUser(
                    (string) $recipientId,
                    UserNotification::TYPE_CHAT_MESSAGE,
                    $title,
                    $body,
                    $actionUrl,
                    'channel_conversation',
                    (string) $conversation->id,
                );
            });
    }
}

if (! function_exists('admin_inbox_notify_provider_request')) {
    function admin_inbox_notify_provider_request(Provider $provider): void
    {
        admin_inbox_notify_all(
            UserNotification::TYPE_PROVIDER_REQUEST,
            translate('New_Provider_Registration'),
            translate('A_new_provider_has_registered') . ': ' . ($provider->company_name ?? ''),
            route('admin.provider.onboarding_details', [$provider->id]),
            'provider',
            (string) $provider->id,
        );
    }
}

if (! function_exists('admin_inbox_notify_withdraw_request')) {
    function admin_inbox_notify_withdraw_request(WithdrawRequest $withdrawRequest): void
    {
        $providerName = $withdrawRequest->user?->provider?->company_name ?? translate('Provider');
        admin_inbox_notify_all(
            UserNotification::TYPE_WITHDRAW_REQUEST,
            translate('New_Withdraw_Request'),
            $providerName . ' — ' . with_currency_symbol($withdrawRequest->amount),
            route('admin.withdraw.request.list', ['status' => 'pending']),
            'withdraw_request',
            (string) $withdrawRequest->id,
        );
    }
}
