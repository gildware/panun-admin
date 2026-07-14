<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks whether server home-bundle cache has been rebuilt since the last content change,
 * plus in-flight rebuild progress for the admin chrome UI.
 */
class CustomerHomeCacheWarmState
{
    private const LAST_WARMED_VERSION_KEY = 'customer_home_cache_last_warmed_global_version';

    private const REBUILD_STATUS_KEY = 'customer_home_cache_rebuild_status';

    private const REBUILD_DONE_KEY = 'customer_home_cache_rebuild_done';

    private const REBUILD_TOTAL_KEY = 'customer_home_cache_rebuild_total';

    private const REBUILD_ERROR_KEY = 'customer_home_cache_rebuild_error';

    private const REBUILD_UPDATED_AT_KEY = 'customer_home_cache_rebuild_updated_at';

    private const REBUILD_STARTED_AT_KEY = 'customer_home_cache_rebuild_started_at';

    public const STATUS_IDLE = 'idle';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_FAILED = 'failed';

    public static function markWarmed(): void
    {
        Cache::forever(self::LAST_WARMED_VERSION_KEY, CustomerHomeContentVersion::global());
    }

    public static function lastWarmedVersion(): int
    {
        return (int) Cache::get(self::LAST_WARMED_VERSION_KEY, 0);
    }

    public static function currentVersion(): int
    {
        return (int) CustomerHomeContentVersion::global();
    }

    public static function needsAdminReminder(): bool
    {
        return self::currentVersion() > self::lastWarmedVersion();
    }

    public static function markRebuildStarted(int $total = 0): void
    {
        $now = now()->timestamp;
        Cache::put(self::REBUILD_STATUS_KEY, self::STATUS_RUNNING, now()->addHours(2));
        Cache::put(self::REBUILD_DONE_KEY, 0, now()->addHours(2));
        Cache::put(self::REBUILD_TOTAL_KEY, max(0, $total), now()->addHours(2));
        Cache::forget(self::REBUILD_ERROR_KEY);
        Cache::put(self::REBUILD_STARTED_AT_KEY, $now, now()->addHours(2));
        Cache::put(self::REBUILD_UPDATED_AT_KEY, $now, now()->addHours(2));
    }

    public static function markRebuildProgress(int $done, int $total): void
    {
        Cache::put(self::REBUILD_STATUS_KEY, self::STATUS_RUNNING, now()->addHours(2));
        Cache::put(self::REBUILD_DONE_KEY, max(0, $done), now()->addHours(2));
        Cache::put(self::REBUILD_TOTAL_KEY, max(0, $total), now()->addHours(2));
        Cache::put(self::REBUILD_UPDATED_AT_KEY, now()->timestamp, now()->addHours(2));
    }

    public static function markRebuildComplete(): void
    {
        $total = (int) Cache::get(self::REBUILD_TOTAL_KEY, 0);
        Cache::put(self::REBUILD_STATUS_KEY, self::STATUS_COMPLETE, now()->addMinutes(30));
        Cache::put(self::REBUILD_DONE_KEY, $total > 0 ? $total : (int) Cache::get(self::REBUILD_DONE_KEY, 0), now()->addMinutes(30));
        Cache::forget(self::REBUILD_ERROR_KEY);
        Cache::put(self::REBUILD_UPDATED_AT_KEY, now()->timestamp, now()->addMinutes(30));
    }

    public static function markRebuildFailed(string $message): void
    {
        $safe = trim($message);
        if ($safe === '') {
            $safe = 'Failed to rebuild home cache';
        }

        Cache::put(self::REBUILD_STATUS_KEY, self::STATUS_FAILED, now()->addHours(2));
        Cache::put(self::REBUILD_ERROR_KEY, mb_substr($safe, 0, 500), now()->addHours(2));
        Cache::put(self::REBUILD_UPDATED_AT_KEY, now()->timestamp, now()->addHours(2));
    }

    /**
     * @return array{status: string, done: int, total: int, percent: int, error: string|null, updated_at: int|null, started_at: int|null}
     */
    public static function rebuildStatus(): array
    {
        $status = (string) Cache::get(self::REBUILD_STATUS_KEY, self::STATUS_IDLE);
        $done = (int) Cache::get(self::REBUILD_DONE_KEY, 0);
        $total = (int) Cache::get(self::REBUILD_TOTAL_KEY, 0);
        $error = Cache::get(self::REBUILD_ERROR_KEY);
        $updatedAt = Cache::get(self::REBUILD_UPDATED_AT_KEY);
        $startedAt = Cache::get(self::REBUILD_STARTED_AT_KEY);

        $percent = 0;
        if ($status === self::STATUS_COMPLETE) {
            $percent = 100;
        } elseif ($total > 0) {
            $percent = (int) min(99, max(0, (int) floor(($done / $total) * 100)));
            if ($status === self::STATUS_RUNNING && $percent < 1) {
                $percent = 1;
            }
        } elseif ($status === self::STATUS_RUNNING) {
            $percent = 1;
        }

        return [
            'status' => $status !== '' ? $status : self::STATUS_IDLE,
            'done' => $done,
            'total' => $total,
            'percent' => $percent,
            'error' => is_string($error) && $error !== '' ? $error : null,
            'updated_at' => is_numeric($updatedAt) ? (int) $updatedAt : null,
            'started_at' => is_numeric($startedAt) ? (int) $startedAt : null,
        ];
    }
}
