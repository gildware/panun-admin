<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Central invalidation for customer home bundle content.
 *
 * Coverage audit (global bump = all apps re-fetch when version changes):
 * - Observed Eloquent models: Banner, Campaign, Advertisement, Category (incl. sub-categories),
 *   Service, Provider, Variation, ServiceVariant, ProviderShowcaseItem, Discount, Zone
 * - Layout / config: MobileAppManagementService, BusinessInformationController (forgetConfigCaches)
 * - Manual: admin Reset home cache button (resetAndWarm)
 * - Personal (logged-in recently viewed): ServiceController::show via bumpPersonal
 *
 * Not globally bumped (by design): favorites, recently viewed list order (personal bump only),
 * wallet, bookings, chat.
 */
class CustomerHomeContentInvalidator
{
    private const WARM_THROTTLE_KEY = 'customer_home_invalidate_warm_lock';

    private const WARM_THROTTLE_SECONDS = 60;

    public static function bumpGlobal(?string $zoneId = null, bool $scheduleWarm = true): void
    {
        CustomerHomeContentVersion::bumpGlobal();

        if ($scheduleWarm) {
            self::scheduleWarm($zoneId);
        }
    }

    public static function bumpPersonal(int|string $userId): void
    {
        CustomerHomeContentVersion::bumpPersonal($userId);
    }

    public static function scheduleWarm(?string $zoneId = null): void
    {
        if (! Cache::add(self::WARM_THROTTLE_KEY, 1, self::WARM_THROTTLE_SECONDS)) {
            return;
        }

        CustomerHomeCacheManager::warmAfterContentChange($zoneId);
    }
}
