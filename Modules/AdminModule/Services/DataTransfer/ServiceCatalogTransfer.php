<?php

namespace Modules\AdminModule\Services\DataTransfer;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ZoneManagement\Entities\Zone;
use Throwable;

class ServiceCatalogTransfer
{
    public const PAYLOAD_TYPE = 'service_catalog_v1';

    public function export(): array
    {
        $categories = DB::table('categories')->get()->map(fn ($r) => (array) $r)->values()->all();
        $categories = $this->sortCategoriesForInsert($categories);
        $categoryIds = array_column($categories, 'id');

        $services = DB::table('services')->orderBy('id')->get()->map(fn ($r) => (array) $r)->values()->all();
        $serviceIds = array_column($services, 'id');

        $variations = [];
        if ($serviceIds !== []) {
            $variations = DB::table('variations')->whereIn('service_id', $serviceIds)->get()->map(fn ($r) => (array) $r)->values()->all();
        }

        $categoryZone = [];
        if ($categoryIds !== []) {
            $categoryZone = DB::table('category_zone')->whereIn('category_id', $categoryIds)->get()->map(fn ($r) => (array) $r)->values()->all();
        }

        $tagLinks = [];
        $tags = [];
        if ($serviceIds !== []) {
            $tagLinks = DB::table('service_tag')->whereIn('service_id', $serviceIds)->get()->map(fn ($r) => (array) $r)->values()->all();
            $tagIds = array_values(array_unique(array_filter(array_column($tagLinks, 'tag_id'))));
            if ($tagIds !== []) {
                $tags = DB::table('tags')->whereIn('id', $tagIds)->get()->map(fn ($r) => (array) $r)->values()->all();
            }
        }

        $faqs = [];
        if ($serviceIds !== []) {
            $faqs = DB::table('faqs')->whereIn('service_id', $serviceIds)->get()->map(fn ($r) => (array) $r)->values()->all();
        }

        $translations = $this->exportCategoryServiceTranslations($categoryIds, $serviceIds);

        $zoneIds = collect($variations)->pluck('zone_id')->filter()
            ->merge(collect($categoryZone)->pluck('zone_id'))
            ->unique()->filter()->values()->all();

        $zones = [];
        $zoneTranslations = [];
        if ($zoneIds !== []) {
            // Polygon WKB is binary and breaks json_encode (invalid UTF-8). Geometry is omitted;
            // zones still match by id / name on import; redraw polygons in admin if you create new zones.
            $zones = DB::table('zones')->whereIn('id', $zoneIds)->get()->map(function ($r) {
                $a = (array) $r;
                unset($a['coordinates']);

                return $a;
            })->values()->all();
            $zoneTranslations = DB::table('translations')
                ->where('translationable_type', Zone::class)
                ->whereIn('translationable_id', $zoneIds)
                ->get()->map(fn ($r) => (array) $r)->values()->all();
        }

        return [
            'meta' => [
                'domain' => 'service',
                'payload_type' => self::PAYLOAD_TYPE,
                'version' => 1,
                'exported_at' => Carbon::now()->toIso8601String(),
                'zones_geometry_omitted' => true,
            ],
            'categories' => $categories,
            'category_zone' => $categoryZone,
            'tags' => $tags,
            'services' => $services,
            'service_tag' => $tagLinks,
            'faqs' => $faqs,
            'variations' => $variations,
            'translations' => $translations,
            'zones' => $zones,
            'zone_translations' => $zoneTranslations,
        ];
    }

