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

        if ($dispatchAsync && self::shouldDispatchAsync()) {
            WarmCustomerHomeBundleCacheJob::dispatch($zoneId);

            return 0;
        }

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

        // QUEUE_CONNECTION=sync runs jobs inline; defer until after the HTTP response.
        WarmCustomerHomeBundleCacheJob::dispatchAfterResponse($zoneId);
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
}
