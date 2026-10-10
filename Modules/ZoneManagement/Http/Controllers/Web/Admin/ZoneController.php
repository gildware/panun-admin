<?php

namespace Modules\ZoneManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneCategoryInheritanceService;
use Modules\ZoneManagement\Services\ZoneGeometryService;
use Modules\ZoneManagement\Services\ZoneMapPathSimplifier;
use Rap2hpoutre\FastExcel\FastExcel;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ZoneController extends Controller
{
    private Zone $zone;

    use AuthorizesRequests;

    public function __construct(Zone $zone)
    {
        $this->zone = $zone;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function create(Request $request): View|Factory|Application
    {
        $this->authorize('zone_view');
        if (!session()->has('location')) {
            $data = Location::get($request->ip());
            $location = [
                'lat' => $data ? $data->latitude : '23.757989',
                'lng' => $data ? $data->longitude : '90.360587'
            ];
            session()->put('location', $location);
        }
        $search = $request->input('search', '');
        $queryParam = $search !== '' && $search !== null ? ['search' => $search] : [];

        $zones = $this->buildZoneListingQuery($request)
            ->paginate(pagination_limit())
            ->appends($queryParam);
        $parentZoneTreeOptions = $this->parentZoneTreeOptionsForCreate();
        $addParentId = trim((string) $request->query('add_parent', ''));
        if ($addParentId !== '' && ! $this->zone->withoutGlobalScope('translate')->whereKey($addParentId)->exists()) {
            $addParentId = '';
        }

        return view('zonemanagement::admin.create', compact('zones', 'search', 'parentZoneTreeOptions', 'addParentId'));
    }

    /**
     * @return array<int, string>
     */
    protected function zoneDescendantIds(string $rootId): array
    {
        $ids = [];
        $queue = [$rootId];

        while ($queue !== []) {
            $current = array_shift($queue);
            $children = Zone::withoutGlobalScope('translate')
                ->where('parent_id', $current)
                ->pluck('id');

            foreach ($children as $cid) {
                $cid = (string) $cid;
                $ids[] = $cid;
                $queue[] = $cid;
            }
        }

        return $ids;
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    protected function parentZoneTreeOptionsForCreate(): array
    {
        $parentZones = $this->zone->withoutGlobalScope('translate')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'description']);

        return Zone::flatTreeOptionsForSelect($parentZones);
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    protected function parentZoneTreeOptionsForEdit(string $excludeZoneId): array
    {
        $excludeIds = array_merge(
            [$excludeZoneId],
            $this->zoneDescendantIds($excludeZoneId)
        );

        $parentZones = $this->zone->withoutGlobalScope('translate')
            ->whereNotIn('id', $excludeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'description']);

        return Zone::flatTreeOptionsForSelect($parentZones);
    }

    /**
     * Zone list. Root zones when parent_id is empty and there is no search.
     * A parent_id lists only that zone's direct children.
     */
    protected function buildZoneListingQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $parentId = trim((string) $request->input('parent_id', ''));
        $scopedToParent = $parentId !== '';

        $query = $this->zone
            ->forAdminList()
            ->with([
                'areas' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'parentZone' => fn ($q) => $q->withoutGlobalScope('translate')->forAdminList(),
            ])
            ->withCount(['providers', 'categories', 'childZones'])
            ->when($scopedToParent, fn ($q) => $q->where('parent_id', $parentId))
            ->when(! $scopedToParent && $search === '', fn ($q) => $q->whereNull('parent_id'))
            ->when($search !== '', function ($query) use ($search) {
                $keys = array_filter(explode(' ', $search));
                if ($keys !== []) {
                    $query->where(function ($q) use ($keys) {
                        foreach ($keys as $key) {
                            $q->orWhere('name', 'LIKE', '%'.$key.'%')
                                ->orWhere('description', 'LIKE', '%'.$key.'%')
                                ->orWhere('ward_number', 'LIKE', '%'.$key.'%')
                                ->orWhere('areas_encompassed', 'LIKE', '%'.$key.'%')
                                ->orWhere('boundary_demarcation', 'LIKE', '%'.$key.'%')
                                ->orWhereHas('areas', fn ($area) => $area->where('name', 'LIKE', '%'.$key.'%'));
                        }
                    });
                }
            })
            ->withoutGlobalScope('translate');

        if ($scopedToParent) {
            return $query->orderByRaw('ward_number IS NULL')->orderBy('ward_number')->orderBy('name');
        }

        return $query->latest();
    }

    public function children(Request $request, string $id): View|Factory|Application
    {
        $this->authorize('zone_view');
        $parentZone = $this->zone->withoutGlobalScope('translate')->forAdminList()->findOrFail($id);
        $request->merge(['parent_id' => $parentZone->id]);
        $search = $request->input('search', '');
        $queryParam = $search !== '' && $search !== null ? ['search' => $search] : [];

        $zones = $this->buildZoneListingQuery($request)
            ->paginate(pagination_limit())
            ->appends($queryParam);
        $zones->withPath(route('admin.zone.children', $parentZone->id));

        return view('zonemanagement::admin.children', compact('zones', 'search', 'parentZone'));
    }

    public function getTable(Request $request)
    {
        $search = $request->input('search', '');
        $page = (int) $request->input('page', 1);
        $parentId = trim((string) $request->input('parent_id', ''));
        $queryParam = $search !== '' && $search !== null ? ['search' => $search] : [];
        $listPath = $parentId !== ''
            ? route('admin.zone.children', $parentId)
            : route('admin.zone.create');

        $zones = $this->buildZoneListingQuery($request)
            ->paginate(pagination_limit())
            ->appends($queryParam);

        $totalCount = $zones->total();
        $zones->withPath($listPath);

        // Fallback logic: If current page has no data, go back one page
        if ($zones->isEmpty() && $page > 1) {
            $page = $page - 1;
            $request->merge(['page' => $page]);

            $zones = $this->buildZoneListingQuery($request)
                ->paginate(pagination_limit())
                ->appends($queryParam);
            $totalCount = $zones->total();
            $zones->withPath($listPath);
        }

        return response()->json([
            'view' => view('zonemanagement::admin.partials._table', compact('zones', 'search', 'totalCount'))->render(),
            'totalCount' => $totalCount,
            'offset' => ($zones->currentPage() - 1) * $zones->perPage(),
            'page' => $zones->currentPage(),
        ]);
    }

    /**
     * Read-only map of one parent and its direct children, so gaps inside that parent stay visible.
     */
    public function coverageMap(Request $request): JsonResponse
    {
        $this->authorize('zone_view');

        $requestedParentId = trim((string) $request->input('parent_id', ''));
        $focusId = $this->coverageFocusParentId($requestedParentId);
        $simplifier = app(ZoneMapPathSimplifier::class);

        $parentChoices = Zone::query()
            ->select(['id', 'name'])
            ->with([
                'translations' => fn ($query) => $query->where('key', 'zone_name')->where('locale', app()->getLocale()),
            ])
            ->withCount('childZones')
            ->has('childZones')
            ->withoutGlobalScope('translate')
            ->orderBy('name')
            ->get();

        if ($focusId !== '' && ! $parentChoices->contains('id', $focusId)) {
            $focusZone = Zone::query()
                ->select(['id', 'name'])
                ->with([
                    'translations' => fn ($query) => $query->where('key', 'zone_name')->where('locale', app()->getLocale()),
                ])
                ->withCount('childZones')
                ->withoutGlobalScope('translate')
                ->find($focusId);
            if ($focusZone) {
                $parentChoices->push($focusZone);
            }
        }

        try {
            $zones = Zone::query()
                ->select(['id', 'name', 'parent_id', 'is_active'])
                ->selectRaw($simplifier->overviewSqlExpression().' as map_ring')
                ->with([
                    'translations' => fn ($query) => $query->where('key', 'zone_name')->where('locale', app()->getLocale()),
                ])
                ->withCount('childZones')
                ->withoutGlobalScope('translate')
                ->where(function ($query) use ($focusId) {
                    $query->where('id', $focusId)->orWhere('parent_id', $focusId);
                })
                ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$focusId])
                ->orderBy('name')
                ->get();
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => translate('Zone_coverage_failed')], 500);
        }

        $payload = [];
        foreach ($zones as $zone) {
            $paths = $this->roundCoveragePaths($simplifier->fromGeoJson($zone->map_ring ?? null));
            $payload[] = [
                'id' => (string) $zone->id,
                'name' => (string) ($zone->name ?? ''),
                'parent_id' => $zone->parent_id ? (string) $zone->parent_id : null,
                'is_active' => (int) $zone->is_active === 1,
                'is_focus' => (string) $zone->id === $focusId,
                'child_count' => (int) $zone->child_zones_count,
                'view_url' => route('admin.zone.view', $zone->id),
                'paths' => count($paths) >= 3 ? $paths : [],
            ];
        }

        $parents = $parentChoices
            ->sortBy(fn ($zone) => mb_strtolower((string) ($zone->name ?? '')))
            ->values()
            ->map(fn ($zone) => [
                'id' => (string) $zone->id,
                'name' => (string) ($zone->name ?? ''),
                'child_count' => (int) $zone->child_zones_count,
            ])
            ->all();

        $gaps = ['whole' => false, 'pieces' => []];
        if ($focusId !== '') {
            try {
                $gaps = app(ZoneGeometryService::class)->uncoveredInsideParent($focusId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'focus_id' => $focusId,
            'parents' => $parents,
            'zones' => $payload,
            'gaps' => $gaps,
        ]);
    }

    /**
     * Parent whose outline and direct children are drawn. Falls back to the first root zone.
     */
    private function coverageFocusParentId(string $requestedParentId): string
    {
        if ($requestedParentId !== '' && Zone::query()->withoutGlobalScope('translate')->whereKey($requestedParentId)->exists()) {
            return $requestedParentId;
        }

        $rootId = Zone::query()
            ->withoutGlobalScope('translate')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->value('id');

        return $rootId ? (string) $rootId : '';
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $paths
     * @return array<int, array{lat: float, lng: float}>
     */
    private function roundCoveragePaths(array $paths): array
    {
        $rounded = [];
        foreach ($paths as $point) {
            $rounded[] = [
                'lat' => round((float) $point['lat'], 5),
                'lng' => round((float) $point['lng'], 5),
            ];
        }

        return $rounded;
    }

    /**
     * Areas stored under one or more zones, for booking and lead selects.
     */
    public function areas(Request $request): JsonResponse
    {
        $zoneIds = array_values(array_filter(array_map(
            'strval',
            (array) $request->input('zone_ids', [])
        )));
        $single = trim((string) $request->input('zone_id', ''));
        if ($single !== '') {
            $zoneIds[] = $single;
        }
        $zoneIds = array_values(array_unique($zoneIds));

        $areas = CustomerLeadArea::query()
            ->where('is_active', true)
            ->when(
                $zoneIds !== [],
                fn ($query) => $query->whereIn('zone_id', $zoneIds),
                fn ($query) => $query->whereRaw('0 = 1')
            )
            ->orderBy('name')
            ->get(['id', 'name', 'zone_id']);

        return response()->json(['areas' => $areas]);
    }

    /**
     * Fetch an administrative boundary polygon (OpenStreetMap via Nominatim).
     * Tries several strategies because a single search often misses polygons (viewbox bias,
     * Point-only first hits, or wording mismatches).
     *
     * @see https://nominatim.org/release-docs/develop/api/Overview/
     */
    public function boundaryFromPlace(Request $request): JsonResponse
    {
        abort_unless(Gate::any(['zone_view', 'zone_add', 'zone_update']), 403);

        $q = trim((string) $request->input('q', ''));
        $name = trim((string) $request->input('name', ''));
        if ($q === '' && $name === '') {
            return response()->json([
                'message' => 'Missing query',
            ], 422);
        }

        $latIn = $request->input('lat');
        $lngIn = $request->input('lng');
        $hasLatLng = is_numeric($latIn) && is_numeric($lngIn);
        $latF = $hasLatLng ? (float) $latIn : null;
        $lngF = $hasLatLng ? (float) $lngIn : null;

        $viewbox = null;
        if ($hasLatLng) {
            $d = 0.55;
            $viewbox = sprintf(
                '%f,%f,%f,%f',
                $lngF - $d,
                $latF + $d,
                $lngF + $d,
                $latF - $d
            );
        }

        $countryCodes = trim((string) $request->input('countrycodes', ''));
        $countryCodes = preg_match('/^[a-z]{2}$/i', $countryCodes) ? strtolower($countryCodes) : '';

        $searchBase = [
            'format' => 'json',
            'polygon_geojson' => 1,
            'addressdetails' => 0,
            'limit' => 15,
        ];
        if ($countryCodes !== '') {
            $searchBase['countrycodes'] = $countryCodes;
        }

        $queryStrings = array_values(array_unique(array_filter([
            $q !== '' ? $q : null,
            $name !== '' && strcasecmp($name, $q) !== 0 ? $name : null,
        ])));

        if ($queryStrings === []) {
            $queryStrings = [$name !== '' ? $name : $q];
        }

        $outerRing = null;

        foreach ($queryStrings as $queryString) {
            $params = array_merge($searchBase, ['q' => $queryString]);
            if ($viewbox !== null) {
                $paramsWithBox = array_merge($params, ['viewbox' => $viewbox]);
                $data = $this->nominatimRequest('search', $paramsWithBox);
                $outerRing = $this->polygonRingFromNominatimSearchResults($data);
                if ($outerRing !== null) {
                    break;
                }
                usleep(200_000);
            }

            $data = $this->nominatimRequest('search', $params);
            $outerRing = $this->polygonRingFromNominatimSearchResults($data);
            if ($outerRing !== null) {
                break;
            }
            usleep(200_000);
        }

        if ($outerRing === null && $hasLatLng) {
            foreach ([14, 12, 10, 8, 6] as $zoom) {
                $rev = $this->nominatimRequest('reverse', [
                    'lat' => $latF,
                    'lon' => $lngF,
                    'zoom' => $zoom,
                    'polygon_geojson' => 1,
                    'format' => 'json',
                    'addressdetails' => 0,
                ]);
                $outerRing = $this->polygonRingFromNominatimSingleResult($rev);
                if ($outerRing !== null) {
                    break;
                }
                usleep(200_000);
            }
        }

        if ($outerRing === null) {
            return response()->json([
                'message' => 'No boundary polygon found for the provided place',
            ], 404);
        }

        $first = $outerRing[0];
        $last = $outerRing[count($outerRing) - 1] ?? null;
        if (is_array($first) && is_array($last) && ($first[0] != $last[0] || $first[1] != $last[1])) {
            $outerRing[] = $first;
        }

        $paths = array_map(function ($point) {
            return [
                'lat' => (float) $point[1],
                'lng' => (float) $point[0],
            ];
        }, $outerRing);

        return response()->json([
            'paths' => app(ZoneMapPathSimplifier::class)->simplify($paths),
        ]);
    }

    /**
     * @return array<mixed>|null Decoded JSON (search: list, reverse: associative row)
     */
    private function nominatimRequest(string $endpoint, array $queryParams): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/'.$endpoint;

        try {
            $response = Http::timeout(22)
                ->retry(2, 300)
                ->withHeaders([
                    'User-Agent' => 'pk-admin-local/1.0 (contact: admin@yourdomain.example)',
                    'Accept-Language' => app()->getLocale(),
                ])
                ->get($url, $queryParams);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array<mixed>|null  $data
     */
    private function polygonRingFromNominatimSearchResults(?array $data): ?array
    {
        if (! is_array($data) || $data === []) {
            return null;
        }

        $pickFromRows = function (bool $adminOnly) use ($data) {
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ($adminOnly) {
                    if (($row['class'] ?? '') !== 'boundary' || ($row['type'] ?? '') !== 'administrative') {
                        continue;
                    }
                }
                $ring = $this->polygonRingFromNominatimGeojsonField($row['geojson'] ?? null);
                if ($ring !== null) {
                    return $ring;
                }
            }

            return null;
        };

        return $pickFromRows(true) ?? $pickFromRows(false);
    }

    /**
     * @param  array<mixed>|null  $row
     */
    private function polygonRingFromNominatimSingleResult(?array $row): ?array
    {
        if (! is_array($row) || $row === []) {
            return null;
        }

        return $this->polygonRingFromNominatimGeojsonField($row['geojson'] ?? null);
    }

    /**
     * @param  mixed  $geojson
     * @return array<int, array{0: float|int, 1: float|int}>|null
     */
    private function polygonRingFromNominatimGeojsonField(mixed $geojson): ?array
    {
        $feature = $geojson;
        if (is_string($feature)) {
            $decoded = json_decode($feature, true);
            $feature = is_array($decoded) ? $decoded : null;
        }
        if (! is_array($feature) || ! isset($feature['type'], $feature['coordinates'])) {
            return null;
        }
        if (! in_array($feature['type'], ['Polygon', 'MultiPolygon'], true)) {
            return null;
        }

        $outerRing = $this->nominatimPolygonOuterRing($feature);

        return ($outerRing !== null && count($outerRing) >= 3) ? $outerRing : null;
    }

    /**
     * @param  array{type: string, coordinates: mixed}  $feature
     * @return array<int, array{0: float|int, 1: float|int}>|null
     */
    private function nominatimPolygonOuterRing(array $feature): ?array
    {
        $type = $feature['type'];
        $coordinates = $feature['coordinates'];
        $pickOuterRing = function (array $polyCoords) {
            return $polyCoords[0] ?? [];
        };
        $ringArea = function (array $ring) {
            $n = count($ring);
            if ($n < 3) {
                return 0.0;
            }
            $area = 0.0;
            for ($i = 0; $i < $n; $i++) {
                $j = ($i + 1) % $n;
                $x1 = (float) $ring[$i][0];
                $y1 = (float) $ring[$i][1];
                $x2 = (float) $ring[$j][0];
                $y2 = (float) $ring[$j][1];
                $area += ($x1 * $y2) - ($x2 * $y1);
            }

            return abs($area) / 2.0;
        };

        if ($type === 'Polygon') {
            $ring = $pickOuterRing($coordinates);

            return count($ring) >= 3 ? $ring : null;
        }

        if ($type === 'MultiPolygon') {
            $largest = null;
            $largestArea = -1.0;
            foreach ($coordinates as $poly) {
                if (! is_array($poly) || empty($poly[0])) {
                    continue;
                }
                $ring = $pickOuterRing($poly);
                $area = $ringArea($ring);
                if ($area > $largestArea) {
                    $largestArea = $area;
                    $largest = $ring;
                }
            }

            return (is_array($largest) && count($largest) >= 3) ? $largest : null;
        }

        return null;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('zone_add');
        $request->validate([
            'name' => 'required|unique:zones|max:191',
            'name.0' => 'required',
            'coordinates' => 'required',
            'parent_id' => 'nullable|uuid|exists:zones,id',
            'description' => 'nullable|string|max:65535',
            'ward_number' => 'nullable|integer|min:1|max:9999',
            'area_names' => 'nullable|array|max:500',
            'area_names.*' => 'nullable|string|max:255',
        ],
        [
            'name.0.required' => translate('default_name_is_required'),
        ]);

        $value = $request->coordinates;
        foreach (explode('),(', trim($value, '()')) as $index => $singleArray) {
            if ($index == 0) {
                $lastcord = explode(',', $singleArray);
            }
            $coords = explode(',', $singleArray);
            $polygon[] = new Point($coords[0], $coords[1]);
        }
        $polygon[] = new Point($lastcord[0], $lastcord[1]);

        $childPolygon = new Polygon([new LineString($polygon)]);
        $this->assertChildZoneWithinParent($request->input('parent_id'), $childPolygon);

        DB::transaction(function () use ($polygon, $request) {
            $zone = $this->zone;
            $zone->name = $request->name[array_search('default', $request->lang)];
            $zone->coordinates = new Polygon([new LineString($polygon)]);
            $zone->parent_id = $request->filled('parent_id') ? $request->parent_id : null;
            $zone->description = Zone::descriptionIncludingBoundary(
                is_string($request->input('description')) ? $request->input('description') : null,
                $this->nullableZoneText($request->input('boundary_demarcation'))
            );
            if ($request->exists('ward_number')) {
                $zone->ward_number = $request->filled('ward_number') ? (int) $request->input('ward_number') : null;
            }
            $zone->boundary_demarcation = null;
            $zone->save();
            $this->syncZoneAreas($zone, $request->input('area_names', []));

            if ($zone->parent_id) {
                app(ZoneCategoryInheritanceService::class)->inheritParentCategorySelection(
                    (string) $zone->id,
                    (string) $zone->parent_id
                );
            }

            $defaultLang = str_replace('_', '-', app()->getLocale());

            $canonicalName = $request->name[array_search('default', $request->lang)];

            foreach ($request->lang as $index => $key) {
                if ($key === 'default') {
                    continue;
                }
                $valueForLocale = ($key === $defaultLang)
                    ? $canonicalName
                    : trim((string) ($request->name[$index] ?? ''));
                if ($valueForLocale === '') {
                    continue;
                }
                Translation::updateOrInsert(
                    [
                        'translationable_type' => 'Modules\ZoneManagement\Entities\Zone',
                        'translationable_id' => $zone->id,
                        'locale' => $key,
                        'key' => 'zone_name',
                    ],
                    ['value' => $valueForLocale]
                );
            }

        });

        Toastr::success(translate(ZONE_STORE_200['message']));

        return back();
    }

    /**
     * Show the specified resource.
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $zone = $this->zone->withoutGlobalScope('translate')->where('id', $id)->first();
        if (isset($zone)) {
            return response()->json(response_formatter(DEFAULT_200, $zone), 200);
        }
        return response()->json(response_formatter(DEFAULT_204, $zone), 204);
    }


    public function view(string $id)
    {
        abort_unless(Gate::any(['zone_view', 'zone_update']), 403);

        return $this->renderZoneForm($id, true);
    }

    public function edit(string $id)
    {
        $this->authorize('zone_update');

        return $this->renderZoneForm($id, false);
    }

    private function renderZoneForm(string $id, bool $viewOnly)
    {
        $simplifier = app(ZoneMapPathSimplifier::class);
        $zone = Zone::query()
            ->select(Zone::adminListColumns())
            ->selectRaw('ST_AsText(ST_Centroid(`coordinates`)) as center')
            ->selectRaw($simplifier->sqlExpression().' as map_ring')
            ->withoutGlobalScope('translate')
            ->find($id);

        if (isset($zone)) {
            $zone->load([
                'areas' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'parentZone' => fn ($q) => $q->withoutGlobalScope('translate')->select('id', 'name'),
            ]);
            $zone->loadCount(['childZones', 'providers', 'categories']);
            $defaultLat = session('location.lat', '23.757989');
            $defaultLng = session('location.lng', '90.360587');

            $postedCoordinates = old('coordinates');
            $coordinatesUnchanged = (string) old('coordinates_unchanged', '1');
            if (is_string($postedCoordinates) && trim($postedCoordinates) !== '' && $coordinatesUnchanged === '0') {
                $mapPaths = $simplifier->fromCoordinateText($postedCoordinates);
            } else {
                $mapPaths = $simplifier->fromGeoJson($zone->map_ring ?? null);
            }

            $centerLat = $defaultLat;
            $centerLng = $defaultLng;
            if (! empty($zone->center) && is_string($zone->center)
                && preg_match('/POINT\s*\(\s*([^\s]+)\s+([^\s]+)\s*\)/i', $zone->center, $centerMatch)) {
                $centerLng = trim($centerMatch[1], " \t\n\r\0\x0B'\"");
                $centerLat = trim($centerMatch[2], " \t\n\r\0\x0B'\"");
            }

            $parentZoneTreeOptions = $this->parentZoneTreeOptionsForEdit($id);

            return view('zonemanagement::admin.edit', compact('zone', 'centerLat', 'centerLng', 'mapPaths', 'parentZoneTreeOptions', 'viewOnly'));
        }

        Toastr::error(translate(DEFAULT_204['message']));
        return back();
    }

    public function getActiveZones($id): JsonResponse
    {
        $allZones = Zone::where('id', '<>', $id)->where('is_active', 1)->withoutGlobalScope('translate')->get();
        $allZoneData = [];

        foreach ($allZones as $item) {
            $data = [];
            foreach ($item->coordinates as $coordinate) {
                $data[] = (object)['lat' => $coordinate->lat, 'lng' => $coordinate->lng];
            }
            $allZoneData[] = $data;
        }
        return response()->json($allZoneData, 200);
    }

    /**
     * GeoJSON-like path for the parent zone boundary (lat/lng pairs for Google Maps),
     * plus sibling child-zone boundaries under the same parent (optional exclude for edit form).
     *
     * @throws AuthorizationException
     */
    public function parentGeometry(Request $request, string $id): JsonResponse
    {
        abort_unless(Gate::any(['zone_view', 'zone_add', 'zone_update']), 403);
        $simplifier = app(ZoneMapPathSimplifier::class);
        $parentRing = Zone::query()
            ->select('id')
            ->selectRaw($simplifier->sqlExpression().' as map_ring')
            ->withoutGlobalScope('translate')
            ->find($id);
        // Missing coordinates is not a hard error: the parent dropdown would otherwise
        // toast on every change when a zone has no drawn boundary yet.
        if (! $parentRing) {
            return response()->json(['paths' => [], 'siblings' => []], 200);
        }

        $parentPaths = $simplifier->fromGeoJson($parentRing->map_ring ?? null);

        $excludeId = $request->query('exclude_zone');
        $excludeId = is_string($excludeId) ? trim($excludeId) : '';

        $siblingsQuery = Zone::query()
            ->selectRaw($simplifier->sqlExpression().' as map_ring')
            ->withoutGlobalScope('translate')
            ->where('parent_id', $id)
            ->whereNotNull('coordinates');

        if ($excludeId !== '') {
            $siblingsQuery->where('id', '<>', $excludeId);
        }

        $siblings = [];
        foreach ($siblingsQuery->get() as $child) {
            $childPaths = $simplifier->fromGeoJson($child->map_ring ?? null);
            if ($childPaths !== []) {
                $siblings[] = ['paths' => $childPaths];
            }
        }

        return response()->json([
            'paths' => $parentPaths,
            'siblings' => $siblings,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function statusUpdate(Request $request, $id): JsonResponse
    {
        $this->authorize('zone_manage_status');
        $zone = $this->zone->where('id', $id)->withoutGlobalScope('translate')->first();
        $this->zone->where('id', $id)->withoutGlobalScope('translate')->update(['is_active' => !$zone->is_active]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('zone_update');
        $request->validate([
            'name' => 'required',
            'name.0' => 'required',
            'coordinates' => 'required_unless:coordinates_unchanged,1',
            'coordinates_unchanged' => 'nullable|in:0,1',
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:zones,id',
                Rule::notIn([$id]),
            ],
            'description' => 'nullable|string|max:65535',
            'ward_number' => 'nullable|integer|min:1|max:9999',
            'area_names' => 'nullable|array|max:500',
            'area_names.*' => 'nullable|string|max:255',
        ],
        [
            'name.0.required' => translate('default_name_is_required'),
        ]);

        $zone = $this->zone->where('id', $id)->withoutGlobalScope('translate')->first();

        if (!isset($zone)) {
            Toastr::success(translate(ZONE_404['message']));
            return back();
        }

        $keepExistingGeometry = $request->input('coordinates_unchanged') === '1' && $zone->coordinates instanceof Polygon;
        if ($keepExistingGeometry) {
            $childPolygon = $zone->coordinates;
        } else {
            $value = (string) $request->coordinates;
            $polygon = [];
            $lastcord = null;
            foreach (explode('),(', trim($value, '()')) as $index => $singleArray) {
                if ($index == 0) {
                    $lastcord = explode(',', $singleArray);
                }
                $coords = explode(',', $singleArray);
                $polygon[] = new Point($coords[0], $coords[1]);
            }
            if ($lastcord === null || count($polygon) < 3) {
                throw ValidationException::withMessages([
                    'coordinates' => translate('draw_your_zone_on_the_map'),
                ]);
            }
            $polygon[] = new Point($lastcord[0], $lastcord[1]);
            $childPolygon = new Polygon([new LineString($polygon)]);
        }

        $this->assertChildZoneWithinParent($request->input('parent_id'), $childPolygon);

        $previousParentId = (string) ($zone->parent_id ?? '');

        $zone->name = $request->name[array_search('default', $request->lang)];
        if (! $keepExistingGeometry) {
            $zone->coordinates = $childPolygon;
        }
        $zone->parent_id = $request->filled('parent_id') ? $request->parent_id : null;
        $zone->description = Zone::descriptionIncludingBoundary(
            is_string($request->input('description')) ? $request->input('description') : null,
            $this->nullableZoneText($request->input('boundary_demarcation'))
        );
        if ($request->exists('ward_number')) {
            $zone->ward_number = $request->filled('ward_number') ? (int) $request->input('ward_number') : null;
        }
        $zone->boundary_demarcation = null;
        $zone->save();
        $this->syncZoneAreas($zone, $request->input('area_names', []));

        $newParentId = (string) ($zone->parent_id ?? '');
        if ($newParentId !== '' && $newParentId !== $previousParentId) {
            app(ZoneCategoryInheritanceService::class)->inheritParentCategorySelection(
                (string) $zone->id,
                $newParentId
            );
        }

        $defaultLang = str_replace('_', '-', app()->getLocale());
        $canonicalName = $request->name[array_search('default', $request->lang)];

        foreach ($request->lang as $index => $key) {
            if ($key === 'default') {
                continue;
            }
            $valueForLocale = ($key === $defaultLang)
                ? $canonicalName
                : trim((string) ($request->name[$index] ?? ''));
            if ($valueForLocale === '') {
                continue;
            }
            Translation::updateOrInsert(
                [
                    'translationable_type' => 'Modules\ZoneManagement\Entities\Zone',
                    'translationable_id' => $zone->id,
                    'locale' => $key,
                    'key' => 'zone_name',
                ],
                ['value' => $valueForLocale]
            );
        }

        Toastr::success(translate(ZONE_UPDATE_200['message']));
        return redirect()->route('admin.zone.create');
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $this->authorize('zone_delete');
        $zone = $this->zone->where('id', $id)->withoutGlobalScope('translate')->first();
        $zone->translations()->delete();
        $zone->delete();
        Toastr::success(translate(ZONE_DESTROY_200['message']));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return string|StreamedResponse
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('zone_export');
        $items = $this->buildZoneListingQuery($request)->get();
        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    protected function syncZoneAreas(Zone $zone, mixed $raw): void
    {
        $lines = is_array($raw)
            ? $raw
            : (preg_split('/\r\n|\r|\n/', is_string($raw) ? $raw : '') ?: []);
        $names = [];
        $seen = [];
        foreach ($lines as $line) {
            $name = trim($line);
            if ($name === '') {
                continue;
            }
            $name = mb_substr($name, 0, 255);
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $names[] = $name;
        }

        $existing = CustomerLeadArea::query()->where('zone_id', $zone->id)->get();
        $keepIds = [];
        foreach ($names as $name) {
            $row = $existing->first(fn (CustomerLeadArea $area) => mb_strtolower($area->name) === mb_strtolower($name));
            if ($row) {
                $row->name = $name;
                $row->is_active = true;
                $row->save();
            } else {
                $row = CustomerLeadArea::query()->create([
                    'name' => $name,
                    'zone_id' => $zone->id,
                    'is_active' => true,
                ]);
            }
            $keepIds[] = $row->id;
        }

        CustomerLeadArea::query()
            ->where('zone_id', $zone->id)
            ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
            ->update(['is_active' => false]);

        $zone->areas_encompassed = $names === [] ? null : implode("\n", $names);
        $zone->save();
    }

    protected function nullableZoneText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function assertChildZoneWithinParent(?string $parentId, Polygon $childPolygon): void
    {
        if (! filled($parentId)) {
            return;
        }

        if (! app(ZoneGeometryService::class)->childPolygonContainedInParentZone($childPolygon, $parentId)) {
            throw ValidationException::withMessages([
                'coordinates' => translate('Child_zone_must_be_inside_parent_boundary'),
            ]);
        }
    }

}