    public function preview(array $payload): array
    {
        $this->assertValidPayload($payload);

        $categories = $payload['categories'] ?? [];
        $services = $payload['services'] ?? [];
        $variations = $payload['variations'] ?? [];

        $mainCat = 0;
        $subCat = 0;
        foreach ($categories as $c) {
            $c = (array) $c;
            $pos = (int) ($c['position'] ?? 1);
            if ($pos === 2 || ! empty($c['parent_id'])) {
                $subCat++;
            } else {
                $mainCat++;
            }
        }

        $sampleCategoryNames = $this->sampleNamesFromTranslations(
            $payload['translations'] ?? [],
            Category::class,
            array_slice(array_column($categories, 'id'), 0, 20),
            'name',
            8
        );

        $sampleServiceNames = $this->sampleNamesFromTranslations(
            $payload['translations'] ?? [],
            Service::class,
            array_slice(array_column($services, 'id'), 0, 20),
            'name',
            8
        );

        $zoneLabels = [];
        foreach ($payload['zones'] ?? [] as $z) {
            $z = (array) $z;
            $zn = $this->zoneDefaultName((string) ($z['id'] ?? ''), $payload['zone_translations'] ?? []);
            if ($zn !== '') {
                $zoneLabels[] = ['name' => $zn];
            }
        }

        $sampleVariations = array_slice($variations, 0, 15);

        return [
            'main_categories' => $mainCat,
            'sub_categories' => $subCat,
            'categories_total' => count($categories),
            'services_total' => count($services),
            'variations_total' => count($variations),
            'tags_total' => count($payload['tags'] ?? []),
            'faqs_total' => count($payload['faqs'] ?? []),
            'category_zone_links' => count($payload['category_zone'] ?? []),
            'zones_referenced' => $zoneLabels,
            'sample_category_names' => $sampleCategoryNames,
            'sample_service_names' => $sampleServiceNames,
            'sample_variations' => array_map(fn ($v) => $this->sanitizeSampleVariationRow((array) $v), $sampleVariations),
            'tree' => $this->buildImportPreviewTree($payload),
        ];
    }

