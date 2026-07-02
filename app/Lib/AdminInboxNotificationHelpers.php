<?php

use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\BookingModule\Entities\Booking;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ChattingModule\Entities\ChannelList;
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
        $channel = ChannelList::query()->find($conversation->channel_id);
        if (! $channel || ! is_support_channel_reference_type($channel->reference_type)) {
            return;
        }

        $sender = User::query()->with(['provider'])->find($conversation->user_id);
        if (!$sender) {
            return;
        }

        $channelType = (string) $channel->reference_type;
        $senderIsCustomer = $channelType === 'support_customer'
            || ($channelType === 'support' && $sender->user_type === USER_TYPES[4]['value']);
        $senderIsProvider = in_array($channelType, ['support_provider', 'support_serviceman'], true)
            || ($channelType === 'support' && $sender->user_type === USER_TYPES[2]['value']);

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
            $userType = 'provider_admin';
        }

        $body = $senderName !== ''
            ? $senderName . ': ' . $messagePreview
            : $messagePreview;

        $actionUrl = route('admin.chat.support', [
            'filter' => 'all',
            'channel_id' => $channelId,
        ]);

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

if (! function_exists('admin_inbox_notify_booking_payment')) {
    function admin_inbox_notify_booking_payment(Booking $booking, float $amount, string $receivedBy): void
    {
        $booking->loadMissing('customer');
        $readableId = $booking->readable_id ?? $booking->id;
        $customerName = trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''));

        admin_inbox_notify_all(
            UserNotification::TYPE_BOOKING,
            translate('Booking_payment_received') . ' #' . $readableId,
            with_currency_symbol($amount) . ' — ' . ucfirst(str_replace('_', ' ', strtolower(trim($receivedBy))))
                . ($customerName !== '' ? ' · ' . $customerName : ''),
            route('admin.booking.details', ['id' => $booking->id]),
            'booking_payment',
            (string) $booking->id . ':' . round($amount, 2) . ':' . strtolower(trim($receivedBy)),
        );
    }
}

if (! function_exists('admin_inbox_notify_booking_ongoing')) {
    function admin_inbox_notify_booking_ongoing(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'provider']);
        $readableId = $booking->readable_id ?? $booking->id;
        $providerName = $booking->provider?->company_name ?? translate('Provider');

        admin_inbox_notify_all(
            UserNotification::TYPE_BOOKING,
            translate('Booking_ongoing') . ' #' . $readableId,
            $providerName . ' ' . translate('marked_booking_as_ongoing'),
            route('admin.booking.details', ['id' => $booking->id]),
            'booking_ongoing',
            (string) $booking->id,
        );
    }
}

if (! function_exists('admin_inbox_notify_booking_customer_canceled')) {
    function admin_inbox_notify_booking_customer_canceled(Booking $booking): void
    {
        $booking->loadMissing('customer');
        $readableId = $booking->readable_id ?? $booking->id;
        $customerName = trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''));

        admin_inbox_notify_all(
            UserNotification::TYPE_BOOKING,
            translate('Booking_canceled_by_customer') . ' #' . $readableId,
            $customerName !== ''
                ? $customerName . ' ' . translate('canceled_the_booking')
                : translate('A_customer_canceled_a_booking'),
            route('admin.booking.details', ['id' => $booking->id]),
            'booking_customer_cancel',
            (string) $booking->id,
        );
    }
}

if (! function_exists('admin_inbox_notify_advertisement_submitted')) {
    function admin_inbox_notify_advertisement_submitted(\Modules\PromotionManagement\Entities\Advertisement $advertisement): void
    {
        $advertisement->loadMissing('provider');
        $providerName = $advertisement->provider?->company_name ?? translate('Provider');
        $adLabel = $advertisement->title ?? (string) ($advertisement->readable_id ?? $advertisement->id);
        $isUpdateRequest = (int) ($advertisement->is_updated ?? 0) === 1;

        admin_inbox_notify_all(
            UserNotification::TYPE_ADVERTISEMENT,
            $isUpdateRequest ? translate('Advertisement_update_request') : translate('New_advertisement_request'),
            $providerName . ' — ' . $adLabel,
            route('admin.advertisements.new-ads-request', ['status' => $isUpdateRequest ? 'update_request' : 'new']),
            'advertisement_submitted',
            (string) $advertisement->id,
        );
    }
}

if (! function_exists('admin_inbox_notify_advertisement_paused_by_provider')) {
    function admin_inbox_notify_advertisement_paused_by_provider(\Modules\PromotionManagement\Entities\Advertisement $advertisement): void
    {
        $advertisement->loadMissing('provider');
        $providerName = $advertisement->provider?->company_name ?? translate('Provider');
        $adLabel = $advertisement->title ?? (string) ($advertisement->readable_id ?? $advertisement->id);

        admin_inbox_notify_all(
            UserNotification::TYPE_ADVERTISEMENT,
            translate('Advertisement_paused_by_provider'),
            $providerName . ' — ' . $adLabel,
            route('admin.advertisements.details', [$advertisement->id]),
            'advertisement_paused_by_provider',
            (string) $advertisement->id . ':paused:' . now()->timestamp,
        );
    }
}

if (! function_exists('admin_inbox_notify_advertisement_resumed_by_provider')) {
    function admin_inbox_notify_advertisement_resumed_by_provider(\Modules\PromotionManagement\Entities\Advertisement $advertisement): void
    {
        $advertisement->loadMissing('provider');
        $providerName = $advertisement->provider?->company_name ?? translate('Provider');
        $adLabel = $advertisement->title ?? (string) ($advertisement->readable_id ?? $advertisement->id);

        admin_inbox_notify_all(
            UserNotification::TYPE_ADVERTISEMENT,
            translate('Advertisement_resumed_by_provider'),
            $providerName . ' — ' . $adLabel,
            route('admin.advertisements.details', [$advertisement->id]),
            'advertisement_resumed_by_provider',
            (string) $advertisement->id . ':resumed:' . now()->timestamp,
        );
    }
}

if (! function_exists('admin_inbox_notify_service_request_submitted')) {
    function admin_inbox_notify_service_request_submitted(\Modules\ServiceManagement\Entities\ServiceRequest $serviceRequest): void
    {
        $serviceRequest->loadMissing(['user.provider']);
        $providerName = $serviceRequest->user?->provider?->company_name ?? translate('Provider');
        $serviceLabel = $serviceRequest->service_name ?? translate('Service');

        admin_inbox_notify_all(
            UserNotification::TYPE_SERVICE_REQUEST,
            translate('New_service_request'),
            $providerName . ' — ' . $serviceLabel,
            route('admin.service.request.list'),
            'service_request_submitted',
            (string) $serviceRequest->id,
        );
    }
}

if (! function_exists('admin_inbox_notify_showcase_submitted')) {
    function admin_inbox_notify_showcase_submitted(\Modules\ProviderManagement\Entities\ProviderShowcaseItem $item): void
    {
        $item->loadMissing('provider');
        $providerName = $item->provider?->company_name ?? translate('Provider');
        $label = $item->title ?: translate('Work_showcase');

        admin_inbox_notify_all(
            UserNotification::TYPE_SHOWCASE,
            translate('New_showcase_submission'),
            $providerName . ' — ' . $label,
            route('admin.provider.showcase_approval', ['status' => 'pending']),
            'showcase_submission',
            (string) $item->id,
        );
    }
}
