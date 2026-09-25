<?php

namespace Modules\ZoneManagement\Services;

use Illuminate\Support\Facades\DB;

class ZoneCategoryInheritanceService
{
    /**
     * When a zone is created under a parent, copy that parent selection onto the new zone.
     *
     * A category counts as having the parent selected when it is linked to the parent zone
     * itself, or to every existing leaf under that parent. Each matching category and all
     * of its sub-categories are then linked to the new zone.
     */
    public function inheritParentCategorySelection(string $newZoneId, ?string $parentZoneId): void
    {
        $newZoneId = (string) $newZoneId;
        $parentZoneId = $parentZoneId !== null ? (string) $parentZoneId : '';
        if ($newZoneId === '' || $parentZoneId === '' || $newZoneId === $parentZoneId) {
            return;
        }

        $childrenByParent = $this->childrenByParent();
        $categoryIds = $this->categoryIdsWithParentSelected($parentZoneId, $newZoneId, $childrenByParent);
        if ($categoryIds === []) {
            return;
        }

        $mainCategoryIds = DB::table('categories')
            ->whereIn('id', $categoryIds)
            ->where('position', 1)
            ->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->all();

        if ($mainCategoryIds === []) {
            return;
        }

        $subCategoryIds = DB::table('categories')
            ->whereIn('parent_id', $mainCategoryIds)
            ->where('position', 2)
            ->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->all();

        $this->attachZoneToCategories(array_merge($mainCategoryIds, $subCategoryIds), $newZoneId);
    }

    /**
     * @return array<string, list<string>>
     */
    private function childrenByParent(): array
    {
        $childrenByParent = [];
        $zones = DB::table('zones')->get(['id', 'parent_id']);
        foreach ($zones as $zone) {
            $parentId = $zone->parent_id !== null && (string) $zone->parent_id !== ''
                ? (string) $zone->parent_id
                : '';
            $childrenByParent[$parentId][] = (string) $zone->id;
        }

        return $childrenByParent;
    }

    /**
     * @param  array<string, list<string>>  $childrenByParent
     * @return list<string>
     */
    private function categoryIdsWithParentSelected(string $parentZoneId, string $newZoneId, array $childrenByParent): array
    {
        $directCategoryIds = DB::table('category_zone')
            ->where('zone_id', $parentZoneId)
            ->pluck('category_id')
            ->map(static fn ($id) => (string) $id)
            ->all();

        $siblingLeafIds = $this->leafIdsUnder($parentZoneId, $newZoneId, $childrenByParent);
        $siblingLeafIds = array_values(array_filter(
            $siblingLeafIds,
            static fn (string $id) => $id !== $parentZoneId
        ));

        $leafCategoryIds = [];
        if ($siblingLeafIds !== []) {
            $links = DB::table('category_zone')
                ->whereIn('zone_id', $siblingLeafIds)
                ->get(['category_id', 'zone_id']);

            $zonesByCategory = [];
            foreach ($links as $link) {
                $zonesByCategory[(string) $link->category_id][(string) $link->zone_id] = true;
            }

            foreach ($zonesByCategory as $categoryId => $zoneSet) {
                $hasEveryLeaf = true;
                foreach ($siblingLeafIds as $leafId) {
                    if (! isset($zoneSet[$leafId])) {
                        $hasEveryLeaf = false;
                        break;
                    }
                }
                if ($hasEveryLeaf) {
                    $leafCategoryIds[] = $categoryId;
                }
            }
        }

        return array_values(array_unique(array_merge($directCategoryIds, $leafCategoryIds)));
    }

    /**
     * Leaf zone ids under $zoneId, ignoring $excludeZoneId.
     * A zone with no remaining children is itself the leaf.
     *
     * @param  array<string, list<string>>  $childrenByParent
     * @return list<string>
     */
    private function leafIdsUnder(string $zoneId, string $excludeZoneId, array $childrenByParent): array
    {
        $children = array_values(array_filter(
            $childrenByParent[$zoneId] ?? [],
            static fn (string $id) => $id !== $excludeZoneId
        ));

        if ($children === []) {
            return $zoneId === $excludeZoneId ? [] : [$zoneId];
        }

        $leaves = [];
        foreach ($children as $childId) {
            $leaves = array_merge($leaves, $this->leafIdsUnder($childId, $excludeZoneId, $childrenByParent));
        }

        return array_values(array_unique($leaves));
    }

    /**
     * @param  list<string>  $categoryIds
     */
    private function attachZoneToCategories(array $categoryIds, string $zoneId): void
    {
        $categoryIds = array_values(array_unique(array_filter($categoryIds)));
        if ($categoryIds === []) {
            return;
        }

        $existing = DB::table('category_zone')
            ->where('zone_id', $zoneId)
            ->whereIn('category_id', $categoryIds)
            ->pluck('category_id')
            ->map(static fn ($id) => (string) $id)
            ->all();
        $existingSet = array_flip($existing);

        $now = now();
        $rows = [];
        foreach ($categoryIds as $categoryId) {
            if (isset($existingSet[$categoryId])) {
                continue;
            }
            $rows[] = [
                'category_id' => $categoryId,
                'zone_id' => $zoneId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('category_zone')->insert($rows);
        }
    }
}