    /**
     * Nested category → subcategory → service → variation tree for import preview UI.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, name: string, synthetic?: bool, children: list<mixed>}>
     */
    private function buildImportPreviewTree(array $payload): array
    {
        $translations = $payload['translations'] ?? [];
        $zoneTranslations = $payload['zone_translations'] ?? [];
        $categories = collect($payload['categories'] ?? [])->map(fn ($c) => (array) $c)->keyBy('id');
        $services = collect($payload['services'] ?? [])->map(fn ($s) => (array) $s);
        $variations = collect($payload['variations'] ?? [])->map(fn ($v) => (array) $v);

        $bySubId = [];
        foreach ($services as $svc) {
            $subId = $svc['sub_category_id'] ?? null;
            if ($subId === null || $subId === '') {
                continue;
            }
            $k = (string) $subId;
            $bySubId[$k][] = $svc;
        }

        $directByMainId = [];
        foreach ($services as $svc) {
            $mainId = $svc['category_id'] ?? null;
            $subId = $svc['sub_category_id'] ?? null;
            if ($mainId === null || $mainId === '' || ($subId !== null && $subId !== '')) {
                continue;
            }
            $k = (string) $mainId;
            $directByMainId[$k][] = $svc;
        }

        $variationNodesForService = function (string $serviceId) use ($variations, $zoneTranslations): array {
            $rows = $variations->filter(fn ($v) => (string) ($v['service_id'] ?? '') === $serviceId)->values()->all();
            usort($rows, function ($a, $b) {
                $ka = ($a['variant_key'] ?? '').' '.($a['zone_id'] ?? '');
                $kb = ($b['variant_key'] ?? '').' '.($b['zone_id'] ?? '');

                return $ka <=> $kb;
            });
            $out = [];
            foreach ($rows as $v) {
                $label = trim((string) ($v['variant'] ?? ''));
                if ($this->looksLikeUuid($label)) {
                    $label = '';
                }
                if ($label === '' && ! empty($v['variant_key'])) {
                    $vk = (string) $v['variant_key'];
                    if (! $this->looksLikeUuid($vk)) {
                        $label = trim($vk);
                    }
                }
                if ($label === '' || $this->looksLikeUuid($label)) {
                    $label = translate('Catalog_variation');
                }
                $zid = isset($v['zone_id']) ? (string) $v['zone_id'] : '';
                $zoneLabel = $zid !== '' ? $this->zoneDefaultName($zid, $zoneTranslations) : '';
                if ($zoneLabel !== '' && $this->looksLikeUuid($zoneLabel)) {
                    $zoneLabel = '';
                }
                $out[] = [
                    'type' => 'variation',
                    'label' => $label,
                    'price' => $v['price'] ?? null,
                    'zone_label' => $zoneLabel,
                ];
            }

            return $out;
        };

        $serviceNodes = function (array $svcList) use ($translations, $variationNodesForService): array {
            usort($svcList, function ($a, $b) use ($translations) {
                $na = $this->translationDisplayName(Service::class, (string) $a['id'], $translations, (string) ($a['name'] ?? ''));
                $nb = $this->translationDisplayName(Service::class, (string) $b['id'], $translations, (string) ($b['name'] ?? ''));

                return strnatcasecmp($na, $nb);
            });
            $nodes = [];
            foreach ($svcList as $svc) {
                $vid = (string) $svc['id'];
                $nodes[] = [
                    'type' => 'service',
                    'name' => $this->treeEntityName(
                        $this->translationDisplayName(Service::class, $vid, $translations, (string) ($svc['name'] ?? '')),
                        'service'
                    ),
                    'children' => $variationNodesForService($vid),
                ];
            }

            return $nodes;
        };

        $roots = $categories->filter(fn ($c) => empty($c['parent_id']))->values()->all();
        usort($roots, function ($a, $b) use ($translations) {
            $na = $this->translationDisplayName(Category::class, (string) $a['id'], $translations, (string) ($a['name'] ?? ''));
            $nb = $this->translationDisplayName(Category::class, (string) $b['id'], $translations, (string) ($b['name'] ?? ''));

            return strnatcasecmp($na, $nb);
        });

        $tree = [];
        foreach ($roots as $main) {
            $mainId = (string) $main['id'];
            $mainName = $this->treeEntityName(
                $this->translationDisplayName(Category::class, $mainId, $translations, (string) ($main['name'] ?? '')),
                'main_cat'
            );

            $subs = $categories->filter(fn ($c) => (string) ($c['parent_id'] ?? '') === $mainId)->values()->all();
            usort($subs, function ($a, $b) use ($translations) {
                $na = $this->translationDisplayName(Category::class, (string) $a['id'], $translations, (string) ($a['name'] ?? ''));
                $nb = $this->translationDisplayName(Category::class, (string) $b['id'], $translations, (string) ($b['name'] ?? ''));

                return strnatcasecmp($na, $nb);
            });

            $subNodes = [];
            foreach ($subs as $sub) {
                $subId = (string) $sub['id'];
                $subName = $this->treeEntityName(
                    $this->translationDisplayName(Category::class, $subId, $translations, (string) ($sub['name'] ?? '')),
                    'sub_cat'
                );
                $svcList = $bySubId[$subId] ?? [];
                $subNodes[] = [
                    'type' => 'subcategory',
                    'name' => $subName,
                    'synthetic' => false,
                    'children' => $serviceNodes($svcList),
                ];
            }

            $direct = $directByMainId[$mainId] ?? [];
            if ($direct !== []) {
                $subNodes[] = [
                    'type' => 'subcategory',
                    'name' => translate('Import_tree_direct_services'),
                    'synthetic' => true,
                    'children' => $serviceNodes($direct),
                ];
            }

            $tree[] = [
                'type' => 'category',
                'name' => $mainName,
                'children' => $subNodes,
            ];
        }

        return $tree;
    }

    /**
     * @param  array<int, array<string, mixed>>  $translations
     */
    private function translationDisplayName(string $modelClass, string $id, array $translations, string $fallbackColumn): string
    {
        $en = null;
        $any = null;
        foreach ($translations as $t) {
            $t = (array) $t;
            if (($t['translationable_type'] ?? '') !== $modelClass || (string) ($t['translationable_id'] ?? '') !== $id) {
                continue;
            }
            if (($t['key'] ?? '') !== 'name') {
                continue;
            }
            $val = trim((string) ($t['value'] ?? ''));
            if ($val === '') {
                continue;
            }
            if (($t['locale'] ?? '') === 'en') {
                $en = $val;
                break;
            }
            if ($any === null) {
                $any = $val;
            }
        }
        if ($en !== null) {
            return $en;
        }
        if ($any !== null) {
            return $any;
        }
        $fb = trim($fallbackColumn);

        return $fb !== '' ? $fb : ($modelClass === Category::class ? 'Category' : 'Service');
    }

