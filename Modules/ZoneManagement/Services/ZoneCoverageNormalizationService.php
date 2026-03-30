<?php

namespace Modules\ZoneManagement\Services;

use Illuminate\Support\Collection;
use Modules\ZoneManagement\Entities\Zone;

class ZoneCoverageNormalizationService
{
    /**
     * Expand cascade selections to leaf zone IDs and apply exclusions (cascade).
     *
     * @param  array<int, string>  $includedZoneIds
     * @param  array<int, string>  $excludedZoneIds
     * @return array<int, string>
     */
    public function normalizeToLeafZoneIds(array $includedZoneIds, array $excludedZoneIds = []): array
    {
        $includedZoneIds = array_values(array_unique(array_filter($includedZoneIds)));
        $excludedZoneIds = array_values(array_unique(array_filter($excludedZoneIds)));

        if ($includedZoneIds === []) {
            return [];
        }

        $zones = Zone::query()
            ->withoutGlobalScope('translate')
            ->get(['id', 'parent_id']);

        $childrenByParent = $zones->groupBy('parent_id');

        $expandedIncluded = [];
        foreach ($includedZoneIds as $id) {
            if (! $zones->firstWhere('id', $id)) {
                continue;
            }
            $expandedIncluded = array_merge($expandedIncluded, $this->leafIdsUnder((string) $id, $childrenByParent));
        }
        $expandedIncluded = array_values(array_unique($expandedIncluded));

        $expandedExcluded = [];
        foreach ($excludedZoneIds as $id) {
            if (! $zones->firstWhere('id', $id)) {
                continue;
            }
            $expandedExcluded = array_merge($expandedExcluded, $this->leafIdsUnder((string) $id, $childrenByParent));
        }
        $expandedExcluded = array_values(array_unique($expandedExcluded));

        return array_values(array_diff($expandedIncluded, $expandedExcluded));
    }

    /**
     * @param  Collection<string, Collection<int, Zone>>  $childrenByParent
     * @return array<int, string>
     */
    private function leafIdsUnder(string $zoneId, Collection $childrenByParent): array
    {
        /** @var Collection<int, Zone> $children */
        $children = $childrenByParent->get($zoneId, collect());
        if ($children->isEmpty()) {
            return [$zoneId];
        }

        $out = [];
        foreach ($children as $child) {
            $out = array_merge($out, $this->leafIdsUnder($child->id, $childrenByParent));
        }

        return $out;
    }
}
