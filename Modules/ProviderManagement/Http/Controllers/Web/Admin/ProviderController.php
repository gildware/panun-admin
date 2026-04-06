<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use App\Lib\CommissionEntitySetup;
use App\Lib\CommissionTierPayload;
use App\Traits\UploadSizeHelperTrait;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\BusinessSettingsModule\Entities\PackageSubscriberFeature;
use Modules\BusinessSettingsModule\Entities\PackageSubscriberLimit;
use Modules\BusinessSettingsModule\Entities\SubscriptionPackage;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\PaymentModule\Traits\SubscriptionTrait;
use Modules\ProviderManagement\Emails\AccountSuspendMail;
use Modules\ProviderManagement\Emails\AccountUnsuspendMail;
use Modules\ProviderManagement\Emails\NewJoiningRequestMail;
use Modules\ProviderManagement\Emails\RegistrationApprovedMail;
use Modules\ProviderManagement\Emails\RegistrationDeniedMail;
use Modules\ProviderManagement\Entities\BankDetail;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderSetting;
use Modules\ProviderManagement\Entities\ProviderIncident;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ProviderManagement\Traits\PreservesAdminProviderFormDrafts;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneCoverageNormalizationService;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProviderController extends Controller
{
    protected Provider $provider;
    protected User $owner;
    protected User $user;
    protected Service $service;
    protected SubscribedService $subscribedService;
    protected Category $category;
    private Booking $booking;
    private Serviceman $serviceman;
    private SubscriptionPackage $subscriptionPackage;
    private PackageSubscriber $packageSubscriber;
    private PackageSubscriberFeature $packageSubscriberFeature;
    private PackageSubscriberLimit $packageSubscriberLimit;
    private Review $review;
    protected Transaction $transaction;
    protected Zone $zone;
    protected BankDetail $bank_detail;
    protected PaymentRequest $paymentRequest;
    protected BookingRepeat $bookingRepeat;
    private BookingStatusHistory $bookingStatusHistory;

    use AuthorizesRequests;
    use PreservesAdminProviderFormDrafts;
    use SubscriptionTrait;
    use UploadSizeHelperTrait;

    public function __construct
    (
        Transaction $transaction,
        Review $review,
        Serviceman $serviceman,
        Provider $provider,
        User $owner,
        Service $service,
        SubscribedService $subscribedService,
        Category $category,
        Booking $booking,
        Zone $zone,
        BankDetail $bank_detail,
        PackageSubscriber $packageSubscriber,
        SubscriptionPackage $subscriptionPackage,
        PackageSubscriberFeature $packageSubscriberFeature,
        PackageSubscriberLimit $packageSubscriberLimit,
        PaymentRequest $paymentRequest,
        BookingRepeat $bookingRepeat,
        BookingStatusHistory $bookingStatusHistory
    )
    {
        $this->provider = $provider;
        $this->owner = $owner;
        $this->user = $owner;
        $this->service = $service;
        $this->subscribedService = $subscribedService;
        $this->category = $category;
        $this->booking = $booking;
        $this->serviceman = $serviceman;
        $this->review = $review;
        $this->transaction = $transaction;
        $this->zone = $zone;
        $this->bank_detail = $bank_detail;
        $this->subscriptionPackage = $subscriptionPackage;
        $this->packageSubscriber = $packageSubscriber;
        $this->packageSubscriberFeature = $packageSubscriberFeature;
        $this->packageSubscriberLimit = $packageSubscriberLimit;
        $this->paymentRequest = $paymentRequest;
        $this->bookingRepeat = $bookingRepeat;
        $this->bookingStatusHistory = $bookingStatusHistory;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('provider_view');

        Validator::make($request->all(), [
            'search' => 'string',
            'status' => 'required|in:active,inactive,all',
            'performance_filter' => 'nullable|in:all,warning,blacklisted',
        ]);

        $search = $request->has('search') ? $request['search'] : '';
        $status = $request->has('status') ? $request['status'] : 'all';
        $performanceFilter = $request->has('performance_filter') ? $request['performance_filter'] : 'all';
        $queryParam = ['search' => $search, 'status' => $status, 'performance_filter' => $performanceFilter];

        $providers = $this->provider->with(['owner', 'zone'])->where(['is_approved' => 1])->withCount(['subscribed_services', 'bookings'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->ofApproval(1)
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->latest()
            ->when($performanceFilter !== 'all', function ($query) use ($performanceFilter) {
                if ($performanceFilter === 'warning') {
                    $query->where('performance_status', 'warning');
                } elseif ($performanceFilter === 'blacklisted') {
                    $query->where('performance_status', 'blacklisted');
                }
            })
            ->paginate(pagination_limit())->appends($queryParam);

        $topCards = [];
        $topCards['total_providers'] = $this->provider->ofApproval(1)->count();
        $topCards['total_onboarding_requests'] = $this->provider->ofApproval(2)->count();
        $topCards['total_active_providers'] = $this->provider->ofApproval(1)->ofStatus(1)->count();
        $topCards['total_inactive_providers'] = $this->provider->ofApproval(1)->ofStatus(0)->count();

        $performanceService = app(ProviderPerformanceService::class);
        $metrics = $performanceService->getAggregatedProviderPerformanceMetrics($providers->getCollection()->pluck('id')->toArray());

        $providers->getCollection()->transform(function ($provider) use ($metrics) {
            $row = $metrics->get($provider->id);

            $jobsCompleted = (int) ($row?->bookings_completed_count ?? $row?->jobs_completed_count ?? 0);
            $jobsCancelled = (int) ($row?->bookings_cancelled_count ?? 0);
            $complaintsCount = (int) ($row?->complaints_count ?? 0);
            $noShowCount = (int) ($row?->no_show_count ?? 0);
            $totalRelevant = max(1, ($jobsCompleted + $jobsCancelled));

            $provider->performance_score = (int) ($row?->performance_score ?? 0);
            $provider->complaints_percent = round(($complaintsCount / $totalRelevant) * 100, 2);
            $provider->no_show_percent = round(($noShowCount / $totalRelevant) * 100, 2);

            return $provider;
        });

        return view('providermanagement::admin.provider.index', compact('providers', 'topCards', 'search', 'status', 'performanceFilter'));
    }

    /**
     * Show top providers by performance score (highest first), among those with at least one revenue-reporting completed booking.
     */
    public function topProviders(Request $request): Renderable
    {
        $this->authorize('provider_view');

        $providers = $this->provider
            ->with(['owner', 'subscribed_services.category'])
            ->ofApproval(1)
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->forRevenueReporting();
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        $performanceService = app(ProviderPerformanceService::class);
        $metrics = $performanceService->getAggregatedProviderPerformanceMetrics($providers->pluck('id')->all());

        $providers = $providers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->values()
            ->take(20)
            ->map(function ($provider) use ($metrics) {
                $provider->performance_score = (int) ($metrics->get($provider->id)->performance_score ?? 0);

                return $provider;
            });

        return view('providermanagement::admin.provider.top_providers', compact('providers'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     * @throws AuthorizationException
     */
    public function create(Request $request): Renderable|RedirectResponse
    {
        $this->authorize('provider_add');

        if ($request->boolean('reset')) {
            $request->session()->forget('_old_input');
            $this->clearProviderFormDraft('create');

            return redirect()->route('admin.provider.create');
        }

        if (! $request->session()->has('_old_input')) {
            $this->clearProviderFormDraft('create');
        }

        $zones = $this->zone->get();
        $zoneTree = $this->zoneTreeForProviderForm();
        $commission = (int)((business_config('provider_commision', 'provider_config'))->live_values ?? null);
        $subscription = (int)((business_config('provider_subscription', 'provider_config'))->live_values ?? null);
        $duration = (int)((business_config('free_trial_period', 'subscription_Setting'))->live_values ?? null);
        $freeTrialStatus = (int)((business_config('free_trial_period', 'subscription_Setting'))->is_active ?? 0);
        $subscriptionPackages = $this->subscriptionPackage->OfStatus(1)->with('subscriptionPackageFeature', 'subscriptionPackageLimit')->get();
        $formattedPackages = $subscriptionPackages->map(function ($subscriptionPackage) {
            return formatSubscriptionPackage($subscriptionPackage, PACKAGE_FEATURES);
        });
        $providerFormDraft = $this->getProviderFormDraftManifest('create');

        return view('providermanagement::admin.provider.create', compact('zones', 'zoneTree', 'commission', 'subscription', 'formattedPackages', 'duration', 'freeTrialStatus', 'providerFormDraft'));
    }

    /**
     * Subcategories available for a zone (wizard step 2 — same rules as provider details › subscribed services).
     */
    public function subcategoriesForCreateWizard(Request $request): JsonResponse
    {
        $this->authorize('provider_add');

        $request->validate([
            'zone_id' => 'nullable|uuid',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'zone_excluded_ids' => 'nullable|array',
            'zone_excluded_ids.*' => 'uuid',
        ]);

        $this->mergeLegacyZoneIdIntoZoneIds($request);
        $leafZoneIds = $this->normalizedProviderLeafZoneIdsFromRequest($request);
        if ($leafZoneIds === []) {
            return response()->json([
                'sub_categories' => [],
                'message' => translate('Select_Zone'),
            ], 422);
        }

        $rows = $this->subCategoriesForZonesQuery($leafZoneIds)
            ->orderBy('name')
            ->get();

        return response()->json([
            'sub_categories' => $rows->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'services_count' => (int) ($c->services_count ?? 0),
            ])->values(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function subCategoriesForZoneQuery(string $zoneId)
    {
        return $this->category->withCount('services')
            ->whereHas('parent.zones', function ($query) use ($zoneId) {
                $query->where('zone_id', $zoneId);
            })
            ->whereHas('parent', function ($query) {
                $query->where('is_active', 1);
            })
            ->ofStatus(1)
            ->ofType('sub');
    }

    /**
     * AJAX duplicate check for contact person phone/email (wizard step + live feedback).
     */
    public function checkOwnerContactUnique(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_person_phone' => 'nullable|string|max:32',
            'contact_person_email' => 'nullable|email|max:191',
            'exclude_user_id' => 'nullable|uuid',
        ]);
        if ($validator->fails()) {
            $fieldErrors = [];
            if ($validator->errors()->has('contact_person_phone')) {
                $fieldErrors['contact_person_phone'] = $validator->errors()->first('contact_person_phone');
            }
            if ($validator->errors()->has('contact_person_email')) {
                $fieldErrors['contact_person_email'] = $validator->errors()->first('contact_person_email');
            }

            return response()->json([
                'valid' => false,
                'messages' => $validator->errors()->all(),
                'field_errors' => $fieldErrors,
            ], 422);
        }

        $phone = trim((string) $request->input('contact_person_phone', ''));
        $email = Str::lower(trim((string) $request->input('contact_person_email', '')));
        $excludeUserId = $request->input('exclude_user_id');

        $fieldErrors = [];

        if ($phone !== '') {
            if ($this->ownerContactPhoneTaken($phone, $excludeUserId)) {
                $fieldErrors['contact_person_phone'] = translate('The contact person phone has already been taken.');
            }
        }

        if ($email !== '') {
            $emailUser = User::findByContactEmail($email);
            if ($emailUser && (string) $emailUser->id !== (string) ($excludeUserId ?? '') && ! $emailUser->qualifiesForCustomerToProviderUpgrade()) {
                $fieldErrors['contact_person_email'] = translate('The contact person email has already been taken.');
            }
        }

        return response()->json([
            'valid' => count($fieldErrors) === 0,
            'field_errors' => $fieldErrors,
        ]);
    }

    /**
     * True if another user already owns this phone (exact, digits-only, or normalized match on MySQL).
     */
    private function ownerContactPhoneTaken(string $phone, ?string $excludeUserId): bool
    {
        $user = User::findByContactPhone($phone);
        if (! $user) {
            return false;
        }
        if ($excludeUserId && (string) $user->id === (string) $excludeUserId) {
            return false;
        }

        return ! $user->qualifiesForCustomerToProviderUpgrade();
    }

    /**
     * Active zones nested by parent_id for admin provider create/edit (root = null parent).
     *
     * @return list<array{id: string, name: string, children: list<array{id: string, name: string, children: list}>}>
     */
    private function zoneTreeForProviderForm(): array
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

    private function mergeLegacyZoneIdIntoZoneIds(Request $request): void
    {
        $ids = $request->input('zone_ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter($ids));
        if ($ids === [] && $request->filled('zone_id')) {
            $request->merge(['zone_ids' => [(string) $request->input('zone_id')]]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function normalizedProviderLeafZoneIdsFromRequest(Request $request): array
    {
        $included = $request->input('zone_ids', []);
        if (! is_array($included)) {
            $included = [];
        }
        $excluded = $request->input('zone_excluded_ids', []);
        if (! is_array($excluded)) {
            $excluded = [];
        }

        return app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds($included, $excluded);
    }

    private function subCategoriesForZonesQuery(array $zoneIds)
    {
        $zoneIds = array_values(array_unique(array_filter($zoneIds)));
        if ($zoneIds === []) {
            return $this->category->newQuery()->whereRaw('1 = 0');
        }

        return $this->category->withCount('services')
            ->whereHas('parent.zones', function ($query) use ($zoneIds) {
                $query->whereIn('category_zone.zone_id', $zoneIds);
            })
            ->whereHas('parent', function ($query) {
                $query->where('is_active', 1);
            })
            ->ofStatus(1)
            ->ofType('sub');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('provider_add');

        if (!$request->filled('plan_type')) {
            $request->merge(['plan_type' => 'commission_based']);
        }

        if (! $request->filled('contact_person_email')) {
            $request->merge(['contact_person_email' => null]);
        }

        $formKey = 'create';
        $this->attachProviderFormDraftToRequest($request, $formKey);

        $preserveDraft = function () use ($request, $formKey) {
            $this->persistProviderFormDraftAfterFailedValidation($request, $formKey);
        };

        $check = $this->validateUploadedFile($request, ['logo', 'contact_person_photo'], 'image', $preserveDraft);
        if ($check !== true) {
            return $check;
        }

        // Contact person identity (Box 5)
        $identityIn = 'passport,driving_license,nid';
        $allowedImageMimes = implode(',', array_column(IMAGEEXTENSION, 'key'));

        $this->mergeLegacyZoneIdIntoZoneIds($request);

        try {
            $validator = Validator::make($request->all(), [
            'provider_type' => 'required|in:company,individual',

            'contact_person_name' => 'required|string|max:191',
            'contact_person_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'contact_person_email' => 'nullable|email|max:191',

            // Account email/phone are derived from contact person details by default.
            // Keep them optional to avoid depending on front-end JS.
            'account_email' => 'nullable|email',
            'account_phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',

            'company_name' => 'required_if:provider_type,company|string|max:191',
            'company_phone' => 'required_if:provider_type,company|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'company_address' => 'required',
            'company_email' => 'required_if:provider_type,company|email',
            'logo' => 'required_if:provider_type,company|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),

            'contact_person_photo' => 'required|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),

            'identity_type' => 'required|in:' . $identityIn,
            'identity_number' => 'required',
            'identity_images' => 'array',
            'identity_images.*' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),

            'identity_pdf_files' => 'nullable|array',
            'identity_pdf_files.*' => 'file|mimes:pdf|max:' . uploadMaxFileSizeInKB('file'),

            // Company identity docs & identity (Box 3)
            'company_identity_type' => 'required_if:provider_type,company|in:trade_license,company_id',
            'company_identity_number' => 'required_if:provider_type,company|string|max:191',
            'company_identity_images' => 'array',
            'company_identity_images.*' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'company_identity_pdf_files' => 'nullable|array',
            'company_identity_pdf_files.*' => 'file|mimes:pdf|max:' . uploadMaxFileSizeInKB('file'),

            'additional_documents' => 'nullable|array',
            'additional_documents.*.name' => 'nullable|string|max:191',
            'additional_documents.*.description' => 'nullable|string',
            'additional_documents.*.files' => 'nullable|array',
            'additional_documents.*.files.*' => 'file|max:' . uploadMaxFileSizeInKB('file') . '|mimes:' . $allowedImageMimes . ',pdf',
            'latitude' => 'required',
            'longitude' => 'required',

            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'uuid',
            'zone_excluded_ids' => 'nullable|array',
            'zone_excluded_ids.*' => 'uuid',

            'subscribed_sub_category_ids' => 'nullable|array',
            'subscribed_sub_category_ids.*' => 'uuid',
        ]);
            $validator->after(function ($v) use ($request) {
                foreach (User::providerContactRegistrationErrors(
                    (string) $request->contact_person_phone,
                    (string) $request->contact_person_email
                ) as $field => $message) {
                    $v->errors()->add($field, $message);
                }
            });
            $validator->validate();
        } catch (ValidationException $e) {
            $this->persistProviderFormDraftAfterFailedValidation($request, $formKey);
            throw $e;
        }

        $leafZoneIds = $this->normalizedProviderLeafZoneIdsFromRequest($request);
        if ($leafZoneIds === []) {
            Toastr::error(translate('Select_Zone'));

            return $this->backWithInputAndDraft($request, $formKey);
        }

        // Enforce at least one contact identity image (PDF upload removed from UI).
        $hasContactImages = $request->has('identity_images') && is_array($request->identity_images) && count($request->identity_images) > 0;
        if (!$hasContactImages) {
            Toastr::error(translate('Please upload at least one contact identity image'));

            return $this->backWithInputAndDraft($request, $formKey);
        }

        // Enforce at least one company identity image when provider is company.
        if ($request->provider_type === 'company') {
            $hasCompanyImages = $request->has('company_identity_images') && is_array($request->company_identity_images) && count($request->company_identity_images) > 0;
            if (!$hasCompanyImages) {
                Toastr::error(translate('Please upload at least one company identity image'));

                return $this->backWithInputAndDraft($request, $formKey);
            }
        }


        if ($request->plan_type == 'subscription_based'){
            $package = $this->subscriptionPackage->where('id',$request->selected_package_id)->ofStatus(1)->first();
            $vatPercentage      = (int)((business_config('subscription_vat', 'subscription_Setting'))->live_values ?? 0);
            if (!$package){
                Toastr::error(translate('Please Select valid plan'));

                return $this->backWithInputAndDraft($request, $formKey);
            }

            $id                 = $package?->id;
            $price              = $package?->price;
            $name               = $package?->name;
        }

        $identityImages = [];
        if ($request->has('identity_images')) {
            foreach ($request->identity_images as $image) {
                if (! $image) {
                    continue;
                }
                $imageName = file_uploader('provider/identity/', APPLICATION_IMAGE_FORMAT, $image);
                $identityImages[] = ['image'=>$imageName, 'storage'=> getDisk()];
            }
        }

        if ($request->has('identity_pdf_files')) {
            foreach ($request->identity_pdf_files as $pdf) {
                if (! $pdf) {
                    continue;
                }
                $pdfName = file_uploader('provider/identity/', 'pdf', $pdf);
                $identityImages[] = ['image'=>$pdfName, 'storage'=> getDisk()];
            }
        }

        $companyIdentityImages = [];
        if ($request->has('company_identity_images')) {
            foreach ($request->company_identity_images as $image) {
                if (! $image) {
                    continue;
                }
                $imageName = file_uploader('provider/company-identity/', APPLICATION_IMAGE_FORMAT, $image);
                $companyIdentityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        if ($request->has('company_identity_pdf_files')) {
            foreach ($request->company_identity_pdf_files as $pdf) {
                if (! $pdf) {
                    continue;
                }
                $pdfName = file_uploader('provider/company-identity/', 'pdf', $pdf);
                $companyIdentityImages[] = ['image' => $pdfName, 'storage' => getDisk()];
            }
        }

        $provider = $this->provider;
        $provider->provider_type = $request->provider_type;

        // For Individual providers, we don't ask company/contact duplication.
        // Still populate provider->company_* for compatibility with existing code/views.
        if ($request->provider_type === 'company') {
            $provider->company_name = $request->company_name;
            $provider->company_phone = $request->company_phone;
            $provider->company_email = $request->company_email;
        } else {
            $provider->company_name = $request->contact_person_name;
            $provider->company_phone = $request->contact_person_phone;
            $provider->company_email = $request->contact_person_email;
        }

        // Edit-mode remove support.
        if ($request->boolean('logo_remove')) {
            $provider->logo = null;
        }
        if ($request->has('logo')) {
            $provider->logo = file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }
        $provider->company_address = $request->company_address;

        $provider->contact_person_name = $request->contact_person_name;
        $provider->contact_person_phone = $request->contact_person_phone;
        $provider->contact_person_email = $request->contact_person_email;

        if ($request->boolean('contact_person_photo_remove')) {
            $provider->contact_person_photo = null;
        }
        if ($request->has('contact_person_photo')) {
            $provider->contact_person_photo = file_uploader('provider/contact_person_photo/', APPLICATION_IMAGE_FORMAT, $request->file('contact_person_photo'));
        }

        // Save company identity docs (only required for provider_type=company).
        if ($request->provider_type === 'company') {
            $provider->company_identity_type = $request->company_identity_type;
            $provider->company_identity_number = $request->company_identity_number;
            $provider->company_identity_images = $companyIdentityImages;
        } else {
            $provider->company_identity_type = null;
            $provider->company_identity_number = null;
            $provider->company_identity_images = [];
        }
        $provider->is_approved = 1;
        $provider->is_active = 1;
        $provider->zone_id = $leafZoneIds[0];
        $provider->coordinates = ['latitude' => $request['latitude'], 'longitude' => $request['longitude']];

        $upgradeOwner = User::resolveCustomerUserForProviderOnboarding(
            (string) $request->contact_person_phone,
            (string) $request->contact_person_email
        );
        if ($upgradeOwner) {
            $owner = User::query()->findOrFail($upgradeOwner->id);
            $owner->customer_app_access = true;
        } else {
            $owner = $this->owner;
            $owner->customer_app_access = false;
        }

        $nameParts = preg_split('/\s+/u', trim((string) $request->contact_person_name), 2, PREG_SPLIT_NO_EMPTY);
        $owner->first_name = $nameParts[0] ?? $owner->first_name ?? '';
        $owner->last_name = $nameParts[1] ?? $owner->last_name ?? '';

        $owner->email = $request->contact_person_email;
        $owner->phone = $request->contact_person_phone;
        $owner->identification_number = $request->identity_number;
        $owner->identification_type = $request->identity_type;
        $owner->is_active = 1;
        $owner->identification_image = $identityImages;
        $owner->password = bcrypt(provider_default_password_plain($request->contact_person_phone));
        $owner->user_type = 'provider-admin';

        DB::transaction(function () use ($provider, $owner, $request, $leafZoneIds) {
            $owner->save();
            $owner->zones()->sync($leafZoneIds);
            $provider->user_id = $owner->id;
            $provider->save();
            $provider->zones()->sync(
                collect($leafZoneIds)->mapWithKeys(fn (string $zid) => [$zid => []])->all()
            );

            $serviceLocation = ['customer'];
            ProviderSetting::create([
                'provider_id'   => $provider->id,
                'key_name'      => 'service_location',
                'live_values'   => json_encode($serviceLocation),
                'test_values'   => json_encode($serviceLocation),
                'settings_type' => 'provider_config',
                'mode'          => 'live',
                'is_active'     => 1,
            ]);

            $allSubs = $this->subCategoriesForZonesQuery($leafZoneIds)->get();
            $allowedIds = $allSubs->pluck('id')->all();
            $rawIds = $request->input('subscribed_sub_category_ids', []);
            if (! is_array($rawIds)) {
                $rawIds = [];
            }
            $requested = [];
            foreach ($rawIds as $rid) {
                if (is_string($rid) && Str::isUuid($rid) && in_array($rid, $allowedIds, true)) {
                    $requested[] = $rid;
                }
            }
            $requested = array_values(array_unique($requested));

            foreach ($allSubs as $subCategory) {
                $this->subscribedService->create([
                    'provider_id' => $provider->id,
                    'category_id' => $subCategory->parent_id,
                    'sub_category_id' => $subCategory->id,
                    'is_subscribed' => in_array($subCategory->id, $requested, true) ? 1 : 0,
                ]);
            }
        });

        // Upload additional documents (optional).
        $additionalDocuments = $request->input('additional_documents', []);
        if (is_array($additionalDocuments) && count($additionalDocuments) > 0) {
            foreach ($additionalDocuments as $docIndex => $doc) {
                $docName = trim($doc['name'] ?? '');
                $docDescription = $doc['description'] ?? null;
                $files = $request->file('additional_documents.' . $docIndex . '.files', []);
                if (! is_array($files)) {
                    $files = $files ? [$files] : [];
                }

                if (!$docName && empty($files)) {
                    continue; // Completely empty row.
                }

                if (empty($files)) {
                    Toastr::error(translate('Please upload at least one file for document'));

                    return $this->backWithInputAndDraft($request, $formKey);
                }

                if (!$docName && !empty($files)) {
                    Toastr::error(translate('Please enter document name'));

                    return $this->backWithInputAndDraft($request, $formKey);
                }

                $documentId = (string) \Illuminate\Support\Str::uuid();
                DB::table('providers_additional_documents')->insert([
                    'id' => $documentId,
                    'provider_id' => $provider->id,
                    'document_name' => $docName,
                    'document_description' => $docDescription,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($files as $file) {
                    if (!$file) continue;
                    $extension = $file->getClientOriginalExtension() ?: 'bin';
                    $filePath = file_uploader(
                        'provider/additional-documents/' . $documentId,
                        $extension,
                        $file
                    );

                    DB::table('providers_additional_document_files')->insert([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'document_id' => $documentId,
                        'file_path' => $filePath,
                        'storage' => getDisk(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $emailStatus = business_config('email_config_status', 'email_config')->live_values;

        if ($emailStatus){
            try {
                Mail::to(User::where('user_type', 'super-admin')->value('email'))->send(new NewJoiningRequestMail($provider));
            } catch (\Exception $exception) {
                info($exception);
            }
        }


        if ($request->plan_type == 'subscription_based') {
            $provider_id = $provider?->id;
            if ($request->plan_price == 'received_money') {

                $payment = $this->paymentRequest;
                $payment->payment_amount = $price;
                $payment->success_hook = 'subscription_success';
                $payment->failure_hook = 'subscription_fail';
                $payment->payer_id = $provider->user_id;
                $payment->payment_method = 'manually';
                $payment->additional_data = json_encode($request->all());
                $payment->attribute = 'provider-reg';
                $payment->attribute_id = $provider_id;
                $payment->payment_platform = 'web';
                $payment->is_paid = 1;
                $payment->save();
                $request['payment_id'] = $payment->id;

                $result = $this->handlePurchasePackageSubscription($id, $provider_id, $request->all() , $price, $name);

                if (!$result) {
                    Toastr::error(translate('Something error'));

                    return $this->backWithInputAndDraft($request, $formKey);
                }
            }
            if ($request->plan_price == 'free_trial') {
                $result = $this->handleFreeTrialPackageSubscription($id, $provider_id, $price, $name);
                if (!$result) {
                    Toastr::error(translate('Something error'));

                    return $this->backWithInputAndDraft($request, $formKey);
                }
            }
        }

        $this->clearProviderFormDraft($formKey);

        return redirect()->route('admin.provider.create')->with('provider_created', [
            'id' => $provider->id,
            'name' => (string) ($provider->company_name ?: $provider->contact_person_name),
        ]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @param Request $request
     * @return Application|Factory|View|\Illuminate\Foundation\Application|RedirectResponse
     */
    public function details($id, Request $request): \Illuminate\Foundation\Application|View|Factory|RedirectResponse|Application
    {
        $this->authorize('provider_view');
        $request->validate([
            'web_page' => 'in:overview,subscribed_services,bookings,serviceman_list,settings,bank_information,reviews,subscription,payment,performance',
        ]);

        $webPage = $request->has('web_page') ? $request['web_page'] : 'overview';

        //overview (no payment widgets; those are on the Payment tab)
        if ($request->web_page == 'overview') {
            $provider = $this->provider->with('owner.account', 'zone')->withCount(['bookings'])->find($id);
            $bookingOverview = DB::table('bookings')->where('provider_id', $id)
                ->select('booking_status', DB::raw('count(*) as total'))
                ->groupBy('booking_status')
                ->get();

            $status = ['accepted', 'ongoing', 'completed', 'canceled'];
            $bookingStatusCounts = [];
            foreach ($status as $item) {
                $bookingStatusCounts[$item] = (int) ($bookingOverview->where('booking_status', $item)->first()->total ?? 0);
            }
            $total = array_values($bookingStatusCounts);

            $subscribedServiceCategoryCounts = DB::table('subscribed_services as ss')
                ->leftJoin('categories as c', 'c.id', '=', 'ss.category_id')
                ->where('ss.provider_id', $id)
                ->where('ss.is_subscribed', 1)
                ->select('ss.category_id', DB::raw('MAX(c.name) as category_name'), DB::raw('COUNT(*) as total'))
                ->groupBy('ss.category_id')
                ->orderByDesc('total')
                ->get();
            $totalSubscribedServices = (int) $subscribedServiceCategoryCounts->sum('total');

            $providerBookingIds = DB::table('bookings')->where('provider_id', $id)->pluck('id')->toArray();
            $bookingIdsWithRepeats = DB::table('booking_repeats')->whereNotNull('booking_id')->distinct()->pluck('booking_id')->toArray();
            $oneTimeQuery = DB::table('bookings')->where('provider_id', $id)->where(function ($q) {
                $q->where('booking_status', 'completed')
                    ->orWhere(function ($q2) {
                        $q2->where('booking_status', 'canceled')
                            ->where('after_visit_cancel', 1);
                    });
            });
            if (!empty($bookingIdsWithRepeats)) {
                $oneTimeQuery->whereNotIn('id', $bookingIdsWithRepeats);
            }
            $completedOneTimeBookingIds = $oneTimeQuery->pluck('id');

            $totalRevenueFromBookings = 0.0;
            $oneTimeBookingsForRevenue = Booking::whereIn('id', $completedOneTimeBookingIds)->with('extra_services')->get();
            foreach ($oneTimeBookingsForRevenue as $b) {
                $totalRevenueFromBookings += get_booking_revenue_reporting_amount($b);
            }

            $totalRevenueFromRepeats = 0.0;
            $completedRepeatIds = collect();
            if (!empty($providerBookingIds)) {
                $completedRepeatIds = DB::table('booking_repeats')
                    ->where('booking_status', 'completed')
                    ->whereIn('booking_id', $providerBookingIds)
                    ->pluck('id');
                $repeatsForRevenue = BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking.extra_services')->get();
                foreach ($repeatsForRevenue as $r) {
                    $totalRevenueFromRepeats += get_booking_total_amount($r);
                }
            }

            $totalRevenue = (float) ($totalRevenueFromBookings + $totalRevenueFromRepeats);
            $totalCompanyCommission = (float) BookingDetailsAmount::whereIn('booking_id', $completedOneTimeBookingIds)->sum('admin_commission');
            $totalCompanyCommission += (float) BookingDetailsAmount::whereIn('booking_repeat_id', $completedRepeatIds)->sum('admin_commission');
            $providerNetEarning = $totalRevenue - $totalCompanyCommission;

            $additionalDocuments = DB::table('providers_additional_documents')
                ->where('provider_id', $id)
                ->orderByDesc('created_at')
                ->get();
            $additionalDocumentFiles = collect();
            if ($additionalDocuments->isNotEmpty()) {
                $additionalDocumentFiles = DB::table('providers_additional_document_files')
                    ->whereIn('document_id', $additionalDocuments->pluck('id'))
                    ->orderBy('id')
                    ->get()
                    ->groupBy('document_id');
            }

            return view('providermanagement::admin.provider.detail.overview', compact(
                'provider',
                'webPage',
                'total',
                'bookingStatusCounts',
                'totalSubscribedServices',
                'subscribedServiceCategoryCounts',
                'totalRevenue',
                'providerNetEarning',
                'totalCompanyCommission',
                'additionalDocuments',
                'additionalDocumentFiles'
            ));

        } //subscribed_services
        elseif ($request->web_page == 'subscribed_services') {
            $search = $request->has('search') ? $request['search'] : '';
            $status = $request->has('status') ? $request['status'] : 'all';
            $rawCategoryIds = $request->input('category_ids', []);
            if (! is_array($rawCategoryIds)) {
                $rawCategoryIds = [];
            }
            $selectedCategoryIds = [];
            $subscribedFilterCategories = collect();

            $subscribedServicesEmptyState = null;
            $subscribedServicesZoneNames = [];
            $eligibleSubCategoryCountForZones = 0;

            // Subcategories union across all leaf zones this provider covers.
            // category_zone uses leaf IDs; provider_zone / zone_id may store parent zones — normalize like create/update.
            $provider = $this->provider->with(['owner', 'zones'])->find($id);
            $coverageZoneIds = $provider->zones->pluck('id')->map(fn ($zid) => (string) $zid)->filter()->values()->all();
            if ($coverageZoneIds === [] && $provider->zone_id) {
                $coverageZoneIds = [(string) $provider->zone_id];
            }
            $leafZoneIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds($coverageZoneIds);

            if ($leafZoneIds === []) {
                $queryParam = array_filter([
                    'web_page' => $webPage,
                    'status' => $status,
                    'search' => trim((string) $search) !== '' ? $search : null,
                ], fn ($v) => $v !== null && $v !== '');

                $subCategories = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    pagination_limit(),
                    1,
                    ['path' => $request->url()]
                );
                $subCategories->appends($queryParam);
                if ($coverageZoneIds === [] && !$provider->zone_id) {
                    $subscribedServicesEmptyState = 'no_zones';
                } else {
                    $subscribedServicesEmptyState = 'zones_unresolved';
                    $subscribedServicesZoneNames = $this->zone->withoutGlobalScope('translate')
                        ->whereIn('id', $coverageZoneIds)
                        ->orderBy('name')
                        ->pluck('name')
                        ->map(fn ($n) => (string) $n)
                        ->filter()
                        ->values()
                        ->all();
                }
            } else {
                $eligibleSubCategoryCountForZones = $this->subCategoriesForZonesQuery($leafZoneIds)->count();
                $subscribedServicesZoneNames = $this->zone->withoutGlobalScope('translate')
                    ->whereIn('id', $leafZoneIds)
                    ->orderBy('name')
                    ->pluck('name')
                    ->map(fn ($n) => (string) $n)
                    ->filter()
                    ->values()
                    ->all();

                $allowedParentIds = $this->subCategoriesForZonesQuery($leafZoneIds)
                    ->pluck('parent_id')
                    ->unique()
                    ->filter()
                    ->values()
                    ->all();

                $subscribedFilterCategories = $this->category
                    ->whereIn('id', $allowedParentIds)
                    ->ofType('main')
                    ->ofStatus(1)
                    ->orderBy('name')
                    ->get();

                $allowedParentIdSet = array_flip($allowedParentIds);
                foreach ($rawCategoryIds as $rid) {
                    if (is_string($rid) && Str::isUuid($rid) && isset($allowedParentIdSet[$rid])) {
                        $selectedCategoryIds[] = $rid;
                    }
                }
                $selectedCategoryIds = array_values(array_unique($selectedCategoryIds));

                $queryParam = array_filter([
                    'web_page' => $webPage,
                    'status' => $status,
                    'search' => trim((string) $search) !== '' ? $search : null,
                    'category_ids' => $selectedCategoryIds !== [] ? $selectedCategoryIds : null,
                ], fn ($v) => $v !== null && $v !== '');

                // Get all subcategories available for the provider's zones
                $subCategoriesQuery = $this->category->withCount('services')
                    ->with(['services'])
                    ->whereHas('parent.zones', function ($query) use ($leafZoneIds) {
                        $query->whereIn('category_zone.zone_id', $leafZoneIds);
                    })
                    ->whereHas('parent', function ($query) {
                        $query->where('is_active', 1);
                    })
                    ->ofStatus(1)
                    ->ofType('sub')
                    ->when($selectedCategoryIds !== [], function ($query) use ($selectedCategoryIds) {
                        $query->whereIn('parent_id', $selectedCategoryIds);
                    });

                // Apply search filter
                if ($search) {
                    $keys = explode(' ', $search);
                    $subCategoriesQuery->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere('name', 'LIKE', '%' . $key . '%');
                        }
                    });
                }

                // Get all subcategories
                $allSubCategories = $subCategoriesQuery->get();

                if ($allSubCategories->isEmpty()) {
                    $subCategories = new \Illuminate\Pagination\LengthAwarePaginator(
                        collect(),
                        0,
                        pagination_limit(),
                        1,
                        ['path' => $request->url()]
                    );
                    $subCategories->appends($queryParam);
                } else {
                    // Ensure all subcategories have a subscribed_service record
                    foreach ($allSubCategories as $subCategory) {
                        $existingService = $this->subscribedService
                            ->where('provider_id', $id)
                            ->where('sub_category_id', $subCategory->id)
                            ->first();

                        if (!$existingService) {
                            $this->subscribedService->create([
                                'provider_id' => $id,
                                'sub_category_id' => $subCategory->id,
                                'category_id' => $subCategory->parent_id,
                                'is_subscribed' => 0
                            ]);
                        }
                    }

                    $subCategories = $this->subscribedService->newQuery()
                        ->where('subscribed_services.provider_id', $id)
                        ->whereIn('subscribed_services.sub_category_id', $allSubCategories->pluck('id')->toArray())
                        ->join('categories as ss_cat_parent', 'ss_cat_parent.id', '=', 'subscribed_services.category_id')
                        ->join('categories as ss_cat_sub', 'ss_cat_sub.id', '=', 'subscribed_services.sub_category_id')
                        ->select('subscribed_services.*')
                        ->with([
                            'category',
                            'sub_category' => function ($query) {
                                return $query->withCount('services')->with(['services', 'parent']);
                            },
                        ])
                        ->when($status != 'all', function ($query) use ($status) {
                            return $query->where('subscribed_services.is_subscribed', (($status == 'subscribed') ? 1 : 0));
                        })
                        ->when(trim((string) $search) !== '', function ($query) use ($search) {
                            $query->where(function ($q) use ($search) {
                                foreach (array_filter(explode(' ', $search)) as $key) {
                                    $q->orWhereHas('sub_category', function ($sub) use ($key) {
                                        $sub->where('name', 'LIKE', '%' . $key . '%');
                                    });
                                }
                            });
                        })
                        ->orderBy('ss_cat_parent.name')
                        ->orderBy('ss_cat_sub.name')
                        ->paginate(pagination_limit())
                        ->appends($queryParam);
                }
            }

            if ($subCategories->total() === 0 && $subscribedServicesEmptyState === null) {
                $subscribedServicesEmptyState = $eligibleSubCategoryCountForZones === 0
                    ? 'no_categories'
                    : 'no_results';
            }

            return view('providermanagement::admin.provider.detail.subscribed-services', compact(
                'subCategories',
                'webPage',
                'status',
                'search',
                'provider',
                'subscribedServicesEmptyState',
                'subscribedServicesZoneNames',
                'subscribedFilterCategories',
                'selectedCategoryIds'
            ));

        } //bookings
        elseif ($request->web_page == 'bookings') {

            $search = $request->has('search') ? $request['search'] : '';
            $queryParam = ['web_page' => $webPage, 'search' => $search];

            $provider = $this->provider->with('owner')->find($id);

            $bookings = $this->booking->where('provider_id', $id)
                ->with(['customer', 'details_amounts'])
                ->where(function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->where('readable_id', 'LIKE', '%' . $key . '%');
                    }
                })
                ->latest()
                ->paginate(pagination_limit())->appends($queryParam);

            return view('providermanagement::admin.provider.detail.bookings', compact('bookings', 'webPage', 'search', 'provider'));

        } //serviceman_list
        elseif ($request->web_page == 'serviceman_list') {
            $queryParam = ['web_page' => $webPage];

            $provider = $this->provider->with('owner')->find($id);

            $servicemen = $this->serviceman
                ->with(['user'])
                ->where('provider_id', $id)
                ->latest()
                ->paginate(pagination_limit())->appends($queryParam);

            return view('providermanagement::admin.provider.detail.serviceman-list', compact('servicemen', 'webPage', 'provider'));

        } //settings
        elseif ($request->web_page == 'settings') {
            $provider = $this->provider->with('owner')->find($id);
            return view('providermanagement::admin.provider.detail.settings', compact('webPage', 'provider'));

        } //bank_info
        elseif ($request->web_page == 'bank_information') {
            $provider = $this->provider->with('owner.account', 'bank_details', 'bank_detail')->find($id);
            return view('providermanagement::admin.provider.detail.bank-information', compact('webPage', 'provider'));

        } //reviews
        elseif ($request->web_page == 'reviews') {

            $search = $request->has('search') ? $request['search'] : '';
            $queryParam = ['search' => $search, 'web_page' => $request['web_page']];

            $provider = $this->provider->with(['reviews'])->where('user_id', $request->user()->id)->first();

            $reviews = $this->booking->with(['reviews.service'])
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    $query->whereHas('reviews', function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->where('review_comment', 'LIKE', '%' . $key . '%')
                                ->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                        }
                    });
                })
                ->whereHas('reviews', function ($query) use ($id) {
                    $query->where('provider_id', $id);
                })
                ->latest()
                ->paginate(pagination_limit())
                ->appends($queryParam);

            $provider = $this->provider->with('owner.account')->withCount(['bookings'])->find($id);

            $bookingOverview = DB::table('bookings')
                ->where('provider_id', $id)
                ->select('booking_status', DB::raw('count(*) as total'))
                ->groupBy('booking_status')
                ->get();

            $status = ['accepted', 'ongoing', 'completed', 'canceled'];
            $total = [];
            foreach ($status as $item) {
                if ($bookingOverview->where('booking_status', $item)->first() !== null) {
                    $total[] = $bookingOverview->where('booking_status', $item)->first()->total;
                } else {
                    $total[] = 0;
                }
            }


            return view('providermanagement::admin.provider.detail.reviews', compact('webPage', 'provider', 'reviews', 'search', 'provider', 'total'));

        }//reviews
        elseif ($request->web_page == 'performance') {
            $provider = $this->provider->with('owner.account')->find($id);
            $performanceService = app(ProviderPerformanceService::class);

            $metricsRow = $performanceService->getAggregatedProviderPerformanceMetrics([$id])->get($id);
            $metrics = (object) ($metricsRow ? (array) $metricsRow : []);

            $incidents = ProviderIncident::query()
                ->where('provider_id', $id)
                ->with(['createdBy', 'booking'])
                ->latest()
                ->paginate(20)
                ->withQueryString();

            return view('providermanagement::admin.provider.detail.performance', compact(
                'webPage',
                'provider',
                'metrics',
                'incidents'
            ));
        }
        elseif ($request->web_page == 'subscription') {

            $provider = $this->provider->with('owner')->where('id', $id)->first();
            $providerId = $provider->id;
            $subscriptionStatus = (int)((business_config('provider_subscription', 'provider_config'))->live_values);
            $commission = $provider->commission_status == 1 ? $provider->commission_percentage : (business_config('default_commission', 'business_information'))->live_values;
            $subscriptionDetails = $this->packageSubscriber->where('provider_id', $id)->first();
            $commissionTierForm = $this->commissionTierFormContext($provider);

            if ($subscriptionDetails){
                $subscriptionPrice = $this->subscriptionPackage->where('id', $subscriptionDetails?->subscription_package_id)->value('price');
                $vatPercentage      = (int)((business_config('subscription_vat', 'subscription_Setting'))->live_values ?? 0);

                $start = Carbon::parse($subscriptionDetails?->package_start_date)->subDay() ?? '';
                $end = Carbon::parse($subscriptionDetails?->package_end_date)?? '';
                $daysDifference = $start->diffInDays($end, false);

                $bookingCheck = $subscriptionDetails?->limits->where('provider_id', $id)->where('key', 'booking')->first();
                $categoryCheck = $subscriptionDetails?->limits->where('provider_id', $id)->where('key', 'category')->first();
                $isBookingLimit = $bookingCheck?->is_limited;
                $isCategoryLimit = $categoryCheck?->is_limited;

                $totalBill = $subscriptionDetails?->logs->where('provider_id', $providerId)->sum('package_price') ?? 0.00;
                $totalPurchase = $subscriptionDetails?->logs->where('provider_id', $providerId)->count() ?? 0;
                $calculationVat = $subscriptionPrice * ($vatPercentage / 100);
                $renewalPrice = $subscriptionPrice + $calculationVat;

                return view('providermanagement::admin.provider.detail.subscription', array_merge(
                    compact('webPage', 'provider', 'subscriptionDetails', 'daysDifference', 'bookingCheck', 'categoryCheck', 'isBookingLimit', 'isCategoryLimit', 'totalBill', 'totalPurchase', 'renewalPrice', 'commission', 'subscriptionStatus'),
                    $commissionTierForm
                ));
            }

            return view('providermanagement::admin.provider.detail.subscription', array_merge(
                compact('webPage', 'provider', 'subscriptionDetails', 'commission', 'subscriptionStatus'),
                $commissionTierForm
            ));

        }
        elseif ($request->web_page == 'payment') {
            $provider = $this->provider->with('owner.account')->find($id);
            $providerId = $provider->id;
            $providerBookingIds = DB::table('bookings')->where('provider_id', $providerId)->pluck('id')->toArray();
            $bookingIdsWithRepeats = DB::table('booking_repeats')->whereNotNull('booking_id')->distinct()->pluck('booking_id')->toArray();

            $oneTimeQuery = DB::table('bookings')->where('provider_id', $providerId)->where('booking_status', 'completed');
            if (!empty($bookingIdsWithRepeats)) {
                $oneTimeQuery->whereNotIn('id', $bookingIdsWithRepeats);
            }
            $completedOneTimeBookingIds = $oneTimeQuery->pluck('id');
            $totalRevenueFromBookings = 0.0;
            $oneTimeBookingsForRevenue = Booking::whereIn('id', $completedOneTimeBookingIds)->with('extra_services')->get();
            foreach ($oneTimeBookingsForRevenue as $b) {
                $totalRevenueFromBookings += get_booking_total_amount($b);
            }
            $totalRevenueFromRepeats = 0.0;
            $completedRepeatIds = collect();
            if (!empty($providerBookingIds)) {
                $completedRepeatIds = DB::table('booking_repeats')->where('booking_status', 'completed')->whereIn('booking_id', $providerBookingIds)->pluck('id');
                $repeatsForRevenue = BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking.extra_services')->get();
                foreach ($repeatsForRevenue as $r) {
                    $totalRevenueFromRepeats += get_booking_total_amount($r);
                }
            }
            $totalRevenue = $totalRevenueFromBookings + $totalRevenueFromRepeats;
            $totalCompanyCommission = (float) BookingDetailsAmount::whereIn('booking_id', $completedOneTimeBookingIds)->sum('admin_commission');
            $totalCompanyCommission += (float) BookingDetailsAmount::whereIn('booking_repeat_id', $completedRepeatIds)->sum('admin_commission');
            $providerNetEarning = $totalRevenue - $totalCompanyCommission;

            // Booking earning report: one row per completed booking/repeat (totals include extra_fee + extra_services)
            $bookingEarningReport = collect();
            $oneTimeBookings = Booking::whereIn('id', $completedOneTimeBookingIds)->with(['details_amounts', 'extra_services'])->get();
            foreach ($oneTimeBookings as $b) {
                $totalAmount = (float) get_booking_total_amount($b);
                $partsCharges = (float) get_booking_spare_parts_amount($b);
                $extraServicesTotal = (float) ($b->extra_services->sum('total') ?? 0);
                $extraServiceCharges = (float) ($b->extra_fee ?? 0) + ($extraServicesTotal - $partsCharges);
                $serviceCharges = (float) $b->total_booking_amount;
                $providerEarning = (float) $b->details_amounts->sum('provider_earning');
                $adminCommission = (float) $b->details_amounts->sum('admin_commission');
                $bookingEarningReport->push((object)[
                    'readable_id' => $b->readable_id ?? $b->id,
                    'total_amount' => $totalAmount,
                    'service_charges' => $serviceCharges,
                    'extra_service_charges' => $extraServiceCharges,
                    'parts_charges' => $partsCharges,
                    'provider_earning' => $providerEarning,
                    'admin_commission' => $adminCommission,
                ]);
            }
            $repeats = BookingRepeat::whereIn('id', $completedRepeatIds)->with(['details_amounts', 'booking.extra_services'])->get();
            foreach ($repeats as $r) {
                $totalAmount = (float) get_booking_total_amount($r);
                $partsCharges = (float) get_booking_spare_parts_amount($r);
                $extraServicesTotal = (float) ($r->booking->extra_services->sum('total') ?? 0);
                $extraServiceCharges = (float) ($r->extra_fee ?? 0) + ($extraServicesTotal - $partsCharges);
                $serviceCharges = (float) $r->total_booking_amount;
                $providerEarning = (float) $r->details_amounts->sum('provider_earning');
                $adminCommission = (float) $r->details_amounts->sum('admin_commission');
                $bookingEarningReport->push((object)[
                    'readable_id' => $r->readable_id ?? $r->id,
                    'total_amount' => $totalAmount,
                    'service_charges' => $serviceCharges,
                    'extra_service_charges' => $extraServiceCharges,
                    'parts_charges' => $partsCharges,
                    'provider_earning' => $providerEarning,
                    'admin_commission' => $adminCommission,
                ]);
            }

            $bookingReportPerPage = 20;
            $bookingReportPage = (int) $request->get('booking_page', 1);
            $bookingEarningReportPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $bookingEarningReport->forPage($bookingReportPage, $bookingReportPerPage)->values(),
                $bookingEarningReport->count(),
                $bookingReportPerPage,
                $bookingReportPage,
                ['path' => $request->url(), 'pageName' => 'booking_page']
            );
            $bookingEarningReportPaginated->withQueryString();

            // Provider ledger: only company↔provider flows (money company sent to provider or received from provider)
            $ledgerQuery = LedgerTransaction::query()
                ->where('provider_id', $providerId)
                ->with(['booking', 'repeat', 'creator', 'bookingPartialPayment'])
                ->orderByDesc('date')
                ->orderByDesc('created_at');
            $providerLedger = $ledgerQuery->paginate(20)->withQueryString();

            return view('providermanagement::admin.provider.detail.payment', compact('provider', 'webPage', 'totalRevenue', 'totalCompanyCommission', 'providerNetEarning', 'bookingEarningReportPaginated', 'providerLedger'));
        }
        return back();
    }

    /**
     * Record a manual payment from company to provider (Add Payment to Provider).
     * Reduces provider's account_receivable, creates ledger OUT and transactions.
     */
    public function addPaymentToProvider(string $id, Request $request): RedirectResponse
    {
        $this->authorize('provider_update');

        $provider = $this->provider->with('owner.account')->find($id);
        if (!$provider || !$provider->owner?->account) {
            Toastr::error(translate('Provider_or_account_not_found'));
            return back();
        }

        $maxAmount = (float) ($provider->owner->account->account_receivable ?? 0);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, $maxAmount)],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'reference_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            recordPaymentToProvider(
                (string) $provider->owner->id,
                (float) $validated['amount'],
                $validated['transaction_id'] ?? null,
                $validated['reference_note'] ?? null,
                $provider->id
            );
        } catch (\InvalidArgumentException $e) {
            Toastr::error($e->getMessage());
            return back();
        }

        Toastr::success(translate('Payment_recorded_successfully'));
        return redirect()->route('admin.provider.details', [$id, 'web_page' => 'payment']);
    }

    /**
     * Collect amount from provider (provider owes company). Records ledger IN and updates accounts.
     */
    public function collectAmountFromProvider(string $id, Request $request): RedirectResponse
    {
        $this->authorize('provider_update');

        $provider = $this->provider->with('owner.account')->find($id);
        if (!$provider || !$provider->owner?->account) {
            Toastr::error(translate('Provider_or_account_not_found'));
            return back();
        }

        $maxAmount = (float) ($provider->owner->account->account_payable ?? 0);
        if ($maxAmount <= 0) {
            Toastr::error(translate('No_amount_to_collect'));
            return back();
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . round($maxAmount, 2)],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'reference_note' => ['nullable', 'string', 'max:500'],
        ]);

        collectCashTransaction(
            $id,
            (float) $validated['amount'],
            $validated['transaction_id'] ?? null,
            $validated['reference_note'] ?? null
        );

        Toastr::success(translate('Amount_collected_successfully'));
        return redirect()->route('admin.provider.details', [$id, 'web_page' => 'payment']);
    }

    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function updateAccountInfo($id, Request $request): RedirectResponse
    {
        $this->authorize('provider_update');

        $validated = $request->validate([
            'bank_detail_id' => 'nullable|uuid|exists:bank_details,id',
            'bank_name' => 'nullable|string|max:191',
            'acc_no' => 'required|string|max:191',
            'acc_holder_name' => 'required|string|max:191',
            'routing_number' => 'required|string|max:191',
        ]);

        $providerId = $id;
        $payload = [
            'bank_name' => $validated['bank_name'],
            'acc_no' => $validated['acc_no'],
            'acc_holder_name' => $validated['acc_holder_name'],
            'routing_number' => $validated['routing_number'],
        ];

        if (!empty($validated['bank_detail_id'])) {
            $this->bank_detail
                ->where('provider_id', $providerId)
                ->where('id', $validated['bank_detail_id'])
                ->update($payload);
        } else {
            $this->bank_detail::create(array_merge($payload, ['provider_id' => $providerId]));
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }


    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deleteAccountInfo($id, Request $request): JsonResponse
    {
        $this->authorize('provider_delete');

        $provider = $this->provider->with(['bank_detail'])->find($id);

        if (!$provider->bank_detail) {
            return response()->json(response_formatter(DEFAULT_404), 200);
        }
        $provider->bank_detail->delete();
        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }


    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return JsonResponse
     */
    public function updateSubscription($id): JsonResponse
    {
        $subscribedService = $this->subscribedService->find($id);
        $this->subscribedService->where('id', $id)->update(['is_subscribed' => !$subscribedService->is_subscribed]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }


    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Application|Factory|View
     */
    public function edit(string $id): View|Factory|Application
    {
        $this->authorize('provider_update');

        $zones = $this->zone->ofStatus(1)->get();
        $zoneTree = $this->zoneTreeForProviderForm();
        $provider = $this->provider->with(['owner', 'zone', 'zones'])->find($id);
        $commission = (int)((business_config('provider_commision', 'provider_config'))->live_values ?? null);
        $subscription = (int)((business_config('provider_subscription', 'provider_config'))->live_values ?? null);
        $duration = (int)((business_config('free_trial_period', 'subscription_Setting'))->live_values ?? null);
        $freeTrialStatus = (int)((business_config('free_trial_period', 'subscription_Setting'))->is_active ?? 0);
        $subscriptionPackages = $this->subscriptionPackage->OfStatus(1)->with('subscriptionPackageFeature', 'subscriptionPackageLimit')->get();
        $formattedPackages = $subscriptionPackages->map(function ($subscriptionPackage) {
            return formatSubscriptionPackage($subscriptionPackage, PACKAGE_FEATURES);
        });
        $packageSubscription = $this->packageSubscriber->where('provider_id', $id)->first();
        $providerFormDraft = $this->getProviderFormDraftManifest('edit_' . $id);
        $existingAdditionalDocuments = DB::table('providers_additional_documents')
            ->where('provider_id', $id)
            ->orderBy('created_at')
            ->get();
        $existingAdditionalDocumentFiles = collect();
        if ($existingAdditionalDocuments->isNotEmpty()) {
            $existingAdditionalDocumentFiles = DB::table('providers_additional_document_files')
                ->whereIn('document_id', $existingAdditionalDocuments->pluck('id'))
                ->orderBy('created_at')
                ->get()
                ->groupBy('document_id');
        }

        return view('providermanagement::admin.provider.edit', compact(
            'provider',
            'zones',
            'zoneTree',
            'commission',
            'subscription',
            'formattedPackages',
            'duration',
            'freeTrialStatus',
            'packageSubscription',
            'providerFormDraft',
            'existingAdditionalDocuments',
            'existingAdditionalDocumentFiles'
        ));
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('provider_update');

        $formKey = 'edit_' . $id;
        $this->attachProviderFormDraftToRequest($request, $formKey);

        $preserveDraft = function () use ($request, $formKey) {
            $this->persistProviderFormDraftAfterFailedValidation($request, $formKey);
        };

        $check = $this->validateUploadedFile($request, ['logo', 'contact_person_photo'], 'image', $preserveDraft);
        if ($check !== true) {
            return $check;
        }

        $provider = $this->provider->with(['owner', 'zones'])->find($id);

        if (! $request->filled('contact_person_email')) {
            $request->merge(['contact_person_email' => null]);
        }

        $this->mergeLegacyZoneIdIntoZoneIds($request);

        $allowedImageMimes = implode(',', array_column(IMAGEEXTENSION, 'key'));

        try {
            Validator::make($request->all(), [
            'provider_type' => 'required|in:company,individual',

            'contact_person_name' => 'required|string|max:191',
            'contact_person_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|unique:users,phone,' . $provider->user_id,
            'contact_person_email' => [
                'nullable',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($provider->user_id),
            ],

            'password' => !is_null($request->password) ? 'string|min:8' : '',
            'confirm_password' => !is_null($request->password) ? 'required|same:password' : '',

            'company_name' => 'required_if:provider_type,company|string|max:191',
            'company_phone' => 'required_if:provider_type,company|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'company_address' => 'required',
            'company_email' => 'required_if:provider_type,company|email',
            'logo' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'contact_person_photo' => 'nullable|image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),

            // Contact person identity (Box 5)
            'identity_type' => 'required|in:passport,driving_license,nid',
            'identity_number' => 'required',
            'identity_images' => 'array',
            'identity_images.*' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),

            'identity_pdf_files' => 'nullable|array',
            'identity_pdf_files.*' => 'file|mimes:pdf|max:' . uploadMaxFileSizeInKB('file'),

            // Company identity docs & identity (Box 3)
            'company_identity_type' => 'required_if:provider_type,company|in:trade_license,company_id',
            'company_identity_number' => 'required_if:provider_type,company|string|max:191',
            'company_identity_images' => 'array',
            'company_identity_images.*' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'company_identity_pdf_files' => 'nullable|array',
            'company_identity_pdf_files.*' => 'file|mimes:pdf|max:' . uploadMaxFileSizeInKB('file'),

            'additional_documents' => 'nullable|array',
            'additional_documents.*.name' => 'nullable|string|max:191',
            'additional_documents.*.description' => 'nullable|string',
            'additional_documents.*.files' => 'nullable|array',
            'additional_documents.*.files.*' => 'file|max:' . uploadMaxFileSizeInKB('file') . '|mimes:' . $allowedImageMimes . ',pdf',
            'latitude' => 'required',
            'longitude' => 'required',

            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'uuid',
            'zone_excluded_ids' => 'nullable|array',
            'zone_excluded_ids.*' => 'uuid',
        ])->validate();
        } catch (ValidationException $e) {
            $this->persistProviderFormDraftAfterFailedValidation($request, $formKey);
            throw $e;
        }

        $leafZoneIds = $this->normalizedProviderLeafZoneIdsFromRequest($request);
        if ($leafZoneIds === []) {
            Toastr::error(translate('Select_Zone'));

            return $this->backWithInputAndDraft($request, $formKey);
        }

        $previousLeafIds = $provider->zones()->pluck('zones.id')->sort()->values()->all();
        if ($previousLeafIds !== collect($leafZoneIds)->sort()->values()->all()) {
            DB::table('subscribed_services')->where('provider_id', $provider->id)->update(['is_subscribed' => 0]);
        }

        // Contact identity is required (Box 5) unless provider already has saved identity docs.
        $hasContactImages = $request->has('identity_images') && is_array($request->identity_images) && count($request->identity_images) > 0;
        $ownerHasExistingIdentity = $provider->owner && is_array($provider->owner->identification_image) && count($provider->owner->identification_image) > 0;
        if (!$hasContactImages && !$ownerHasExistingIdentity) {
            Toastr::error(translate('Please upload at least one contact identity image'));

            return $this->backWithInputAndDraft($request, $formKey);
        }

        // Company identity is required only for provider_type=company.
        if ($request->provider_type === 'company') {
            $hasCompanyImages = $request->has('company_identity_images') && is_array($request->company_identity_images) && count($request->company_identity_images) > 0;
            $providerHasExistingCompanyIdentity = is_array($provider->company_identity_images) && count($provider->company_identity_images) > 0;

            if (!$hasCompanyImages && !$providerHasExistingCompanyIdentity) {
                Toastr::error(translate('Please upload at least one company identity image'));

                return $this->backWithInputAndDraft($request, $formKey);
            }
        }

        if ($request->plan_type == 'subscription_based'){
            $package = $this->subscriptionPackage->where('id',$request->selected_package_id)->ofStatus(1)->first();
            $vatPercentage      = (int)((business_config('subscription_vat', 'subscription_Setting'))->live_values ?? 0);
            if (!$package){
                Toastr::error(translate('Please Select valid plan'));

                return $this->backWithInputAndDraft($request, $formKey);
            }

            $packageId          = $package?->id;
            $price              = $package?->price;
            $name               = $package?->name;
        }

        $identityImages = [];
        if (! is_null($request->identity_images)) {
            foreach ($request->identity_images as $image) {
                if (! $image) {
                    continue;
                }
                $imageName = file_uploader('provider/identity/', APPLICATION_IMAGE_FORMAT, $image);
                $identityImages[] = ['image'=>$imageName, 'storage'=> getDisk()];
            }
        }

        if (! is_null($request->identity_pdf_files)) {
            foreach ($request->identity_pdf_files as $pdf) {
                if (! $pdf) {
                    continue;
                }
                $pdfName = file_uploader('provider/identity/', 'pdf', $pdf);
                $identityImages[] = ['image'=>$pdfName, 'storage'=> getDisk()];
            }
        }

        $companyIdentityImages = [];
        if (! is_null($request->company_identity_images)) {
            foreach ($request->company_identity_images as $image) {
                if (! $image) {
                    continue;
                }
                $imageName = file_uploader('provider/company-identity/', APPLICATION_IMAGE_FORMAT, $image);
                $companyIdentityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        if (! is_null($request->company_identity_pdf_files)) {
            foreach ($request->company_identity_pdf_files as $pdf) {
                if (! $pdf) {
                    continue;
                }
                $pdfName = file_uploader('provider/company-identity/', 'pdf', $pdf);
                $companyIdentityImages[] = ['image' => $pdfName, 'storage' => getDisk()];
            }
        }

        $provider->provider_type = $request->provider_type;
        if ($request->provider_type === 'company') {
            $provider->company_name = $request->company_name;
            $provider->company_phone = $request->company_phone;
            $provider->company_email = $request->company_email;
        } else {
            $provider->company_name = $request->contact_person_name;
            $provider->company_phone = $request->contact_person_phone;
            $provider->company_email = $request->contact_person_email;
        }

        if ($request->has('logo')) {
            $provider->logo = file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }
        $provider->company_address = $request->company_address;

        if ($request->provider_type === 'company') {
            $provider->company_identity_type = $request->company_identity_type;
            $provider->company_identity_number = $request->company_identity_number;
            if (count($companyIdentityImages) > 0) {
                $provider->company_identity_images = $companyIdentityImages;
            }
        } else {
            $provider->company_identity_type = null;
            $provider->company_identity_number = null;
            $provider->company_identity_images = [];
        }
        $provider->contact_person_name = $request->contact_person_name;
        $provider->contact_person_phone = $request->contact_person_phone;
        $provider->contact_person_email = $request->contact_person_email;
        if ($request->has('contact_person_photo')) {
            $provider->contact_person_photo = file_uploader('provider/contact_person_photo/', APPLICATION_IMAGE_FORMAT, $request->file('contact_person_photo'));
        }
        $provider->zone_id = $leafZoneIds[0];
        $provider->coordinates = ['latitude' => $request['latitude'], 'longitude' => $request['longitude']];

        $owner = $provider->owner()->first();
        // Account (owner) information is derived from contact person by default.
        $owner->email = $request->contact_person_email;
        $owner->phone = $request->contact_person_phone;
        $owner->identification_number = $request->identity_number;
        $owner->identification_type = $request->identity_type;
        if (count($identityImages) > 0) {
            $owner->identification_image = $identityImages;
        }
        if (!is_null($request->password)) {
            $owner->password = bcrypt($request->password);
        }
        $owner->user_type = 'provider-admin';

        if ($provider->is_approved == '2' || $provider->is_approved == '0') {
            $provider->is_approved = 1;
            $provider->is_active = 1;
            $owner->is_active = 1;

            $emailStatus = business_config('email_config_status', 'email_config')->live_values;

            if ($emailStatus){
                try {
                    Mail::to($provider?->owner?->email)->send(new RegistrationApprovedMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }

        }

        DB::transaction(function () use ($provider, $owner, $leafZoneIds) {
            $owner->save();
            $owner->zones()->sync($leafZoneIds);
            $provider->save();
            $provider->zones()->sync(
                collect($leafZoneIds)->mapWithKeys(fn (string $zid) => [$zid => []])->all()
            );
        });

        // Upload additional documents (optional) - replace existing on edit.
        if ($request->has('additional_documents')) {
            $additionalDocuments = $request->input('additional_documents', []);
            $existingDocsBeforeReplace = DB::table('providers_additional_documents')
                ->where('provider_id', $id)
                ->get(['id']);
            $existingDocFileMap = [];
            if ($existingDocsBeforeReplace->isNotEmpty()) {
                $existingDocFiles = DB::table('providers_additional_document_files')
                    ->whereIn('document_id', $existingDocsBeforeReplace->pluck('id'))
                    ->get(['document_id', 'file_path', 'storage']);
                foreach ($existingDocFiles as $existingFile) {
                    $existingDocFileMap[$existingFile->document_id][] = [
                        'file_path' => $existingFile->file_path,
                        'storage' => $existingFile->storage,
                    ];
                }
            }

            if (is_array($additionalDocuments) && count($additionalDocuments) > 0) {
                foreach ($additionalDocuments as $docIndex => $doc) {
                    $docName = trim($doc['name'] ?? '');
                    $docDescription = $doc['description'] ?? null;
                    $existingDocumentId = (string) ($doc['existing_document_id'] ?? '');
                    $files = $request->file('additional_documents.' . $docIndex . '.files', []);
                    if (! is_array($files)) {
                        $files = $files ? [$files] : [];
                    }

                    if (!$docName && empty($files)) {
                        continue;
                    }

                    $hasExistingFiles = $existingDocumentId !== '' && !empty($existingDocFileMap[$existingDocumentId] ?? []);
                    if (empty($files) && !$hasExistingFiles) {
                        Toastr::error(translate('Please upload at least one file for document'));

                        return $this->backWithInputAndDraft($request, $formKey);
                    }
                }
            }

            DB::table('providers_additional_documents')->where('provider_id', $id)->delete();

            if (is_array($additionalDocuments) && count($additionalDocuments) > 0) {
                foreach ($additionalDocuments as $docIndex => $doc) {
                    $docName = trim($doc['name'] ?? '');
                    $docDescription = $doc['description'] ?? null;
                    $existingDocumentId = (string) ($doc['existing_document_id'] ?? '');
                    $files = $request->file('additional_documents.' . $docIndex . '.files', []);
                    if (! is_array($files)) {
                        $files = $files ? [$files] : [];
                    }
                    $hasExistingFiles = $existingDocumentId !== '' && !empty($existingDocFileMap[$existingDocumentId] ?? []);

                    if (!$docName && empty($files) && !$hasExistingFiles) {
                        continue;
                    }

                    $documentId = (string) \Illuminate\Support\Str::uuid();
                    DB::table('providers_additional_documents')->insert([
                        'id' => $documentId,
                        'provider_id' => $provider->id,
                        'document_name' => $docName,
                        'document_description' => $docDescription,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($hasExistingFiles) {
                        foreach (($existingDocFileMap[$existingDocumentId] ?? []) as $existingFileRow) {
                            $existingFilePath = (string) ($existingFileRow['file_path'] ?? '');
                            if ($existingFilePath !== '' && !str_contains($existingFilePath, '/')) {
                                // Preserve original physical location when historical rows stored filename only.
                                $existingFilePath = 'provider/additional-documents/' . $existingDocumentId . '/' . $existingFilePath;
                            }
                            DB::table('providers_additional_document_files')->insert([
                                'id' => (string) \Illuminate\Support\Str::uuid(),
                                'document_id' => $documentId,
                                'file_path' => $existingFilePath,
                                'storage' => $existingFileRow['storage'] ?? 'public',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    foreach ($files as $file) {
                        if (!$file) continue;
                        $extension = $file->getClientOriginalExtension() ?: 'bin';
                        $filePath = file_uploader(
                            'provider/additional-documents/' . $documentId,
                            $extension,
                            $file
                        );

                        DB::table('providers_additional_document_files')->insert([
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'document_id' => $documentId,
                            'file_path' => $filePath,
                            'storage' => getDisk(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        if ($request->plan_type == 'subscription_based') {
            $provider_id = optional($provider)->id;
            $result = true;

            $packageSubscription = $this->packageSubscriber->where('provider_id', $id)->first();

            if ($packageSubscription === null || $packageSubscription->subscription_package_id != $packageId) {

                if ($request->plan_price == 'received_money') {

                    $payment = $this->paymentRequest;
                    $payment->payment_amount = $price;
                    $payment->success_hook = 'subscription_success';
                    $payment->failure_hook = 'subscription_fail';
                    $payment->payer_id = $provider->user_id;
                    $payment->payment_method = 'manually';
                    $payment->additional_data = json_encode($request->all());
                    $payment->attribute = 'provider-reg';
                    $payment->attribute_id = $provider_id;
                    $payment->payment_platform = 'web';
                    $payment->is_paid = 1;
                    $payment->save();
                    $request['payment_id'] = $payment->id;

                    $result = $packageSubscription === null
                        ? $this->handlePurchasePackageSubscription($packageId, $provider_id, $request->all(), $price, $name)
                        : $this->handleShiftPackageSubscription($packageId, $provider_id, $request->all(), $price, $name);
                } elseif ($request->plan_price == 'free_trial') {
                    $result = $this->handleFreeTrialPackageSubscription($packageId, $provider_id, $price, $name);
                } else {
                    Toastr::error(translate('Invalid plan price'));

                    return $this->backWithInputAndDraft($request, $formKey);
                }
            }

            if (!$result) {
                Toastr::error(translate('Something went wrong'));

                return $this->backWithInputAndDraft($request, $formKey);
            }
        }

        if ($request->plan_type == 'commission_based'){
            $this->packageSubscriber->where('provider_id', $id)->delete();
        }


        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        $this->clearProviderFormDraft($formKey);

        return redirect()->route('admin.provider.edit', [$id])->with('provider_updated', [
            'id' => $provider->id,
            'name' => (string) ($provider->company_name ?: $provider->contact_person_name),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $this->authorize('provider_delete');

        Validator::make($request->all(), [
            'provider_id' => 'required'
        ]);

        $providers = $this->provider->where('id', $id);
        if ($providers->count() > 0) {
            foreach ($providers->get() as $provider) {
                file_remover('provider/logo/', $provider->logo);
                if (!empty($provider->owner->identification_image)) {
                    foreach ($provider->owner->identification_image as $image) {
                        file_remover('provider/identity/', $image);
                    }
                }

                $provider->servicemen->each(function ($serviceman) {
                    $serviceman->user->update(['is_active' => 0]);
                });

                $provider->owner()->delete();
            }
            $providers->delete();
            Toastr::success(translate(DEFAULT_DELETE_200['message']));
            return back();
        }

        Toastr::error(translate(DEFAULT_FAIL_200['message']));
        return back();
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return JsonResponse
     */
    public function statusUpdate($id): JsonResponse
    {
        $this->authorize('provider_manage_status');

        $provider = $this->provider->where('id', $id)->first();
        $this->provider->where('id', $id)->update(['is_active' => !$provider->is_active]);
        $owner = $this->owner->where('id', $provider->user_id)->first();
        $owner->is_active = !$provider->is_active;
        $owner->save();

        $emailStatus = business_config('email_config_status', 'email_config')->live_values;
        if ($owner?->is_active == 1) {
            if ($emailStatus){
                try {
                    Mail::to($provider?->owner?->email)->send(new AccountUnsuspendMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }
        } else {
            if ($emailStatus) {
                try {
                    Mail::to($provider?->owner?->email)->send(new AccountSuspendMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }

        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return JsonResponse
     */
    public function serviceAvailability($id): JsonResponse
    {
        $this->authorize('provider_manage_status');

        $provider = $this->provider->where('id', $id)->first();
        $this->provider->where('id', $id)->update(['service_availability' => !$provider->service_availability]);
        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Toggle provider visibility in customer app listing APIs.
     * @param $id
     * @return JsonResponse
     */
    public function appAvailability($id): JsonResponse
    {
        $this->authorize('provider_manage_status');

        $provider = $this->provider->where('id', $id)->first();
        $this->provider->where('id', $id)->update(['app_availability' => !$provider->app_availability]);
        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return JsonResponse
     */
    public function suspendUpdate($id): JsonResponse
    {
        $this->authorize('provider_manage_status');

        $provider = $this->provider->where('id', $id)->first();
        $this->provider->where('id', $id)->update(['is_suspended' => !$provider->is_suspended]);
        $provider_info = $this->provider->where('id', $id)->first();

        if ($provider_info?->is_suspended == '1') {
            $provider = $provider_info?->owner;
            $title = get_push_notification_message('provider_suspend', 'provider_notification', $provider?->current_language_key);
            if ($provider?->fcm_token && $title) {
                device_notification($provider?->fcm_token, $title, null, null, $provider_info->id, 'suspend');
            }

            $emailStatus = business_config('email_config_status', 'email_config')->live_values;

            if ($emailStatus){
                try {
                    Mail::to($provider?->owner?->email)->send(new AccountSuspendMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }

        } else {
            $provider = $provider_info?->owner;
            $title = get_push_notification_message('provider_suspension_remove', 'provider_notification', $provider?->current_language_key);
            if ($provider?->fcm_token && $title) {
                device_notification($provider?->fcm_token, $title, null, null, $provider_info->id, 'suspend');
            }

            $emailStatus = business_config('email_config_status', 'email_config')->live_values;

            if ($emailStatus){
                try {
                    Mail::to($provider?->owner?->email)->send(new AccountUnsuspendMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }

        }

        return response()->json(response_formatter(DEFAULT_SUSPEND_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @param Request $request
     * @return RedirectResponse
     */
    public function commissionUpdate($id, Request $request): RedirectResponse
    {
        $this->authorize('provider_manage_status');

        $request->validate([
            'commission_status' => 'required|in:default,custom',
        ]);

        $provider = $this->provider->where('id', $id)->first();

        $hadCustom = $provider && (int) $provider->commission_status === 1;
        $wantsCustom = $request->commission_status === 'custom';
        if (($hadCustom || $wantsCustom) && ! Gate::allows('commission_custom_provider_update')) {
            abort(403);
        }

        if ($request->commission_status === 'custom') {
            $rules = [
                'commission_service_mode' => 'required|in:fixed,tiered',
                'commission_spare_mode' => 'required|in:fixed,tiered',
                'commission_service_fixed_amount' => 'nullable|numeric|min:0',
                'commission_spare_fixed_amount' => 'nullable|numeric|min:0',
                'commission_service_tiers' => 'nullable|array',
                'commission_service_tiers.*.from' => 'nullable|numeric|min:0',
                'commission_service_tiers.*.to' => 'nullable|numeric|min:0',
                'commission_service_tiers.*.amount_type' => 'nullable|in:percentage,fixed',
                'commission_service_tiers.*.amount' => 'nullable|numeric|min:0',
                'commission_spare_tiers' => 'nullable|array',
                'commission_spare_tiers.*.from' => 'nullable|numeric|min:0',
                'commission_spare_tiers.*.to' => 'nullable|numeric|min:0',
                'commission_spare_tiers.*.amount_type' => 'nullable|in:percentage,fixed',
                'commission_spare_tiers.*.amount' => 'nullable|numeric|min:0',
            ];
            Validator::make($request->all(), $rules)
                ->after(fn (\Illuminate\Validation\Validator $v) => CommissionTierPayload::validateGroups($v, $request, CommissionTierPayload::defaultValidationGroups()))
                ->validate();

            $serviceGroup = CommissionTierPayload::normalizeGroupFromRequest(
                $request,
                'commission_service_mode',
                'commission_service_fixed_amount',
                'commission_service_tiers'
            );
            $spareGroup = CommissionTierPayload::normalizeGroupFromRequest(
                $request,
                'commission_spare_mode',
                'commission_spare_fixed_amount',
                'commission_spare_tiers'
            );
            $tierSetup = [
                'service' => $serviceGroup,
                'spare_parts' => $spareGroup,
            ];
            $provider->commission_status = 1;
            $provider->commission_tier_setup = $tierSetup;
            $provider->commission_percentage = (float) derive_default_commission_percentage_from_service_group($serviceGroup);
        } else {
            $provider->commission_status = 0;
        }
        $provider->save();

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }

    /**
     * @return array{tierService: array, tierSpare: array, previewCurrencySymbol: string, previewCurrencyCode: string}
     */
    protected function commissionTierFormContext(Provider $provider): array
    {
        return CommissionEntitySetup::tierFormContext(
            is_array($provider->commission_tier_setup) ? $provider->commission_tier_setup : [],
            (int) $provider->commission_status === 1
        );
    }

    public function onboardingRequest(Request $request): Factory|View|Application
    {

        $this->authorize('onboarding_request_view');

        $status = $request->status == 'denied' ? 'denied' : 'onboarding';
        $search = $request['search'];
        $queryParam = ['status' => $status, 'search' => $request['search']];

        $providers = $this->provider->with(['owner', 'zone'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('company_name', 'LIKE', '%' . $key . '%')
                        ->orWhere('contact_person_name', 'LIKE', '%' . $key . '%')
                        ->orWhere('contact_person_phone', 'LIKE', '%' . $key . '%')
                        ->orWhere('contact_person_email', 'LIKE', '%' . $key . '%');
                }
            })
            ->ofApproval($status == 'onboarding' ? 2 : 0)
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParam);

        $providersCount = [
            'onboarding' => $this->provider->ofApproval(2)->get()->count(),
            'denied' => $this->provider->ofApproval(0)->get()->count(),
        ];

        return View('providermanagement::admin.provider.onboarding', compact('providers', 'search', 'status', 'providersCount'));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @param Request $request
     * @return View|\Illuminate\Foundation\Application|Factory|Application
     */
    public function onboardingDetails($id, Request $request): View|\Illuminate\Foundation\Application|Factory|Application
    {
        $this->authorize('onboarding_request_view');
        $provider = $this->provider->with('owner.account')->withCount(['bookings'])->find($id);
        return view('providermanagement::admin.provider.detail.onboarding-details', compact('provider'));
    }

    public function updateApproval($id, $status, Request $request): JsonResponse
    {
        $this->authorize('onboarding_request_manage_status');

        $emailStatus = business_config('email_config_status', 'email_config')->live_values;

        if ($status == 'approve') {
            $this->provider->where('id', $id)->update(['is_active' => 1, 'is_approved' => 1]);
            $provider = $this->provider->with('owner')->where('id', $id)->first();
            $provider->owner->is_active = 1;
            $provider->owner->save();

            $approval  = isNotificationActive(null, 'registration', 'email', 'provider');
            if ($approval && $emailStatus) {
                try {
                    Mail::to($provider?->owner?->email)->send(new RegistrationApprovedMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }

        } elseif ($status == 'deny') {
            $this->provider->where('id', $id)->update(['is_active' => 0, 'is_approved' => 0]);
            $provider = $this->provider->with('owner')->where('id', $id)->first();
            $provider->owner->is_active = 0;
            $provider->owner->save();
            $deny  = isNotificationActive(null, 'registration', 'email', 'provider');
            if ($deny && $emailStatus) {
                try {
                    Mail::to($provider?->owner?->email)->send(new RegistrationDeniedMail($provider));
                } catch (\Exception $exception) {
                    info($exception);
                }
            }

        } else {
            return response()->json(response_formatter(DEFAULT_400), 200);
        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return string|StreamedResponse
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('provider_delete');

        $items = $this->provider->with(['owner', 'zone'])->where(['is_approved' => 1])->withCount(['subscribed_services', 'bookings'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->ofApproval(1)
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->latest()
            ->latest()->get();

        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    public function reviewsDownload(Request $request)
    {
        $items = $this->review->with(['booking'])
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                foreach ($keys as $key) {
                    $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                }
            })
            ->where('provider_id', $request->provider_id)
            ->latest()
            ->get();
        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    public function availableProviderList(Request $request): JsonResponse
    {
        $sort_by = $request->input('sort_by', 'default');
        $search = $request->search;
        $bookingId = $request->booking_id;
        $booking = $this->booking->where('id', $bookingId)->first();

        if (!isset($booking)) {
            $bookingRepeat = $this->bookingRepeat->where('id', $bookingId)->first();
            if ($bookingRepeat) {
                $booking = $this->booking->where('id', $bookingRepeat->booking_id)->first();
                if ($booking) {
                    $bookingId = $bookingRepeat->booking_id;
                }
            }
        }

        if (! $booking) {
            return response()->json(['view' => '', 'message' => 'Booking not found'], 404);
        }

        $allProviders = $this->provider
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($sort_by === 'top-rated', function ($query) {
                return $query->orderBy('avg_rating', 'desc');
            })
            ->when($sort_by === 'bookings-completed', function ($query) {
                $query->withCount(['bookings' => function ($query) {
                    $query->where('booking_status', 'completed');
                }]);
                $query->orderBy('bookings_count', 'desc');
            })
            ->when($sort_by !== 'bookings-completed', function ($query) {
                return $query->withCount('bookings');
            })
            ->when(filled($booking->sub_category_id), function ($query) use ($booking) {
                $query->whereHas('subscribed_services', function ($q) use ($booking) {
                    $q->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
                });
            })
            ->coveringLeafZone($booking->zone_id)
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->where('service_availability', 1)
            ->where('is_active_for_jobs', 1)
            ->when($sort_by === 'default', function ($q) {
                // Deprioritize "warning" providers compared to "active" providers.
                $q->orderByRaw("CASE performance_status WHEN 'active' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END");
            })
            ->withCount('reviews')
            ->ofApproval(1)
            ->ofStatus(1)
            ->get();

        $providers = [];

        foreach ($allProviders as $provider) {
            if (provider_accepts_booking_service_location($provider->id, $booking->service_location)) {
                $providers[] = $provider;
            }
        }

        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'detail.service.category', 'detail.service.subCategory', 'detail.variation', 'customer', 'provider', 'service_address', 'serviceman', 'service_address', 'status_histories.user'])->find($bookingId);

        return response()->json([
            'view' => view('bookingmodule::admin.booking.partials.details.provider-info-modal-data', compact('providers', 'booking', 'search', 'sort_by'))->render(),
        ]);
    }

    public function providerInfo(Request $request): JsonResponse
    {
        $booking = $this->booking->where('id', $request->booking_id)->first();

        return response()->json([
            'view' => view('providermanagement::admin.partials.details._provider-data', compact('booking'))->render(),
            'serviceman_view' => view('providermanagement::admin.partials.details._serviceman-data', compact('booking'))->render(),
        ]);
    }

    public function reassignProvider(Request $request): JsonResponse
    {
        $changedBy = $request->user()->id;
        $providerId = $request->provider_id;

        if (!$providerId || !$request->booking_id) {
            return response()->json(['message' => 'Invalid request data'], 400);
        }

        $sort_by = $request->input('sort_by', 'default');
        $search = $request->search;

        $booking = $this->booking->find($request->booking_id);
        $bookingRepeat = $this->bookingRepeat->where('id', $request->booking_id)->with('booking')->first();

        if ($booking) {
            $oldProviderId = $booking->provider_id;
            $this->updateBooking($booking, $providerId, $changedBy);

            if (!is_null($booking->repeat)) {
                $this->updateRepeatBookings($booking->repeat, $providerId, $booking->provider_id ? 1 : 0);
            }

            if ((string) $oldProviderId !== (string) $providerId) {
                $previousProvider = $oldProviderId ? $this->provider->with('owner')->find($oldProviderId) : null;
                $booking->refresh();
                $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                    ->sendBookingProviderChange($booking, $previousProvider);
            }

            $this->sendProviderNotification($providerId, $booking->id, 'booking');
            $providers = $this->fetchProviders($request, $booking);

            return response()->json([
                'view' => view('bookingmodule::admin.booking.partials.details.provider-info-modal-data', compact('providers', 'booking', 'search', 'sort_by'))->render(),
            ]);
        }

        if ($bookingRepeat) {
            $bookingRepeat->loadMissing('booking');
            $oldProviderId = $bookingRepeat->booking?->provider_id;
            $this->updateBookingRepeat($bookingRepeat, $providerId, $changedBy);

            if ((string) $oldProviderId !== (string) $providerId) {
                $mainBooking = $this->booking->find($bookingRepeat->booking_id);
                if ($mainBooking) {
                    $previousProvider = $oldProviderId ? $this->provider->with('owner')->find($oldProviderId) : null;
                    $mainBooking->refresh();
                    $mainBooking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingProviderChange($mainBooking, $previousProvider);
                }
            }

            $this->sendProviderNotification($providerId, $bookingRepeat->id, 'repeat');
            $providers = $this->fetchProviders($request, $bookingRepeat->booking);

            return response()->json([
                'view' => view('bookingmodule::admin.booking.partials.details.provider-info-modal-data', [
                    'providers' => $providers,
                    'booking' => $bookingRepeat,
                    'search' => $search,
                    'sort_by' => $sort_by,
                ])->render(),
            ]);
        }

        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    private function updateBooking($booking, $providerId, $changedBy): void
    {
        $booking->update([
            'provider_id' => $providerId,
            'serviceman_id' => null,
            'booking_status' => 'accepted',
        ]);

        $this->bookingStatusHistory->create([
            'booking_id' => $booking->id,
            'changed_by' => $changedBy,
            'booking_status' => 'accepted',
        ]);
    }

    private function updateRepeatBookings($repeats, $providerId, $isReassign): void
    {
        foreach ($repeats->whereIn('booking_status', ['pending', 'accepted', 'ongoing']) as $repeat) {
            $repeat->update([
                'provider_id' => $providerId,
                'serviceman_id' => null,
                'booking_status' => 'accepted',
                'is_reassign' => $isReassign,
            ]);
        }
    }

    private function updateBookingRepeat($bookingRepeat, $providerId, $changedBy): void
    {
        $allBookingRepeat = $this->bookingRepeat->where('booking_id', $bookingRepeat->booking_id)->get();
        foreach ($allBookingRepeat as $item){
            $item->update([
                'provider_id' => $providerId,
                'serviceman_id' => null,
                'booking_status' => 'accepted',
            ]);

            $this->bookingStatusHistory->create([
                'booking_id' => 0,
                'booking_repeat_id' => $item->id,
                'changed_by' => $changedBy,
                'booking_status' => 'accepted',
            ]);
        }

        if ($bookingRepeat->booking) {
            $this->updateBooking($bookingRepeat->booking, $providerId, $changedBy);
        }
    }

    private function sendProviderNotification($providerId, $bookingId, $type): void
    {
        $provider = $this->provider->with('owner')->find($providerId);

        if ($provider && isset($provider->owner)) {
            $fcmToken = $provider->owner->fcm_token;
            $languageKey = $provider->owner->current_language_key;

            $bookingNotificationStatus = business_config('booking', 'notification_settings')->live_values;
            if ($fcmToken && $bookingNotificationStatus['push_notification_booking']) {
                $readableId = $this->booking->where('id', $bookingId)->value('readable_id');
                $title = translate('Admin has assigned you booking ID') . ' ' . $readableId;
                device_notification($fcmToken, $title, null, null, $bookingId, 'booking', '', '', '', '', $type);
            }
        }
    }

    private function fetchProviders(Request $request, Booking $booking)
    {
        $allProviders = $this->provider
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->search);
                $query->where(function ($q) use ($keys) {
                    foreach ($keys as $key) {
                        $q->orWhere('company_phone', 'LIKE', "%{$key}%")
                            ->orWhere('company_email', 'LIKE', "%{$key}%")
                            ->orWhere('company_name', 'LIKE', "%{$key}%");
                    }
                });
            })
            ->when($request->sort_by === 'top-rated', fn ($q) => $q->orderBy('avg_rating', 'desc'))
            ->when($request->sort_by === 'bookings-completed', function ($q) {
                $q->withCount(['bookings' => fn ($query) => $query->where('booking_status', 'completed')])
                    ->orderBy('bookings_count', 'desc');
            })
            ->when(filled($booking->sub_category_id), function ($query) use ($booking) {
                $query->whereHas('subscribed_services', fn ($q) => $q->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1));
            })
            ->coveringLeafZone($booking->zone_id)
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->when($request->sort_by !== 'bookings-completed', fn ($q) => $q->withCount('bookings'))
            ->where('service_availability', 1)
            ->where('is_active_for_jobs', 1)
            ->ofApproval(1)
            ->ofStatus(1)
            ->when($request->sort_by === 'default', fn ($q) => $q->orderByRaw("CASE performance_status WHEN 'active' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END"))
            ->get();

        $providers = [];
        foreach ($allProviders as $provider) {
            if (provider_accepts_booking_service_location($provider->id, $booking->service_location)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    public function getProviderInfo($providerId): JsonResponse
    {
        $provider = $this->provider->with('reviews')->findOrFail($providerId);
        $reviews = DB::table('reviews')->where('provider_id', $provider->id)->count();
        return response()->json(['reviews' => $reviews, 'rating' => $provider->avg_rating]);
    }

}
