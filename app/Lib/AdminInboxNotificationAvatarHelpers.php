<?php

use Modules\AdminModule\Entities\UserNotification;
use Modules\BookingModule\Entities\Booking;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ProviderManagement\Entities\WithdrawRequest;
use Modules\ServiceManagement\Entities\ServiceRequest;
use Modules\UserManagement\Entities\User;

if (! function_exists('resolve_admin_notification_sender')) {
    /**
     * Resolve who triggered an admin inbox notification for avatar display.
     *
     * @return array{sender_type: string, sender_image_url: ?string}
     */
    function resolve_admin_notification_sender(UserNotification $notification): array
    {
        [$senderType, $senderId] = infer_admin_notification_sender_identity($notification);

        $imageUrl = match ($senderType) {
            'provider' => resolve_notification_provider_sender_image($senderId),
            'customer' => resolve_notification_customer_sender_image($senderId),
            default => null,
        };

        return [
            'sender_type' => $senderType,
            'sender_image_url' => $imageUrl,
        ];
    }
}

if (! function_exists('infer_admin_notification_sender_identity')) {
    /**
     * @return array{0: string, 1: ?string}
     */
    function infer_admin_notification_sender_identity(UserNotification $notification): array
    {
        $referenceType = (string) ($notification->reference_type ?? '');
        $referenceId = (string) ($notification->reference_id ?? '');

        if ($referenceType === 'provider' && $referenceId !== '') {
            return ['provider', $referenceId];
        }

        if ($referenceType === 'channel_conversation' && $referenceId !== '') {
            return resolve_admin_notification_sender_from_user(
                ChannelConversation::query()->find($referenceId)?->user_id
            );
        }

        if ($referenceType === 'withdraw_request' && $referenceId !== '') {
            $withdrawRequest = WithdrawRequest::query()->with('user.provider')->find($referenceId);
            $providerId = $withdrawRequest?->user?->provider?->id;

            return $providerId ? ['provider', (string) $providerId] : ['admin', null];
        }

        if (in_array($referenceType, [
            'advertisement_submitted',
            'advertisement_paused_by_provider',
            'advertisement_resumed_by_provider',
        ], true) && $referenceId !== '') {
            $advertisementId = strtok($referenceId, ':') ?: $referenceId;
            $advertisement = \Modules\PromotionManagement\Entities\Advertisement::query()->find($advertisementId);

            return $advertisement?->provider_id
                ? ['provider', (string) $advertisement->provider_id]
                : ['admin', null];
        }

        if ($referenceType === 'service_request_submitted' && $referenceId !== '') {
            return resolve_admin_notification_sender_from_service_request(
                ServiceRequest::query()->with('user.provider')->find($referenceId)
            );
        }

        if ($referenceType === 'showcase_submission' && $referenceId !== '') {
            $item = ProviderShowcaseItem::query()->find($referenceId);

            return $item?->provider_id
                ? ['provider', (string) $item->provider_id]
                : ['admin', null];
        }

        if ($referenceType === 'profile_change_request' && $referenceId !== '') {
            $changeRequest = ProviderChangeRequest::query()->find($referenceId);

            return $changeRequest?->provider_id
                ? ['provider', (string) $changeRequest->provider_id]
                : ['admin', null];
        }

        if ($referenceType === 'booking_ongoing' && $referenceId !== '') {
            $booking = Booking::query()->find($referenceId);

            return $booking?->provider_id
                ? ['provider', (string) $booking->provider_id]
                : ['admin', null];
        }

        if ($referenceType === 'booking_customer_cancel' && $referenceId !== '') {
            $booking = Booking::query()->find($referenceId);

            return $booking?->customer_id
                ? ['customer', (string) $booking->customer_id]
                : ['admin', null];
        }

        if ($referenceType === 'booking_payment' && $referenceId !== '') {
            return resolve_admin_notification_sender_from_booking_payment($referenceId);
        }

        if ($referenceType === 'booking' && $referenceId !== '') {
            if ($notification->type === UserNotification::TYPE_PROVIDER_WITHDRAWAL) {
                return resolve_admin_notification_sender_from_booking_provider($referenceId);
            }

            $booking = Booking::query()->find($referenceId);

            return $booking?->customer_id
                ? ['customer', (string) $booking->customer_id]
                : ['admin', null];
        }

        return ['admin', null];
    }
}

if (! function_exists('resolve_admin_notification_sender_from_user')) {
    /**
     * @return array{0: string, 1: ?string}
     */
    function resolve_admin_notification_sender_from_user(mixed $userId): array
    {
        if (! $userId) {
            return ['admin', null];
        }

        $user = User::query()->with('provider')->find($userId);
        if (! $user) {
            return ['admin', null];
        }

        if ($user->provider) {
            return ['provider', (string) $user->provider->id];
        }

        if (in_array($user->user_type, CUSTOMER_USER_TYPES, true)) {
            return ['customer', (string) $user->id];
        }

        if ($user->user_type === 'provider-admin' && $user->provider) {
            return ['provider', (string) $user->provider->id];
        }

        return ['admin', null];
    }
}

if (! function_exists('resolve_admin_notification_sender_from_service_request')) {
    /**
     * @return array{0: string, 1: ?string}
     */
    function resolve_admin_notification_sender_from_service_request(?ServiceRequest $serviceRequest): array
    {
        if (! $serviceRequest?->user) {
            return ['admin', null];
        }

        if ($serviceRequest->user->provider) {
            return ['provider', (string) $serviceRequest->user->provider->id];
        }

        return ['customer', (string) $serviceRequest->user_id];
    }
}

if (! function_exists('resolve_admin_notification_sender_from_booking_payment')) {
    /**
     * reference_id format: {booking_id}:{amount}:{received_by}
     *
     * @return array{0: string, 1: ?string}
     */
    function resolve_admin_notification_sender_from_booking_payment(string $referenceId): array
    {
        $parts = explode(':', $referenceId, 3);
        $bookingId = $parts[0] ?? '';
        $receivedBy = strtolower((string) ($parts[2] ?? ''));

        $booking = $bookingId !== '' ? Booking::query()->find($bookingId) : null;
        if (! $booking) {
            return ['admin', null];
        }

        if (str_contains($receivedBy, 'provider') && $booking->provider_id) {
            return ['provider', (string) $booking->provider_id];
        }

        if (str_contains($receivedBy, 'customer') && $booking->customer_id) {
            return ['customer', (string) $booking->customer_id];
        }

        return ['admin', null];
    }
}

if (! function_exists('resolve_admin_notification_sender_from_booking_provider')) {
    /**
     * @return array{0: string, 1: ?string}
     */
    function resolve_admin_notification_sender_from_booking_provider(string $bookingId): array
    {
        $booking = Booking::query()->find($bookingId);
        if (! $booking?->provider_id) {
            return ['admin', null];
        }

        return ['provider', (string) $booking->provider_id];
    }
}
