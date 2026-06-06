<?php

namespace Modules\ZoneManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ZoneManagement\Entities\Zone;

class ZoneTreeBuilder
{
    /**
     * Zone IDs linked to at least one active main category.
     *
     * @return array<string, true>
     */
    public function zoneIdsWithMainCategories(): array
    {
        $ids = DB::table('category_zone')
            ->join('categories', 'categories.id', '=', 'category_zone.category_id')
            ->where('categories.is_active', 1)
            ->where('categories.position', 1)
            ->distinct()
            ->pluck('category_zone.zone_id');

        $map = [];
        foreach ($ids as $id) {
            $map[(string) $id] = true;
        }

        return $map;
    }

    /**
     * Active zones nested by parent_id (same structure as admin provider form).
     *
     * @return list<array{id: string, name: string, description: string, children: list}>
     */
    public function buildActiveZoneTree(bool $onlyZonesWithCategories = false): array
    {
        $zones = Zone::query()->ofStatus(1)->orderBy('name')->get(['id', 'name', 'parent_id', 'description']);
        $byParent = $zones->groupBy(fn (Zone $z) => $z->parent_id ?? '');

        $zoneIdsWithCategories = $onlyZonesWithCategories ? $this->zoneIdsWithMainCategories() : null;

        $build = function (string $parentKey) use (&$build, $byParent, $zoneIdsWithCategories): array {
            /** @var Collection<int, Zone> $rows */
            $rows = $byParent->get($parentKey, collect());

            $nodes = [];
            foreach ($rows as $z) {
                $node = [
                    'id' => (string) $z->id,
                    'name' => (string) $z->name,
                    'description' => trim((string) ($z->description ?? '')),
                    'children' => $build((string) $z->id),
                ];

                if ($zoneIdsWithCategories !== null) {
                    if (! $this->zoneNodeHasCategories($node, $zoneIdsWithCategories)) {
                        continue;
                    }
                }

                $nodes[] = $node;
            }

            return $nodes;
        };

        return $build('');
    }

    /**
     * @param  array{id: string, children: list}  $node
     * @param  array<string, true>  $zoneIdsWithCategories
     */
    private function zoneNodeHasCategories(array $node, array $zoneIdsWithCategories): bool
    {
        if (isset($zoneIdsWithCategories[$node['id']])) {
            return true;
        }

        foreach ($node['children'] as $child) {
            if ($this->zoneNodeHasCategories($child, $zoneIdsWithCategories)) {
                return true;
            }
        }

        return false;
    }
}
