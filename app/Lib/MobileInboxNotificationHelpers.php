<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\PromotionManagement\Entities\PushNotificationUser;

if (! function_exists('mobile_inbox_unread_exists_subquery')) {
    function mobile_inbox_unread_exists_subquery(Builder $query, string $userId): Builder
    {
        return $query->whereNotExists(function ($sub) use ($userId) {
            $sub->selectRaw('1')
                ->from('push_notification_users')
                ->whereColumn('push_notification_users.push_notification_id', 'push_notifications.id')
                ->where('push_notification_users.user_id', $userId)
                ->whereNotNull('push_notification_users.read_at');
        });
    }
}

if (! function_exists('mobile_inbox_user_visibility_query')) {
    function mobile_inbox_user_visibility_query(Builder $query, string $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId) {
            $query->whereDoesntHave('pushNotificationUser')
                ->orWhereHas('pushNotificationUser', function (Builder $q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        });
    }
}

if (! function_exists('mobile_inbox_unread_count')) {
    function mobile_inbox_unread_count(
        Builder $baseQuery,
        string $userId,
    ): int {
        return (int) mobile_inbox_unread_exists_subquery(clone $baseQuery, $userId)->count();
    }
}

if (! function_exists('mobile_inbox_mark_read')) {
    function mobile_inbox_mark_read(string $notificationId, string $userId): bool
    {
        $notification = PushNotification::query()->where('id', $notificationId)->first();
        if (! $notification) {
            return false;
        }

        $row = PushNotificationUser::query()->firstOrNew([
            'push_notification_id' => $notificationId,
            'user_id' => $userId,
        ]);
        $row->read_at = now();
        $row->save();

        return true;
    }
}

if (! function_exists('mobile_inbox_mark_all_read')) {
    function mobile_inbox_mark_all_read(Builder $baseQuery, string $userId): int
    {
        $notificationIds = mobile_inbox_unread_exists_subquery(clone $baseQuery, $userId)
            ->pluck('id');

        $marked = 0;
        foreach ($notificationIds as $notificationId) {
            if (mobile_inbox_mark_read((string) $notificationId, $userId)) {
                $marked++;
            }
        }

        return $marked;
    }
}

if (! function_exists('mobile_inbox_matches_zone_ids')) {
    /**
     * Notifications with empty zone_ids are treated as global and visible to all zones.
     */
    function mobile_inbox_matches_zone_ids(Builder $query, array $zoneIds): Builder
    {
        if ($zoneIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($zoneIds) {
            foreach ($zoneIds as $zoneId) {
                $query->orWhereJsonContains('zone_ids', $zoneId);
            }
            $query->orWhere('zone_ids', '[]')->orWhereNull('zone_ids');
        });
    }
}

if (! function_exists('mobile_inbox_matches_zone_id')) {
    function mobile_inbox_matches_zone_id(Builder $query, string $zoneId): Builder
    {
        return $query->where(function (Builder $query) use ($zoneId) {
            $query->whereJsonContains('zone_ids', $zoneId)
                ->orWhere('zone_ids', '[]')
                ->orWhereNull('zone_ids');
        });
    }
}

if (! function_exists('notification_admin_app_logo_url')) {
    /**
     * Customer/provider app logo used for admin-triggered notifications (same branding as in-app support chat).
     */
    function notification_admin_app_logo_url(string $app = 'customer'): ?string
    {
        $service = app(\Modules\BusinessSettingsModule\Services\MobileAppManagementService::class);
        $logoKey = $app === 'provider' ? 'provider_app_logo' : 'customer_app_logo';
        $url = $service->resolveIconPathForApi($app, $logoKey, 'light')
            ?? $service->resolveIconPathForApi($app, $logoKey, 'dark');

        if ($url) {
            return $url;
        }

        return getBusinessSettingsImageFullPath(
            key: 'business_favicon',
            settingType: 'business_information',
            path: 'business/',
            defaultPath: null
        );
    }
}

if (! function_exists('resolve_notification_provider_sender_image')) {
    function resolve_notification_provider_sender_image(?string $providerId): ?string
    {
        if (! $providerId) {
            return null;
        }

        $provider = \Modules\ProviderManagement\Entities\Provider::query()->find($providerId);
        if (! $provider) {
            return null;
        }

        return $provider->list_avatar_full_path ?? $provider->logo_full_path ?? $provider->contact_person_photo_full_path;
    }
}

if (! function_exists('resolve_notification_customer_sender_image')) {
    function resolve_notification_customer_sender_image(?string $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        $user = \Modules\UserManagement\Entities\User::query()->find($userId);

        return $user?->profile_image_full_path;
    }
}

