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

        // QUEUE_CONNECTION=sync: only warm after response when the SAPI can finish first.
        // Otherwise mark for lazy rebuild (version already bumped).
        if (self::canFinishHttpResponseEarly()) {
            WarmCustomerHomeBundleCacheJob::dispatchAfterResponse($zoneId);
        }

        return 0;
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
