<?php

namespace Modules\ZoneManagement\Services;

use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Modules\ZoneManagement\Entities\Zone;

class ZoneGeometryService
{
    /**
     * True when the child's polygon is fully inside the parent's stored geometry (MySQL ST_Contains).
     */
    public function childPolygonContainedInParentZone(Polygon $childPolygon, string $parentZoneId): bool
    {
        $parentExists = Zone::query()
            ->withoutGlobalScope('translate')
            ->where('id', $parentZoneId)
            ->whereNotNull('coordinates')
            ->exists();

        if (! $parentExists) {
            return false;
        }

        try {
            $childWkt = $childPolygon->toWkt();
            $row = DB::selectOne(
                'SELECT ST_Contains(z.coordinates, ST_GeomFromText(?, ST_SRID(z.coordinates))) AS ok
                 FROM zones z WHERE z.id = ? LIMIT 1',
                [$childWkt, $parentZoneId]
            );
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return isset($row->ok) && (int) $row->ok === 1;
    }
    /**
     * Pick the most specific (finest) zone for a point when polygons nest (parent/child).
     * Removes any candidate that has another candidate as a strict descendant in the tree.
     */
    public function resolveLeafZoneForPoint(Point $point): ?Zone
    {
        $candidates = Zone::query()
            ->withoutGlobalScope('translate')
            ->ofStatus(1)
            ->whereContains('coordinates', $point)
            ->get(['id', 'parent_id']);

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            return Zone::query()->withoutGlobalScope('translate')->find($candidates->first()->id);
        }

        $finest = $candidates->filter(function (Zone $a) use ($candidates) {
            foreach ($candidates as $b) {
                if ($a->id === $b->id) {
                    continue;
                }
                if ($this->zoneIsStrictDescendantOf($b, $a)) {
                    return false;
                }
            }

            return true;
        });

        if ($finest->count() === 1) {
            return Zone::query()->withoutGlobalScope('translate')->find($finest->first()->id);
        }

        /* Overlapping siblings or disjoint matches — choose deepest by tree depth, then id */
        $picked = $finest->sortBy([
            fn (Zone $z) => -1 * $this->ancestorDepth($z),
            fn (Zone $z) => $z->id,
        ])->first();

        return Zone::query()->withoutGlobalScope('translate')->find($picked->id);
    }

    private function zoneIsStrictDescendantOf(Zone $maybeDescendant, Zone $ancestor): bool
    {
        $currentId = $maybeDescendant->parent_id;
        $guard = 0;
        while ($currentId && $guard < 64) {
            if ($currentId === $ancestor->id) {
                return true;
            }
            $currentId = Zone::query()
                ->withoutGlobalScope('translate')
                ->where('id', $currentId)
                ->value('parent_id');
            $guard++;
        }

        return false;
    }

    private function ancestorDepth(Zone $zone): int
    {
        $depth = 0;
        $currentId = $zone->parent_id;
        $guard = 0;
        while ($currentId && $guard < 64) {
            $depth++;
            $currentId = Zone::query()
                ->withoutGlobalScope('translate')
                ->where('id', $currentId)
                ->value('parent_id');
            $guard++;
        }

        return $depth;
    }
}
