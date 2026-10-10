<?php

namespace Modules\ZoneManagement\Services;

/**
 * Google Maps builds a draggable handle for every vertex of an editable polygon.
 * District-scale boundaries (thousands of points) freeze the editor, so the map
 * receives a reduced ring while the stored geometry stays untouched until edited.
 */
class ZoneMapPathSimplifier
{
    public const MAX_POINTS = 400;

    /** Degrees. About 300m — enough to keep a regional outline recognizable. */
    public const SQL_TOLERANCE = 0.003;

    public function sqlExpression(string $column = 'coordinates', ?int $maxPoints = null, ?float $tolerance = null): string
    {
        $max = $maxPoints ?? self::MAX_POINTS;
        $tolerance = $tolerance ?? self::SQL_TOLERANCE;

        return "ST_AsGeoJSON(IF({$column} IS NULL, NULL, IF(ST_NumPoints(ST_ExteriorRing({$column})) > {$max}, ST_Simplify({$column}, {$tolerance}), {$column})))";
    }

    /**
     * Bird’s-eye rings. Large districts can be coarse; ward-sized polygons stay tight
     * so a single tolerance does not erase them.
     */
    public function overviewSqlExpression(string $column = 'coordinates'): string
    {
        $tolerance = <<<SQL
CASE
    WHEN ST_Area({$column}) > 0.05 THEN 0.012
    WHEN ST_Area({$column}) > 0.005 THEN 0.004
    WHEN ST_Area({$column}) > 0.0005 THEN 0.0012
    ELSE 0.00018
END
SQL;
        $simplified = "IFNULL(ST_Simplify({$column}, {$tolerance}), {$column})";

        return "ST_AsGeoJSON(IF({$column} IS NULL, NULL, IF(ST_NumPoints(ST_ExteriorRing({$column})) > 80, {$simplified}, {$column})))";
    }

    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    public function fromGeoJson(mixed $geojson): array
    {
        if (! is_string($geojson) || $geojson === '') {
            return [];
        }

        $decoded = json_decode($geojson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $ring = $decoded['coordinates'][0] ?? null;
        if (! is_array($ring)) {
            return [];
        }

        $paths = [];
        foreach ($ring as $pair) {
            if (! is_array($pair) || count($pair) < 2 || ! is_numeric($pair[0]) || ! is_numeric($pair[1])) {
                continue;
            }
            $paths[] = ['lat' => (float) $pair[1], 'lng' => (float) $pair[0]];
        }

        return $this->dropClosingPoint($paths);
    }

    /**
     * Textarea format produced by the zone editor: (lat,lng),(lat,lng)
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    public function fromCoordinateText(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $paths = [];
        foreach (explode('),(', trim($value, "() \n\r\t")) as $single) {
            $coords = array_map('trim', explode(',', $single));
            if (count($coords) < 2 || ! is_numeric($coords[0]) || ! is_numeric($coords[1])) {
                continue;
            }
            $paths[] = ['lat' => (float) $coords[0], 'lng' => (float) $coords[1]];
        }

        return $this->dropClosingPoint($paths);
    }

    /**
     * @param  array<int, array{lat: float|int|string, lng: float|int|string}>  $paths
     * @return array<int, array{lat: float, lng: float}>
     */
    public function simplify(array $paths, int $maxPoints = self::MAX_POINTS): array
    {
        $points = $this->dropClosingPoint($this->normalize($paths));
        if (count($points) <= $maxPoints) {
            return $points;
        }

        $epsilon = 0.0002;
        $simplified = $points;
        for ($i = 0; $i < 8 && count($simplified) > $maxPoints; $i++) {
            $simplified = $this->douglasPeucker($points, $epsilon);
            $epsilon *= 1.8;
        }

        if (count($simplified) < 3) {
            return $points;
        }

        return $simplified;
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $paths
     * @return array<int, array{lat: float, lng: float}>
     */
    private function dropClosingPoint(array $paths): array
    {
        $count = count($paths);
        if ($count < 2) {
            return $paths;
        }

        $first = $paths[0];
        $last = $paths[$count - 1];
        if (abs($first['lat'] - $last['lat']) < 1e-10 && abs($first['lng'] - $last['lng']) < 1e-10) {
            array_pop($paths);
        }

        return $paths;
    }

    /**
     * @param  array<int, mixed>  $paths
     * @return array<int, array{lat: float, lng: float}>
     */
    private function normalize(array $paths): array
    {
        $points = [];
        foreach ($paths as $point) {
            if (is_object($point)) {
                $point = (array) $point;
            }
            if (! is_array($point) || ! isset($point['lat'], $point['lng']) || ! is_numeric($point['lat']) || ! is_numeric($point['lng'])) {
                continue;
            }
            $points[] = ['lat' => (float) $point['lat'], 'lng' => (float) $point['lng']];
        }

        return $points;
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $points
     * @return array<int, array{lat: float, lng: float}>
     */
    private function douglasPeucker(array $points, float $epsilon): array
    {
        $count = count($points);
        if ($count <= 2) {
            return $points;
        }

        $keep = array_fill(0, $count, false);
        $keep[0] = true;
        $keep[$count - 1] = true;
        $stack = [[0, $count - 1]];

        while ($stack !== []) {
            [$start, $end] = array_pop($stack);
            $maxDistance = 0.0;
            $index = $start;
            for ($i = $start + 1; $i < $end; $i++) {
                $distance = $this->perpendicularDistance($points[$i], $points[$start], $points[$end]);
                if ($distance > $maxDistance) {
                    $maxDistance = $distance;
                    $index = $i;
                }
            }
            if ($maxDistance <= $epsilon) {
                continue;
            }
            $keep[$index] = true;
            if ($index - $start > 1) {
                $stack[] = [$start, $index];
            }
            if ($end - $index > 1) {
                $stack[] = [$index, $end];
            }
        }

        $simplified = [];
        foreach ($keep as $i => $flag) {
            if ($flag) {
                $simplified[] = $points[$i];
            }
        }

        return $simplified;
    }

    /**
     * @param  array{lat: float, lng: float}  $point
     * @param  array{lat: float, lng: float}  $start
     * @param  array{lat: float, lng: float}  $end
     */
    private function perpendicularDistance(array $point, array $start, array $end): float
    {
        $dx = $end['lng'] - $start['lng'];
        $dy = $end['lat'] - $start['lat'];
        if ($dx == 0.0 && $dy == 0.0) {
            return hypot($point['lng'] - $start['lng'], $point['lat'] - $start['lat']);
        }

        $t = (($point['lng'] - $start['lng']) * $dx + ($point['lat'] - $start['lat']) * $dy) / ($dx * $dx + $dy * $dy);
        $t = max(0.0, min(1.0, $t));

        return hypot($point['lng'] - ($start['lng'] + $t * $dx), $point['lat'] - ($start['lat'] + $t * $dy));
    }
}
