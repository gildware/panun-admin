<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Modules\ProviderManagement\Entities\Provider;

class ZoneProviderEligibilityService
{
    public const CACHE_TTL = 60;

    private const CACHE_VERSION = 'v1';

    /** @var array<string, array{strict: list<string>, ads_base: list<string>, booking: list<string>, advertisement: list<string>}> */
    private static array $requestCache = [];

    public function __construct(
        private Provider $provider,
        private ProviderPackageEligibilityResolver $eligibilityResolver,
    ) {}

    /**
     * @return array{strict: list<string>, ads_base: list<string>, booking: list<string>, advertisement: list<string>}
     */
    public function snapshot(?string $zoneId = null): array
    {
        $zoneId = (string) ($zoneId ?? Config::get('zone_id') ?? '');
        if ($zoneId === '') {
            return $this->emptySnapshot();
        }

        if (isset(self::$requestCache[$zoneId])) {
            return self::$requestCache[$zoneId];
        }

        $snapshot = Cache::remember(
            self::cacheKey($zoneId),
            self::CACHE_TTL,
            fn () => $this->buildSnapshot($zoneId),
        );

        self::$requestCache[$zoneId] = $snapshot;

        return $snapshot;
    }

    /**
     * @return list<string>
     */
    public function bookingEligibleIds(?string $zoneId = null): array
    {
        return $this->snapshot($zoneId)['booking'];
    }

    /**
     * @return list<string>
     */
    public function advertisementEligibleIds(?string $zoneId = null): array
    {
        return $this->snapshot($zoneId)['advertisement'];
    }

    public static function forgetZone(?string $zoneId): void
    {
        if ($zoneId) {
            Cache::forget(self::cacheKey($zoneId));
            unset(self::$requestCache[$zoneId]);
        }
    }

    /**
     * @return array{strict: list<string>, ads_base: list<string>, booking: list<string>, advertisement: list<string>}
     */
    private function buildSnapshot(string $zoneId): array
    {
        $strictIds = $this->provider
            ->coveringLeafZone($zoneId)
            ->ofStatus(1)
            ->where('app_availability', 1)
            ->where('is_suspended', 0)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $adsBaseIds = $this->provider
            ->coveringLeafZone($zoneId)
            ->ofStatus(1)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $preloadIds = array_values(array_unique(array_merge($strictIds, $adsBaseIds)));
        if ($preloadIds === []) {
            return $this->emptySnapshot();
        }

        $resolver = $this->eligibilityResolver->preload($preloadIds);

        return [
            'strict' => $strictIds,
            'ads_base' => $adsBaseIds,
            'booking' => $resolver->filterBookingEligible($strictIds),
            'advertisement' => $resolver->filterAdvertisementEligible($adsBaseIds),
        ];
    }

    /**
     * @return array{strict: list<string>, ads_base: list<string>, booking: list<string>, advertisement: list<string>}
     */
    private function emptySnapshot(): array
    {
        return [
            'strict' => [],
            'ads_base' => [],
            'booking' => [],
            'advertisement' => [],
        ];
    }

    private static function cacheKey(string $zoneId): string
    {
        return 'zone_provider_eligibility:'.self::CACHE_VERSION.':'.$zoneId;
    }
}
