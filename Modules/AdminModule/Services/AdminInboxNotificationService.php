<?php

namespace Modules\AdminModule\Services;

use App\Support\AdminHeaderChatCounts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\AdminModule\Entities\UserNotification;
use Modules\UserManagement\Entities\User;

class AdminInboxNotificationService
{
    public function notifyAllAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): void {
        $adminIds = User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->notifyUser(
                (string) $adminId,
                $type,
                $title,
                $body,
                $actionUrl,
                $referenceType,
                $referenceId,
            );
        }
    }

    public function notifyUser(
        string $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): ?UserNotification {
        if ($referenceType !== null && $referenceId !== null) {
            $exists = UserNotification::query()
                ->where('user_id', $userId)
                ->where('type', $type)
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->whereNull('read_at')
                ->exists();

            if ($exists) {
                return null;
            }
        }

        $notification = UserNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        $this->clearUserCache($userId);

        return $notification;
    }

    public function unreadCount(string $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function readCount(string $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->count();
    }

    public function recent(string $userId, int $limit = 50): Collection
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->latest()
            ->take($limit)
            ->get();
    }

    public function paginated(string $userId, ?string $filter = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForUser(string $notificationId, string $userId): ?UserNotification
    {
        return UserNotification::query()
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();
    }

    public function unreadSince(string $userId, ?string $sinceId = null): Collection
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->latest();

        if ($sinceId) {
            $since = UserNotification::query()->find($sinceId);
            if ($since) {
                $query->where('created_at', '>', $since->created_at);
            }
        }

        return $query->get();
    }

    public function markAsRead(string $notificationId, string $userId): bool
    {
        $updated = UserNotification::query()
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated) {
            $this->clearUserCache($userId);
        }

        return (bool) $updated;
    }

    public function markAllAsRead(string $userId): int
    {
        $updated = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated) {
            $this->clearUserCache($userId);
        }

        return $updated;
    }

    public function clearUserCache(string $userId): void
    {
        Cache::forget("admin_header_counts:{$userId}");
        Cache::forget("admin_inbox_notifications:{$userId}");
        AdminHeaderChatCounts::forgetForUser($userId);
    }
}
