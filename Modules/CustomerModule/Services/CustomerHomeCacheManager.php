<?php

namespace Modules\CustomerModule\Services;

use Modules\CustomerModule\Jobs\WarmCustomerHomeBundleCacheJob;
use Modules\ProviderManagement\Services\ZoneProviderEligibilityService;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Home cache lifecycle for Hostinger / file-cache deployments.
 *
 * Only admin "Reset home cache" (or artisan customer:home-cache:warm) rebuilds.
 * Content saves do not invalidate or warm — API keeps serving the last build.
 */
class CustomerHomeCacheManager
{
    /**
     * Manual rebuild: bump version (so apps refetch), clear eligibility, rebuild store.
     */
    public static function resetAndWarm(?string $zoneId = null, bool $dispatchAsync = true): int
    {
        // Version bump is intentional here ONLY — tells mobile apps a new build exists.
        CustomerHomeContentInvalidator::bumpGlobal($zoneId, scheduleWarm: false);
        self::forgetZoneEligibility($zoneId);
        // Clear short-lived version endpoint cache so clients see the new version quickly.
        self::forgetVersionEndpointCaches();

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

        // Hostinger often uses QUEUE_CONNECTION=sync — finish HTTP then rebuild,
        // or rebuild inline when the SAPI cannot flush early.
        if (self::canFinishHttpResponseEarly()) {
            WarmCustomerHomeBundleCacheJob::dispatchAfterResponse($zoneId);

            return 0;
        }

        return CustomerHomeBaseBundleCache::warmAll($zoneId);
    }

    /**
     * @deprecated Auto-warm after content edits is disabled (manual rebuild only).
     */
    public static function warmAfterContentChange(?string $zoneId = null, bool $blocking = false): void
    {
        // Intentionally no-op. Home cache updates only via resetAndWarm / artisan.
    }

    /**
     * @deprecated Auto-warm on get-zone-id / cache miss is disabled.
     */
    public static function ensureZoneWarm(string $zoneId): void
    {
        // Intentionally no-op. Admin must rebuild home cache manually.
    }

    /**
     * @deprecated Auto dispatch warm is disabled.
     */
    public static function dispatchWarmOnly(?string $zoneId = null): void
    {
        // Intentionally no-op.
    }

    private static function forgetVersionEndpointCaches(): void
    {
        // Version payloads use a short TTL; best-effort forget by known prefixes is hard
        // on file cache. Bumping TTL window (30s) is enough after rebuild.
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
