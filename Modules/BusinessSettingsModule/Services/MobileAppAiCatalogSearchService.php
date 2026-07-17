<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Cache;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Full app catalog for mobile in-app AI (same data the customer app uses to browse and book).
 */
class MobileAppAiCatalogSearchService
{
    private const CATALOG_STATS_CACHE_KEY = 'mobile_app_ai_catalog_stats_v1';

    private const CATALOG_STATS_TTL = 300;

    /**
     * Compact stats injected into every chat session so the model knows to use tools.
     *
     * @return array<string, mixed>
     */
    public function catalogStatsSnapshot(): array
    {
        return Cache::remember(self::CATALOG_STATS_CACHE_KEY, self::CATALOG_STATS_TTL, function () {
            $serviceCount = Service::query()->where('is_active', 1)->count();
            $zoneCount = Zone::query()->where('is_active', 1)->count();
            $categories = Category::query()
                ->where('is_active', 1)
                ->where('position', 1)
                ->orderBy('name')
                ->limit(25)
                ->get(['id', 'name']);

            $categorySummaries = [];
            foreach ($categories as $cat) {
                $n = Service::query()
                    ->where('is_active', 1)
                    ->where('category_id', $cat->id)
                    ->count();
                if ($n > 0) {
                    $categorySummaries[] = (string) $cat->name.' ('.$n.')';
                }
            }

            return [
                'active_service_count' => $serviceCount,
                'active_zone_count' => $zoneCount,
                'category_summaries' => $categorySummaries,
            ];
        });
    }