if (! function_exists('infer_notification_sender_type')) {
    function infer_notification_sender_type(PushNotification $notification): string
    {
        if (filled($notification->sender_type) && $notification->sender_type !== 'admin') {
            return (string) $notification->sender_type;
        }

        if (filled($notification->sender_id)) {
            return (string) ($notification->sender_type ?: 'admin');
        }

        $type = strtolower((string) ($notification->notification_type ?? ''));
        $toUsers = is_array($notification->to_users) ? $notification->to_users : [];
        $toCustomer = in_array('customer', $toUsers, true);
        $toProvider = in_array('provider-admin', $toUsers, true)
            || in_array('provider-serviceman', $toUsers, true)
            || in_array('provider-employee', $toUsers, true);

        if ($type === 'booking' && filled($notification->booking_id)) {
            $booking = \Modules\BookingModule\Entities\Booking::query()->find($notification->booking_id);
            if ($booking) {
                if ($toProvider && $booking->customer_id) {
                    return 'customer';
                }

                if ($toCustomer && $booking->provider_id) {
                    $status = strtolower((string) ($booking->booking_status ?? ''));
                    if (in_array($status, ['accepted', 'ongoing', 'completed', 'canceled', 'cancelled', 'arrived'], true)) {
                        return 'provider';
                    }
                }
            }
        }

        if ($type === 'service_request' && $toProvider) {
            $title = strtolower((string) ($notification->title ?? ''));
            if (
                str_contains($title, 'new service')
                || str_contains($title, 'request arrived')
                || str_contains($title, 'arrived')
            ) {
                return 'customer';
            }
        }

        return 'admin';
    }
}

if (! function_exists('infer_notification_sender_id')) {
    function infer_notification_sender_id(PushNotification $notification, string $senderType): ?string
    {
        if (
            filled($notification->sender_id)
            && filled($notification->sender_type)
            && $notification->sender_type !== 'admin'
        ) {
            return (string) $notification->sender_id;
        }

        if (in_array($senderType, ['provider', 'customer'], true) && filled($notification->booking_id)) {
            $booking = \Modules\BookingModule\Entities\Booking::query()->find($notification->booking_id);
            if (! $booking) {
                return null;
            }

            return $senderType === 'provider'
                ? ($booking->provider_id ? (string) $booking->provider_id : null)
                : ($booking->customer_id ? (string) $booking->customer_id : null);
        }

        return null;
    }
}

if (! function_exists('resolve_persisted_notification_sender')) {
    /**
     * Determine sender metadata when persisting transactional inbox notifications.
     *
     * @return array{0: string, 1: ?string}
     */
    function resolve_persisted_notification_sender(
        ?string $senderType,
        ?string $senderId,
        ?string $notificationType,
        ?string $inboxAudience,
        mixed $bookingId,
        ?string $bookingStatusOverride = null,
    ): array {
        if (filled($senderId)) {
            return [(string) ($senderType ?: 'admin'), (string) $senderId];
        }

        if (filled($senderType) && $senderType !== 'admin') {
            return [(string) $senderType, null];
        }

        $notificationType = strtolower((string) ($notificationType ?? ''));
        $inboxAudience = (string) ($inboxAudience ?? '');
        $toProvider = in_array($inboxAudience, ['provider-admin', 'provider-serviceman', 'provider-employee'], true);
        $toCustomer = $inboxAudience === 'customer';

        if ($notificationType === 'booking' && filled($bookingId)) {
            $booking = \Modules\BookingModule\Entities\Booking::query()->find(normalize_notification_booking_id($bookingId));
            if ($booking) {
                if ($toProvider && $booking->customer_id) {
                    return ['customer', (string) $booking->customer_id];
                }

                if ($toCustomer && $booking->provider_id) {
                    $status = strtolower((string) ($bookingStatusOverride ?? $booking->booking_status ?? ''));
                    if (in_array($status, ['accepted', 'ongoing', 'completed', 'canceled', 'cancelled', 'arrived'], true)) {
                        return ['provider', (string) $booking->provider_id];
                    }
                }
            }
        }

        return ['admin', null];
    }
}

if (! function_exists('resolve_notification_sender_image')) {
    /**
     * Avatar for who triggered the notification: admin app logo, provider photo, customer photo, or admin broadcast image.
     */
    function resolve_notification_sender_image(PushNotification $notification, string $appAudience = 'customer'): ?string
    {
        if (filled($notification->cover_image)) {
            return $notification->cover_image_full_path;
        }

        $senderType = infer_notification_sender_type($notification);
        $senderId = infer_notification_sender_id($notification, $senderType);

        return match ($senderType) {
            'provider' => resolve_notification_provider_sender_image($senderId),
            'customer' => resolve_notification_customer_sender_image($senderId),
            default => notification_admin_app_logo_url($appAudience),
        };
    }
}

if (! function_exists('mobile_inbox_enrich_paginator')) {
    function mobile_inbox_enrich_paginator(LengthAwarePaginator $paginator, string $userId, string $appAudience = 'customer'): LengthAwarePaginator
    {
        $readStates = PushNotificationUser::query()
            ->where('user_id', $userId)
            ->whereIn('push_notification_id', collect($paginator->items())->pluck('id'))
            ->get(['push_notification_id', 'read_at'])
            ->keyBy('push_notification_id');

        foreach ($paginator->items() as $item) {
            $readAt = $readStates->get($item->id)?->read_at;
            $item->is_read = $readAt !== null;
            $item->read_at = $readAt;
            $item->sender_type = infer_notification_sender_type($item);
            $item->sender_image_full_path = resolve_notification_sender_image($item, $appAudience);
        }

        return $paginator;
    }
}
