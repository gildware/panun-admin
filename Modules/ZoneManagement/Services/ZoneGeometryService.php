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

        /* Overlapping siblings or disjoint matches — deepest in the tree, then smallest polygon */
        $areaById = $this->zoneAreasById($finest->pluck('id')->all());
        $picked = $finest->sortBy([
            fn (Zone $z) => -1 * $this->ancestorDepth($z),
            fn (Zone $z) => $areaById[(string) $z->id] ?? PHP_FLOAT_MAX,
            fn (Zone $z) => $z->id,
        ])->first();

        return Zone::query()->withoutGlobalScope('translate')->find($picked->id);
    }

    /**
     * Leaf → parent → … → root for a lat/lng (empty when the point is outside every active zone).
     *
     * @return list<array{id: string, name: string}>
     */
    public function resolveZonePathForLatLng(mixed $lat, mixed $lng): array
    {
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return [];
        }
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return [];
        }

        return $this->resolveZonePathForPoint(new Point((float) $lat, (float) $lng));
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function resolveZonePathForPoint(Point $point): array
    {
        $leaf = $this->resolveLeafZoneForPoint($point);
        if (! $leaf) {
            return [];
        }

        return $this->pathFromLeafToRoot($leaf);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function pathFromLeafToRoot(Zone $leaf): array
    {
        $ids = Zone::selfAndAncestorIds((string) $leaf->id);
        if ($ids === []) {
            return [];
        }

        $zones = Zone::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(fn (Zone $zone) => (string) $zone->id);

        $path = [];
        foreach ($ids as $id) {
            $zone = $zones->get((string) $id);
            if (! $zone) {
                continue;
            }
            $name = trim((string) ($zone->name ?? ''));
            $path[] = [
                'id' => (string) $zone->id,
                'name' => $name !== '' ? $name : '—',
            ];
        }

        return $path;
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

    /**
     * Parts of a parent polygon that no direct child covers.
     * Tiny slivers from boundary noise are dropped.
     *
     * @return array{whole: bool, pieces: list<array{rings: list<list<array{lat: float, lng: float}>>, lat: float, lng: float, area_km2: float}>}
     */
    public function uncoveredInsideParent(string $parentId): array
    {
        $empty = ['whole' => false, 'pieces' => []];
        if ($parentId === '') {
            return $empty;
        }

        $childIds = Zone::query()
            ->withoutGlobalScope('translate')
            ->where('parent_id', $parentId)
            ->whereNotNull('coordinates')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        try {
            if ($childIds === []) {
                DB::statement('SET @pk_gap_diff := (SELECT coordinates FROM zones WHERE id = ?)', [$parentId]);

                return [
                    'whole' => true,
                    'pieces' => $this->gapPiecesFromSession(true),
                ];
            }

            $started = false;
            foreach ($childIds as $childId) {
                try {
                    if (! $started) {
                        DB::statement('SET @pk_gap_union := (SELECT coordinates FROM zones WHERE id = ?)', [$childId]);
                        $started = true;
                    } else {
                        DB::statement(
                            'SET @pk_gap_union := ST_Union(@pk_gap_union, (SELECT coordinates FROM zones WHERE id = ?))',
                            [$childId]
                        );
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }

            if (! $started) {
                return $empty;
            }

            DB::statement(
                'SET @pk_gap_diff := (SELECT ST_Difference(coordinates, @pk_gap_union) FROM zones WHERE id = ?)',
                [$parentId]
            );

            return [
                'whole' => false,
                'pieces' => $this->gapPiecesFromSession(false),
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return $empty;
        }
    }

    /**
     * Square degrees. About 0.2 km² near Srinagar — below this is boundary noise, not a missing area.
     */
    private const GAP_MIN_AREA = 0.00002;

    /**
     * @return list<array{rings: list<list<array{lat: float, lng: float}>>, lat: float, lng: float, area_km2: float}>
     */
    private function gapPiecesFromSession(bool $whole): array
    {
        $meta = DB::selectOne('SELECT ST_GeometryType(@pk_gap_diff) AS t, ST_NumGeometries(@pk_gap_diff) AS n');
        $type = strtoupper((string) ($meta->t ?? ''));
        $count = $type === 'MULTIPOLYGON' || $type === 'GEOMETRYCOLLECTION'
            ? (int) ($meta->n ?? 0)
            : ($type === 'POLYGON' ? 1 : 0);

        $pieces = [];
        for ($index = 1; $index <= $count; $index++) {
            $row = DB::selectOne(
                'SELECT ST_Area(g) AS area,
                        ST_AsGeoJSON(IF(ST_GeometryType(g) = \'POLYGON\' AND ST_NumPoints(ST_ExteriorRing(g)) > 120, ST_Simplify(g, 0.0004), g)) AS gj,
                        ST_Y(ST_Centroid(g)) AS lat,
                        ST_X(ST_Centroid(g)) AS lng
                 FROM (
                    SELECT IF(? = 1 AND ? = \'POLYGON\', @pk_gap_diff, ST_GeometryN(@pk_gap_diff, ?)) AS g
                 ) x',
                [$count, $type, $index]
            );
            if (! $row || ! isset($row->area) || (float) $row->area < self::GAP_MIN_AREA) {
                continue;
            }
            $rings = $this->ringsFromGeoJson(is_string($row->gj) ? $row->gj : null);
            if ($rings === [] || count($rings[0]) < 3) {
                continue;
            }
            $inside = $this->interiorPoint($rings);
            $lat = $inside['lat'] ?? (float) $row->lat;
            $lng = $inside['lng'] ?? (float) $row->lng;
            $km2 = (float) $row->area * 111.32 * 111.32 * cos(deg2rad($lat));
            $pieces[] = [
                'rings' => $rings,
                'lat' => round($lat, 5),
                'lng' => round($lng, 5),
                'area_km2' => round(max($km2, 0), 1),
            ];
        }

        usort($pieces, fn ($a, $b) => $b['area_km2'] <=> $a['area_km2']);
        if (! $whole) {
            $pieces = array_slice($pieces, 0, 8);
        }

        return $pieces;
    }

    /**
     * @return list<list<array{lat: float, lng: float}>>
     */
    private function ringsFromGeoJson(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }
        $type = $decoded['type'] ?? '';
        $coordinates = $decoded['coordinates'] ?? null;
        if ($type === 'Polygon' && is_array($coordinates)) {
            return $this->normalizeRings($coordinates);
        }
        if ($type === 'MultiPolygon' && is_array($coordinates) && isset($coordinates[0]) && is_array($coordinates[0])) {
            return $this->normalizeRings($coordinates[0]);
        }

        return [];
    }

    /**
     * A point that lies in the uncovered shape, so the place name is not taken from a covered hole.
     *
     * @param  list<list<array{lat: float, lng: float}>>  $rings
     * @return array{lat: float, lng: float}|null
     */
    private function interiorPoint(array $rings): ?array
    {
        $outer = $rings[0] ?? [];
        if ($outer === []) {
            return null;
        }
        $lat = array_sum(array_column($outer, 'lat')) / count($outer);
        $lng = array_sum(array_column($outer, 'lng')) / count($outer);
        $candidates = [['lat' => $lat, 'lng' => $lng]];
        $count = count($outer);
        for ($i = 0; $i < $count; $i += max(1, (int) floor($count / 24))) {
            $a = $outer[$i];
            $b = $outer[($i + 1) % $count];
            $midLat = ($a['lat'] + $b['lat']) / 2;
            $midLng = ($a['lng'] + $b['lng']) / 2;
            $candidates[] = [
                'lat' => ($midLat * 0.65) + ($lat * 0.35),
                'lng' => ($midLng * 0.65) + ($lng * 0.35),
            ];
        }
        foreach ($candidates as $point) {
            if ($this->pointInRings($point, $rings)) {
                return $point;
            }
        }

        return null;
    }

    /**
     * @param  array{lat: float, lng: float}  $point
     * @param  list<list<array{lat: float, lng: float}>>  $rings
     */
    private function pointInRings(array $point, array $rings): bool
    {
        if (! $this->pointInRing($point, $rings[0])) {
            return false;
        }
        $holes = count($rings);
        for ($i = 1; $i < $holes; $i++) {
            if ($this->pointInRing($point, $rings[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{lat: float, lng: float}  $point
     * @param  list<array{lat: float, lng: float}>  $ring
     */
    private function pointInRing(array $point, array $ring): bool
    {
        $inside = false;
        $count = count($ring);
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = $ring[$i]['lat'];
            $yj = $ring[$j]['lat'];
            $xi = $ring[$i]['lng'];
            $xj = $ring[$j]['lng'];
            $intersect = (($yi > $point['lat']) !== ($yj > $point['lat']))
                && ($point['lng'] < ($xj - $xi) * ($point['lat'] - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @param  list<list<mixed>>  $rings
     * @return list<list<array{lat: float, lng: float}>>
     */
    private function normalizeRings(array $rings): array
    {
        $out = [];
        foreach ($rings as $ring) {
            if (! is_array($ring)) {
                continue;
            }
            $points = [];
            foreach ($ring as $pair) {
                if (! is_array($pair) || count($pair) < 2 || ! is_numeric($pair[0]) || ! is_numeric($pair[1])) {
                    continue;
                }
                $points[] = [
                    'lat' => round((float) $pair[1], 5),
                    'lng' => round((float) $pair[0], 5),
                ];
            }
            if (count($points) >= 2) {
                $first = $points[0];
                $last = $points[count($points) - 1];
                if (abs($first['lat'] - $last['lat']) < 1e-8 && abs($first['lng'] - $last['lng']) < 1e-8) {
                    array_pop($points);
                }
            }
            if (count($points) >= 3) {
                $out[] = $points;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, float>
     */
    private function zoneAreasById(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        if ($ids === []) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = DB::select(
                "SELECT id, ST_Area(coordinates) AS area FROM zones WHERE id IN ({$placeholders})",
                $ids
            );
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->id] = isset($row->area) ? (float) $row->area : PHP_FLOAT_MAX;
        }

        return $out;
    }
}
