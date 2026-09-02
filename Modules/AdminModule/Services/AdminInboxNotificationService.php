<?php

namespace Modules\AdminModule\Services;

use App\Support\AdminHeaderChatCounts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\UserNotification;
use Modules\UserManagement\Entities\User;
use Throwable;

class AdminInboxNotificationService
{
    public function notifyAllAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $category = null,
    ): void {
        $category = $category ?? UserNotification::categoryForType($type);

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
                $category,
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
        ?string $category = null,
    ): ?UserNotification {
        $category = $category ?? UserNotification::categoryForType($type);

        try {
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

            $payload = [
                'user_id' => $userId,
                'type' => $type,
                'title' => Str::limit((string) $title, 250, ''),
                'body' => $body !== null ? Str::limit((string) $body, 5000, '') : null,
                'action_url' => $actionUrl !== null ? Str::limit((string) $actionUrl, 250, '') : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId !== null ? Str::limit((string) $referenceId, 250, '') : null,
            ];

            if (Schema::hasColumn('user_notifications', 'category')) {
                $payload['category'] = $category;
            }

            $notification = UserNotification::create($payload);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        $this->clearUserCache($userId);

        return $notification;
    }

    public function unreadCount(string $userId, ?string $category = null): int
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at');

        if ($category !== null) {
            $query->where('category', $category);
        }

        return $query->count();
    }

    public function readCount(string $userId, ?string $category = null): int
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNotNull('read_at');

        if ($category !== null) {
            $query->where('category', $category);
        }

        return $query->count();
    }

    public function recent(string $userId, int $limit = 50, ?string $category = null): Collection
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->latest();

        if ($category !== null) {
            $query->where('category', $category);
        }

        return $query->take($limit)->get();
    }

    public function paginated(string $userId, ?string $filter = null, ?string $category = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($category !== null) {
            $query->where('category', $category);
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

    public function unreadSince(string $userId, ?string $sinceId = null, ?string $category = null): Collection
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->latest();

        if ($category !== null) {
            $query->where('category', $category);
        }

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

    public function markAllAsRead(string $userId, ?string $category = null): int
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at');

        if ($category !== null) {
            $query->where('category', $category);
        }

        $updated = $query->update(['read_at' => now()]);

        if ($updated) {
            $this->clearUserCache($userId);
        }

        return $updated;
    }

    public function clearUserCache(string $userId): void
    {
        try {
            Cache::forget("admin_header_counts:{$userId}");
            Cache::forget("admin_inbox_notifications:{$userId}");
            AdminHeaderChatCounts::forgetForUser($userId);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
