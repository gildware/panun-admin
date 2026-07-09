<?php

namespace Modules\ServiceManagement\Http\Controllers\Web\Admin;

use App\Lib\AdditionalChargeEntityOverrides;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\BookingModule\Entities\Booking;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ReviewModule\Entities\Review;
use Modules\ReviewModule\Entities\ReviewReply;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Tag;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\ServiceDetailPreviewPayloadBuilder;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;
use Modules\ServiceManagement\Services\ServiceOverviewDefaultsService;
use Modules\ServiceManagement\Support\ServiceOverviewIconPresets;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ServiceController extends Controller
{
    private Review $review;
    private ReviewReply $reviewReply;
    private Faq $faq;
    private Variation $variation;
    private Zone $zone;
    private Category $category;
    private Booking $booking;
    private Service $service;
    private Provider $provider;

    use AuthorizesRequests;
    use UploadSizeHelperTrait;

    public function __construct(Service $service, Booking $booking, Category $category, Zone $zone, Variation $variation, Faq $faq, Review $review, ReviewReply $reviewReply, Provider $provider)
    {
        $this->service = $service;
        $this->booking = $booking;
        $this->category = $category;
        $this->zone = $zone;
        $this->variation = $variation;
        $this->faq = $faq;
        $this->review = $review;
        $this->reviewReply = $reviewReply;
        $this->provider = $provider;
    }

    private function applyServiceTaxFieldsFromRequest(Request $request, Service $service): void
    {
        if ($request->boolean('tax_override')) {
            $request->validate([
                'tax' => 'required|numeric|min:0|max:100',
                'tax_label' => 'nullable|string|max:191',
            ]);
            $service->tax = (float) $request->tax;
            $service->tax_label = $request->filled('tax_label') ? $request->tax_label : null;
        } else {
            $service->tax = null;
            $service->tax_label = null;
        }
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function create(Request $request): View|Factory|Application
    {
        $this->authorize('service_add');
        $categories = $this->category->ofStatus(1)->ofType('main')->ordered()->get();
        $zones = $this->zone->ofStatus(1)->latest()->get();

        session()->forget('variations');

        return view('servicemanagement::admin.create', compact('categories', 'zones'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function index(Request $request): View|Factory|Application
    {
        $this->authorize('service_view');
        $request->validate([
            'status' => 'in:active,inactive,all',
            'zone_id' => 'uuid',
            'category_id' => 'nullable|uuid',
            'sub_category_id' => 'nullable|uuid',
        ]);

        $search = $request->input('search', '');
        $status = $request->input('status', 'all');
        $category_id = $request->input('category_id', '');
        $sub_category_id = $request->input('sub_category_id', '');

        $queryParams = [
            'search' => $search,
            'status' => $status,
            'category_id' => $category_id,
            'sub_category_id' => $sub_category_id,
        ];

        $filterCounter = collect($queryParams)->filter(function ($value, $key) {
            if ($key === 'status' && ($value === 'all' || $value === null || $value === '')) {
                return false;
            }

            return !is_null($value) && $value !== '';
        })->count();

        $categories = $this->category->ofStatus(1)->ofType('main')->ordered()->get();
        $subCategories = collect();
        if ($category_id) {
            $subCategories = $this->category->ofStatus(1)->ofType('sub')
                ->where('parent_id', $category_id)
                ->ordered()
                ->get();
        }

        $services = $this->service->with(['category', 'subCategory', 'storage_thumbnail'])
            ->withCount('variations')
            ->ordered()
            ->when($request->filled('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })->when($request->filled('sub_category_id'), function ($query) use ($request) {
                return $query->where('sub_category_id', $request->sub_category_id);
            })->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                if ($request['status'] == 'active') {
                    return $query->where(['is_active' => 1]);
                } else {
                    return $query->where(['is_active' => 0]);
                }
            })->when($request->has('zone_id'), function ($query) use ($request) {
                return $query->whereHas('category.zonesBasicInfo', function ($queryZone) use ($request) {
                    $queryZone->where('zone_id', $request['zone_id']);
                });
            })->paginate(pagination_limit())->appends($queryParams);

        return view('servicemanagement::admin.list', compact(
            'services',
            'search',
            'status',
            'categories',
            'subCategories',
            'category_id',
            'sub_category_id',
            'queryParams',
            'filterCounter'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('service_add');

        $check = $this->validateUploadedFile($request, ['cover_image', 'thumbnail']);
        if ($check !== true) {
            return $check;
        }

        $variations = session('variations');
        session()->forget('variations');

        $request->validate([
                'name' => 'required|max:191',
                'name.0' => 'required|max:191',
                'category_id' => 'required|uuid',
                'sub_category_id' => 'required|uuid',
                'cover_image' => 'required|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
                'thumbnail' => 'required|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
                'description' => 'required',
                'description.0' => 'required',
                'short_description' => 'required',
                'short_description.0' => 'required',
                'min_bidding_price' => 'required|numeric|min:0|not_in:0',
            ]
        );


        $tagIds = [];
        if ($request->tags != null) {
            $tags = explode(",", $request->tags);
        }
        if (isset($tags)) {
            foreach ($tags as $key => $value) {
                $tag = Tag::firstOrNew(['tag' => $value]);
                $tag->save();
                $tagIds[] = $tag->id;
            }
        }

        $service = $this->service;
        $service->name = $this->resolveActiveLocalizedValue($request, 'name');
        $service->category_id = $request->category_id;
        $service->sub_category_id = $request->sub_category_id;
        $service->short_description = $this->resolveActiveLocalizedValue($request, 'short_description');
        $service->description = $this->resolveActiveLocalizedValue($request, 'description');
        $service->cover_image = media_file_uploader(
            \App\Support\MediaStoragePath::serviceDir($service->name ?? 'service'),
            'png',
            $request->file('cover_image')
        );
        $service->thumbnail = media_file_uploader(
            \App\Support\MediaStoragePath::serviceDir($service->name ?? 'service'),
            'png',
            $request->file('thumbnail')
        );
        $this->applyServiceTaxFieldsFromRequest($request, $service);
        $service->min_bidding_price = $request->min_bidding_price;
        $service->sort_order = (int) ($this->service
            ->where('sub_category_id', $request->sub_category_id)
            ->max('sort_order') ?? -1) + 1;
        $service->save();
        $service->tags()->sync($tagIds);

        //decoding url encoded keys
        $data = $request->all();
        $data = collect($data)->map(function ($value, $key) {
            $key = urldecode($key);
            return [$key => $value];
        })->collapse()->all();

        $variationFormat = [];
        if ($variations) {
            $zones = $this->zone->ofStatus(1)->latest()->get();
            $variantsSpec = [];
            foreach ($variations as $item) {
                $variantsSpec[] = [
                    'variant_key' => $item['variant_key'],
                    'variant' => $item['variant'],
                    'description' => $item['description'] ?? null,
                    'image' => $item['image'] ?? null,
                ];
            }
            [$variationFormat, $variationPricing] = $this->buildAdminServiceVariations((string) $service->id, $data, $variantsSpec, $zones, $service);
            $service->variation_pricing = $variationPricing;
            $service->save();
        }

        $service->variations()->createMany($variationFormat);

        $this->syncServiceTranslations($request, $service);

        Toastr::success(translate(SERVICE_STORE_200['message']));

        return redirect()
            ->route('admin.service.edit', ['id' => $service->id, 'tab' => 'variations'])
            ->with('service_created', translate(SERVICE_STORE_200['message']));
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @param string $id
     * @return Application|Factory|View|RedirectResponse
     * @throws AuthorizationException
     */
    public function show(Request $request, string $id): View|Factory|RedirectResponse|Application
    {
        $this->authorize('service_view');
        $service = $this->service
            ->where('id', $id)
            ->with(['category' => function ($query) {
                $query->ofStatus(1);
            },'subCategory' => function ($query) {
                $query->ofStatus(1);
            }, 'category.zones', 'category.children', 'variations.zone', 'serviceVariants.zonePrices', 'reviews', 'faqs'])
            ->withCount(['bookings'])
            ->first();

        $service->total_review_count = $service?->reviews?->avg('review_rating') ?? 0;

        $ongoing = $this->booking
            ->whereHas('detail', function ($query) use ($id) {
                return $query->where('service_id', $id);
            })
            ->where(['booking_status' => 'ongoing'])
            ->count();

        $canceled = $this->booking
            ->whereHas('detail', function ($query) use ($id) {
                return $query->where('service_id', $id);
            })
            ->where(['booking_status' => 'canceled'])
            ->count();

        $faqs = $service->faqs;
        $overviewDefaults = ServiceOverviewDefaultsService::get();
        $overviewIconOptions = ServiceOverviewIconPresets::options();
        $overviewContent = $service->overview_content ?? [];
        $resolvedOverviewContent = ServiceOverviewContentResolver::resolveForService($service);
        $servicePreviewPayload = ServiceDetailPreviewPayloadBuilder::build($service, $resolvedOverviewContent, $faqs);

        $search = $request->has('review_search') ? $request['review_search'] : '';
        $webPage = $request->has('review_page') || $request->has('review_search') ? 'review' : ($request->get('web_page', 'general'));
        $queryParam = ['search' => $search, 'web_page' => $webPage];

        $reviews = $this->review->with(['customer', 'booking'])
            ->where('service_id', $id)
            ->when($request->has('review_search') && !empty($request['review_search']), function ($query) use ($request) {
                $keys = explode(' ', $request['review_search']);
                foreach ($keys as $key) {
                    $query->where('review_comment', 'LIKE', '%' . $key . '%')
                        ->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                }
            })
            ->latest()->paginate(pagination_limit(), ['*'], 'review_page')->appends($queryParam);

        $rating_group_count = DB::table('reviews')
            ->select('review_rating', DB::raw('count(*) as total'))
            ->groupBy('review_rating')
            ->get();

        if (isset($service)) {
            $service['ongoing_count'] = $ongoing;
            $service['canceled_count'] = $canceled;
            return view('servicemanagement::admin.detail', compact('service', 'faqs', 'reviews', 'rating_group_count', 'webPage', 'search', 'overviewDefaults', 'overviewIconOptions', 'overviewContent', 'resolvedOverviewContent', 'servicePreviewPayload'));
        }

        Toastr::error(translate(DEFAULT_204['message']));
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Application|Factory|View|RedirectResponse
     * @throws AuthorizationException
     */
    public function edit(string $id): View|Factory|RedirectResponse|Application
    {
        $this->authorize('service_update');
        $service = $this->service->withoutGlobalScope('translate')->where('id', $id)->with(['category.children', 'category.zones', 'variations', 'serviceVariants.zonePrices'])->first();
        if (isset($service)) {
            $editingVariants = $service->variations->pluck('variant_key')->unique()->toArray();
            session()->put('editing_variants', $editingVariants);
            $categories = $this->category->ofStatus(1)->ofType('main')->ordered()->get();

            $category = $this->category->where('id', $service->category_id)->with(['zones'])->first();
            $zones = $category->zones ?? [];
            session()->put('category_wise_zones', $zones);

            $tagNames = [];
            if ($service->tags) {
                foreach ($service->tags as $tag) {
                    $tagNames[] = $tag['tag'];
                }
            }

            session()->forget('variations');

            $commissionEntityUseCustom = (int) ($service->commission_custom ?? 0) === 1;
            $commissionCtx = CommissionEntitySetup::tierFormContext(
                is_array($service->commission_tier_setup) ? $service->commission_tier_setup : [],
                $commissionEntityUseCustom
            );

            $additionalChargeOverrideRows = AdditionalChargeEntityOverrides::rowsForEntity(
                is_array($service->additional_charge_overrides) ? $service->additional_charge_overrides : null
            );

            $overviewDefaults = ServiceOverviewDefaultsService::get();
            $overviewIconOptions = ServiceOverviewIconPresets::options();
            $overviewContent = $service->overview_content ?? [];

            return view('servicemanagement::admin.edit', array_merge(
                compact('categories', 'zones', 'service', 'tagNames', 'commissionEntityUseCustom', 'additionalChargeOverrideRows', 'overviewDefaults', 'overviewIconOptions', 'overviewContent'),
                $commissionCtx
            ));
        }

        Toastr::info(translate(DEFAULT_204['message']));
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
        $this->authorize('service_update');

        $check = $this->validateUploadedFile($request, ['cover_image', 'thumbnail']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|max:191',
            'name.0' => 'required|max:191',
            'category_id' => 'required|uuid',
            'sub_category_id' => 'required|uuid',
            'description' => 'required',
            'description.0' => 'required',
            'short_description' => 'required',
            'short_description.0' => 'required',
            'variants' => 'required|array',
            'min_bidding_price' => 'required|numeric|min:0|not_in:0',
            'cover_image' => 'nullable|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'thumbnail' => 'nullable|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),

        ]);

        $service = $this->service->find($id);
        if (!isset($service)) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $tagIds = [];
        if ($request->tags != null) {
            $tags = explode(",", $request->tags);
        }
        if (isset($tags)) {
            foreach ($tags as $key => $value) {
                $tag = Tag::firstOrNew(['tag' => $value]);
                $tag->save();
                $tagIds[] = $tag->id;
            }
        }


        $service->name = $this->resolveActiveLocalizedValue($request, 'name');
        $service->category_id = $request->category_id;
        $service->sub_category_id = $request->sub_category_id;
        $service->short_description = $this->resolveActiveLocalizedValue($request, 'short_description');
        $service->description = $this->resolveActiveLocalizedValue($request, 'description');

        if ($request->hasFile('cover_image')) {
            $service->cover_image = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir($service),
                'png',
                $request->file('cover_image'),
                $service->cover_image
            );
        }

        if ($request->hasFile('thumbnail')) {
            $service->thumbnail = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir($service),
                'png',
                $request->file('thumbnail'),
                $service->thumbnail
            );
        }

        $service->min_bidding_price = $request->min_bidding_price;
        $service->save();
        $service->tags()->sync($tagIds);

        $this->persistServiceVariations($request, $service);
        $this->syncServiceTranslations($request, $service);

        return redirect()
            ->route('admin.service.edit', ['id' => $id, 'tab' => 'variations'])
            ->with('service_updated', translate(DEFAULT_UPDATE_200['message']));

    }

    public function updateBasic(Request $request, string $id): RedirectResponse
    {
        $this->authorize('service_update');

        $check = $this->validateUploadedFile($request, ['cover_image', 'thumbnail']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|max:191',
            'name.0' => 'required|max:191',
            'category_id' => 'required|uuid',
            'sub_category_id' => 'required|uuid',
            'description' => 'required',
            'description.0' => 'required',
            'short_description' => 'required',
            'short_description.0' => 'required',
            'min_bidding_price' => 'required|numeric|min:0|not_in:0',
            'cover_image' => 'nullable|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'thumbnail' => 'nullable|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        $service = $this->service->find($id);
        if (!isset($service)) {
            Toastr::error(translate(DEFAULT_204['message']));

            return redirect()->route('admin.service.index');
        }

        $tagIds = [];
        if ($request->tags != null) {
            $tags = explode(",", $request->tags);
        }
        if (isset($tags)) {
            foreach ($tags as $value) {
                $tag = Tag::firstOrNew(['tag' => $value]);
                $tag->save();
                $tagIds[] = $tag->id;
            }
        }

        $service->name = $this->resolveActiveLocalizedValue($request, 'name');
        $service->category_id = $request->category_id;
        $service->sub_category_id = $request->sub_category_id;
        $service->short_description = $this->resolveActiveLocalizedValue($request, 'short_description');
        $service->description = $this->resolveActiveLocalizedValue($request, 'description');

        if ($request->hasFile('cover_image')) {
            $service->cover_image = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir($service),
                'png',
                $request->file('cover_image'),
                $service->cover_image
            );
        }

        if ($request->hasFile('thumbnail')) {
            $service->thumbnail = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir($service),
                'png',
                $request->file('thumbnail'),
                $service->thumbnail
            );
        }

        $service->min_bidding_price = $request->min_bidding_price;
        $service->save();
        $service->tags()->sync($tagIds);
        $this->syncServiceTranslations($request, $service);

        return redirect()
            ->route('admin.service.edit', ['id' => $id, 'tab' => 'info'])
            ->with('service_updated', translate(DEFAULT_UPDATE_200['message']));
    }

    public function updateVariations(Request $request, string $id): RedirectResponse
    {
        $this->authorize('service_update');

        $request->validate([
            'variants' => 'required|array',
        ]);

        $service = $this->service->find($id);
        if (!isset($service)) {
            Toastr::error(translate(DEFAULT_204['message']));

            return redirect()->route('admin.service.index');
        }

        $this->persistServiceVariations($request, $service);

        return redirect()
            ->route('admin.service.edit', ['id' => $id, 'tab' => 'variations'])
            ->with('service_updated', translate(DEFAULT_UPDATE_200['message']));
    }

    private function resolveActiveLocalizedValue(Request $request, string $field): string
    {
        $langKeys = $request->lang ?? [];
        $values = $request->input($field, []);
        $activeLang = (string) $request->input('active_lang', 'default');

        if ($activeLang !== '' && $activeLang !== 'default') {
            $activeIndex = array_search($activeLang, $langKeys, true);
            if ($activeIndex !== false && array_key_exists($activeIndex, $values)) {
                return (string) $values[$activeIndex];
            }
        }

        $defaultIndex = array_search('default', $langKeys, true);

        return (string) ($values[$defaultIndex !== false ? $defaultIndex : 0] ?? '');
    }

    private function syncDefaultLocaleTranslationsFromService(Service $service): void
    {
        $defaultLang = str_replace('_', '-', app()->getLocale());

        if ($defaultLang === '' || $defaultLang === 'default') {
            return;
        }

        foreach (['name', 'short_description', 'description'] as $field) {
            $value = $service->getRawOriginal($field);
            if ($value === null) {
                continue;
            }

            Translation::updateOrInsert(
                [
                    'translationable_type' => Service::class,
                    'translationable_id' => $service->id,
                    'locale' => $defaultLang,
                    'key' => $field,
                ],
                ['value' => $value]
            );
        }
    }

    private function syncServiceTranslations(Request $request, Service $service): void
    {
        $defaultLang = str_replace('_', '-', app()->getLocale());

        foreach ($request->lang as $index => $key) {
            if ($defaultLang == $key && !($request->name[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\ServiceManagement\Entities\Service',
                            'translationable_id' => $service->id,
                            'locale' => $key,
                            'key' => 'name'],
                        ['value' => $service->name]
                    );
                }
            } elseif ($request->name[$index] && $key != 'default') {
                Translation::updateOrInsert(
                    [
                        'translationable_type' => 'Modules\ServiceManagement\Entities\Service',
                        'translationable_id' => $service->id,
                        'locale' => $key,
                        'key' => 'name'],
                    ['value' => $request->name[$index]]
                );
            }

            if ($defaultLang == $key && !($request->short_description[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\ServiceManagement\Entities\Service',
                            'translationable_id' => $service->id,
                            'locale' => $key,
                            'key' => 'short_description'],
                        ['value' => $service->short_description]
                    );
                }
            } elseif ($request->short_description[$index] && $key != 'default') {
                Translation::updateOrInsert(
                    [
                        'translationable_type' => 'Modules\ServiceManagement\Entities\Service',
                        'translationable_id' => $service->id,
                        'locale' => $key,
                        'key' => 'short_description'],
                    ['value' => $request->short_description[$index]]
                );
            }

            if ($defaultLang == $key && !($request->description[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\ServiceManagement\Entities\Service',
                            'translationable_id' => $service->id,
                            'locale' => $key,
                            'key' => 'description'],
                        ['value' => $service->description]
                    );
                }
            } elseif ($request->description[$index] && $key != 'default') {
                Translation::updateOrInsert(
                    [
                        'translationable_type' => 'Modules\ServiceManagement\Entities\Service',
                        'translationable_id' => $service->id,
                        'locale' => $key,
                        'key' => 'description'],
                    ['value' => $request->description[$index]]
                );
            }
        }

        $this->syncDefaultLocaleTranslationsFromService($service);
    }

    private function persistServiceVariations(Request $request, Service $service): void
    {
        $service->load('serviceVariants');
        $service->variations()->delete();

        $data = $request->all();
        $data = collect($data)->map(function ($value, $key) {
            $key = urldecode($key);

            return [$key => $value];
        })->collapse()->all();

        $zones = $this->zone->latest()->get();
        $serviceVariantsByKey = $service->serviceVariants->keyBy('variant_key');
        $sessionVariations = session('variations', []);
        $sessionByKey = collect($sessionVariations)->keyBy('variant_key');
        $variantsSpec = [];
        foreach ($data['variants'] as $item) {
            $meta = $serviceVariantsByKey->get($item);
            $sessionMeta = $sessionByKey->get($item);
            $variantsSpec[] = [
                'variant_key' => $item,
                'variant' => $meta?->title ?? $sessionMeta['variant'] ?? str_replace('-', ' ', $item),
                'description' => $data['variant_description'][$item]
                    ?? $meta?->getRawOriginal('description')
                    ?? ($sessionMeta['description'] ?? null),
            ];
        }

        $existingKeys = collect($variantsSpec)->pluck('variant_key');
        foreach ($sessionVariations as $item) {
            if ($existingKeys->contains($item['variant_key'])) {
                continue;
            }
            $variantsSpec[] = [
                'variant_key' => $item['variant_key'],
                'variant' => $item['variant'],
                'description' => $item['description'] ?? null,
                'image' => $item['image'] ?? null,
            ];
        }

        $variantsSpec = collect($variantsSpec)
            ->keyBy('variant_key')
            ->values()
            ->all();

        $keptKeys = collect($variantsSpec)->pluck('variant_key')->unique()->values();
        ServiceVariant::query()
            ->where('service_id', $service->id)
            ->whereNotIn('variant_key', $keptKeys)
            ->get()
            ->each
            ->delete();

        [$variationFormat, $variationPricing] = $this->buildAdminServiceVariations((string) $service->id, $data, $variantsSpec, $zones, $service);

        $service->variation_pricing = $variationPricing;
        $service->save();

        $service->variations()->createMany($variationFormat);
        session()->forget('variations');
        session()->forget('editing_variants');
    }

    public function updateChargesTax(Request $request, string $id): RedirectResponse
    {
        $this->authorize('service_update');
        $service = $this->service->find($id);
        if (! isset($service)) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }
        $this->applyServiceTaxFieldsFromRequest($request, $service);
        $service->save();

        return redirect()
            ->route('admin.service.edit', ['id' => $id, 'tab' => 'charges'])
            ->with('service_updated', translate('Entity_charges_saved'));
    }

    public function updateChargesCommission(Request $request, string $id): RedirectResponse
    {
        $this->authorize('service_update');
        if (! Gate::allows('commission_custom_service_update')) {
            abort(403);
        }
        $service = $this->service->find($id);
        if (! isset($service)) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }
        CommissionEntitySetup::applyFromRequestToModel($request, $service);
        $service->save();

        return redirect()
            ->route('admin.service.edit', ['id' => $id, 'tab' => 'charges'])
            ->with('service_updated', translate('Entity_charges_saved'));
    }

    public function updateChargesAdditional(Request $request, string $id): RedirectResponse
    {
        $this->authorize('service_update');
        $service = $this->service->find($id);
        if (! isset($service)) {
            Toastr::error(translate(DEFAULT_204['message']));

            return back();
        }
        AdditionalChargeEntityOverrides::applyFromRequestToModel($request, $service);
        $service->save();

        return redirect()
            ->route('admin.service.edit', ['id' => $id, 'tab' => 'charges'])
            ->with('service_updated', translate('Entity_charges_saved'));
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
        $this->authorize('service_delete');
        $service = $this->service->where('id', $id)->first();
        if (isset($service)) {
            foreach (['thumbnail', 'cover_image'] as $item) {
                file_remover('service/', $service[$item]);
            }
            $service->translations()->delete();
            $service->serviceVariants()->delete();
            $service->variations()->delete();
            $service->delete();

            Toastr::success(translate(DEFAULT_DELETE_200['message']));
            return back();
        }
        Toastr::success(translate(DEFAULT_204['message']));
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
        $this->authorize('service_manage_status');
        $service = $this->service->where('id', $id)->first();
        $this->service->where('id', $id)->update(['is_active' => !$service->is_active]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function reviewStatusUpdate(Request $request, $id): JsonResponse
    {
        $review = $this->review->where('id', $id)->first();
        $this->review->where('id', $id)->update(['is_active' => !$review->is_active]);

        foreach (['service_id' => $review->service_id, 'provider_id' => $review->provider_id] as $key => $value) {
            $ratingGroupCount = DB::table('reviews')->where($key, $value)->where('is_active', 1)
                ->select('review_rating', DB::raw('count(*) as total'))
                ->groupBy('review_rating')
                ->get();

            $totalRating = 0;
            $ratingCount = 0;
            foreach ($ratingGroupCount as $count) {
                $totalRating += round($count->review_rating * $count->total, 2);
                $ratingCount += $count->total;
            }

            $query = collect([]);
            if ($key == 'service_id') {
                $query = $this->service->where(['id' => $value]);
            } elseif ($key == 'provider_id') {
                $query = $this->provider->where(['id' => $value]);
            }

            // Check if $ratingCount is greater than 0 before calculating the average rating
            if ($ratingCount > 0) {
                $avgRating = round($totalRating / $ratingCount, 2);
            } else {
                $avgRating = 0; // Handle cases where there are no ratings
            }

            $query->update([
                'rating_count' => $ratingCount,
                'avg_rating' => $avgRating
            ]);
        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }


    public function ajaxAddVariant(Request $request): JsonResponse
    {
        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return response()->json(['flag' => 0, 'message' => translate('invalid_file')]);
        }

        $request->validate([
            'name' => 'required|string|max:191',
            'price' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:'.uploadMaxFileSizeInKB('image').'|mimes:'.implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = media_file_uploader(
                \App\Support\MediaStoragePath::serviceDir('variant-temp'),
                'png',
                $request->file('image')
            );
        }

        $variation = [
            'variant' => $request['name'],
            'variant_key' => str_replace(' ', '-', $request['name']),
            'price' => $request['price'],
            'description' => $request['description'] ?? null,
            'image' => $imagePath,
        ];

        $zones = session()->has('category_wise_zones') ? session('category_wise_zones') : [];
        $existingData = session()->has('variations') ? session('variations') : [];
        $editingVariants = session()->has('editing_variants') ? session('editing_variants') : [];

        if (!self::searchForKey($request['name'], $existingData) && !in_array(str_replace(' ', '-', $request['name']), $editingVariants)) {
            $existingData[] = $variation;
            session()->put('variations', $existingData);
        } else {
            return response()->json(['flag' => 0, 'message' => translate('already_exist')]);
        }

        return response()->json(['flag' => 1, 'template' => view('servicemanagement::admin.partials._variant-data', compact('zones'))->render()]);
    }

    public function ajaxRemoveVariant($variant_key)
    {
        $zones = session()->has('category_wise_zones') ? session('category_wise_zones') : [];
        $existingData = session()->has('variations') ? session('variations') : [];

        $filtered = collect($existingData)->filter(function ($values) use ($variant_key) {
            return $values['variant_key'] != $variant_key;
        })->values()->toArray();

        session()->put('variations', $filtered);

        return response()->json(['flag' => 1, 'template' => view('servicemanagement::admin.partials._variant-data', compact('zones'))->render()]);
    }

    public function ajaxDeleteDbVariant($variant_key, $service_id)
    {
        $zones = session()->has('category_wise_zones') ? session('category_wise_zones') : $this->zone->ofStatus(1)->latest()->get();
        $this->variation->where(['variant_key' => $variant_key, 'service_id' => $service_id])->delete();
        $serviceVariant = ServiceVariant::query()
            ->where(['variant_key' => $variant_key, 'service_id' => $service_id])
            ->first();
        if ($serviceVariant) {
            $serviceVariant->delete();
        }
        $variants = $this->variation->where(['service_id' => $service_id])->get();
        $service = $this->service->with('serviceVariants')->find($service_id);
        if ($service && is_array($service->variation_pricing)) {
            $vp = $service->variation_pricing;
            unset($vp[$variant_key]);
            $service->variation_pricing = $vp;
            $service->save();
        }

        return response()->json(['flag' => 1, 'template' => view('servicemanagement::admin.partials._update-variant-data', compact('zones', 'variants', 'service'))->render()]);
    }

    /**
     * @param  array<int, array{variant_key: string, variant: string}>  $variantsSpec
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, array{use_zone_pricing: bool, default_price: float}>}
     */
    protected function buildAdminServiceVariations(string $serviceId, array $data, array $variantsSpec, $zones, ?Service $service = null): array
    {
        $variationFormat = [];
        $variationPricing = [];
        $serviceModel = $service ?? $this->service->find($serviceId);
        $serviceDir = $serviceModel
            ? \App\Support\MediaStoragePath::serviceDir($serviceModel)
            : \App\Support\MediaStoragePath::serviceDir('variant-temp');

        foreach ($variantsSpec as $index => $spec) {
            $keyStr = $spec['variant_key'];
            $variantLabel = $spec['variant'] ?? str_replace('-', ' ', $keyStr);
            $description = $spec['description'] ?? ($data['variant_description'][$keyStr] ?? null);

            $variant = ServiceVariant::query()
                ->where('service_id', $serviceId)
                ->where('variant_key', $keyStr)
                ->first();

            if (! $variant) {
                $variant = new ServiceVariant();
                $variant->service_id = $serviceId;
                $variant->variant_key = $keyStr;
            }

            $variant->title = $variantLabel;
            $variant->description = $description;
            $variant->sort_order = $index;
            $variant->is_active = true;

            if (! empty($spec['image'])) {
                $variant->image = $spec['image'];
            } else {
                $variantImages = request()->file('variant_image');
                if (is_array($variantImages) && isset($variantImages[$keyStr])) {
                    $variant->image = media_file_uploader(
                        $serviceDir,
                        'png',
                        $variantImages[$keyStr],
                        $variant->image
                    );
                }
            }

            $variant->save();

            $useZone = ! empty($data['variant_use_zone_pricing'][$keyStr]);
            $defaultPrice = (float) ($data['variant_default_price'][$keyStr] ?? 0);

            $variationPricing[$keyStr] = [
                'use_zone_pricing' => $useZone,
                'default_price' => $defaultPrice,
            ];

            foreach ($zones as $zone) {
                $price = $useZone
                    ? (float) ($data[$keyStr . '_' . $zone->id . '_price'] ?? 0)
                    : $defaultPrice;
                $variationFormat[] = [
                    'variant' => $variantLabel,
                    'variant_key' => $keyStr,
                    'service_variant_id' => $variant->id,
                    'zone_id' => $zone->id,
                    'price' => $price,
                    'service_id' => $serviceId,
                ];
            }
        }

        return [$variationFormat, $variationPricing];
    }

    function searchForKey($variant, $array): int|string|null
    {
        foreach ($array as $key => $val) {
            if ($val['variant'] === $variant) {
                return true;
            }
        }
        return false;
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return string|StreamedResponse
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('service_export');
        $items = $this->service->with(['category.zonesBasicInfo'])->latest()
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('name', 'LIKE', '%' . $key . '%');
                }
            })
            ->when($request->has('category_id'), function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })->when($request->has('sub_category_id'), function ($query) use ($request) {
                return $query->where('sub_category_id', $request->sub_category_id);
            })->when($request->has('zone_id'), function ($query) use ($request) {
                return $query->whereHas('category.zonesBasicInfo', function ($queryZone) use ($request) {
                    $queryZone->where('zone_id', $request['zone_id']);
                });
            })->latest()->get();

        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    public function reviewsDownload(Request $request)
    {
        $items = $this->review->with(['customer', 'booking'])
            ->when($request->has('review_search') && !empty($request['review_search']), function ($query) use ($request) {
                $keys = explode(' ', $request['review_search']);
                foreach ($keys as $key) {
                    $query->where('review_comment', 'LIKE', '%' . $key . '%')
                        ->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                }
            })
            ->where('service_id', $request->service_id)
            ->latest()
            ->get();

        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }
}
