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

if (! function_exists('mobile_inbox_enrich_paginator')) {
    function mobile_inbox_enrich_paginator(LengthAwarePaginator $paginator, string $userId): LengthAwarePaginator
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
        }

        return $paginator;
    }
}
