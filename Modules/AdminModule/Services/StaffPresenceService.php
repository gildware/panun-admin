<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\StaffPresencePeriod;
use Modules\UserManagement\Entities\User;

class StaffPresenceService
{
    public const STATUS_ONLINE = 'online';
    public const STATUS_AWAY = 'away';
    public const STATUS_ON_BREAK = 'on_break';
    public const STATUS_OFFLINE = 'offline';

    public const ONLINE_THRESHOLD_SECONDS = 90;

    /** @var array<int, string> */
    private const PERIOD_STATUSES = [
        self::STATUS_OFFLINE,
        self::STATUS_AWAY,
        self::STATUS_ON_BREAK,
        self::STATUS_ONLINE,
    ];

    /** @var array<int, string> */
    private const LAST_PERIOD_STATUSES = [
        self::STATUS_OFFLINE,
        self::STATUS_AWAY,
        self::STATUS_ON_BREAK,
    ];

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

        $previousDisplay = $this->resolveDisplayStatus($user);
        $lastSeenBefore = $user->last_seen_at;

        $updates = ['last_seen_at' => now()];

        if ($page !== null && $page !== '') {
            $updates['last_visited_page'] = Str::limit(trim($page), 255, '');
        }

        if ($user->staff_presence_status === self::STATUS_OFFLINE) {
            $updates['staff_presence_status'] = self::STATUS_ONLINE;
        }

        $user->update($updates);

        $fresh = $user->fresh();
        $newDisplay = $this->resolveDisplayStatus($fresh);
        $fallbackStart = ($previousDisplay === self::STATUS_OFFLINE && $lastSeenBefore)
            ? $lastSeenBefore->copy()->addSeconds(self::ONLINE_THRESHOLD_SECONDS)
            : null;

