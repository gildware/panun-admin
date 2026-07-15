<?php

namespace Modules\CustomerModule\Services;

/**
 * Home content version + invalidation.
 *
 * Manual rebuild model (Hostinger / file cache):
 * - Global version is bumped ONLY when admin clicks "Reset home cache".
 * - Eloquent observers do NOT bump or warm (content edits keep serving last build).
 * - Personal version still bumps for logged-in recently-viewed (does not clear shared cache).
 */
class CustomerHomeContentInvalidator
{
    /**
     * @param  bool  $scheduleWarm  Ignored — auto-warm is disabled; use resetAndWarm.
     */
    public static function bumpGlobal(?string $zoneId = null, bool $scheduleWarm = true): void
    {
        CustomerHomeContentVersion::bumpGlobal();
        // Never schedule warm from here. Rebuild is admin-only via CustomerHomeCacheManager::resetAndWarm.
    }

    public static function bumpPersonal(int|string $userId): void
    {
        CustomerHomeContentVersion::bumpPersonal($userId);
    }

    /**
     * @deprecated Auto warm after content change is disabled.
     */
    public static function scheduleWarm(?string $zoneId = null): void
    {
        // Intentionally no-op.
    }
}