    public static function bustCatalogStatsCache(): void
    {
        Cache::forget(self::CATALOG_STATS_CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveCustomerZoneId(?User $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        $addr = UserAddress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('zone_id')
            ->where('zone_id', '!=', '')
            ->orderByDesc('id')
            ->first(['zone_id']);

        $zoneId = (string) ($addr?->zone_id ?? '');

        return $zoneId !== '' ? $zoneId : null;
    }

    public function searchServices(
        string $query,
        int $limit = 40,
        ?string $categoryId = null,
        ?string $subCategoryId = null,
        ?User $user = null,
    ): array {
        $q = trim($query);
        $limit = min(80, max(1, $limit));

        $builder = Service::query()
            ->where('is_active', 1)
            ->with(['category:id,name', 'subCategory:id,name'])
            ->orderBy('name');

        if ($categoryId !== null && $categoryId !== '') {
            $builder->where('category_id', $categoryId);
        }
        if ($subCategoryId !== null && $subCategoryId !== '') {
            $builder->where('sub_category_id', $subCategoryId);
        }

        if ($q !== '') {
            $builder->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%'.$q.'%')
                    ->orWhere('short_description', 'like', '%'.$q.'%')
                    ->orWhereHas('subCategory', fn ($c) => $c->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%'.$q.'%'));
            });
        }

        $services = $builder->limit($limit)->get([
            'id', 'name', 'short_description', 'category_id', 'sub_category_id', 'variation_pricing',
        ]);

        $zoneId = $this->resolveCustomerZoneId($user);
        $out = [];
        foreach ($services as $s) {
            $variants = $this->variantOptionsForService($s, $zoneId);
            if ($variants === []) {
                continue;
            }
            $out[] = [
                'service_id' => (string) $s->id,
                'name' => (string) $s->name,
                'short_description' => $this->trimText((string) ($s->short_description ?? ''), 220),
                'category' => (string) ($s->category?->name ?? ''),
                'sub_category' => (string) ($s->subCategory?->name ?? ''),
                'category_id' => (string) $s->category_id,
                'sub_category_id' => (string) $s->sub_category_id,
                'variants' => $variants,
                'default_variant_key' => $variants[0]['variant_key'],
            ];
        }

        $total = Service::query()->where('is_active', 1)->count();
        $selectable = $this->buildServiceSelectableOptions($out);

        return [
            'ok' => true,
            'query' => $q,
            'total_active_services_in_app' => $total,
            'count' => count($out),
            'services' => $out,
            'selectable_options' => $selectable,
            'assistant_instruction' => count($out) === 0
                ? 'No match — try list_full_service_catalog, list_service_categories, or a broader search query.'
                : 'Show selectable_options as a numbered list (step 1). After customer picks, call list_service_variations_for_booking — do NOT skip to cart.',
        ];
    }

    /**
     * Paginated full catalog (browse like the app home/categories).
     *
     * @return array<string, mixed>
     */
    public function listFullCatalog(int $offset = 0, int $limit = 50, ?string $categoryId = null): array
    {
        $offset = max(0, $offset);
        $limit = min(80, max(10, $limit));

        $builder = Service::query()
            ->where('is_active', 1)
            ->with(['category:id,name', 'subCategory:id,name'])
            ->orderBy('name');

        if ($categoryId !== null && $categoryId !== '') {
            $builder->where('category_id', $categoryId);
        }

        $total = (clone $builder)->count();
        $services = $builder->offset($offset)->limit($limit)->get([
            'id', 'name', 'short_description', 'category_id', 'sub_category_id', 'variation_pricing',
        ]);

        $grouped = [];
        foreach ($services as $s) {
            $variants = $this->variantOptionsForService($s);
            if ($variants === []) {
                continue;
            }
            $catKey = (string) ($s->category?->name ?? 'Other');
            if (! isset($grouped[$catKey])) {
                $grouped[$catKey] = [
                    'category_name' => $catKey,
                    'category_id' => (string) $s->category_id,
                    'services' => [],
                ];
            }
            $grouped[$catKey]['services'][] = [
                'service_id' => (string) $s->id,
                'name' => (string) $s->name,
                'short_description' => $this->trimText((string) ($s->short_description ?? ''), 180),
                'sub_category' => (string) ($s->subCategory?->name ?? ''),
                'sub_category_id' => (string) $s->sub_category_id,
                'default_variant_key' => $variants[0]['variant_key'],
            ];
        }

        $flat = [];
        foreach ($grouped as $g) {
            foreach ($g['services'] as $s) {
                $flat[] = array_merge($s, [
                    'category_id' => $g['category_id'],
                    'category' => $g['category_name'],
                ]);
            }
        }

        return [
            'ok' => true,
            'total_active_services' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'returned' => $services->count(),
            'has_more' => ($offset + $limit) < $total,
            'next_offset' => ($offset + $limit) < $total ? $offset + $limit : null,
            'catalog_by_category' => array_values($grouped),
            'selectable_options' => $this->buildServiceSelectableOptions($flat),
            'assistant_instruction' => 'Show selectable_options as numbered list (step 1). If has_more, offer to show more. After pick → list_service_variations_for_booking.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceDetails(string $serviceId, ?string $zoneId = null): array
    {
        $serviceId = trim($serviceId);
        if ($serviceId === '') {
            return ['ok' => false, 'error' => 'missing_service_id'];
        }

        $service = Service::query()
            ->where('is_active', 1)
            ->with(['category:id,name', 'subCategory:id,name'])
            ->find($serviceId);

        if (!$service) {
            return ['ok' => false, 'error' => 'service_not_found'];
        }

        $variants = [];
        foreach ($this->variantOptionsForService($service) as $v) {
            $row = [
                'variant_key' => $v['variant_key'],
                'label' => $v['label'],
            ];
            if ($zoneId !== null && $zoneId !== '') {
                $variation = Variation::firstForBookingZone($serviceId, $v['variant_key'], $zoneId, false);
                $row['price_in_zone'] = $variation !== null ? (float) $variation->price : null;
                $row['bookable_in_zone'] = $variation !== null && (float) $variation->price > 0;
            }
            $variants[] = $row;
        }

        return [
            'ok' => true,
            'service' => [
                'service_id' => (string) $service->id,
                'name' => (string) $service->name,
                'short_description' => $this->trimText((string) ($service->short_description ?? ''), 400),
                'category' => (string) ($service->category?->name ?? ''),
                'category_id' => (string) $service->category_id,
                'sub_category' => (string) ($service->subCategory?->name ?? ''),
                'sub_category_id' => (string) $service->sub_category_id,
                'variants' => $variants,
            ],
            'zone_id_for_pricing' => $zoneId,
            'assistant_instruction' => 'Explain the service in plain language. If zone pricing is shown, mention starting price only from price_in_zone. To book: add_service_to_customer_cart with service_id, category_id, sub_category_id, variant_key, zone_id.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listServiceAreas(int $limit = 40): array
    {
        $limit = min(60, max(1, $limit));

        $zones = Zone::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'description']);

        $rows = $zones->map(static function ($z) {
            $desc = (string) ($z->description ?? '');
            if ($desc !== '' && mb_strlen($desc) > 1200) {
                $desc = mb_substr($desc, 0, 1200).'…';
            }

            return [
                'zone_id' => (string) $z->id,
                'name' => (string) $z->name,
                'areas_covered' => $desc,
            ];
        })->values()->all();

        return [
            'ok' => true,
            'zone_count' => count($rows),
            'zones' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listCategories(int $limit = 50): array
    {
        $limit = min(80, max(1, $limit));

        $categories = Category::query()
            ->where('is_active', 1)
            ->where('position', 1)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        $subcategories = Category::query()
            ->where('is_active', 1)
            ->where('position', 2)
            ->orderBy('name')
            ->limit($limit * 3)
            ->get(['id', 'name', 'parent_id']);

        $withCounts = $categories->map(function ($c) {
            $count = Service::query()->where('is_active', 1)->where('category_id', $c->id)->count();

            return [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'active_service_count' => $count,
            ];
        })->values()->all();

        return [
            'ok' => true,
            'total_active_services' => Service::query()->where('is_active', 1)->count(),
            'categories' => $withCounts,
            'subcategories' => $subcategories->map(fn ($c) => [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'parent_category_id' => (string) $c->parent_id,
            ])->values()->all(),
            'assistant_instruction' => 'Use category id with list_full_service_catalog or search_catalog_services to show services in that category.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listCustomerAddresses(User $user): array
    {
        $rows = UserAddress::query()
            ->where('user_id', $user->id)
            ->completeForSelection()
            ->orderByDesc('id')
            ->limit(12)
            ->get([
                'id', 'address', 'zone_id', 'city', 'street', 'lat', 'lon',
                'address_label', 'contact_person_name', 'contact_person_number',
            ]);

        $options = [];
        $n = 1;
        foreach ($rows as $a) {
            $line = trim((string) ($a->address ?? ''));
            $label = trim((string) ($a->address_label ?? ''));
            $options[] = [
                'option' => $n,
                'service_address_id' => (int) $a->id,
                'zone_id' => (string) ($a->zone_id ?? ''),
                'address_label' => $label,
                'address' => $line,
                'contact_person_name' => (string) ($a->contact_person_name ?? ''),
                'contact_person_number' => (string) ($a->contact_person_number ?? ''),
                'display' => $n.'. '.($label !== '' ? $label.' — ' : '').$line,
            ];
            $n++;
        }

        return [
            'ok' => true,
            'count' => count($options),
            'selectable_options' => $options,
            'new_address_hint' => 'To add a new address: Home → tap location bar → **Add new address** in the app, then ask the customer to reply "done" and call this tool again.',
            'assistant_instruction' => count($options) > 0
                ? 'Show selectable_options (step 4). Customer picks a number → use that service_address_id and zone_id for list_booking_providers and add_service_to_customer_cart.'
                : 'No saved addresses — explain new_address_hint; do not add to cart until they save an address in the app.',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $services
     * @return list<array<string, mixed>>
     */
    private function buildServiceSelectableOptions(array $services): array
    {
        $options = [];
        $n = 1;
        foreach ($services as $s) {
            $name = (string) ($s['name'] ?? '');
            $sub = (string) ($s['sub_category'] ?? '');
            $desc = $this->trimText((string) ($s['short_description'] ?? ''), 80);
            $display = $n.'. '.$name;
            if ($sub !== '') {
                $display .= ' ('.$sub.')';
            }
            if ($desc !== '') {
                $display .= ' — '.$desc;
            }
            $options[] = [
                'option' => $n,
                'service_id' => (string) ($s['service_id'] ?? ''),
                'category_id' => (string) ($s['category_id'] ?? ''),
                'sub_category_id' => (string) ($s['sub_category_id'] ?? ''),
                'default_variant_key' => (string) ($s['default_variant_key'] ?? $s['variant_key'] ?? ''),
                'name' => $name,
                'display' => $display,
            ];
            $n++;
        }

        return $options;
    }

    /**
     * @return list<array{variant_key: string, label: string}>
     */
    private function variantOptionsForService(Service $service, ?string $zoneId = null): array
    {
        $vp = (array) ($service->variation_pricing ?? []);
        $keys = array_keys($vp);
        sort($keys);

        $out = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if ($key === '') {
                continue;
            }
            if ($zoneId !== null && $zoneId !== '' && ! $this->variantBookableInZone($service->id, $key, $zoneId)) {
                continue;
            }
            $label = is_array($vp[$key] ?? null) && isset($vp[$key]['variant'])
                ? (string) $vp[$key]['variant']
                : str_replace('-', ' ', $key);
            $out[] = ['variant_key' => $key, 'label' => $label];
        }

        if ($out === []) {
            $dbKeys = Variation::query()
                ->where('service_id', $service->id)
                ->orderBy('variant_key')
                ->pluck('variant_key')
                ->unique()
                ->filter();
            foreach ($dbKeys as $key) {
                $key = (string) $key;
                if ($zoneId !== null && $zoneId !== '' && ! $this->variantBookableInZone($service->id, $key, $zoneId)) {
                    continue;
                }
                $out[] = ['variant_key' => $key, 'label' => str_replace('-', ' ', $key)];
            }
        }

        return $out;
    }

    private function variantBookableInZone(string $serviceId, string $variantKey, string $zoneId): bool
    {
        $variation = Variation::firstForBookingZone($serviceId, $variantKey, $zoneId, false);

        return $variation !== null && (float) $variation->price > 0;
    }

    private function trimText(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max).'…';
    }
}
