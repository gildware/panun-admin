<?php

namespace Modules\ServiceManagement\Services;

use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;

class CatalogTreeService
{
    /**
     * @return list<array{id: string, label: string, prefix: string, name: string, description: string}>
     */
    public function zoneTreeOptions(): array
    {
        return Zone::flatTreeOptionsForSelect(
            Zone::query()->ofStatus(1)->orderBy('name')->get(['id', 'name', 'parent_id', 'description'])
        );
    }

    /**
     * Lightweight shell for the catalog page (no full tree).
     *
     * @return array{
     *     stats: array{categories: int, sub_categories: int, services: int, variations: int},
     *     tree: list<array<string, mixed>>,
     *     zoneTreeOptions: list<array{id: string, label: string, prefix: string, name: string, description: string}>
     * }
     */
    public function shell(?string $zoneId = null, string $status = 'all'): array
    {
        $zoneTreeOptions = $this->zoneTreeOptions();

        if ($zoneId === null || $zoneId === '') {
            return [
                'stats' => [
                    'categories' => 0,
                    'sub_categories' => 0,
                    'services' => 0,
                    'variations' => 0,
                ],
                'tree' => [],
                'zoneTreeOptions' => $zoneTreeOptions,
            ];
        }

        $categories = $this->categories($zoneId, $status);

        return [
            'stats' => [
                'categories' => count($categories),
                'sub_categories' => 0,
                'services' => 0,
                'variations' => 0,
            ],
            'tree' => $categories,
            'zoneTreeOptions' => $zoneTreeOptions,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(string $zoneId, string $status = 'all'): array
    {
        $categoryZoneIds = Zone::coverageMatchZoneIds($zoneId);

        $mainCategories = Category::query()
            ->ofType('main')
            ->whereHas('zonesBasicInfo', fn ($query) => $query->whereIn('zones.id', $categoryZoneIds))
            ->with([
                'zonesBasicInfo:id,name',
                'storage',
                'children' => fn ($q) => $q->ofType('sub')->ordered()->select(['id', 'parent_id', 'is_active', 'sort_order']),
            ])
            ->ordered()
            ->get();

        $mainCategoryIds = $mainCategories->pluck('id')->filter()->values()->all();
        $directServiceCounts = $this->directServiceCountsByCategory($mainCategoryIds, $status);

        $tree = [];
        foreach ($mainCategories as $main) {
            if (! $this->matchesStatus((int) $main->is_active, $status)) {
                continue;
            }

            $subCount = 0;
            foreach ($main->children as $sub) {
                if ($this->matchesStatus((int) $sub->is_active, $status)) {
                    $subCount++;
                }
            }
            if (($directServiceCounts[(string) $main->id] ?? 0) > 0) {
                $subCount++;
            }

            $tree[] = [
                'type' => 'category',
                'id' => (string) $main->id,
                'name' => (string) $main->name,
                'slug' => (string) ($main->slug ?? ''),
                'is_active' => (int) $main->is_active === 1,
                'image' => $main->image_full_path,
                'edit_url' => route('admin.category.edit', $main->id),
                'sort_order' => (int) ($main->sort_order ?? 0),
                'zone_names' => $main->zonesBasicInfo->pluck('name')->values()->all(),
                'sub_count' => $subCount,
                'children' => [],
            ];
        }

        return $tree;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function subcategories(string $zoneId, string $categoryId, string $status = 'all'): array
    {
        $main = $this->findZoneMainCategory($zoneId, $categoryId);
        if ($main === null) {
            return [];
        }

        $subs = Category::query()
            ->ofType('sub')
            ->where('parent_id', $main->id)
            ->with('storage')
            ->ordered()
            ->get();

        $subIds = $subs
            ->filter(fn (Category $sub) => $this->matchesStatus((int) $sub->is_active, $status))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $serviceCounts = $this->serviceCountsBySubCategory($subIds, $status);
        $directCount = $this->directServiceCountsByCategory([(string) $main->id], $status)[(string) $main->id] ?? 0;

        $nodes = [];
        foreach ($subs as $sub) {
            if (! $this->matchesStatus((int) $sub->is_active, $status)) {
                continue;
            }

            $nodes[] = [
                'type' => 'subcategory',
                'id' => (string) $sub->id,
                'name' => (string) $sub->name,
                'slug' => (string) ($sub->slug ?? ''),
                'is_active' => (int) $sub->is_active === 1,
                'image' => $sub->image_full_path,
                'edit_url' => route('admin.sub-category.edit', $sub->id),
                'sort_order' => (int) ($sub->sort_order ?? 0),
                'service_count' => (int) ($serviceCounts[(string) $sub->id] ?? 0),
                'children' => [],
            ];
        }

        if ($directCount > 0) {
            $nodes[] = [
                'type' => 'subcategory',
                'id' => 'direct-'.$main->id,
                'name' => translate('Import_tree_direct_services'),
                'slug' => '',
                'is_active' => true,
                'image' => null,
                'edit_url' => null,
                'synthetic' => true,
                'service_count' => $directCount,
                'children' => [],
            ];
        }

        return $nodes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function services(string $zoneId, string $subcategoryId, string $status = 'all'): array
    {
        $query = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->with([
                'storage_thumbnail',
                'serviceVariants' => fn ($q) => $q->orderBy('sort_order'),
            ]);

        if (str_starts_with($subcategoryId, 'direct-')) {
            $categoryId = substr($subcategoryId, strlen('direct-'));
            if ($this->findZoneMainCategory($zoneId, $categoryId) === null) {
                return [];
            }
            $query->where('category_id', $categoryId)->where(function ($q) {
                $q->whereNull('sub_category_id')->orWhere('sub_category_id', '');
            });
        } else {
            $sub = Category::query()
                ->ofType('sub')
                ->with('parent')
                ->find($subcategoryId);
            if ($sub === null || $sub->parent === null) {
                return [];
            }
            if ($this->findZoneMainCategory($zoneId, (string) $sub->parent_id) === null) {
                return [];
            }
            $query->where('sub_category_id', $subcategoryId);
        }

        if ($status === 'active') {
            $query->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('is_active', 0);
        }

        $services = $query->ordered()->get();
        if ($services->isEmpty()) {
            return [];
        }

        $variationRowsByService = $this->variationRowsByServiceIds(
            $services->pluck('id')->map(fn ($id) => (string) $id)->all()
        );

        // Warm zone coverage once for this request.
        Variation::zoneIdsMatchingBookingSelection($zoneId);

        $nodes = [];
        foreach ($services as $service) {
            $rows = $variationRowsByService->get((string) $service->id, collect());
            $summary = $this->variationSummaryFromPreloaded($service, $zoneId, $rows);

            $nodes[] = [
                'type' => 'service',
                'id' => (string) $service->id,
                'name' => (string) $service->name,
                'slug' => (string) ($service->slug ?? ''),
                'is_active' => (int) $service->is_active === 1,
                'image' => $service->thumbnail_full_path ?? null,
                'edit_url' => route('admin.service.edit', $service->id),
                'detail_url' => route('admin.service.detail', $service->id),
                'sort_order' => (int) ($service->sort_order ?? 0),
                'variation_count' => $summary['count'],
                'min_price' => $summary['min_price'],
                'children' => [],
            ];
        }

        return $nodes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function variations(string $zoneId, string $serviceId): array
    {
        $service = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->with([
                'serviceVariants' => fn ($q) => $q->with('storage_image')->orderBy('sort_order'),
            ])
            ->find($serviceId);

        if ($service === null) {
            return [];
        }

        if ($service->category_id) {
            if ($this->findZoneMainCategory($zoneId, (string) $service->category_id) === null) {
                return [];
            }
        }

        $rows = Variation::query()
            ->withoutGlobalScopes()
            ->where('service_id', $service->id)
            ->get();

        Variation::zoneIdsMatchingBookingSelection($zoneId);

        return $this->variationNodesFromPreloaded($service, $zoneId, $rows);
    }

    /**
     * Full tree (legacy /admin/catalog/tree). Prefer lazy column endpoints for the UI.
     *
     * @return array{
     *     stats: array{categories: int, sub_categories: int, services: int, variations: int},
     *     tree: list<array<string, mixed>>,
     *     zoneTreeOptions: list<array{id: string, label: string, prefix: string, name: string, description: string}>
     * }
     */
    public function build(?string $zoneId = null, string $status = 'all'): array
    {
        $zoneTreeOptions = $this->zoneTreeOptions();
        $emptyStats = [
            'categories' => 0,
            'sub_categories' => 0,
            'services' => 0,
            'variations' => 0,
        ];

        if ($zoneId === null || $zoneId === '') {
            return [
                'stats' => $emptyStats,
                'tree' => [],
                'zoneTreeOptions' => $zoneTreeOptions,
            ];
        }

        $categories = $this->categories($zoneId, $status);
        $stats = $emptyStats;
        $tree = [];

        foreach ($categories as $category) {
            $subs = $this->subcategories($zoneId, $category['id'], $status);
            $stats['sub_categories'] += count($subs);
            $subNodes = [];

            foreach ($subs as $sub) {
                $serviceNodes = $this->services($zoneId, $sub['id'], $status);
                $stats['services'] += count($serviceNodes);

                $serviceNodesWithChildren = [];
                foreach ($serviceNodes as $serviceNode) {
                    $variationNodes = $this->variations($zoneId, $serviceNode['id']);
                    $stats['variations'] += count($variationNodes);
                    $serviceNode['children'] = $variationNodes;
                    $serviceNode['variation_count'] = count($variationNodes);
                    $serviceNodesWithChildren[] = $serviceNode;
                }

                $sub['service_count'] = count($serviceNodesWithChildren);
                $sub['children'] = $serviceNodesWithChildren;
                $subNodes[] = $sub;
            }

            $category['sub_count'] = count($subNodes);
            $category['children'] = $subNodes;
            $tree[] = $category;
            $stats['categories']++;
        }

        return [
            'stats' => $stats,
            'tree' => $tree,
            'zoneTreeOptions' => $zoneTreeOptions,
        ];
    }

    private function findZoneMainCategory(string $zoneId, string $categoryId): ?Category
    {
        $categoryZoneIds = Zone::coverageMatchZoneIds($zoneId);

        return Category::query()
            ->ofType('main')
            ->where('id', $categoryId)
            ->whereHas('zonesBasicInfo', fn ($query) => $query->whereIn('zones.id', $categoryZoneIds))
            ->first();
    }

    /**
     * @param  list<string>  $categoryIds
     * @return array<string, int>
     */
    private function directServiceCountsByCategory(array $categoryIds, string $status): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $query = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->whereIn('category_id', $categoryIds)
            ->where(function ($q) {
                $q->whereNull('sub_category_id')->orWhere('sub_category_id', '');
            });

        if ($status === 'active') {
            $query->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('is_active', 0);
        }

        return $query
            ->selectRaw('category_id, COUNT(*) as aggregate_count')
            ->groupBy('category_id')
            ->pluck('aggregate_count', 'category_id')
            ->mapWithKeys(fn ($count, $id) => [(string) $id => (int) $count])
            ->all();
    }

    /**
     * @param  list<string>  $subCategoryIds
     * @return array<string, int>
     */
    private function serviceCountsBySubCategory(array $subCategoryIds, string $status): array
    {
        if ($subCategoryIds === []) {
            return [];
        }

        $query = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->whereIn('sub_category_id', $subCategoryIds);

        if ($status === 'active') {
            $query->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('is_active', 0);
        }

        return $query
            ->selectRaw('sub_category_id, COUNT(*) as aggregate_count')
            ->groupBy('sub_category_id')
            ->pluck('aggregate_count', 'sub_category_id')
            ->mapWithKeys(fn ($count, $id) => [(string) $id => (int) $count])
            ->all();
    }

    /**
     * @param  list<string>  $serviceIds
     * @return Collection<string, Collection<int, Variation>>
     */
    private function variationRowsByServiceIds(array $serviceIds): Collection
    {
        if ($serviceIds === []) {
            return collect();
        }

        return Variation::query()
            ->withoutGlobalScopes()
            ->whereIn('service_id', $serviceIds)
            ->get()
            ->groupBy(fn (Variation $row) => (string) $row->service_id);
    }

    /**
     * @return array{count: int, min_price: float|null}
     */
    private function variationSummaryFromPreloaded(Service $service, string $zoneId, Collection $variationRows): array
    {
        $variantKeys = ($service->relationLoaded('serviceVariants')
            ? $service->serviceVariants->pluck('variant_key')
            : collect()
        )
            ->merge($variationRows->pluck('variant_key'))
            ->filter()
            ->unique()
            ->values();

        $prices = [];
        $count = 0;
        foreach ($variantKeys as $variantKey) {
            $variation = Variation::resolveFromPreloaded(
                $service,
                (string) $variantKey,
                $zoneId,
                $variationRows,
                false
            );
            if ($variation === null) {
                continue;
            }
            $count++;
            if ($variation->price !== null && $variation->price !== '') {
                $prices[] = (float) $variation->price;
            }
        }

        $minPrice = $prices !== [] ? min($prices) : null;
        if ($minPrice === null && (float) ($service->min_bidding_price ?? 0) > 0) {
            $minPrice = (float) $service->min_bidding_price;
        }

        return [
            'count' => $count,
            'min_price' => $minPrice,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function variationNodesFromPreloaded(Service $service, string $zoneId, Collection $variationRows): array
    {
        $variantsByKey = $service->relationLoaded('serviceVariants')
            ? $service->serviceVariants->keyBy('variant_key')
            : collect();

        $variantKeys = $variantsByKey
            ->sortBy(fn ($meta) => (int) ($meta->sort_order ?? 0))
            ->keys()
            ->merge($variationRows->pluck('variant_key'))
            ->filter()
            ->unique()
            ->values();

        $nodes = [];
        foreach ($variantKeys as $variantKey) {
            $variation = Variation::resolveFromPreloaded(
                $service,
                (string) $variantKey,
                $zoneId,
                $variationRows,
                false
            );

            if ($variation === null) {
                continue;
            }

            $meta = $variantsByKey->get($variation->variant_key);
            $label = $meta?->title ? trim((string) $meta->title) : '';
            if ($label === '') {
                $label = trim((string) ($variation->variant ?? ''));
            }
            if ($this->looksLikeUuid($label)) {
                $label = '';
            }
            if ($label === '' && ! empty($variation->variant_key)) {
                $vk = (string) $variation->variant_key;
                if (! $this->looksLikeUuid($vk)) {
                    $label = str_replace('-', ' ', trim($vk));
                }
            }
            if ($label === '' || $this->looksLikeUuid($label)) {
                $label = translate('Catalog_variation');
            }

            $nodes[] = [
                'type' => 'variation',
                'id' => (string) ($meta?->id ?? $variation->id),
                'label' => $label,
                'description' => $meta?->description ? trim((string) $meta->description) : null,
                'image' => $meta?->image_full_path,
                'is_active' => $meta ? (bool) $meta->is_active : true,
                'price' => $variation->price,
                'variant_key' => $variation->variant_key,
                'service_id' => (string) $service->id,
                'sort_order' => (int) ($meta?->sort_order ?? 0),
                'reorderable' => $meta !== null,
                'edit_url' => route('admin.service.edit', $service->id),
            ];
        }

        return $nodes;
    }

    private function matchesStatus(int $isActive, string $status): bool
    {
        if ($status === 'active') {
            return $isActive === 1;
        }
        if ($status === 'inactive') {
            return $isActive !== 1;
        }

        return true;
    }

    private function looksLikeUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($value));
    }
}
