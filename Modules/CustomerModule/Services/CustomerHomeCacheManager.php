<?php

namespace Modules\CustomerModule\Services;

use Modules\CustomerModule\Jobs\WarmCustomerHomeBundleCacheJob;
use Modules\ProviderManagement\Services\ZoneProviderEligibilityService;
use Modules\ZoneManagement\Entities\Zone;

class CustomerHomeCacheManager
{
    /**
     * Bump content version, clear zone eligibility snapshots, and rebuild shared home cache.
     */
    public static function resetAndWarm(?string $zoneId = null, bool $dispatchAsync = true): int
    {
        CustomerHomeContentInvalidator::bumpGlobal($zoneId, scheduleWarm: false);
        self::forgetZoneEligibility($zoneId);
        CustomerHomeCacheWarmState::markRebuildStarted(
            CustomerHomeBaseBundleCache::estimateRebuildTotal($zoneId)
        );

        if (! $dispatchAsync) {
            return CustomerHomeBaseBundleCache::warmAll($zoneId);
        }

        if (self::shouldDispatchAsync()) {
            WarmCustomerHomeBundleCacheJob::dispatch($zoneId);

            return 0;
        }

        // QUEUE_CONNECTION=sync: prefer after-response warm when the SAPI can flush first.
        if (self::canFinishHttpResponseEarly()) {
            WarmCustomerHomeBundleCacheJob::dispatchAfterResponse($zoneId);

            return 0;
        }

        // `php artisan serve` / built-in PHP server cannot finish the response early, and a
        // sync queue with no after-response dispatch left the UI "Rebuilding… 95%" forever.
        // Explicit reset must warm inline so progress can reach complete.
        return CustomerHomeBaseBundleCache::warmAll($zoneId);
    }

    public static function warmAfterContentChange(?string $zoneId = null, bool $blocking = false): void
    {
        if ($blocking) {
            CustomerHomeBaseBundleCache::warmAll($zoneId);

            return;
        }

        if (self::shouldDispatchAsync()) {
            WarmCustomerHomeBundleCacheJob::dispatch($zoneId);

            return;
        }

        if (app()->runningInConsole()) {
            CustomerHomeBaseBundleCache::warmAll($zoneId);

            return;
        }

        // Content version is already bumped, so customers miss stale cache keys automatically.
        // Only queue an after-response warm when the SAPI can finish the HTTP response first.
        // php artisan serve / built-in PHP server cannot — warming there blocks admin saves for seconds.
        if (self::canFinishHttpResponseEarly()) {
            WarmCustomerHomeBundleCacheJob::dispatchAfterResponse($zoneId);
        }
    }

    /**
     * Warm a single zone's shared home-bundle without bumping content version.
     * Called from get-zone-id so the subsequent /home-bundle is usually a cache hit.
     * Throttled per zone so frequent map moves do not flood the queue.
     */
    public static function ensureZoneWarm(string $zoneId): void
    {
        $zoneId = trim($zoneId);
        if ($zoneId === '') {
            return;
        }

        $throttleKey = 'customer_home_zone_warm:'.$zoneId;
        if (! \Illuminate\Support\Facades\Cache::add($throttleKey, 1, 120)) {
            return;
        }

        self::warmAfterContentChange($zoneId);
    }

    private static function forgetZoneEligibility(?string $zoneId): void
    {
        if ($zoneId !== null && $zoneId !== '') {
            ZoneProviderEligibilityService::forgetZone($zoneId);

            return;
        }

        $zoneIds = Zone::query()->where('is_active', 1)->pluck('id');
        foreach ($zoneIds as $id) {
            ZoneProviderEligibilityService::forgetZone((string) $id);
        }
    }

    private static function shouldDispatchAsync(): bool
    {
        return (string) config('queue.default') !== 'sync';
    }

    private static function canFinishHttpResponseEarly(): bool
    {
        return function_exists('fastcgi_finish_request')
            || function_exists('litespeed_finish_request');
    }
}
