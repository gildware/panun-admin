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
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneGeometryService;
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
        $search = $request['search'];
        $queryParam = $search ? ['search' => $request['search']] : '';

        $zones = $this->zone
            ->withCount(['providers', 'categories'])
            ->with([
                'parentZone' => fn($q) => $q->withoutGlobalScope('translate'),
                'childZones' => fn($q) => $q->withoutGlobalScope('translate'),
            ])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->withoutGlobalScope('translate')
            ->latest()->paginate(pagination_limit())->appends($queryParam);
        $parentZoneChoices = $this->zone->withoutGlobalScope('translate')->orderBy('name')->get(['id', 'name']);

        return view('zonemanagement::admin.create', compact('zones', 'search', 'parentZoneChoices'));
    }

    public function getTable(Request $request)
    {
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        $queryParam = ['search' => $search];

        $zones = $this->zone
            ->withCount(['providers', 'categories'])
            ->with([
                'parentZone' => fn($q) => $q->withoutGlobalScope('translate'),
                'childZones' => fn($q) => $q->withoutGlobalScope('translate'),
            ])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->withoutGlobalScope('translate')
            ->latest()->paginate(pagination_limit())->appends($queryParam);

        $totalCount = $zones->total();
        $zones->withPath(route('admin.zone.create'));

        // Fallback logic: If current page has no data, go back one page
        if ($zones->isEmpty() && $page > 1) {
            $page = $page - 1;
            $request->merge(['page' => $page]);

            $zones = $this->zone
                ->withCount(['providers', 'categories'])
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->orWhere('name', 'LIKE', '%' . $key . '%');
                    }
                })
                ->withoutGlobalScope('translate')
                ->latest()->paginate(pagination_limit())->appends($queryParam);
        }

        return response()->json([
            'view' =>  view('zonemanagement::admin.partials._table', compact('zones', 'search', 'totalCount'))->render(),
            'totalCount' => $totalCount,
            'offset' => ($zones->currentPage() - 1) * $zones->perPage(),
            'page' => $zones->currentPage(),
        ]);
    }

    /**
     * Fetch an exact boundary polygon for a given place name (e.g. "Srinagar")
     * using Nominatim's polygon_geojson output.
     *
     * Response:
     *  {
     *    paths: [{lat: float, lng: float}, ...] // outer ring only (holes ignored)
     *  }
     */
    public function boundaryFromPlace(Request $request): JsonResponse
    {
        $this->authorize('zone_view');

        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json([
                'message' => 'Missing query',
            ], 422);
        }

        $nominatimUrl = 'https://nominatim.openstreetmap.org/search';

        try {
            $response = Http::timeout(20)
                ->retry(2, 200)
                ->withHeaders([
                    // Nominatim usage policy requires an explicit User-Agent.
                    'User-Agent' => 'pk-admin-local/1.0 (contact: admin@yourdomain.example)',
                    'Accept-Language' => app()->getLocale(),
                ])
                ->get($nominatimUrl, [
                    'q' => $q,
                    'format' => 'json',
                    'polygon_geojson' => 1,
                    'addressdetails' => 0,
                    'limit' => 1,
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Boundary lookup request error: ' . $e->getMessage(),
            ], 500);
        }

        if (!$response->ok()) {
            return response()->json([
                'message' => 'Boundary lookup failed (HTTP ' . $response->status() . ')',
            ], 500);
        }

        $data = $response->json();
        $feature = $data[0]['geojson'] ?? null;
        if (!is_array($feature)) {
            // Some Nominatim responses return geojson as a JSON string.
            if (is_string($feature)) {
                $decoded = json_decode($feature, true);
                $feature = is_array($decoded) ? $decoded : null;
            }
        }

        if (!$feature || !isset($feature['type'], $feature['coordinates'])) {
            return response()->json([
                'message' => 'No boundary polygon found for the provided place',
            ], 404);
        }

        // Extract outer ring only.
        $type = $feature['type'];
        $coordinates = $feature['coordinates'];

        // Ring coordinates are in [lng, lat] per GeoJSON spec.
        $pickOuterRing = function (array $polyCoords) {
            // $polyCoords is either:
            //  - Polygon: [ outerRing, hole1, ...]
            //  - MultiPolygon polygon: [ outerRing, hole1, ...]
            return $polyCoords[0] ?? [];
        };

        $ringArea = function (array $ring) {
            // Shoelace formula in lng/lat space (approx). Works well enough for picking "largest".
            $n = count($ring);
            if ($n < 3) return 0.0;

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

        $outerRing = [];

        if ($type === 'Polygon') {
            $outerRing = $pickOuterRing($coordinates);
        } elseif ($type === 'MultiPolygon') {
            // coordinates: [ polygon1, polygon2, ...]
            // each polygon: [ outerRing, hole1, ...]
            $largest = null;
            $largestArea = -1;
            foreach ($coordinates as $poly) {
                if (!is_array($poly) || empty($poly[0])) continue;
                $ring = $pickOuterRing($poly);
                $area = $ringArea($ring);
                if ($area > $largestArea) {
                    $largestArea = $area;
                    $largest = $ring;
                }
            }
            $outerRing = is_array($largest) ? $largest : [];
        } else {
            return response()->json([
                'message' => 'Unsupported geojson type',
            ], 400);
        }

        if (count($outerRing) < 3) {
            return response()->json([
                'message' => 'Boundary polygon is empty',
            ], 404);
        }

        // Ensure ring is closed (first == last) so google draws properly.
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
            'paths' => $paths,
        ]);
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
            $zone->save();

            $defaultLang = str_replace('_', '-', app()->getLocale());

            foreach ($request->lang as $index => $key) {
                if ($defaultLang == $key && !($request->name[$index])) {
                    if ($key != 'default') {
                        Translation::updateOrInsert(
                            [
                                'translationable_type' => 'Modules\ZoneManagement\Entities\Zone',
                                'translationable_id' => $zone->id,
                                'locale' => $key,
                                'key' => 'zone_name'
                            ],
                            ['value' => $zone->name]
                        );
                    }
                } else {

                    if ($request->name[$index] && $key != 'default') {
                        Translation::updateOrInsert(
                            [
                                'translationable_type' => 'Modules\ZoneManagement\Entities\Zone',
                                'translationable_id' => $zone->id,
                                'locale' => $key,
                                'key' => 'zone_name'
                            ],
                            ['value' => $request->name[$index]]
                        );
                    }
                }
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


    public function edit(string $id)
    {
        $this->authorize('zone_update');
        $zone = Zone::selectRaw("*,ST_AsText(ST_Centroid(`coordinates`)) as center")->withoutGlobalScope('translate')->find($id);

        if (isset($zone)) {
            $defaultLat = session('location.lat', '23.757989');
            $defaultLng = session('location.lng', '90.360587');

            $area = ['coordinates' => []];
            $currentZone = [];

            if ($zone->coordinates !== null) {
                $firstRing = $zone->coordinates[0] ?? null;
                if ($firstRing !== null) {
                    $decoded = json_decode($firstRing->toJson(), true);
                    if (is_array($decoded) && ! empty($decoded['coordinates'])) {
                        $area = $decoded;
                        $currentZone = format_coordinates($decoded['coordinates']);
                    }
                }
            }

            $centerLat = $defaultLat;
            $centerLng = $defaultLng;
            if (! empty($zone->center) && is_string($zone->center)
                && preg_match('/POINT\s*\(\s*([^\s]+)\s+([^\s]+)\s*\)/i', $zone->center, $centerMatch)) {
                $centerLng = trim($centerMatch[1], " \t\n\r\0\x0B'\"");
                $centerLat = trim($centerMatch[2], " \t\n\r\0\x0B'\"");
            }

            $parentZoneChoices = $this->zone->withoutGlobalScope('translate')->where('id', '<>', $id)->orderBy('name')->get(['id', 'name']);

            return view('zonemanagement::admin.edit', compact('zone', 'currentZone', 'centerLat', 'centerLng', 'area', 'parentZoneChoices'));
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
     * GeoJSON-like path for the parent zone boundary (lat/lng pairs for Google Maps).
     *
     * @throws AuthorizationException
     */
    public function parentGeometry(string $id): JsonResponse
    {
        abort_unless(Gate::any(['zone_view', 'zone_add', 'zone_update']), 403);
        $zone = Zone::withoutGlobalScope('translate')->find($id);
        // This endpoint is used by the "select parent zone" dropdown in create/edit UI.
        // The UX should not treat "missing coordinates" as a hard error (404),
        // otherwise jQuery triggers the AJAX fail() branch and shows an error toast every time.
        if (! $zone || $zone->coordinates === null) {
            return response()->json(['paths' => []], 200);
        }

        $firstRing = $zone->coordinates[0] ?? null;
        if ($firstRing === null) {
            return response()->json(['paths' => []], 200);
        }

        $paths = [];
        $coords = $firstRing->toArray()['coordinates'] ?? [];
        foreach ($coords as $pair) {
            if (! is_array($pair) || count($pair) < 2) {
                continue;
            }
            $paths[] = ['lat' => (float) $pair[1], 'lng' => (float) $pair[0]];
        }

        return response()->json(['paths' => $paths]);
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
            'coordinates' => 'required',
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:zones,id',
                Rule::notIn([$id]),
            ],
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

        $zone = $this->zone->where('id', $id)->withoutGlobalScope('translate')->first();

        if (!isset($zone)) {
            Toastr::success(translate(ZONE_404['message']));
            return back();
        }

        $zone->name = $request->name[array_search('default', $request->lang)];
        $zone->coordinates = new Polygon([new LineString($polygon)]);
        $zone->parent_id = $request->filled('parent_id') ? $request->parent_id : null;
        $zone->save();

        $defaultLang = str_replace('_', '-', app()->getLocale());

        foreach ($request->lang as $index => $key) {
            if ($defaultLang == $key && !($request->name[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\ZoneManagement\Entities\Zone',
                            'translationable_id' => $zone->id,
                            'locale' => $key,
                            'key' => 'zone_name'
                        ],
                        ['value' => $zone->name]
                    );
                }
            } else {

                if ($request->name[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\ZoneManagement\Entities\Zone',
                            'translationable_id' => $zone->id,
                            'locale' => $key,
                            'key' => 'zone_name'
                        ],
                        ['value' => $request->name[$index]]
                    );
                }
            }
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
        $items = $this->zone->withoutGlobalScope('translate')->withCount(['providers', 'categories'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->latest()->get();
        return (new FastExcel($items))->download(time() . '-file.xlsx');
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
