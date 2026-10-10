<?php

namespace Modules\ZoneManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ZoneManagement\Entities\Zone;
use RuntimeException;

/**
 * Swaps a database over to an exported zone tree.
 *
 * A live zone whose name matches a zone in the file keeps its id, so bookings,
 * categories, providers, and leads on that zone stay put. Its boundary is replaced.
 * A live zone with no matching name has its links moved to its parent, and only
 * then is the old row deleted. A new zone that no live row claimed is inserted.
 */
class ZoneReplacementService
{
    private int $exportedCount = 0;

    public function exportedCount(): int
    {
        return $this->exportedCount;
    }

    public function export(string $path): string
    {
        $path = $this->absolutePath($path);
        $columns = $this->zoneColumns();
        $select = array_map(fn (string $column) => '`'.$column.'`', $columns);
        $select[] = 'ST_AsText(`coordinates`) AS wkt';

        $rows = DB::select('SELECT '.implode(', ', $select).' FROM `zones` ORDER BY `name`');
        $this->exportedCount = count($rows);
        if ($this->exportedCount === 0) {
            throw new RuntimeException('This database has no zones to export.');
        }

        $payload = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'zones' => array_map(function ($row) use ($columns) {
                $zone = ['wkt' => $row->wkt];
                foreach ($columns as $column) {
                    $zone[$column] = $row->{$column};
                }

                return $zone;
            }, $rows),
        ];

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create '.$directory);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json) === false) {
            throw new RuntimeException('Could not write '.$path);
        }

        return $path;
    }

    /**
     * @return array{summary: string, rows: list<string>, counts: array<string, int>, footer: string}
     */
    public function replace(string $path, bool $execute): array
    {
        $plan = $this->buildPlan($this->readExport($path));
        $counts = $this->countMoves($plan['retire']);

        if ($plan['unmapped'] !== []) {
            return $this->present($plan, $counts, false, true);
        }

        if ($execute) {
            DB::transaction(function () use ($plan) {
                $this->apply($plan);
            });
        }

        return $this->present($plan, $counts, $execute, false);
    }

    /**
     * @param  list<array<string, mixed>>  $incoming
     * @return array<string, mixed>
     */
    private function buildPlan(array $incoming): array
    {
        if ($incoming === []) {
            throw new RuntimeException('The export file has no zones.');
        }

        $required = ['ward_number', 'areas_encompassed', 'boundary_demarcation'];
        foreach ($required as $column) {
            if (! Schema::hasColumn('zones', $column)) {
                throw new RuntimeException('Run php artisan migrate on this database before replacing zones. Missing column: zones.'.$column);
            }
        }

        return $this->planZones($this->liveZones(), $incoming);
    }

    /**
     * @param  list<array{id: string, name: string, parent_id: ?string, aliases?: list<string>}>  $live
     * @param  list<array<string, mixed>>  $incoming
     * @return array<string, mixed>
     */
    public function planZones(array $live, array $incoming): array
    {
        $newById = [];
        foreach ($incoming as $zone) {
            $newById[(string) $zone['id']] = $zone;
        }

        $byParentAndName = [];
        $byName = [];
        foreach ($newById as $zone) {
            $parentName = '';
            if (! empty($zone['parent_id']) && isset($newById[(string) $zone['parent_id']])) {
                $parentName = (string) $newById[(string) $zone['parent_id']]['name'];
            }
            $key = $this->norm($parentName).'|'.$this->norm((string) $zone['name']);
            $byParentAndName[$key][] = $zone;
            $byName[$this->norm((string) $zone['name'])][] = $zone;
        }

        $liveById = [];
        foreach ($live as $zone) {
            $liveById[$zone['id']] = $zone;
        }

        $ordered = $live;
        usort($ordered, function (array $a, array $b) use ($liveById) {
            return $this->depth($a['id'], $liveById) <=> $this->depth($b['id'], $liveById);
        });

        $claim = [];
        $resolution = [];
        foreach ($ordered as $zone) {
            $match = $this->matchNew($zone, $liveById, $byParentAndName, $byName);
            if ($match !== null) {
                $newId = (string) $match['zone']['id'];
                if (isset($claim[$newId])) {
                    $resolution[$zone['id']] = [
                        'action' => 'retire',
                        'target' => $claim[$newId],
                        'name' => $zone['name'],
                        'reason' => 'same name is already kept',
                    ];
                    continue;
                }
                $claim[$newId] = $zone['id'];
                $resolution[$zone['id']] = [
                    'action' => 'keep',
                    'target' => $zone['id'],
                    'name' => $zone['name'],
                    'reason' => $match['reason'],
                    'new' => $match['zone'],
                    'old_name' => $zone['name'],
                ];
                continue;
            }

            $parentId = (string) ($zone['parent_id'] ?? '');
            if ($parentId !== '' && isset($resolution[$parentId]) && $resolution[$parentId]['action'] !== 'unmapped') {
                $resolution[$zone['id']] = [
                    'action' => 'retire',
                    'target' => $resolution[$parentId]['target'],
                    'name' => $zone['name'],
                    'reason' => 'moved to parent '.$this->nameOf($resolution[$parentId]['target'], $liveById, $resolution),
                ];
                continue;
            }

            $resolution[$zone['id']] = [
                'action' => 'unmapped',
                'target' => null,
                'name' => $zone['name'],
                'reason' => 'no zone with this name, and no parent to move it to',
            ];
        }

        $inserts = [];
        foreach ($newById as $newId => $zone) {
            if (! isset($claim[$newId])) {
                $zone['resolved_id'] = $newId;
                $zone['resolved_parent_id'] = $this->resolveParentId($zone['parent_id'] ?? null, $claim);
                $inserts[] = $zone;
            }
        }

        $keeps = [];
        $retire = [];
        $unmapped = [];
        foreach ($resolution as $liveId => $item) {
            if ($item['action'] === 'keep') {
                $new = $item['new'];
                $new['resolved_id'] = $liveId;
                $new['resolved_parent_id'] = $this->resolveParentId($new['parent_id'] ?? null, $claim);
                $item['new'] = $new;
                $keeps[$liveId] = $item;
            } elseif ($item['action'] === 'retire') {
                if ($item['target'] === $liveId) {
                    throw new RuntimeException('Refusing to move '.$item['name'].' onto itself.');
                }
                $retire[$liveId] = $item;
            } else {
                $unmapped[$liveId] = $item;
            }
        }

        return [
            'keeps' => $keeps,
            'retire' => $retire,
            'inserts' => $inserts,
            'unmapped' => $unmapped,
            'names' => $liveById,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $retire
     * @return array<string, int>
     */
    private function countMoves(array $retire): array
    {
        $counts = [
            'Bookings moved' => 0,
            'Category links moved' => 0,
            'Provider links moved' => 0,
            'Prices moved' => 0,
            'Prices left on the destination' => 0,
            'Lead records moved' => 0,
            'Areas moved' => 0,
        ];

        if ($retire === []) {
            return $counts;
        }

        $oldIds = array_keys($retire);
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'zone_id')) {
            $counts['Bookings moved'] = (int) DB::table('bookings')->whereIn('zone_id', $oldIds)->count();
        }
        if (Schema::hasTable('category_zone')) {
            $counts['Category links moved'] = (int) DB::table('category_zone')->whereIn('zone_id', $oldIds)->count();
        }
        if (Schema::hasTable('provider_zone')) {
            $counts['Provider links moved'] = (int) DB::table('provider_zone')->whereIn('zone_id', $oldIds)->count();
        }
        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'zone_id')) {
            $counts['Provider links moved'] += (int) DB::table('providers')->whereIn('zone_id', $oldIds)->count();
        }
        if (Schema::hasTable('variations')) {
            $counts['Prices moved'] = (int) DB::table('variations')->whereIn('zone_id', $oldIds)->count();
        }
        if (Schema::hasTable('customer_lead_areas')) {
            $counts['Areas moved'] = (int) DB::table('customer_lead_areas')->whereIn('zone_id', $oldIds)->count();
        }
        if (Schema::hasTable('lead_type_histories')) {
            $leadQuery = DB::table('lead_type_histories');
            $leadQuery->where(function ($query) use ($oldIds) {
                foreach ($oldIds as $id) {
                    $query->orWhere('data', 'like', '%'.$id.'%');
                }
            });
            $counts['Lead records moved'] = (int) $leadQuery->count();
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function apply(array $plan): void
    {
        $this->insertZonesInOrder($plan['inserts']);

        foreach ($plan['keeps'] as $item) {
            $this->updateKeptZone($item);
        }

        $this->assertParentsExist($plan);

        foreach ($plan['retire'] as $oldId => $item) {
            $this->repoint((string) $oldId, (string) $item['target']);
        }

        $retiredIds = array_map('strval', array_keys($plan['retire']));
        $this->assertNothingPointsAt($retiredIds);

        if ($retiredIds !== []) {
            DB::table('zones')->whereIn('id', $retiredIds)->update(['parent_id' => null]);
            DB::table('zones')->whereIn('id', $retiredIds)->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $zones
     */
    private function insertZonesInOrder(array $zones): void
    {
        $pending = array_values($zones);
        $ready = [];
        $guard = 0;
        while ($pending !== []) {
            $progress = false;
            foreach ($pending as $index => $zone) {
                $parent = $zone['resolved_parent_id'] ?? null;
                $parentReady = $parent === null || $parent === ''
                    || isset($ready[$parent])
                    || DB::table('zones')->where('id', $parent)->exists();
                if (! $parentReady) {
                    continue;
                }
                if (empty($zone['wkt'])) {
                    throw new RuntimeException('New zone '.$zone['name'].' has no boundary in the export.');
                }
                $this->insertZone($zone);
                $ready[(string) $zone['resolved_id']] = true;
                unset($pending[$index]);
                $progress = true;
            }
            if (! $progress || ++$guard > 1000) {
                throw new RuntimeException('Could not insert zones because a parent is missing.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $zone
     */
    private function insertZone(array $zone): void
    {
        $id = (string) $zone['resolved_id'];
        $now = now();
        DB::insert(
            'INSERT INTO `zones` (`id`, `name`, `parent_id`, `ward_number`, `description`, `areas_encompassed`, `boundary_demarcation`, `is_active`, `coordinates`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?), ?, ?)',
            [
                $id,
                $zone['name'],
                $zone['resolved_parent_id'],
                $zone['ward_number'] ?? null,
                $zone['description'] ?? null,
                $zone['areas_encompassed'] ?? null,
                $zone['boundary_demarcation'] ?? null,
                (int) ($zone['is_active'] ?? 1),
                $zone['wkt'] ?: null,
                $now,
                $now,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function updateKeptZone(array $item): void
    {
        $zone = $item['new'];
        if (empty($zone['wkt'])) {
            throw new RuntimeException('Zone '.$zone['name'].' has no boundary in the export. Nothing was changed.');
        }
        DB::update(
            'UPDATE `zones`
             SET `name` = ?, `parent_id` = ?, `ward_number` = ?, `description` = ?, `areas_encompassed` = ?, `boundary_demarcation` = ?, `is_active` = ?, `coordinates` = ST_GeomFromText(?), `updated_at` = ?
             WHERE `id` = ?',
            [
                $zone['name'],
                $zone['resolved_parent_id'],
                $zone['ward_number'] ?? null,
                $zone['description'] ?? null,
                $zone['areas_encompassed'] ?? null,
                $zone['boundary_demarcation'] ?? null,
                (int) ($zone['is_active'] ?? 1),
                $zone['wkt'] ?: null,
                now(),
                $item['target'],
            ]
        );

        if (Schema::hasTable('translations')) {
            DB::table('translations')
                ->where('translationable_type', Zone::class)
                ->where('translationable_id', $item['target'])
                ->where('key', 'zone_name')
                ->where('value', $item['old_name'])
                ->update(['value' => $zone['name']]);
        }
    }

    private function repoint(string $oldId, string $newId): void
    {
        $this->repointAreas($oldId, $newId);
        $this->repointPivot('category_zone', 'category_id', $oldId, $newId);
        $this->repointPivot('provider_zone', 'provider_id', $oldId, $newId);
        $this->repointPivot('user_zones', 'user_id', $oldId, $newId);
        $this->repointVariations($oldId, $newId);

        foreach ($this->zoneIdColumns() as $column) {
            $table = $column['table'];
            if (in_array($table, ['category_zone', 'provider_zone', 'user_zones', 'variations', 'customer_lead_areas'], true)) {
                continue;
            }
            DB::table($table)->where('zone_id', $oldId)->update(['zone_id' => $newId]);
        }

        foreach ($this->zoneListColumns() as $column) {
            $table = $column['table'];
            $field = $column['column'];
            DB::table($table)
                ->where($field, 'like', '%'.$oldId.'%')
                ->orderBy($this->primaryKey($table))
                ->chunkById(100, function ($rows) use ($table, $field, $oldId, $newId) {
                    foreach ($rows as $row) {
                        $key = $this->primaryKey($table);
                        $value = (string) ($row->{$field} ?? '');
                        $replaced = str_replace($oldId, $newId, $value);
                        if ($replaced !== $value) {
                            DB::table($table)->where($key, $row->{$key})->update([$field => $replaced]);
                        }
                    }
                }, $this->primaryKey($table));
        }

        $this->repointLeadHistory($oldId, $newId);
    }

    private function repointAreas(string $oldId, string $newId): void
    {
        if (! Schema::hasTable('customer_lead_areas') || ! Schema::hasColumn('customer_lead_areas', 'zone_id')) {
            return;
        }

        $areas = DB::table('customer_lead_areas')->where('zone_id', $oldId)->get();
        foreach ($areas as $area) {
            $keeper = DB::table('customer_lead_areas')
                ->where('zone_id', $newId)
                ->whereRaw('LOWER(`name`) = ?', [mb_strtolower((string) $area->name)])
                ->first();

            if ($keeper) {
                if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'area_id')) {
                    DB::table('bookings')->where('area_id', $area->id)->update(['area_id' => $keeper->id]);
                }
                $this->repointLeadAreaId((string) $area->id, (string) $keeper->id);
                DB::table('customer_lead_areas')->where('id', $area->id)->delete();
                continue;
            }

            DB::table('customer_lead_areas')->where('id', $area->id)->update(['zone_id' => $newId]);
        }
    }

    private function repointPivot(string $table, string $partner, string $oldId, string $newId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'zone_id') || ! Schema::hasColumn($table, $partner)) {
            return;
        }

        $partners = DB::table($table)->where('zone_id', $oldId)->pluck($partner);
        if ($partners->isEmpty()) {
            return;
        }

        $already = DB::table($table)->where('zone_id', $newId)->whereIn($partner, $partners)->pluck($partner);
        if ($already->isNotEmpty()) {
            DB::table($table)->where('zone_id', $oldId)->whereIn($partner, $already)->delete();
        }

        DB::table($table)->where('zone_id', $oldId)->update(['zone_id' => $newId]);
    }

    private function repointVariations(string $oldId, string $newId): void
    {
        if (! Schema::hasTable('variations')) {
            return;
        }

        $rows = DB::table('variations')->where('zone_id', $oldId)->get(['id', 'service_id', 'variant_key']);
        foreach ($rows as $row) {
            $clash = DB::table('variations')
                ->where('zone_id', $newId)
                ->where('service_id', $row->service_id)
                ->where('variant_key', $row->variant_key)
                ->exists();
            if ($clash) {
                DB::table('variations')->where('id', $row->id)->delete();
                continue;
            }
            DB::table('variations')->where('id', $row->id)->update(['zone_id' => $newId]);
        }
    }

    private function repointLeadHistory(string $oldId, string $newId): void
    {
        if (! Schema::hasTable('lead_type_histories')) {
            return;
        }

        DB::table('lead_type_histories')
            ->where('data', 'like', '%'.$oldId.'%')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($oldId, $newId) {
                foreach ($rows as $row) {
                    $data = json_decode((string) $row->data, true);
                    if (! is_array($data)) {
                        continue;
                    }
                    $updated = $this->rewriteZoneIds($data, $oldId, $newId);
                    if ($updated === $data) {
                        continue;
                    }
                    DB::table('lead_type_histories')->where('id', $row->id)->update([
                        'data' => json_encode($updated, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }

    private function repointLeadAreaId(string $oldAreaId, string $newAreaId): void
    {
        if (! Schema::hasTable('lead_type_histories')) {
            return;
        }

        DB::table('lead_type_histories')
            ->where('data', 'like', '%"area_id":'.$oldAreaId.'%')
            ->orWhere('data', 'like', '%"area_id":"'.$oldAreaId.'"%')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($oldAreaId, $newAreaId) {
                foreach ($rows as $row) {
                    $data = json_decode((string) $row->data, true);
                    if (! is_array($data) || ! array_key_exists('area_id', $data)) {
                        continue;
                    }
                    if ((string) $data['area_id'] !== $oldAreaId) {
                        continue;
                    }
                    $data['area_id'] = is_numeric($newAreaId) ? (int) $newAreaId : $newAreaId;
                    DB::table('lead_type_histories')->where('id', $row->id)->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rewriteZoneIds(array $data, string $oldId, string $newId): array
    {
        foreach ($data as $key => $value) {
            if ($key === 'zone_id' && (string) $value === $oldId) {
                $data[$key] = $newId;
                continue;
            }
            if ($key === 'zone_ids' && is_array($value)) {
                $data[$key] = array_values(array_map(function ($id) use ($oldId, $newId) {
                    return (string) $id === $oldId ? $newId : $id;
                }, $value));
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->rewriteZoneIds($value, $oldId, $newId);
            }
        }

        return $data;
    }

    /**
     * @param  list<string>  $retiredIds
     */
    private function assertNothingPointsAt(array $retiredIds): void
    {
        foreach ($retiredIds as $id) {
            foreach ($this->zoneIdColumns() as $column) {
                $count = (int) DB::table($column['table'])->where($column['column'], $id)->count();
                if ($count > 0) {
                    throw new RuntimeException($column['table'].'.'.$column['column'].' still has '.$count.' rows for '.$id.'. Nothing was deleted.');
                }
            }

            $children = (int) DB::table('zones')->where('parent_id', $id)->whereNotIn('id', $retiredIds)->count();
            if ($children > 0) {
                throw new RuntimeException('A kept zone still uses '.$id.' as its parent. Nothing was deleted.');
            }

            if (Schema::hasTable('lead_type_histories')) {
                $leads = (int) DB::table('lead_type_histories')->where('data', 'like', '%'.$id.'%')->count();
                if ($leads > 0) {
                    throw new RuntimeException('A lead still stores zone '.$id.'. Nothing was deleted.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function assertParentsExist(array $plan): void
    {
        $ids = [];
        foreach ($plan['keeps'] as $item) {
            $ids[] = (string) $item['new']['resolved_parent_id'];
        }
        foreach ($plan['inserts'] as $zone) {
            $ids[] = (string) ($zone['resolved_parent_id'] ?? '');
        }
        $ids = array_values(array_filter(array_unique($ids)));
        if ($ids === []) {
            return;
        }

        $found = DB::table('zones')->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $missing = array_diff($ids, $found);
        if ($missing !== []) {
            throw new RuntimeException('A zone parent is missing: '.implode(', ', $missing));
        }
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $byParentAndName
     * @param  array<string, list<array<string, mixed>>>  $byName
     * @param  array<string, array<string, mixed>>  $liveById
     * @return array{zone: array<string, mixed>, reason: string}|null
     */
    private function matchNew(array $zone, array $liveById, array $byParentAndName, array $byName): ?array
    {
        $parentName = '';
        $parentId = (string) ($zone['parent_id'] ?? '');
        if ($parentId !== '' && isset($liveById[$parentId])) {
            $parentName = (string) $liveById[$parentId]['name'];
        }

        $aliases = [$this->norm((string) $zone['name'])];
        foreach ($zone['aliases'] ?? [] as $alias) {
            $aliases[] = $this->norm($alias);
        }
        $aliases = array_values(array_unique(array_filter($aliases)));

        foreach ($aliases as $alias) {
            $key = $this->norm($parentName).'|'.$alias;
            if (isset($byParentAndName[$key]) && count($byParentAndName[$key]) === 1) {
                return ['zone' => $byParentAndName[$key][0], 'reason' => 'same name and parent'];
            }
        }

        foreach ($aliases as $alias) {
            if (isset($byName[$alias]) && count($byName[$alias]) === 1) {
                return ['zone' => $byName[$alias][0], 'reason' => 'same name'];
            }
        }

        return null;
    }

    /**
     * @return list<array{id: string, name: string, parent_id: ?string, aliases: list<string>}>
     */
    private function liveZones(): array
    {
        $zones = DB::table('zones')->select(['id', 'name', 'parent_id'])->get();
        $aliases = [];
        if (Schema::hasTable('translations')) {
            $rows = DB::table('translations')
                ->where('translationable_type', Zone::class)
                ->where('key', 'zone_name')
                ->get(['translationable_id', 'value']);
            foreach ($rows as $row) {
                $aliases[(string) $row->translationable_id][] = (string) $row->value;
            }
        }

        return $zones->map(function ($zone) use ($aliases) {
            return [
                'id' => (string) $zone->id,
                'name' => (string) $zone->name,
                'parent_id' => $zone->parent_id ? (string) $zone->parent_id : null,
                'aliases' => $aliases[(string) $zone->id] ?? [],
            ];
        })->all();
    }

    /**
     * @param  array<string, array{id: string, parent_id: ?string}>  $liveById
     */
    private function depth(string $id, array $liveById): int
    {
        $depth = 0;
        $guard = 0;
        while (isset($liveById[$id]['parent_id']) && $liveById[$id]['parent_id'] && isset($liveById[$liveById[$id]['parent_id']])) {
            $id = $liveById[$id]['parent_id'];
            $depth++;
            if (++$guard > 30) {
                break;
            }
        }

        return $depth;
    }

    /**
     * @param  array<string, string>  $claim
     */
    private function resolveParentId(mixed $parentId, array $claim): ?string
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }
        $parentId = (string) $parentId;

        return $claim[$parentId] ?? $parentId;
    }

    /**
     * @param  array<string, array<string, mixed>>  $liveById
     * @param  array<string, array<string, mixed>>  $resolution
     */
    private function nameOf(string $id, array $liveById, array $resolution): string
    {
        if (isset($liveById[$id])) {
            return (string) $liveById[$id]['name'];
        }
        if (isset($resolution[$id]['name'])) {
            return (string) $resolution[$id]['name'];
        }

        return $id;
    }

    /**
     * @return list<string>
     */
    private function zoneColumns(): array
    {
        $columns = ['id', 'parent_id', 'name', 'is_active'];
        foreach (['ward_number', 'description', 'areas_encompassed', 'boundary_demarcation'] as $column) {
            if (Schema::hasColumn('zones', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * @return list<array{table: string, column: string}>
     */
    private function zoneIdColumns(): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ? AND TABLE_NAME <> ?',
            ['zone_id', 'zones']
        );

        $columns = [];
        foreach ($rows as $row) {
            $table = (string) $row->table_name;
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue;
            }
            $columns[] = ['table' => $table, 'column' => 'zone_id'];
        }

        return $columns;
    }

    /**
     * @return list<array{table: string, column: string}>
     */
    private function zoneListColumns(): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?',
            ['zone_ids']
        );

        $columns = [];
        foreach ($rows as $row) {
            $table = (string) $row->table_name;
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue;
            }
            $columns[] = ['table' => $table, 'column' => 'zone_ids'];
        }

        return $columns;
    }

    private function primaryKey(string $table): string
    {
        $row = DB::selectOne(
            'SELECT COLUMN_NAME AS column_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_KEY = ?
             ORDER BY ORDINAL_POSITION LIMIT 1',
            [$table, 'PRI']
        );

        return $row ? (string) $row->column_name : 'id';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readExport(string $path): array
    {
        $path = $this->absolutePath($path);
        if (! is_file($path)) {
            throw new RuntimeException('Export file not found: '.$path);
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload) || (int) ($payload['version'] ?? 0) !== 1 || ! is_array($payload['zones'] ?? null)) {
            throw new RuntimeException('This file is not a zone replacement export.');
        }

        foreach ($payload['zones'] as $zone) {
            foreach (['id', 'name'] as $key) {
                if (! isset($zone[$key]) || $zone[$key] === '') {
                    throw new RuntimeException('A zone in the export is missing '.$key.'.');
                }
            }
        }

        return $payload['zones'];
    }

    private function absolutePath(string $path): string
    {
        if ($path !== '' && $path[0] === '/') {
            return $path;
        }

        return base_path($path);
    }

    private function norm(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return $name;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, int>  $counts
     * @return array{summary: string, rows: list<string>, counts: array<string, int>, footer: string}
     */
    private function present(array $plan, array $counts, bool $executed, bool $blocked): array
    {
        $rows = [];
        if ($plan['unmapped'] !== []) {
            $rows[] = 'These live zones could not be placed, so nothing can be deleted:';
            foreach ($plan['unmapped'] as $item) {
                $rows[] = '  '.$item['name'].' — '.$item['reason'];
            }
        }

        if ($plan['retire'] !== []) {
            $rows[] = 'Old zones whose links move to the parent, and which are deleted only after that:';
            foreach ($plan['retire'] as $item) {
                $rows[] = '  '.$item['name'].' → '.$item['reason'];
            }
        }

        $rows[] = 'Kept '.count($plan['keeps']).' zones whose names match. Their ids stay, so existing links stay, and the boundary is replaced.';
        $rows[] = 'Adding '.count($plan['inserts']).' new zones.';
        if ($plan['inserts'] !== []) {
            $names = array_map(fn (array $zone) => (string) $zone['name'], $plan['inserts']);
            $rows[] = '  '.implode(', ', $names);
        }

        $summary = $blocked
            ? 'Stopped. Some live zones have no matching new zone.'
            : ($executed ? 'Zone replacement finished.' : 'Dry run. No rows were changed.');

        $footer = $blocked
            ? 'Nothing was changed.'
            : ($executed
                ? 'Old zones were deleted only after their bookings, categories, providers, and leads pointed at a zone that still exists. Provider subscriptions stay on the provider; a provider moved to a parent covers every ward under that parent.'
                : 'Run the same command with --execute when this plan is what you want. Provider subscriptions stay on the provider; a provider moved to a parent covers every ward under that parent.');

        return [
            'summary' => $summary,
            'rows' => $rows,
            'counts' => $counts,
            'footer' => $footer,
        ];
    }
}
