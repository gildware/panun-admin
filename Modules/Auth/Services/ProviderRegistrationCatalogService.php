<?php

namespace Modules\Auth\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\CategoryManagement\Entities\Category;
use Modules\ZoneManagement\Services\ZoneCoverageNormalizationService;

class ProviderRegistrationCatalogService
{
    public function __construct(
        private Category $category,
        private ZoneCoverageNormalizationService $zoneCoverageNormalization
    ) {}

    /**
     * All zone IDs from the request (selected + leaf descendants).
     *
     * @return array<int, string>
     */
    public function rawZoneIdsFromRequest(Request $request): array
    {
        $zoneIds = $request->input('zone_ids', []);
        if (! is_array($zoneIds)) {
            $zoneIds = [];
        }
        $zoneIds = array_values(array_filter(array_map('strval', $zoneIds)));
        if ($zoneIds === [] && $request->filled('zone_id')) {
            $zoneIds = [(string) $request->input('zone_id')];
        }

        return $zoneIds;
    }

    /**
     * Zone IDs used to match categories: selected IDs plus normalized leaf coverage.
     *
     * @return array<int, string>
     */
    public function catalogZoneIdsFromRequest(Request $request): array
    {
        $raw = $this->rawZoneIdsFromRequest($request);
        if ($raw === []) {
            return [];
        }

        $excluded = $request->input('zone_excluded_ids', []);
        if (! is_array($excluded)) {
            $excluded = [];
        }

        $leafZoneIds = $this->zoneCoverageNormalization->normalizeToLeafZoneIds($raw, $excluded);

        return array_values(array_unique(array_merge($raw, $leafZoneIds)));
    }

    /**
     * @return array<int, string>
     */
    public function leafZoneIdsFromRequest(Request $request): array
    {
        $raw = $this->rawZoneIdsFromRequest($request);
        if ($raw === []) {
            return [];
        }

        $excluded = $request->input('zone_excluded_ids', []);
        if (! is_array($excluded)) {
            $excluded = [];
        }

        $leafZoneIds = $this->zoneCoverageNormalization->normalizeToLeafZoneIds($raw, $excluded);

        return $leafZoneIds !== [] ? $leafZoneIds : $raw;
    }

    /**
     * @param  array<int, string>  $zoneIds
     */
    public function categoriesQuery(array $zoneIds): Builder
    {
        $zoneIds = array_values(array_unique(array_filter($zoneIds)));
        if ($zoneIds === []) {
            return $this->category->newQuery()->whereRaw('1 = 0');
        }

        return $this->category->ofStatus(1)->ofType('main')
            ->whereHas('zones', function ($query) use ($zoneIds) {
                $query->whereIn('category_zone.zone_id', $zoneIds);
            })
            ->mainWithActiveCatalog()
            ->orderBy('name');
    }

    /**
     * @param  array<int, string>  $zoneIds
     */
    public function subCategoriesQuery(string $categoryId, array $zoneIds): Builder
    {
        $zoneIds = array_values(array_unique(array_filter($zoneIds)));
        if ($zoneIds === [] || $categoryId === '') {
            return $this->category->newQuery()->whereRaw('1 = 0');
        }

        return $this->category->withCount(['services' => function ($query) {
                $query->where('is_active', 1);
            }])
            ->where('parent_id', $categoryId)
            ->whereHas('parent.zones', function ($query) use ($zoneIds) {
                $query->whereIn('category_zone.zone_id', $zoneIds);
            })
            ->whereHas('parent', function ($query) {
                $query->where('is_active', 1);
            })
            ->ofStatus(1)
            ->ofType('sub')
            ->withActiveServices()
            ->orderBy('name');
    }

    /**
     * @param  array<int, string>  $zoneIds
     */
    public function allSubCategoriesForZonesQuery(array $zoneIds): Builder
    {
        $zoneIds = array_values(array_unique(array_filter($zoneIds)));
        if ($zoneIds === []) {
            return $this->category->newQuery()->whereRaw('1 = 0');
        }

        return $this->category->withCount(['services' => function ($query) {
                $query->where('is_active', 1);
            }])
            ->whereHas('parent.zones', function ($query) use ($zoneIds) {
                $query->whereIn('category_zone.zone_id', $zoneIds);
            })
            ->whereHas('parent', function ($query) {
                $query->where('is_active', 1);
            })
            ->ofStatus(1)
            ->ofType('sub')
            ->withActiveServices();
    }
}
