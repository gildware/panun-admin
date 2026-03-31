<?php

namespace Modules\ServiceManagement\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Modules\CategoryManagement\Entities\Category;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneCoverageNormalizationService;

class Variation extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'float',
    ];

    protected $fillable = ['variant', 'variant_key', 'zone_id', 'price', 'service_id'];

    public function zone(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Zone IDs to match variation rows: selected zone plus descendant leaves (pricing often uses leaf IDs).
     *
     * @return array<int, string>
     */
    public static function zoneIdsMatchingBookingSelection(string $zoneId): array
    {
        $selected = (string) $zoneId;
        $leafIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds([$selected]);

        return array_values(array_unique(array_merge([$selected], $leafIds)));
    }

    /**
     * Main category is linked to at least one zone overlapping the booking zone (selected + descendant leaves).
     */
    public static function categoryAvailableForBookingZone(string $categoryId, string $bookingZoneId): bool
    {
        $zoneIds = static::zoneIdsMatchingBookingSelection($bookingZoneId);
        if ($zoneIds === []) {
            return false;
        }

        return Category::query()
            ->withoutGlobalScope('translate')
            ->where('id', $categoryId)
            ->where('is_active', 1)
            ->whereHas('zones', function ($query) use ($zoneIds) {
                $query->whereIn('zones.id', $zoneIds);
            })
            ->exists();
    }

    /**
     * Stored per-variant flags on the service, or inferred for legacy rows (single price across zones => default pricing).
     *
     * @return array{use_zone_pricing: bool, default_price: float}
     */
    public static function variationPricingConfig(Service $service, string $variantKey): array
    {
        $stored = $service->variation_pricing[$variantKey] ?? null;
        if (is_array($stored) && array_key_exists('use_zone_pricing', $stored)) {
            return [
                'use_zone_pricing' => (bool) $stored['use_zone_pricing'],
                'default_price' => (float) ($stored['default_price'] ?? 0),
            ];
        }

        $prices = static::query()
            ->where('service_id', $service->id)
            ->where('variant_key', $variantKey)
            ->pluck('price')
            ->map(fn ($p) => round((float) $p, 4));

        if ($prices->isEmpty()) {
            return [
                'use_zone_pricing' => true,
                'default_price' => 0,
            ];
        }

        $unique = $prices->unique()->values();
        if ($unique->count() <= 1) {
            return [
                'use_zone_pricing' => false,
                'default_price' => (float) ($unique->first() ?? 0),
            ];
        }

        return [
            'use_zone_pricing' => true,
            'default_price' => static::minPositivePriceAmongZones((string) $service->id, $variantKey),
        ];
    }

    /**
     * Lowest positive price for this variant across all zones (fallback when default_price is missing / zero).
     */
    public static function minPositivePriceAmongZones(string $serviceId, string $variantKey): float
    {
        $v = static::query()
            ->where('service_id', $serviceId)
            ->where('variant_key', $variantKey)
            ->where('price', '>', 0)
            ->min('price');

        return (float) ($v ?? 0);
    }

    /**
     * For zone pricing ON: admin default column, else smallest positive zone price in DB.
     */
    public static function resolveDefaultPriceWhenZonePricing(Service $service, string $variantKey, array $config): float
    {
        $d = (float) ($config['default_price'] ?? 0);
        if ($d > 0) {
            return $d;
        }

        return static::minPositivePriceAmongZones((string) $service->id, $variantKey);
    }

    /**
     * @param  array{use_zone_pricing: bool, default_price: float}  $config
     */
    protected static function syntheticVariationFromBase(
        ?self $base,
        string $serviceId,
        string $variantKey,
        string $zoneId,
        float $price
    ): ?self {
        if (! $base) {
            return null;
        }

        return new Variation([
            'variant' => $base->variant,
            'variant_key' => $variantKey,
            'service_id' => $serviceId,
            'zone_id' => (string) $zoneId,
            'price' => $price,
        ]);
    }

    /**
     * When zone pricing is off: always use default_price. When on: zone-specific row if price &gt; 0, else default/fallback.
     */
    public static function firstForBookingZone(
        string $serviceId,
        string $variantKey,
        string $zoneId,
        bool $requirePositivePrice = true
    ): ?self {
        $service = Service::query()->select('id', 'category_id', 'variation_pricing')->find($serviceId);
        if (! $service) {
            return null;
        }

        $config = static::variationPricingConfig($service, $variantKey);

        $base = static::query()
            ->where('service_id', $serviceId)
            ->where('variant_key', $variantKey)
            ->first();

        if (! $config['use_zone_pricing']) {
            $price = $config['default_price'];
            if ($requirePositivePrice && $price <= 0) {
                return null;
            }

            return static::syntheticVariationFromBase($base, $serviceId, $variantKey, $zoneId, $price);
        }

        $zoneIds = static::zoneIdsMatchingBookingSelection($zoneId);

        // 1) Prefer a positive-priced row for the booking zone (exact, then leaves).
        if ($zoneIds !== []) {
            $hit = static::query()
                ->where('service_id', $serviceId)
                ->where('variant_key', $variantKey)
                ->whereIn('zone_id', $zoneIds)
                ->where('price', '>', 0)
                ->orderByRaw('CASE WHEN zone_id = ? THEN 0 ELSE 1 END', [$zoneId])
                ->orderBy('price')
                ->first();
            if ($hit) {
                return $hit;
            }
        }

        // 2) No usable zone price: use admin default for the variation, then any positive zone price in DB.
        $fallback = static::resolveDefaultPriceWhenZonePricing($service, $variantKey, $config);
        if ($fallback > 0) {
            return static::syntheticVariationFromBase($base, $serviceId, $variantKey, $zoneId, $fallback);
        }

        if ($requirePositivePrice) {
            return static::query()
                ->where('service_id', $serviceId)
                ->where('variant_key', $variantKey)
                ->where('price', '>', 0)
                ->orderBy('price')
                ->first();
        }

        // Display / non-booking: prefer positive zone row; if zone row is 0, show default / min-positive instead of 0.
        if ($zoneIds !== []) {
            $zoneRow = static::query()
                ->where('service_id', $serviceId)
                ->where('variant_key', $variantKey)
                ->whereIn('zone_id', $zoneIds)
                ->orderByRaw('CASE WHEN zone_id = ? THEN 0 ELSE 1 END', [$zoneId])
                ->orderByRaw('CASE WHEN price > 0 THEN 0 ELSE 1 END')
                ->orderBy('price')
                ->first();
            if ($zoneRow && $zoneRow->price > 0) {
                return $zoneRow;
            }
            if ($zoneRow && $zoneRow->price <= 0) {
                $showPrice = static::resolveDefaultPriceWhenZonePricing($service, $variantKey, $config);
                if ($showPrice > 0) {
                    return static::syntheticVariationFromBase($base, $serviceId, $variantKey, $zoneId, $showPrice);
                }

                return $zoneRow;
            }
        }

        return static::query()
            ->where('service_id', $serviceId)
            ->where('variant_key', $variantKey)
            ->orderBy('price')
            ->first();
    }

    /**
     * All distinct variants for the service when its category is available in the booking zone.
     *
     * @return Collection<int, self>
     */
    public static function listForBookingZone(string $serviceId, string $zoneId): Collection
    {
        $service = Service::query()->select('id', 'category_id', 'variation_pricing')->find($serviceId);
        if (! $service || ! static::categoryAvailableForBookingZone((string) $service->category_id, $zoneId)) {
            return collect();
        }

        $keys = static::query()
            ->where('service_id', $serviceId)
            ->distinct()
            ->pluck('variant_key')
            ->filter()
            ->values();

        return $keys
            ->map(fn ($vk) => static::firstForBookingZone($serviceId, (string) $vk, $zoneId, false))
            ->filter()
            ->sortBy('variant_key')
            ->values();
    }

    protected static function booted()
    {
        static::addGlobalScope('zone_wise_data', function (Builder $builder) {
            if (request()->is('api/*/customer?*') || request()->is('api/*/customer/*')) {
                $builder->where(['zone_id' => Config::get('zone_id')])->with(['zone:id,name']);
            } elseif (request()->is('api/*/provider?*') || request()->is('api/*/provider/*')) {
                if (auth()->check() && auth()->user()->provider != null) {
                    $p = auth()->user()->provider;
                    $zoneIds = $p->zones()->pluck('zones.id');
                    if ($zoneIds->isEmpty() && $p->zone_id) {
                        $zoneIds = collect([(string) $p->zone_id]);
                    }
                    $builder->whereIn('zone_id', $zoneIds)->with(['zone:id,name']);
                }
            }
        });
    }
}