        $this->handleDisplayStatusTransition($fresh, $previousDisplay, $newDisplay, now(), $fallbackStart);
        $this->syncOpenPeriodForDisplayStatus($fresh, $newDisplay, now(), $lastSeenBefore);
    }

    public function setStatus(User $user, string $status): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        if (! in_array($status, self::statuses(), true)) {
            return;
        }

        $previousDisplay = $this->resolveDisplayStatus($user);
        $updates = ['staff_presence_status' => $status];

        if ($status === self::STATUS_ONLINE) {
            $updates['last_seen_at'] = now();
        }

        $user->update($updates);

        $fresh = $user->fresh();
        $newDisplay = $this->resolveDisplayStatus($fresh);
        $this->handleDisplayStatusTransition($fresh, $previousDisplay, $newDisplay);
        $this->syncOpenPeriodForDisplayStatus($fresh, $newDisplay);
    }

    public function markOffline(User $user): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        $previousDisplay = $this->resolveDisplayStatus($user);

        $user->update([
            'staff_presence_status' => self::STATUS_OFFLINE,
            'last_seen_at' => now(),
        ]);

        $fresh = $user->fresh();
        $newDisplay = $this->resolveDisplayStatus($fresh);
        $this->handleDisplayStatusTransition($fresh, $previousDisplay, $newDisplay);
        $this->syncOpenPeriodForDisplayStatus($fresh, $newDisplay);
    }

    public function markOnlineOnLogin(User $user): void
    {
        if (! $this->isAdminStaff($user)) {
            return;
        }

        $previousDisplay = $this->resolveDisplayStatus($user);
        $lastSeenBefore = $user->last_seen_at;

        $user->update([
            'staff_presence_status' => self::STATUS_ONLINE,
            'last_seen_at' => now(),
        ]);

        $fresh = $user->fresh();
        $newDisplay = $this->resolveDisplayStatus($fresh);
        $fallbackStart = ($previousDisplay === self::STATUS_OFFLINE && $lastSeenBefore)
            ? $lastSeenBefore->copy()->addSeconds(self::ONLINE_THRESHOLD_SECONDS)
            : null;

        $this->handleDisplayStatusTransition($fresh, $previousDisplay, $newDisplay, now(), $fallbackStart);
        $this->syncOpenPeriodForDisplayStatus($fresh, $newDisplay);
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
        $users = User::query()
            ->ofType(ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'profile_image', 'user_type', 'staff_presence_status', 'last_seen_at', 'last_visited_page'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $statsByUser = $this->computeTodayPresenceStatsForUsers($users);

        return $users->map(function (User $user) use ($statsByUser) {
            return $this->formatStaffMember($user, $statsByUser[$user->id] ?? null);
        });
    }

    public function formatStaffMember(User $user, ?array $todayStats = null): array
    {
        $displayStatus = $this->resolveDisplayStatus($user);
        $todayStats ??= $this->computeTodayPresenceStats($user);

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
            'last_offline_period_today' => $todayStats['last_offline_period_today'],
            'last_offline_period_today_seconds' => $todayStats['last_offline_period_today_seconds'],
            'total_offline_today' => $todayStats['total_offline_today'],
            'total_offline_today_seconds' => $todayStats['total_offline_today_seconds'],
            'last_away_period_today' => $todayStats['last_away_period_today'],
            'last_away_period_today_seconds' => $todayStats['last_away_period_today_seconds'],
            'total_away_today' => $todayStats['total_away_today'],
            'total_away_today_seconds' => $todayStats['total_away_today_seconds'],
            'last_break_period_today' => $todayStats['last_break_period_today'],
            'last_break_period_today_seconds' => $todayStats['last_break_period_today_seconds'],
            'total_break_today' => $todayStats['total_break_today'],
            'total_break_today_seconds' => $todayStats['total_break_today_seconds'],
            'total_online_today' => $todayStats['total_online_today'],
            'total_online_today_seconds' => $todayStats['total_online_today_seconds'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, is_today: bool}>
     */
    public function listAvailableHistoryDates(): array
    {
        $periods = StaffPresencePeriod::query()->get(['started_at', 'ended_at']);

        if ($periods->isEmpty()) {
            return [];
        }

        $dates = collect();

        foreach ($periods as $period) {
            $cursor = $period->started_at->copy()->startOfDay();
            $endDay = ($period->ended_at ?? now())->copy()->startOfDay();

            while ($cursor->lte($endDay)) {
                $dates->push($cursor->toDateString());
                $cursor->addDay();
            }
        }

        return $dates->unique()->sortDesc()->values()->map(function (string $date) {
            $carbon = Carbon::parse($date);

            return [
                'value' => $date,
                'label' => $carbon->format('M j, Y'),
                'is_today' => $carbon->isToday(),
            ];
        })->all();
    }

    public function listStaffPresenceHistory(string $date): Collection
    {
        $dayStart = Carbon::parse($date)->startOfDay();
        $isToday = $dayStart->isToday();
        $dayEnd = $isToday ? now() : $dayStart->copy()->endOfDay();

        $users = User::query()
            ->ofType(ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->select(['id', 'first_name', 'last_name', 'email', 'profile_image', 'user_type', 'staff_presence_status', 'last_seen_at'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $statsByUser = $this->computePresenceStatsForUsers($users, $dayStart, $dayEnd, ! $isToday);

        return $users->map(function (User $user) use ($statsByUser) {
            $stats = $statsByUser[$user->id] ?? [];

            return [
                'id' => $user->id,
                'name' => trim($user->first_name.' '.$user->last_name) ?: ($user->email ?? 'Staff'),
                'email' => $user->email,
                'user_type' => $user->user_type,
                'profile_image' => $user->profile_image_full_path,
                'last_offline_period' => $stats['last_offline_period_today'] ?? '—',
                'total_offline' => $stats['total_offline_today'] ?? '—',
                'last_away_period' => $stats['last_away_period_today'] ?? '—',
                'total_away' => $stats['total_away_today'] ?? '—',
                'last_break_period' => $stats['last_break_period_today'] ?? '—',
                'total_break' => $stats['total_break_today'] ?? '—',
                'total_online' => $stats['total_online_today'] ?? '—',
            ];
        });
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, array<string, mixed>>
     */
    public function computeTodayPresenceStatsForUsers(Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        return $this->computePresenceStatsForUsers($users, Carbon::today(), now(), false);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, array<string, mixed>>
     */
    public function computePresenceStatsForUsers(
        Collection $users,
        Carbon $dayStart,
        Carbon $dayEnd,
        bool $isHistorical
    ): array {
        if ($users->isEmpty()) {
            return [];
        }

        $userIds = $users->pluck('id')->all();

        $periods = StaffPresencePeriod::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('status', self::PERIOD_STATUSES)
            ->where('started_at', '<=', $dayEnd)
            ->where(function ($query) use ($dayStart) {
                $query->where('ended_at', '>=', $dayStart)
                    ->orWhereNull('ended_at');
            })
            ->orderBy('started_at')
            ->get()
            ->groupBy('user_id');

        $stats = [];

        foreach ($users as $user) {
            $stats[$user->id] = $this->buildDayPresenceStats(
                $user,
                $periods->get($user->id, collect()),
                $dayStart,
                $dayEnd,
                $isHistorical
            );
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    public function computeTodayPresenceStats(User $user): array
    {
        return $this->buildDayPresenceStats(
            $user,
            StaffPresencePeriod::query()
                ->where('user_id', $user->id)
                ->whereIn('status', self::PERIOD_STATUSES)
                ->where('started_at', '<=', now())
                ->where(function ($query) {
                    $query->where('started_at', '>=', Carbon::today())
                        ->orWhere('ended_at', '>=', Carbon::today())
                        ->orWhereNull('ended_at');
                })
                ->orderBy('started_at')
                ->get(),
            Carbon::today(),
            now(),
            false
        );
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

    public function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '—';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return $hours.'h '.$minutes.'m';
        }

        if ($minutes > 0) {
            return $minutes.'m';
        }

        return $remainingSeconds.'s';
    }

    private function handleDisplayStatusTransition(
        User $user,
        string $previousDisplay,
        string $newDisplay,
        ?Carbon $at = null,
        ?Carbon $startedAtFallback = null
    ): void {
        $at ??= now();

        if ($previousDisplay === $newDisplay) {
            return;
        }

        foreach (self::PERIOD_STATUSES as $status) {
            if ($previousDisplay === $status && $newDisplay !== $status) {
                $this->closeOpenPeriod($user->id, $status, $at, $startedAtFallback);
            }
        }

        foreach (self::PERIOD_STATUSES as $status) {
            if ($newDisplay === $status && $previousDisplay !== $status) {
                $this->openPeriod($user->id, $status, $at);
            }
        }
    }

    private function openPeriod(string $userId, string $status, Carbon $startedAt): void
    {
        if (! in_array($status, self::PERIOD_STATUSES, true)) {
            return;
        }

        StaffPresencePeriod::query()->create([
            'user_id' => $userId,
            'status' => $status,
            'started_at' => $startedAt,
            'ended_at' => null,
        ]);
    }

    private function closeOpenPeriod(
        string $userId,
        string $status,
        Carbon $endedAt,
        ?Carbon $startedAtFallback = null
    ): void {
        $period = StaffPresencePeriod::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($period) {
            $period->update(['ended_at' => $endedAt]);

            return;
        }

        if ($startedAtFallback && $startedAtFallback->lt($endedAt)) {
            StaffPresencePeriod::query()->create([
                'user_id' => $userId,
                'status' => $status,
                'started_at' => $startedAtFallback,
                'ended_at' => $endedAt,
            ]);
        }
    }

    /**
     * @param  Collection<int, StaffPresencePeriod>  $periods
     * @return array<string, mixed>
     */
    private function buildDayPresenceStats(
        User $user,
        Collection $periods,
        Carbon $dayStart,
        Carbon $dayEnd,
        bool $isHistorical = false
    ): array {
        $result = [
            'last_offline_period_today' => '—',
            'last_offline_period_today_seconds' => null,
            'total_offline_today' => '—',
            'total_offline_today_seconds' => 0,
            'last_away_period_today' => '—',
            'last_away_period_today_seconds' => null,
            'total_away_today' => '—',
            'total_away_today_seconds' => 0,
            'last_break_period_today' => '—',
            'last_break_period_today_seconds' => null,
            'total_break_today' => '—',
            'total_break_today_seconds' => 0,
            'total_online_today' => '—',
            'total_online_today_seconds' => 0,
        ];

        foreach (self::LAST_PERIOD_STATUSES as $status) {
            $statusPeriods = $periods->where('status', $status);
            $totalSeconds = 0;
            $completed = [];

            foreach ($statusPeriods as $period) {
                $start = $period->started_at->copy()->max($dayStart);
                $end = $this->effectivePeriodEnd($user, $period, $dayEnd, $isHistorical)->copy()->min($dayEnd);

                if (! $start->lt($end)) {
                    continue;
                }

                $seconds = (int) $start->diffInSeconds($end);
                $totalSeconds += $seconds;

                if ($period->ended_at !== null && $period->ended_at->lte($dayEnd)) {
                    $completed[] = [
                        'seconds' => $seconds,
                        'ended_at' => $period->ended_at,
                    ];
                }
            }

            if ($status === self::STATUS_OFFLINE && ! $isHistorical) {
                $totalSeconds += $this->implicitInactiveOfflineSeconds($user, $statusPeriods, $dayStart, $dayEnd);
            }

            $result['total_'.$this->statsKeyPrefix($status).'_today_seconds'] = $totalSeconds;
            $result['total_'.$this->statsKeyPrefix($status).'_today'] = $totalSeconds > 0
                ? $this->formatDuration($totalSeconds)
                : '—';

            if ($completed !== []) {
                usort($completed, fn (array $a, array $b) => $b['ended_at'] <=> $a['ended_at']);
                $lastSeconds = (int) $completed[0]['seconds'];
                $prefix = $this->statsKeyPrefix($status);
                $result['last_'.$prefix.'_period_today_seconds'] = $lastSeconds;
                $result['last_'.$prefix.'_period_today'] = $this->formatDuration($lastSeconds);
            }
        }

        $onlinePeriods = $periods->where('status', self::STATUS_ONLINE);
        $onlineSeconds = 0;

        foreach ($onlinePeriods as $period) {
            $start = $period->started_at->copy()->max($dayStart);
            $end = $this->effectivePeriodEnd($user, $period, $dayEnd, $isHistorical)->copy()->min($dayEnd);

            if ($start->lt($end)) {
                $onlineSeconds += (int) $start->diffInSeconds($end);
            }
        }

        if (! $isHistorical) {
            $onlineSeconds += $this->uncountedOnlineSeconds($user, $onlinePeriods, $dayStart, $dayEnd);
        }

        $result['total_online_today_seconds'] = $onlineSeconds;
        $result['total_online_today'] = $onlineSeconds > 0
            ? $this->formatDuration($onlineSeconds)
            : '—';

        return $result;
    }

    private function uncountedOnlineSeconds(
        User $user,
        Collection $onlinePeriods,
        Carbon $dayStart,
        Carbon $dayEnd
    ): int {
        if ($this->resolveDisplayStatus($user) !== self::STATUS_ONLINE) {
            return 0;
        }

        if ($onlinePeriods->contains(fn (StaffPresencePeriod $period) => $period->ended_at === null)) {
            return 0;
        }

        $start = $this->inferPeriodStart($user, $dayStart);

        if (! $start->lt($dayEnd)) {
            return 0;
        }

        return (int) $start->diffInSeconds($dayEnd);
    }

    private function syncOpenPeriodForDisplayStatus(
        User $user,
        string $displayStatus,
        ?Carbon $at = null,
        ?Carbon $continuityStart = null
    ): void {
        $at ??= now();

        if (! in_array($displayStatus, self::PERIOD_STATUSES, true)) {
            return;
        }

        if ($this->hasOpenPeriod($user->id, $displayStatus)) {
            return;
        }

        if ($displayStatus === self::STATUS_ONLINE && $continuityStart && $continuityStart->isSameDay($at)) {
            $startedAt = $continuityStart->copy()->max(Carbon::today());
        } else {
            $startedAt = $this->inferPeriodStart($user, Carbon::today());
        }

        if ($displayStatus === self::STATUS_OFFLINE && ! $this->isRecentlyActive($user)) {
            $inactiveAt = $this->inactiveSince($user);
            $startedAt = $inactiveAt ? $inactiveAt->copy()->max(Carbon::today()) : $at;
        }

        if ($startedAt->gt($at)) {
            $startedAt = $at->copy();
        }

        $this->openPeriod($user->id, $displayStatus, $startedAt);
    }

    private function inferPeriodStart(User $user, Carbon $dayStart): Carbon
    {
        $lastEnded = StaffPresencePeriod::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', $dayStart)
            ->max('ended_at');

        if ($lastEnded) {
            return Carbon::parse($lastEnded)->copy()->max($dayStart);
        }

        $firstStartToday = StaffPresencePeriod::query()
            ->where('user_id', $user->id)
            ->where('started_at', '>=', $dayStart)
            ->min('started_at');

        if ($firstStartToday) {
            return Carbon::parse($firstStartToday)->copy()->max($dayStart);
        }

        return now();
    }

    private function hasOpenPeriod(string $userId, string $status): bool
    {
        return StaffPresencePeriod::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->whereNull('ended_at')
            ->exists();
    }

    private function effectivePeriodEnd(
        User $user,
        StaffPresencePeriod $period,
        Carbon $referenceEnd,
        bool $isHistorical = false
    ): Carbon {
        if ($period->ended_at) {
            return $period->ended_at;
        }

        if ($isHistorical) {
            return $referenceEnd;
        }

        $displayStatus = $this->resolveDisplayStatus($user);
        $inactiveAt = $this->inactiveSince($user);

        if (in_array($period->status, [self::STATUS_AWAY, self::STATUS_ON_BREAK], true)
            && $displayStatus === self::STATUS_OFFLINE
            && $inactiveAt
            && $inactiveAt->gt($period->started_at)) {
            return $inactiveAt;
        }

        if ($period->status === self::STATUS_ONLINE && $displayStatus !== self::STATUS_ONLINE) {
            return $user->last_seen_at ?? $referenceEnd;
        }

        return $referenceEnd;
    }

    /**
     * @param  Collection<int, StaffPresencePeriod>  $offlinePeriods
     */
    private function implicitInactiveOfflineSeconds(
        User $user,
        Collection $offlinePeriods,
        Carbon $dayStart,
        Carbon $dayEnd
    ): int {
        if ($this->resolveDisplayStatus($user) !== self::STATUS_OFFLINE) {
            return 0;
        }

        if ($offlinePeriods->contains(fn (StaffPresencePeriod $period) => $period->ended_at === null)) {
            return 0;
        }

        $inactiveAt = $this->inactiveSince($user);

        if (! $inactiveAt) {
            return 0;
        }

        $start = $inactiveAt->copy()->max($dayStart);

        if (! $start->lt($dayEnd)) {
            return 0;
        }

        return (int) $start->diffInSeconds($dayEnd);
    }

    private function inactiveSince(User $user): ?Carbon
    {
        if ($this->isRecentlyActive($user) || ! $user->last_seen_at) {
            return null;
        }

        return $user->last_seen_at->copy()->addSeconds(self::ONLINE_THRESHOLD_SECONDS);
    }

    private function statsKeyPrefix(string $status): string
    {
        return match ($status) {
            self::STATUS_ON_BREAK => 'break',
            default => $status,
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
