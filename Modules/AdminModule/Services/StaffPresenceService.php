<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\UserManagement\Entities\User;

class StaffPresenceService
{
    public const STATUS_ONLINE = 'online';
    public const STATUS_AWAY = 'away';
    public const STATUS_ON_BREAK = 'on_break';
    public const STATUS_OFFLINE = 'offline';

    public const ONLINE_THRESHOLD_SECONDS = 90;

    public static function statuses(): array
    {
        return [
            self::STATUS_ONLINE,
            self::STATUS_AWAY,
            self::STATUS_ON_BREAK,
            self::STATUS_OFFLINE,
        ];
    }

    public function heartbeat(User $user, ?string $page = null): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        $updates = ['last_seen_at' => now()];

        if ($page !== null && $page !== '') {
            $updates['last_visited_page'] = Str::limit(trim($page), 255, '');
        }

        if ($user->staff_presence_status === self::STATUS_OFFLINE) {
            $updates['staff_presence_status'] = self::STATUS_ONLINE;
        }

        $user->update($updates);
    }

    public function setStatus(User $user, string $status): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        if (! in_array($status, self::statuses(), true)) {
            return;
        }

        $updates = ['staff_presence_status' => $status];

        if ($status === self::STATUS_ONLINE) {
            $updates['last_seen_at'] = now();
        }

        $user->update($updates);
    }

    public function markOffline(User $user): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        $user->update([
            'staff_presence_status' => self::STATUS_OFFLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function markOnlineOnLogin(User $user): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        $user->update([
            'staff_presence_status' => self::STATUS_ONLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function resolveDisplayStatus(User $user): string
    {
        if (! $this->isRecentlyActive($user)) {
            return self::STATUS_OFFLINE;
        }

        $status = (string) ($user->staff_presence_status ?? self::STATUS_OFFLINE);

        if ($status === self::STATUS_OFFLINE) {
            return self::STATUS_ONLINE;
        }

        return in_array($status, [self::STATUS_AWAY, self::STATUS_ON_BREAK], true)
            ? $status
            : self::STATUS_ONLINE;
    }

    public function listStaffPresence(?string $excludeUserId = null): Collection
    {
        return User::query()
            ->ofType(ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'profile_image', 'user_type', 'staff_presence_status', 'last_seen_at', 'last_visited_page'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user) => $this->formatStaffMember($user));
    }

    public function formatStaffMember(User $user): array
    {
        $displayStatus = $this->resolveDisplayStatus($user);

        return [
            'id' => $user->id,
            'name' => trim($user->first_name.' '.$user->last_name) ?: ($user->email ?? 'Staff'),
            'email' => $user->email,
            'phone' => $user->phone,
            'user_type' => $user->user_type,
            'profile_image' => $user->profile_image_full_path,
            'presence_status' => $displayStatus,
            'presence_label' => $this->statusLabel($displayStatus),
            'last_seen_at' => $user->last_seen_at?->toIso8601String(),
            'last_visited_page' => $user->last_visited_page,
            'last_visited_page_label' => $this->formatLastVisitedPage($user->last_visited_page),
            'is_current_user' => auth()->id() === $user->id,
        ];
    }

    public function formatLastVisitedPage(?string $page): string
    {
        if ($page === null || trim($page) === '') {
            return '—';
        }

        $page = trim($page);

        if (str_starts_with($page, '/')) {
            $segments = array_values(array_filter(explode('/', trim($page, '/'))));

            if ($segments === []) {
                return translate('Dashboard');
            }

            if (($segments[0] ?? '') === 'admin') {
                array_shift($segments);
            }

            if ($segments === []) {
                return translate('Dashboard');
            }

            $label = str_replace(['-', '_'], ' ', (string) end($segments));

            return ucwords($label);
        }

        return Str::limit($page, 60);
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_ONLINE => translate('Online'),
            self::STATUS_AWAY => translate('Away'),
            self::STATUS_ON_BREAK => translate('On_Break'),
            default => translate('Offline'),
        };
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_ONLINE => 'bg-success',
            self::STATUS_AWAY => 'bg-warning text-dark',
            self::STATUS_ON_BREAK => 'bg-info text-dark',
            default => 'bg-secondary',
        };
    }

    public function statusPillClass(string $status): string
    {
        return match ($status) {
            self::STATUS_ONLINE => 'bg-success text-white',
            self::STATUS_AWAY => 'bg-warning text-dark',
            self::STATUS_ON_BREAK => 'bg-info text-dark',
            default => 'bg-secondary text-white',
        };
    }

    public function statusDotClass(string $status): string
    {
        return match ($status) {
            self::STATUS_ONLINE => 'bg-success',
            self::STATUS_AWAY => 'bg-warning',
            self::STATUS_ON_BREAK => 'bg-info',
            default => 'bg-secondary',
        };
    }

    private function isAdminStaff(User $user): bool
    {
        return in_array($user->user_type, ADMIN_USER_TYPES, true);
    }

    private function isRecentlyActive(User $user): bool
    {
        if (! $user->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->greaterThan(Carbon::now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS));
    }
}
