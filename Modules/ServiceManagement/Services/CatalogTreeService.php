<?php

namespace Modules\ServiceManagement\Services;

use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ZoneManagement\Entities\Zone;

class CatalogTreeService
{
    /**
     * @return array{
     *     stats: array{categories: int, sub_categories: int, services: int, variations: int},
     *     tree: list<array<string, mixed>>,
     *     zones: list<array{id: string, name: string}>
     * }
     */
    public function build(?string $zoneId = null, string $status = 'all'): array
    {
        $zones = Zone::query()->orderBy('name')->get(['id', 'name'])->map(fn (Zone $z) => [
            'id' => (string) $z->id,
            'name' => (string) $z->name,
        ])->values()->all();

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
                'zones' => $zones,
            ];
        }

        $mainCategories = Category::query()
            ->ofType('main')
            ->whereHas('zonesBasicInfo', fn ($query) => $query->where('zones.id', $zoneId))
            ->with([
                'zonesBasicInfo:id,name',
                'children' => fn ($q) => $q->ofType('sub')->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        $subIds = $mainCategories->flatMap(fn (Category $c) => $c->children)->pluck('id')->filter()->values();
        $mainIds = $mainCategories->pluck('id');

        $servicesQuery = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->with([
                'variations.zone:id,name',
                'serviceVariants' => fn ($q) => $q->with('storage_image'),
            ])
            ->where(function ($q) use ($subIds, $mainIds) {
                if ($subIds->isNotEmpty()) {
                    $q->whereIn('sub_category_id', $subIds);
                }
                $q->orWhere(function ($q2) use ($mainIds) {
                    $q2->whereIn('category_id', $mainIds)
                        ->where(function ($q3) {
                            $q3->whereNull('sub_category_id')->orWhere('sub_category_id', '');
                        });
                });
            });

        if ($status === 'active') {
            $servicesQuery->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $servicesQuery->where('is_active', 0);
        }

        $servicesQuery->whereHas('variations', fn ($query) => $query->where('zone_id', $zoneId));

        $services = $servicesQuery->orderBy('name')->get();

        $bySubId = $services->groupBy(fn (Service $s) => (string) $s->sub_category_id);
        $directByMainId = $services
            ->filter(fn (Service $s) => empty($s->sub_category_id))
            ->groupBy(fn (Service $s) => (string) $s->category_id);

        $stats = [
            'categories' => 0,
            'sub_categories' => 0,
            'services' => 0,
            'variations' => 0,
        ];

        $tree = [];

        foreach ($mainCategories as $main) {
            if ($status === 'active' && (int) $main->is_active !== 1) {
                continue;
            }
            if ($status === 'inactive' && (int) $main->is_active === 1) {
                continue;
            }

            $subNodes = [];
            foreach ($main->children as $sub) {
                if ($status === 'active' && (int) $sub->is_active !== 1) {
                    continue;
                }
                if ($status === 'inactive' && (int) $sub->is_active === 1) {
                    continue;
                }

                $svcList = $bySubId->get((string) $sub->id, collect());
                $serviceNodes = $this->serviceNodes($svcList, $zoneId, $stats);

                if ($status !== 'all' && $serviceNodes === [] && $svcList->isEmpty()) {
                    continue;
                }

                $stats['sub_categories']++;
                $subNodes[] = [
                    'type' => 'subcategory',
                    'id' => (string) $sub->id,
                    'name' => (string) $sub->name,
                    'slug' => (string) ($sub->slug ?? ''),
                    'is_active' => (int) $sub->is_active === 1,
                    'image' => $sub->image_full_path,
                    'edit_url' => route('admin.sub-category.edit', $sub->id),
                    'service_count' => count($serviceNodes),
                    'children' => $serviceNodes,
                ];
            }

            $direct = $directByMainId->get((string) $main->id, collect());
            if ($direct->isNotEmpty()) {
                $directNodes = $this->serviceNodes($direct, $zoneId, $stats);
                if ($directNodes !== []) {
                    $stats['sub_categories']++;
                    $subNodes[] = [
                        'type' => 'subcategory',
                        'id' => 'direct-'.$main->id,
                        'name' => translate('Import_tree_direct_services'),
                        'slug' => '',
                        'is_active' => true,
                        'image' => null,
                        'edit_url' => null,
                        'synthetic' => true,
                        'service_count' => count($directNodes),
                        'children' => $directNodes,
                    ];
                }
            }

            if ($status !== 'all' && $subNodes === []) {
                continue;
            }

            $stats['categories']++;
            $tree[] = [
                'type' => 'category',
                'id' => (string) $main->id,
                'name' => (string) $main->name,
                'slug' => (string) ($main->slug ?? ''),
                'is_active' => (int) $main->is_active === 1,
                'image' => $main->image_full_path,
                'edit_url' => route('admin.category.edit', $main->id),
                'zone_names' => $main->zonesBasicInfo->pluck('name')->values()->all(),
                'sub_count' => count($subNodes),
                'children' => $subNodes,
            ];
        }

        return [
            'stats' => $stats,
            'tree' => $tree,
            'zones' => $zones,
        ];
    }

    /**
     * @param  Collection<int, Service>  $services
     * @return list<array<string, mixed>>
     */
    private function serviceNodes(Collection $services, ?string $zoneId, array &$stats): array
    {
        $nodes = [];

        foreach ($services as $service) {
            $variationNodes = $this->variationNodes($service, $zoneId);

            if ($zoneId !== null && $zoneId !== '' && $variationNodes === []) {
                continue;
            }

            $stats['services']++;
            $stats['variations'] += count($variationNodes);

            $prices = array_filter(array_column($variationNodes, 'price'), fn ($p) => $p !== null && $p !== '');

            $nodes[] = [
                'type' => 'service',
                'id' => (string) $service->id,
                'name' => (string) $service->name,
                'slug' => (string) ($service->slug ?? ''),
                'is_active' => (int) $service->is_active === 1,
                'image' => $service->thumbnail_full_path ?? null,
                'edit_url' => route('admin.service.edit', $service->id),
                'detail_url' => route('admin.service.detail', $service->id),
                'variation_count' => count($variationNodes),
                'min_price' => $prices !== [] ? min($prices) : null,
                'children' => $variationNodes,
            ];
        }

        return $nodes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function variationNodes(Service $service, ?string $zoneId): array
    {
        $variantsByKey = $service->serviceVariants->keyBy('variant_key');
        $rows = $service->variations->sortBy(fn ($v) => ($v->variant_key ?? '').' '.($v->zone_id ?? ''))->values();

        $nodes = [];
        foreach ($rows as $variation) {
            if ($zoneId !== null && $zoneId !== '' && (string) $variation->zone_id !== $zoneId) {
                continue;
            }

            $label = trim((string) ($variation->variant ?? ''));
            if ($this->looksLikeUuid($label)) {
                $label = '';
            }
            if ($label === '' && ! empty($variation->variant_key)) {
                $vk = (string) $variation->variant_key;
                if (! $this->looksLikeUuid($vk)) {
                    $label = trim($vk);
                }
            }
            $meta = $variantsByKey->get($variation->variant_key);
            if ($label === '' && $meta?->title) {
                $label = trim((string) $meta->title);
            }
            if ($label === '' || $this->looksLikeUuid($label)) {
                $label = translate('Catalog_variation');
            }

            $nodes[] = [
                'type' => 'variation',
                'id' => (string) $variation->id,
                'label' => $label,
                'description' => $meta?->description ? trim((string) $meta->description) : null,
                'image' => $meta?->image_full_path,
                'is_active' => $meta ? (bool) $meta->is_active : true,
                'price' => $variation->price,
                'zone_id' => $variation->zone_id ? (string) $variation->zone_id : null,
                'zone_name' => $variation->zone?->name,
                'variant_key' => $variation->variant_key,
                'service_id' => (string) $service->id,
                'edit_url' => route('admin.service.edit', $service->id),
            ];
        }

        return $nodes;
    }

    private function looksLikeUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($value));
    }
}
