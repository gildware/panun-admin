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

    protected $fillable = ['variant', 'variant_key', 'zone_id', 'price', 'service_id', 'service_variant_id'];

    public function zone(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function serviceVariant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ServiceVariant::class, 'service_variant_id', 'id');
    }

    /**
     * Zone IDs to match variation rows: selected zone plus descendant leaves (pricing often uses leaf IDs).
     *
     * @return array<int, string>
     */
    public static function zoneIdsMatchingBookingSelection(string $zoneId): array
    {
        static $cache = [];

        $selected = (string) $zoneId;
        if (array_key_exists($selected, $cache)) {
            return $cache[$selected];
        }

        $leafIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds([$selected]);

        return $cache[$selected] = array_values(array_unique(array_merge([$selected], $leafIds)));
    }

    /**
     * Resolve a variation using preloaded rows (no per-variant DB lookups).
     */
    public static function resolveFromPreloaded(
        Service $service,
        string $variantKey,
        string $zoneId,
        Collection $serviceVariationRows,
        bool $requirePositivePrice = true
    ): ?self {
        return static::firstForBookingZonePreloaded(
            $service,
            $variantKey,
            $zoneId,
            $requirePositivePrice,
            $serviceVariationRows
        );
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
     * Pricing/booking lookups must see all zone rows; the customer API global scope only applies to eager-loaded lists.
     */
    protected static function variantQuery(): Builder
    {
        return static::query()->withoutGlobalScopes();
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

        $prices = static::variantQuery()
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
        $v = static::variantQuery()
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
    ): self {
        $variantLabel = $base?->variant ?? ucwords(str_replace('-', ' ', $variantKey));

        return new Variation([
            'variant' => $variantLabel,
            'variant_key' => $variantKey,
            'service_id' => $serviceId,
            'service_variant_id' => $base?->service_variant_id,
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

        $base = static::variantQuery()
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
            $hit = static::variantQuery()
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
            return static::variantQuery()
                ->where('service_id', $serviceId)
                ->where('variant_key', $variantKey)
                ->where('price', '>', 0)
                ->orderBy('price')
                ->first();
        }

        // Display / non-booking: prefer positive zone row; if zone row is 0, show default / min-positive instead of 0.
        if ($zoneIds !== []) {
            $zoneRow = static::variantQuery()
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

        return static::variantQuery()
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

        $keys = static::variantQuery()
            ->where('service_id', $serviceId)
            ->distinct()
            ->pluck('variant_key')
            ->filter()
            ->values();

        $sortMap = ServiceVariant::query()
            ->where('service_id', $serviceId)
            ->pluck('sort_order', 'variant_key');

        return $keys
            ->map(fn ($vk) => static::firstForBookingZone($serviceId, (string) $vk, $zoneId, false))
            ->filter()
            ->sortBy(fn (self $row) => [
                (int) ($sortMap[(string) $row->variant_key] ?? 9999),
                (string) $row->variant_key,
            ])
            ->values();
    }

    /**
     * Parse zone id header / address value (single UUID, comma list, or bracketed list).
     *
     * @return array<int, string>
     */
    public static function parseZoneIdCandidates(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $cleaned = str_replace(['[', ']', '"', "'"], '', $raw);
        $parts = array_map('trim', explode(',', $cleaned));

        return array_values(array_filter($parts, function (string $id): bool {
            return preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $id) === 1;
        }));
    }

    /**
     * Customer-app payload: one row per variant for the booking zone (zone tree + default/fallback pricing).
     *
     * @return array{zone_id: string|null, default_price: float, zone_wise_variations: list<array{variant_key: string, variant_name: string, price: float}>}
     */
    public static function variationsAppFormatForCustomer(string $serviceId, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? Config::get('zone_id');
        $candidates = static::parseZoneIdCandidates(is_string($zoneId) ? $zoneId : null);
        $formatting = [
            'zone_id' => $candidates[0] ?? (is_string($zoneId) ? $zoneId : null),
            'default_price' => 0.0,
            'zone_wise_variations' => [],
        ];

        if ($candidates === []) {
            return $formatting;
        }

        /** @var array<string, self> $seen */
        $seen = [];
        $resolvedZone = null;

        foreach ($candidates as $candidate) {
            $list = static::listForBookingZone($serviceId, $candidate);
            if ($list->isNotEmpty() && $resolvedZone === null) {
                $resolvedZone = $candidate;
            }

            foreach ($list as $variation) {
                $key = (string) $variation->variant_key;
                if (! isset($seen[$key])) {
                    $seen[$key] = $variation;
                }
            }
        }

        if ($seen === []) {
            return $formatting;
        }

        $formatting['zone_id'] = $resolvedZone ?? $candidates[0];

        $serviceVariants = ServiceVariant::query()
            ->where('service_id', $serviceId)
            ->whereIn('variant_key', array_keys($seen))
            ->where('is_active', true)
            ->with('storage_image')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('variant_key');

        $orderedKeys = $serviceVariants->keys()
            ->merge(array_keys($seen))
            ->unique()
            ->values();

        foreach ($orderedKeys as $variantKey) {
            $variation = $seen[(string) $variantKey] ?? null;
            if (! $variation) {
                continue;
            }
            $variantMeta = $serviceVariants->get((string) $variantKey);
            $formatting['zone_wise_variations'][] = [
                'variant_key' => $variation->variant_key,
                'variant_name' => $variantMeta?->title ?? $variation->variant,
                'description' => $variantMeta?->description,
                'note' => $variantMeta?->note,
                'image' => $variantMeta?->image,
                'image_full_path' => $variantMeta?->image_full_path,
                'icon' => $variantMeta?->icon,
                'price' => (float) $variation->price,
            ];
        }

        if ($formatting['zone_wise_variations'] !== []) {
            $formatting['default_price'] = (float) $formatting['zone_wise_variations'][0]['price'];
        }

        return $formatting;
    }

    /**
     * @param  list<string>  $categoryIds
     * @return array<string, bool>
     */
    public static function categoriesAvailableForBookingZoneMap(array $categoryIds, string $bookingZoneId): array
    {
        $zoneIds = static::zoneIdsMatchingBookingSelection($bookingZoneId);
        $normalizedCategoryIds = array_values(array_unique(array_filter(array_map('strval', $categoryIds))));

        if ($zoneIds === [] || $normalizedCategoryIds === []) {
            return array_fill_keys($normalizedCategoryIds, false);
        }

        $availableIds = Category::query()
            ->withoutGlobalScope('translate')
            ->whereIn('id', $normalizedCategoryIds)
            ->where('is_active', 1)
            ->whereHas('zones', function ($query) use ($zoneIds) {
                $query->whereIn('zones.id', $zoneIds);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->flip()
            ->all();

        $availability = [];
        foreach ($normalizedCategoryIds as $categoryId) {
            $availability[$categoryId] = isset($availableIds[$categoryId]);
        }

        return $availability;
    }

    /**
     * @return array{use_zone_pricing: bool, default_price: float}
     */
    private static function variationPricingConfigFromPreloaded(Service $service, string $variantKey, Collection $variantRows): array
    {
        $stored = $service->variation_pricing[$variantKey] ?? null;
        if (is_array($stored) && array_key_exists('use_zone_pricing', $stored)) {
            return [
                'use_zone_pricing' => (bool) $stored['use_zone_pricing'],
                'default_price' => (float) ($stored['default_price'] ?? 0),
            ];
        }

        $prices = $variantRows
            ->where('variant_key', $variantKey)
            ->pluck('price')
            ->map(fn ($price) => round((float) $price, 4));

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

        $minPositive = $variantRows
            ->where('variant_key', $variantKey)
            ->where('price', '>', 0)
            ->min('price');

        return [
            'use_zone_pricing' => true,
            'default_price' => (float) ($minPositive ?? 0),
        ];
    }

    private static function firstForBookingZonePreloaded(
        Service $service,
        string $variantKey,
        string $zoneId,
        bool $requirePositivePrice,
        Collection $serviceVariationRows
    ): ?self {
        $serviceId = (string) $service->id;
        $variantRows = $serviceVariationRows->where('variant_key', $variantKey)->values();
        $config = static::variationPricingConfigFromPreloaded($service, $variantKey, $serviceVariationRows);
        $base = $variantRows->first();

        if (! $config['use_zone_pricing']) {
            $price = $config['default_price'];
            if ($requirePositivePrice && $price <= 0) {
                return null;
            }

            return static::syntheticVariationFromBase($base, $serviceId, $variantKey, $zoneId, $price);
        }

        $zoneIds = static::zoneIdsMatchingBookingSelection($zoneId);

        if ($zoneIds !== []) {
            $hit = $variantRows
                ->filter(fn (self $row) => in_array((string) $row->zone_id, $zoneIds, true) && $row->price > 0)
                ->sortBy(fn (self $row) => [((string) $row->zone_id === $zoneId ? 0 : 1), (float) $row->price])
                ->first();
            if ($hit) {
                return $hit;
            }
        }

        $fallback = static::resolveDefaultPriceFromPreloaded($service, $variantKey, $config, $serviceVariationRows);
        if ($fallback > 0) {
            return static::syntheticVariationFromBase($base, $serviceId, $variantKey, $zoneId, $fallback);
        }

        if ($requirePositivePrice) {
            return $variantRows
                ->where('price', '>', 0)
                ->sortBy('price')
                ->first();
        }

        if ($zoneIds !== []) {
            $zoneRow = $variantRows
                ->filter(fn (self $row) => in_array((string) $row->zone_id, $zoneIds, true))
                ->sortBy(fn (self $row) => [((string) $row->zone_id === $zoneId ? 0 : 1), ($row->price > 0 ? 0 : 1), (float) $row->price])
                ->first();
            if ($zoneRow && $zoneRow->price > 0) {
                return $zoneRow;
            }
            if ($zoneRow && $zoneRow->price <= 0) {
                $showPrice = static::resolveDefaultPriceFromPreloaded($service, $variantKey, $config, $serviceVariationRows);
                if ($showPrice > 0) {
                    return static::syntheticVariationFromBase($base, $serviceId, $variantKey, $zoneId, $showPrice);
                }

                return $zoneRow;
            }
        }

        return $variantRows->sortBy('price')->first();
    }

    /**
     * @param  array{use_zone_pricing: bool, default_price: float}  $config
     */
    private static function resolveDefaultPriceFromPreloaded(
        Service $service,
        string $variantKey,
        array $config,
        Collection $serviceVariationRows
    ): float {
        $default = (float) ($config['default_price'] ?? 0);
        if ($default > 0) {
            return $default;
        }

        $minPositive = $serviceVariationRows
            ->where('variant_key', $variantKey)
            ->where('price', '>', 0)
            ->min('price');

        return (float) ($minPositive ?? 0);
    }

    /**
     * @return Collection<int, self>
     */
    private static function listForBookingZonePreloaded(
        Service $service,
        string $zoneId,
        bool $categoryAvailable,
        Collection $serviceVariationRows
    ): Collection {
        if (! $categoryAvailable) {
            return collect();
        }

        $keys = $serviceVariationRows
            ->pluck('variant_key')
            ->filter()
            ->unique()
            ->values();

        $sortMap = ServiceVariant::query()
            ->where('service_id', $service->id)
            ->pluck('sort_order', 'variant_key');

        return $keys
            ->map(fn ($variantKey) => static::firstForBookingZonePreloaded(
                $service,
                (string) $variantKey,
                $zoneId,
                false,
                $serviceVariationRows
            ))
            ->filter()
            ->sortBy(fn (self $row) => [
                (int) ($sortMap[(string) $row->variant_key] ?? 9999),
                (string) $row->variant_key,
            ])
            ->values();
    }

    /**
     * @param  array<int, string>  $candidates
     * @param  array{zone_id: string|null, default_price: float, zone_wise_variations: list<array<string, mixed>>}  $emptyTemplate
     * @return array{zone_id: string|null, default_price: float, zone_wise_variations: list<array<string, mixed>>}
     */
    private static function variationsAppFormatFromPreloaded(
        Service $service,
        array $candidates,
        Collection $serviceVariationRows,
        Collection $serviceVariantsByKey,
        array $emptyTemplate,
        array &$categoryAvailabilityCache = []
    ): array {
        $formatting = $emptyTemplate;
        $categoryId = (string) $service->category_id;

        /** @var array<string, self> $seen */
        $seen = [];
        $resolvedZone = null;

        foreach ($candidates as $candidate) {
            $cacheKey = $categoryId.'|'.$candidate;
            if (! array_key_exists($cacheKey, $categoryAvailabilityCache)) {
                $categoryAvailabilityCache[$cacheKey] = static::categoryAvailableForBookingZone($categoryId, $candidate);
            }
            if (! $categoryAvailabilityCache[$cacheKey]) {
                continue;
            }

            $list = static::listForBookingZonePreloaded($service, $candidate, true, $serviceVariationRows);
            if ($list->isNotEmpty() && $resolvedZone === null) {
                $resolvedZone = $candidate;
            }

            foreach ($list as $variation) {
                $key = (string) $variation->variant_key;
                if (! isset($seen[$key])) {
                    $seen[$key] = $variation;
                }
            }
        }

        if ($seen === []) {
            return $formatting;
        }

        $formatting['zone_id'] = $resolvedZone ?? $candidates[0];

        $orderedKeys = $serviceVariantsByKey->sortBy('sort_order')->keys()
            ->merge(array_keys($seen))
            ->unique()
            ->values();

        foreach ($orderedKeys as $variantKey) {
            $variation = $seen[(string) $variantKey] ?? null;
            if (! $variation) {
                continue;
            }
            $variantMeta = $serviceVariantsByKey->get((string) $variantKey);
            $formatting['zone_wise_variations'][] = [
                'variant_key' => $variation->variant_key,
                'variant_name' => $variantMeta?->title ?? $variation->variant,
                'description' => $variantMeta?->description,
                'note' => $variantMeta?->note,
                'image' => $variantMeta?->image,
                'image_full_path' => $variantMeta?->image_full_path,
                'icon' => $variantMeta?->icon,
                'price' => (float) $variation->price,
            ];
        }

        if ($formatting['zone_wise_variations'] !== []) {
            $formatting['default_price'] = (float) $formatting['zone_wise_variations'][0]['price'];
        }

        return $formatting;
    }

    /**
     * Batch-build customer variation payloads for list endpoints (favorites/search/home).
     *
     * @param  list<string>  $serviceIds
     * @return array<string, array{zone_id: string|null, default_price: float, zone_wise_variations: list<array<string, mixed>>}>
     */
    public static function variationsAppFormatForManyServices(array $serviceIds, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? Config::get('zone_id');
        $candidates = static::parseZoneIdCandidates(is_string($zoneId) ? $zoneId : null);
        $emptyTemplate = [
            'zone_id' => $candidates[0] ?? (is_string($zoneId) ? $zoneId : null),
            'default_price' => 0.0,
            'zone_wise_variations' => [],
        ];

        $normalizedIds = array_values(array_unique(array_filter(array_map('strval', $serviceIds))));
        if ($normalizedIds === [] || $candidates === []) {
            return array_fill_keys($normalizedIds, $emptyTemplate);
        }

        $services = Service::query()
            ->select('id', 'category_id', 'variation_pricing')
            ->whereIn('id', $normalizedIds)
            ->get()
            ->keyBy(fn (Service $service) => (string) $service->id);

        $allVariations = static::variantQuery()
            ->whereIn('service_id', $normalizedIds)
            ->get()
            ->groupBy(fn (self $variation) => (string) $variation->service_id);

        $allServiceVariants = ServiceVariant::query()
            ->whereIn('service_id', $normalizedIds)
            ->where('is_active', true)
            ->with('storage_image')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (ServiceVariant $variant) => (string) $variant->service_id)
            ->map(fn (Collection $variants) => $variants->keyBy('variant_key'));

        $categoryAvailabilityCache = [];
        $result = [];

        foreach ($normalizedIds as $serviceId) {
            $service = $services->get($serviceId);
            if (! $service) {
                $result[$serviceId] = $emptyTemplate;
                continue;
            }

            $result[$serviceId] = static::variationsAppFormatFromPreloaded(
                $service,
                $candidates,
                $allVariations->get($serviceId, collect()),
                $allServiceVariants->get($serviceId, collect()),
                $emptyTemplate,
                $categoryAvailabilityCache
            );
        }

        return $result;
    }

    protected static function booted()
    {
        static::addGlobalScope('zone_wise_data', function (Builder $builder) {
            if (is_customer_api_request()) {
                $candidates = static::parseZoneIdCandidates(Config::get('zone_id'));
                if ($candidates !== []) {
                    $builder->whereIn('zone_id', $candidates)->with(['zone:id,name']);
                } else {
                    $builder->whereRaw('0 = 1');
                }
            } elseif (is_provider_api_request()) {
                $p = auth()->user()?->provider;
                if ($p != null) {
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