    /**
     * @return array{variant: mixed, variant_key: mixed, price: mixed}
     */
    private function sanitizeSampleVariationRow(array $v): array
    {
        $vk = $v['variant_key'] ?? null;
        if (is_string($vk) && $this->looksLikeUuid($vk)) {
            $vk = null;
        }
        $var = $v['variant'] ?? null;
        if (is_string($var) && $this->looksLikeUuid(trim($var))) {
            $var = null;
        }

        return [
            'variant' => $var,
            'variant_key' => $vk,
            'price' => $v['price'] ?? null,
        ];
    }

    private function looksLikeUuid(string $s): bool
    {
        $s = trim($s);
        if ($s === '') {
            return false;
        }
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $s)) {
            return true;
        }
        if (preg_match('/^[0-9a-f]{32}$/i', $s)) {
            return true;
        }

        return false;
    }

    private function treeEntityName(string $name, string $kind): string
    {
        $n = trim($name);
        if ($n === '' || $this->looksLikeUuid($n)) {
            return match ($kind) {
                'main_cat' => translate('category'),
                'sub_cat' => translate('sub_category'),
                'service' => translate('Service'),
                default => $n,
            };
        }

        return $name;
    }

    /**
     * @return array{imported: array<string, int>, warnings: array<int, string>}
     */
    public function import(array $payload): array
    {
        $this->assertValidPayload($payload);

        $warnings = [];
        $imported = [
            'zones' => 0,
            'categories' => 0,
            'category_zone' => 0,
            'tags' => 0,
            'services' => 0,
            'service_tag' => 0,
            'faqs' => 0,
            'variations' => 0,
        ];

        DB::beginTransaction();

        try {
            $zoneMap = $this->buildZoneIdMap($payload['zones'] ?? [], $payload['zone_translations'] ?? [], $warnings);

            foreach ($this->sortCategoriesForInsert($payload['categories'] ?? []) as $row) {
                $row = (array) $row;
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                unset($row['id']);
                DB::table('categories')->updateOrInsert(['id' => $id], array_merge($row, ['id' => $id]));
                $imported['categories']++;
            }

            $this->replaceTranslationsForType(Category::class, array_column($payload['categories'] ?? [], 'id'), $payload['translations'] ?? []);

            $categoryIds = array_column($payload['categories'] ?? [], 'id');
            if ($categoryIds !== []) {
                DB::table('category_zone')->whereIn('category_id', $categoryIds)->delete();
            }
            foreach ($payload['category_zone'] ?? [] as $pivot) {
                $pivot = (array) $pivot;
                $zid = $zoneMap[$pivot['zone_id'] ?? ''] ?? ($pivot['zone_id'] ?? null);
                if (! $zid || ! Schema::hasTable('category_zone')) {
                    continue;
                }
                if (! DB::table('zones')->where('id', $zid)->exists()) {
                    $warnings[] = 'Skipped a category–zone link (target zone missing).';

                    continue;
                }
                DB::table('category_zone')->insert([
                    'category_id' => $pivot['category_id'],
                    'zone_id' => $zid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $imported['category_zone']++;
            }

            foreach ($payload['tags'] ?? [] as $row) {
                $row = (array) $row;
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                unset($row['id']);
                DB::table('tags')->updateOrInsert(['id' => $id], array_merge($row, ['id' => $id]));
                $imported['tags']++;
            }

            $serviceIds = array_column($payload['services'] ?? [], 'id');
            foreach ($payload['services'] ?? [] as $row) {
                $row = (array) $row;
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                unset($row['id']);
                DB::table('services')->updateOrInsert(['id' => $id], array_merge($row, ['id' => $id]));
                $imported['services']++;
            }

            if ($serviceIds !== []) {
                DB::table('variations')->whereIn('service_id', $serviceIds)->delete();
                DB::table('service_tag')->whereIn('service_id', $serviceIds)->delete();
                DB::table('faqs')->whereIn('service_id', $serviceIds)->delete();
            }

            foreach ($payload['service_tag'] ?? [] as $pivot) {
                $pivot = (array) $pivot;
                DB::table('service_tag')->insert([
                    'service_id' => $pivot['service_id'],
                    'tag_id' => $pivot['tag_id'],
                    'created_at' => $pivot['created_at'] ?? now(),
                    'updated_at' => $pivot['updated_at'] ?? now(),
                ]);
                $imported['service_tag']++;
            }

            foreach ($payload['faqs'] ?? [] as $row) {
                $row = (array) $row;
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                unset($row['id']);
                DB::table('faqs')->updateOrInsert(['id' => $id], array_merge($row, ['id' => $id]));
                $imported['faqs']++;
            }

            foreach ($payload['variations'] ?? [] as $row) {
                $row = (array) $row;
                $oldZone = $row['zone_id'] ?? null;
                $row['zone_id'] = $zoneMap[$oldZone] ?? $oldZone;
                if (! DB::table('zones')->where('id', $row['zone_id'])->exists()) {
                    $warnings[] = 'Skipped a variation row (target zone missing).';

                    continue;
                }
                unset($row['id']);
                DB::table('variations')->insert(array_merge($row, [
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ]));
                $imported['variations']++;
            }

            $this->replaceTranslationsForType(Service::class, $serviceIds, $payload['translations'] ?? []);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['imported' => $imported, 'warnings' => $warnings];
    }

    public function assertValidPayload(array $payload): void
    {
        $type = $payload['meta']['payload_type'] ?? null;
        if ($type !== self::PAYLOAD_TYPE) {
            throw new InvalidArgumentException('Invalid service catalog export file (wrong payload_type).');
        }
        if (! isset($payload['categories']) || ! is_array($payload['categories'])) {
            throw new InvalidArgumentException('Invalid service catalog export file (missing categories).');
        }
        if (! isset($payload['services']) || ! is_array($payload['services'])) {
            throw new InvalidArgumentException('Invalid service catalog export file (missing services).');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function sortCategoriesForInsert(array $categories): array
    {
        $byId = collect($categories)->keyBy('id');
        $sorted = [];
        $remaining = $byId->keys()->all();

        while ($remaining !== []) {
            $beforeCount = count($remaining);
            foreach ($remaining as $k => $id) {
                $r = (array) $byId[$id];
                $parentId = $r['parent_id'] ?? null;
                if (empty($parentId) || in_array($parentId, array_column($sorted, 'id'), true)) {
                    $sorted[] = $r;
                    unset($remaining[$k]);
                }
            }
            if (count($remaining) === $beforeCount) {
                foreach ($remaining as $id) {
                    $sorted[] = (array) $byId[$id];
                }
                break;
            }
        }

        return $sorted;
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function exportCategoryServiceTranslations(array $categoryIds, array $serviceIds): array
    {
        if ($categoryIds === [] && $serviceIds === []) {
            return [];
        }

        $q = DB::table('translations')->where(function ($query) use ($categoryIds, $serviceIds) {
            if ($categoryIds !== []) {
                $query->where(function ($q) use ($categoryIds) {
                    $q->where('translationable_type', Category::class)
                        ->whereIn('translationable_id', $categoryIds);
                });
            }
            if ($serviceIds !== []) {
                if ($categoryIds !== []) {
                    $query->orWhere(function ($q) use ($serviceIds) {
                        $q->where('translationable_type', Service::class)
                            ->whereIn('translationable_id', $serviceIds);
                    });
                } else {
                    $query->where(function ($q) use ($serviceIds) {
                        $q->where('translationable_type', Service::class)
                            ->whereIn('translationable_id', $serviceIds);
                    });
                }
            }
        });

        return $q->get()->map(fn ($r) => (array) $r)->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $translations
     * @param  array<int, string>  $entityIds
     * @return array<int, string>
     */
    private function sampleNamesFromTranslations(array $translations, string $type, array $entityIds, string $key, int $limit): array
    {
        $names = [];
        $ids = array_flip($entityIds);
        foreach ($translations as $t) {
            $t = (array) $t;
            if (($t['translationable_type'] ?? '') !== $type) {
                continue;
            }
            if (($t['key'] ?? '') !== $key) {
                continue;
            }
            $tid = $t['translationable_id'] ?? null;
            if ($tid && isset($ids[$tid]) && ($t['locale'] ?? '') === 'en') {
                $names[] = (string) $t['value'];
            }
            if (count($names) >= $limit) {
                break;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  array<int, array<string, mixed>>  $zoneTranslations
     * @param  array<int, string>  $warnings
     * @return array<string, string>
     */
    private function buildZoneIdMap(array $zones, array $zoneTranslations, array &$warnings): array
    {
        $map = [];
        foreach ($zones as $z) {
            $z = (array) $z;
            $oldId = (string) ($z['id'] ?? '');
            if ($oldId === '') {
                continue;
            }
            if (DB::table('zones')->where('id', $oldId)->exists()) {
                $map[$oldId] = $oldId;

                continue;
            }
            $name = $this->zoneDefaultName($oldId, $zoneTranslations);
            $newId = null;
            if ($name !== '') {
                $newId = DB::table('translations')
                    ->where('translationable_type', Zone::class)
                    ->where('key', 'zone_name')
                    ->where('value', $name)
                    ->value('translationable_id');
            }
            if ($newId) {
                $map[$oldId] = (string) $newId;

                continue;
            }
            try {
                unset($z['id'], $z['coordinates']);
                DB::table('zones')->insert(array_merge($z, ['id' => $oldId]));
                foreach ($zoneTranslations as $tr) {
                    $tr = (array) $tr;
                    if (($tr['translationable_id'] ?? '') === $oldId && ($tr['translationable_type'] ?? '') === Zone::class) {
                        unset($tr['id']);
                        DB::table('translations')->insert($tr);
                    }
                }
                $map[$oldId] = $oldId;
            } catch (Throwable $e) {
                $fallback = DB::table('zones')->where('is_active', 1)->value('id')
                    ?? DB::table('zones')->value('id');
                if ($fallback) {
                    $warnings[] = $name !== ''
                        ? 'A zone could not be created ('.$name.'); some prices were mapped to another zone.'
                        : 'A zone could not be created; some prices were mapped to another zone.';
                    $map[$oldId] = (string) $fallback;
                } else {
                    $map[$oldId] = $oldId;
                    $warnings[] = 'No zones are configured; some variation prices may not import.';
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $zoneTranslations
     */
    private function zoneDefaultName(string $zoneId, array $zoneTranslations): string
    {
        foreach ($zoneTranslations as $t) {
            $t = (array) $t;
            if (($t['translationable_type'] ?? '') === Zone::class
                && (string) ($t['translationable_id'] ?? '') === $zoneId
                && ($t['key'] ?? '') === 'zone_name'
                && ($t['locale'] ?? '') === 'en') {
                return (string) ($t['value'] ?? '');
            }
        }
        foreach ($zoneTranslations as $t) {
            $t = (array) $t;
            if (($t['translationable_type'] ?? '') === Zone::class
                && (string) ($t['translationable_id'] ?? '') === $zoneId
                && ($t['key'] ?? '') === 'zone_name') {
                return (string) ($t['value'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param  array<int, string>  $ids
     * @param  array<int, array<string, mixed>>  $allTranslations
     */
    private function replaceTranslationsForType(string $type, array $ids, array $allTranslations): void
    {
        $ids = array_values(array_filter($ids));
        if ($ids === []) {
            return;
        }
        DB::table('translations')->where('translationable_type', $type)->whereIn('translationable_id', $ids)->delete();
        $idSet = array_fill_keys(array_map('strval', $ids), true);
        foreach ($allTranslations as $row) {
            $row = (array) $row;
            if (($row['translationable_type'] ?? '') !== $type) {
                continue;
            }
            $tid = isset($row['translationable_id']) ? (string) $row['translationable_id'] : '';
            if ($tid === '' || ! isset($idSet[$tid])) {
                continue;
            }
            unset($row['id']);
            DB::table('translations')->insert($row);
        }
    }
}
