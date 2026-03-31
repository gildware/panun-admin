<?php

namespace Modules\CategoryManagement\Http\Controllers\Web\Admin;

use App\Lib\CommissionEntitySetup;
use App\Traits\UploadSizeHelperTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class CategoryController extends Controller
{

    private Variation $variation;
    private Zone $zone;
    private Category $category;

    use AuthorizesRequests;
    use UploadSizeHelperTrait;

    public function __construct(Category $category, Zone $zone, Variation $variation)
    {
        $this->category = $category;
        $this->zone = $zone;
        $this->variation = $variation;
    }

    /**
     * Active zones nested by parent_id for admin category create/edit forms.
     *
     * @return list<array{id: string, name: string, children: list<array{id: string, name: string, children: list}>}>>
     */
    private function zoneTreeForCategoryForm(): array
    {
        $zones = $this->zone->ofStatus(1)->orderBy('id')->get();
        $byParent = $zones->groupBy(fn (Zone $z) => $z->parent_id ?? '');

        $build = function (string $parentKey) use (&$build, $byParent): array {
            /** @var \Illuminate\Support\Collection<int, Zone> $rows */
            $rows = $byParent->get($parentKey, collect());

            return $rows->map(function (Zone $z) use ($build): array {
                return [
                    'id' => (string) $z->id,
                    'name' => (string) $z->name,
                    'children' => $build((string) $z->id),
                ];
            })->values()->all();
        };

        return $build('');
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function create(Request $request): View|Factory|Application
    {
        $this->authorize('category_view');
        $search = $request->has('search') ? $request['search'] : '';
        $status = $request->has('status') ? $request['status'] : 'all';
        $queryParams = ['search' => $search, 'status' => $status];

        $categories = $this->category->with('storage')->withCount(['children', 'zones' => function ($query) {
            $query->withoutGlobalScope('translate');
        }])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->when($status != 'all', function ($query) use ($status) {
                $query->ofStatus($status == 'active' ? 1 : 0);
            })
            ->ofType('main')
            ->latest()->paginate(pagination_limit())->appends($queryParams);

        $zones = $this->zone->where('is_active', 1)->withoutGlobalScope('translate')->get();
        $zoneTree = $this->zoneTreeForCategoryForm();

        $selectedZoneIds = $request->input('zone_ids', []);
        if (! is_array($selectedZoneIds)) {
            $selectedZoneIds = [];
        }
        $selectedZoneIds = array_values(array_filter(array_map(fn ($id) => (string) $id, $selectedZoneIds), fn ($v) => filled($v) && $v !== 'all'));

        return view('categorymanagement::admin.create', compact('categories', 'zones', 'zoneTree', 'selectedZoneIds', 'search', 'status'));
    }

    public function getTable(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        $queryParams = ['search' => $search, 'status' => $status];

        $categories = Category::with('storage')->withCount(['children', 'zones' => function ($query) {
            $query->withoutGlobalScope('translate');
        }])
            ->when($search, function ($query) use ($search) {
                $keys = explode(' ', $search);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', "%$key%");
                }
            })
            ->when($status != 'all', function ($query) use ($status) {
                $query->ofStatus($status == 'active' ? 1 : 0);
            })
            ->ofType('main')
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParams);

        $totalCategory = $categories->total();
        $categories->withPath(route('admin.category.create'));

        // Fallback logic: If current page has no data, go back one page
        if ($categories->isEmpty() && $page > 1) {
            $page = $page - 1;
            $request->merge(['page' => $page]);

            $categories = Category::with('storage')->withCount(['children', 'zones' => function ($query) {
                $query->withoutGlobalScope('translate');
            }])
                ->when($search, function ($query) use ($search) {
                    $keys = explode(' ', $search);
                    foreach ($keys as $key) {
                        $query->orWhere('name', 'LIKE', "%$key%");
                    }
                })
                ->when($status != 'all', function ($query) use ($status) {
                    $query->ofStatus($status == 'active' ? 1 : 0);
                })
                ->ofType('main')
                ->latest()
                ->paginate(pagination_limit())
                ->appends($queryParams);
        }

        return response()->json([
            'view' =>  view('categorymanagement::admin.partials._table', compact('categories', 'search', 'status', 'totalCategory'))->render(),
            'totalCategory' => $totalCategory,
            'offset' => ($categories->currentPage() - 1) * $categories->perPage(),
            'page' => $categories->currentPage(),
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
        $this->authorize('category_add');

        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|unique:categories',
            'name.0' => 'required',
            'zone_ids' => 'required|array',
            'image' => 'required|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
        ],
            [
                'name.0.required' => translate('default_name_is_required'),
            ]);

        $category = $this->category;
        $category->name = $request->name[array_search('default', $request->lang)];
        $category->image = file_uploader('category/', APPLICATION_IMAGE_FORMAT, $request->file('image'));
        $category->parent_id = 0;
        $category->position = 1;
        $category->description = null;
        $category->save();
        $category->zones()->sync($request->zone_ids);

        $defaultLanguage = str_replace('_', '-', app()->getLocale());

        $data = [];

        foreach ($request->lang as $index => $key) {
            if ($defaultLanguage == $key && !($request->name[$index])) {
                if ($key != 'default') {
                    $data[] = array(
                        'translationable_type' => 'Modules\CategoryManagement\Entities\Category',
                        'translationable_id' => $category->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $category->name,
                    );
                }
            } else {

                if ($request->name[$index] && $key != 'default') {
                    $data[] = array(
                        'translationable_type' => 'Modules\CategoryManagement\Entities\Category',
                        'translationable_id' => $category->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $request->name[$index],
                    );
                }
            }
        }
        if (count($data)) {
            Translation::insert($data);
        }

        Toastr::success(translate(CATEGORY_STORE_200['message']));
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return View|Factory|Application|RedirectResponse
     * @throws AuthorizationException
     */
    public function edit(string $id): View|Factory|Application|RedirectResponse
    {
        $this->authorize('category_update');
        $category = $this->category->withoutGlobalScope('translate')->with(['zones' => function ($query) {
            $query->withoutGlobalScope('translate');
        }])->ofType('main')->where('id', $id)->first();
        if (isset($category)) {
            $zones = $this->zone->where('is_active', 1)->withoutGlobalScope('translate')->get();
            $zoneTree = $this->zoneTreeForCategoryForm();
            $selectedZoneIds = $category->zones->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

            $commissionEntityUseCustom = (int) ($category->commission_custom ?? 0) === 1;
            $commissionCtx = CommissionEntitySetup::tierFormContext(
                is_array($category->commission_tier_setup) ? $category->commission_tier_setup : [],
                $commissionEntityUseCustom
            );

            return view('categorymanagement::admin.edit', array_merge(
                compact('category', 'zones', 'zoneTree', 'selectedZoneIds', 'commissionEntityUseCustom'),
                $commissionCtx
            ));
        }

        Toastr::error(translate(DEFAULT_204['message']));
        return back();
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse|RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $this->authorize('category_update');

        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|unique:categories,name,' . $id,
            'name.0' => 'required',
            'zone_ids' => 'required|array',
            'image' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
        ],
            [
                'name.0.required' => translate('default_name_is_required'),
            ]);

        $category = $this->category->ofType('main')->where('id', $id)->first();
        if (!$category) {
            return response()->json(response_formatter(CATEGORY_204), 204);
        }

        if (Gate::allows('commission_custom_category_update')) {
            CommissionEntitySetup::applyFromRequestToModel($request, $category);
        }

        $category->name = $request->name[array_search('default', $request->lang)];

        if ($request->has('image')) {
            $category->image = file_uploader('category/', APPLICATION_IMAGE_FORMAT, $request->file('image'), $category->image);
        }

        $category->parent_id = 0;
        $category->position = 1;
        $category->description = null;
        $category->update();

        $category->zones()->sync($request->zone_ids);

        $defaultLanguage = str_replace('_', '-', app()->getLocale());

        foreach ($request->lang as $index => $key) {
            if ($defaultLanguage == $key && !($request->name[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\CategoryManagement\Entities\Category',
                            'translationable_id' => $category->id,
                            'locale' => $key,
                            'key' => 'name'],
                        ['value' => $category->name]
                    );
                }
            } else {

                if ($request->name[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\CategoryManagement\Entities\Category',
                            'translationable_id' => $category->id,
                            'locale' => $key,
                            'key' => 'name'],
                        ['value' => $request->name[$index]]
                    );
                }
            }
        }

        return redirect()
            ->route('admin.category.edit', $id)
            ->with('category_updated', translate(CATEGORY_UPDATE_200['message']));
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
        $this->authorize('category_delete');
        $category = $this->category->ofType('main')->where('id', $id)->first();
        if (isset($category)) {
            file_remover('category/', $category->image);
            $category->zones()->sync([]);
            $category->translations()->delete();
            $category->delete();
            Toastr::success(translate(CATEGORY_DESTROY_200['message']));
            return back();
        }
        Toastr::success(translate(CATEGORY_204['message']));
        return back();
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
        $this->authorize('category_manage_status');
        $category = $this->category->where('id', $id)->first();
        $this->category->where('id', $id)->update(['is_active' => !$category->is_active]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function featuredUpdate(Request $request, $id): JsonResponse
    {
        $this->authorize('category_manage_status');
        $category = $this->category->where('id', $id)->first();
        $this->category->where('id', $id)->update(['is_featured' => !$category->is_featured]);

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function childes(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:all,active,inactive',
            'id' => 'required|uuid'
        ]);

        $childes = $this->category->when($request['status'] != 'all', function ($query) use ($request) {
            return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
        })->ofType('sub')->with(['zones'])->where('parent_id', $request['id'])->orderBY('name', 'asc')->paginate(pagination_limit());

        return response()->json(response_formatter(DEFAULT_200, $childes), 200);
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @return JsonResponse
     */
    public function ajaxChildes(Request $request, $id): JsonResponse
    {
        $categories = $this->category->ofStatus(1)->ofType('sub')->where('parent_id', $id)->orderBY('name', 'asc')->get();
        $category = $this->category->where('id', $id)->with(['zones'])->first();
        $zones = $category->zones;

        session()->put('category_wise_zones', $zones);

        $variants = $this->variation->where(['service_id' => $request['service_id']])->get();
        $service = $request->filled('service_id')
            ? Service::query()->find($request->input('service_id'))
            : null;

        return response()->json([
            'template' => view('categorymanagement::admin.partials._childes-selector', compact('categories'))->render(),
            'template_for_zone' => view('servicemanagement::admin.partials._category-wise-zone', compact('zones'))->render(),
            'template_for_variant' => view('servicemanagement::admin.partials._variant-data', compact('zones'))->render(),
            'template_for_update_variant' => view('servicemanagement::admin.partials._update-variant-data', compact('zones', 'variants', 'service'))->render()
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function ajaxChildesOnly(Request $request, $id): JsonResponse
    {
        $categories = $this->category->ofStatus(1)->ofType('sub')->where('parent_id', $id)->orderBY('name', 'asc')->get();
        $subCategoryId = $request->sub_category_id ?? null;

        return response()->json([
            'template' => view('categorymanagement::admin.partials._childes-selector', compact('categories', 'subCategoryId'))->render()
        ], 200);
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return string|StreamedResponse
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('category_export');
        $items = $this->category->withCount(['children', 'zones'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->ofType('main')
            ->latest()->latest()->get();
        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }
}
