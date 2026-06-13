<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Traits\UploadSizeHelperTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Entities\SubscriptionPackage;
use Modules\PaymentModule\Entities\Setting;
use Modules\PaymentModule\Traits\SubscriptionTrait;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\PromotionManagement\Entities\PushNotificationUser;
use Modules\ProviderManagement\Emails\NewJoiningRequestMail;
use Modules\Auth\Services\ProviderRegistrationDraftService;
use Modules\Auth\Services\ProviderRegistrationSubscriptionService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderRegistrationDraft;
use Modules\ProviderManagement\Entities\ProviderSetting;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use App\Lib\PaymentAccessToken;
use Modules\ZoneManagement\Services\ZoneCoverageNormalizationService;

class RegisterController extends Controller
{
    use UploadSizeHelperTrait;

    protected Provider $provider;
    protected User $owner;
    protected User $user;
    protected Serviceman $serviceman;
    private SubscriptionPackage $subscriptionPackage;

    use SubscriptionTrait;
    use UploadSizeHelperTrait;

    public function __construct(Provider $provider, User $owner, User $user, Serviceman $serviceman, SubscriptionPackage $subscriptionPackage)
    {
        $this->provider = $provider;
        $this->owner = $owner;
        $this->user = $user;
        $this->serviceman = $serviceman;
        $this->subscriptionPackage = $subscriptionPackage;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function customerRegister(Request $request): JsonResponse
    {
        $check = $this->validateUploadedFile($request, ['profile_image']);
        if ($check !== true) {
            return $check;
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'password' => 'required|min:8',
            'gender' => 'in:male,female,others',
            'confirm_password' => 'required|same:password',
            'profile_image' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 403);
        }

        if (User::where('email', $request['email'])->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [["error_code" => "email", "message" => translate('Email already taken')]]), 400);
        }
        if (User::where('phone', $request['phone'])->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [["error_code" => "phone", "message" => translate('Phone already taken')]]), 400);
        }

        $user = $this->user;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->profile_image = $request->has('profile_image') ? file_uploader('user/profile_image/', APPLICATION_IMAGE_FORMAT, $request->profile_image) : 'default.png';
        $user->date_of_birth = $request->date_of_birth;
        $user->gender = $request->gender ?? 'male';
        $user->password = bcrypt($request->password);
        $user->user_type = 'customer';
        $user->customer_app_access = true;
        $user->is_active = 1;

        if ($request->has('referral_code')) {
            $customerReferralEarning = business_config('customer_referral_earning', 'customer_config')->live_values ?? 0;
            $amount = business_config('referral_value_per_currency_unit', 'customer_config')->live_values ?? 0;
            $userWhoRerreded = User::where('ref_code', $request['referral_code'])->first();

            if (is_null($userWhoRerreded)) {
                return response()->json(response_formatter(REFERRAL_CODE_INVALID_400), 404);
            }

            if ($customerReferralEarning == 1 && isset($userWhoRerreded)){

                referralEarningTransactionDuringRegistration($userWhoRerreded, $amount);

                $userRefund  = isNotificationActive(null, 'refer_earn', 'notification', 'user');
                $title = get_push_notification_message('referral_code_used', 'customer_notification', $user?->current_language_key);
                if ($title && $userWhoRerreded->fcm_token && $userRefund) {
                    device_notification($userWhoRerreded->fcm_token, $title, null, null, null, 'general', null, $userWhoRerreded->id);
                }

                $pushNotification = new PushNotification();
                $pushNotification->title = translate('Your Referral Code Has Been Used!');
                $pushNotification->description = translate("Congratulations! Your referral code was used by a new user. Get ready to earn rewards when they complete their first booking.");
                $pushNotification->to_users = ['customer'];
                $pushNotification->zone_ids = [config('zone_id') == null ? $request['zone_id'] : config('zone_id')];
                $pushNotification->is_active = 1;
                $pushNotification->cover_image = asset('/assets/admin/img/referral_2.png');
                $pushNotification->save();

                $pushNotificationUser = new PushNotificationUser();
                $pushNotificationUser->push_notification_id = $pushNotification->id;
                $pushNotificationUser->user_id = $userWhoRerreded->id;
                $pushNotificationUser->save();
            }
        }

        $user->referred_by = $userWhoRerreded->id ?? null;
        $user->save();

        $phoneVerification = checkActiveSMSGatewayCount();
        $emailVerification = login_setup('email_verification')?->value ?? 0;

        if (!$phoneVerification && !$emailVerification){
            $loginData = ['token' => $user->createToken(CUSTOMER_PANEL_ACCESS)->accessToken, 'is_active' => $user['is_active']];
            return response()->json(response_formatter(REGISTRATION_200, $loginData), 200);
        }

        return response()->json(response_formatter(REGISTRATION_200), 200);
    }


    /**
     * Validate provider contact phone/email before multi-step registration continues.
     */
    public function verifyProviderCredentials(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_email' => 'nullable|email|max:191',
            'account_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $phone = trim((string) $request->input('account_phone'));
        $email = trim((string) $request->input('account_email', ''));

        $errors = [];
        foreach (User::providerContactRegistrationErrors($phone, $email) as $field => $message) {
            if ($field === 'contact_person_phone') {
                $errors[] = ['error_code' => 'contact_person_phone', 'message' => $message];
                $errors[] = ['error_code' => 'phone', 'message' => $message];
                $errors[] = ['error_code' => 'account_phone', 'message' => $message];
            } elseif ($field === 'contact_person_email') {
                $errors[] = ['error_code' => 'contact_person_email', 'message' => $message];
                $errors[] = ['error_code' => 'email', 'message' => $message];
                $errors[] = ['error_code' => 'account_email', 'message' => $message];
            }
        }

        if ($errors !== []) {
            return response()->json(response_formatter(DEFAULT_400, null, $errors), 400);
        }

        return response()->json(response_formatter(DEFAULT_200), 200);
    }

    /**
     * Save one step of provider self-registration (resume later via registration_token).
     */
    public function getProviderRegistrationDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'registration_token' => 'required|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $draft = ProviderRegistrationDraft::query()
            ->where('registration_token', $request->input('registration_token'))
            ->first();

        if (! $draft) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(
            response_formatter(DEFAULT_200, app(ProviderRegistrationDraftService::class)->toApiPayload($draft)),
            200
        );
    }

    public function saveProviderRegistrationStep(Request $request): JsonResponse
    {
        $allSteps = array_merge(
            ProviderRegistrationDraftService::STEPS_INDIVIDUAL,
            ProviderRegistrationDraftService::STEPS_COMPANY
        );
        $allSteps = array_values(array_unique($allSteps));

        $validator = Validator::make($request->all(), [
            'registration_token' => 'nullable|string|max:64',
            'step' => 'required|string|in:' . implode(',', $allSteps),
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $draft = null;
        if ($request->filled('registration_token')) {
            $draft = ProviderRegistrationDraft::query()
                ->where('registration_token', $request->input('registration_token'))
                ->first();
        }

        if (! $draft && $request->input('step') === 'contact_info' && $request->filled('contact_person_phone')) {
            $draft = app(ProviderRegistrationDraftService::class)->findOrCreateForPhone(
                (string) $request->input('contact_person_phone')
            );
        }

        if (! $draft) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $step = (string) $request->input('step');
        $this->normalizeCompanyIdentityImageUploads($request);

        if ($step === 'company_documents') {
            $formData = is_array($draft->form_data) ? $draft->form_data : [];
            $filePaths = is_array($formData['file_paths'] ?? null) ? $formData['file_paths'] : [];
            $existing = is_array($filePaths['company_identity_images'] ?? null)
                ? array_values(array_filter($filePaths['company_identity_images']))
                : [];
            $hasNewUpload = $request->hasFile('company_identity_image')
                || $request->hasFile('company_identity_images');
            if (! $hasNewUpload && $existing === []) {
                return response()->json(
                    response_formatter(DEFAULT_400, null, [[
                        'error_code' => 'company_identity_image',
                        'message' => translate('Company identity document is required'),
                    ]]),
                    400
                );
            }
        }

        $uploadCheck = $this->validateCompanyIdentityUploadSize($request, $step);
        if ($uploadCheck !== true) {
            return $uploadCheck;
        }

        $check = $this->validateUploadedFile($request, $this->draftUploadFieldsForStep($step));
        if ($check !== true) {
            return $check instanceof RedirectResponse
                ? response()->json(response_formatter(DEFAULT_400), 400)
                : $check;
        }

        $draft = app(ProviderRegistrationDraftService::class)->saveStep($draft, $request);

        return response()->json(
            response_formatter(PROVIDER_REGISTRATION_STEP_SAVED, app(ProviderRegistrationDraftService::class)->toApiPayload($draft)),
            200
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeIdentityImageUploads(Request $request): void
    {
        if ($request->hasFile('identity_images')) {
            return;
        }

        $files = array_values(array_filter([
            $request->file('identity_image_front'),
            $request->file('identity_image_back'),
        ]));

        if ($files !== []) {
            $request->files->set('identity_images', $files);
        }
    }

    private function normalizeCompanyIdentityImageUploads(Request $request): void
    {
        if ($request->hasFile('company_identity_image')) {
            $request->files->set('company_identity_images', [$request->file('company_identity_image')]);

            return;
        }

        if ($request->hasFile('company_identity_images')) {
            $files = $request->file('company_identity_images');
            $request->files->set('company_identity_images', array_values(array_filter(
                is_array($files) ? $files : [$files]
            )));

            return;
        }

        $collected = [];
        foreach ($request->allFiles() as $key => $file) {
            if ($key === 'company_identity_images' || preg_match('/^company_identity_images(\.\d+|\[\d+\])?$/', (string) $key)) {
                if (is_array($file)) {
                    foreach ($file as $item) {
                        if ($item) {
                            $collected[] = $item;
                        }
                    }
                } elseif ($file) {
                    $collected[] = $file;
                }
            }
        }

        for ($i = 0; $i < 5; $i++) {
            if ($request->hasFile("company_identity_images.$i")) {
                $collected[] = $request->file("company_identity_images.$i");
            }
        }

        if ($collected !== []) {
            $request->files->set('company_identity_images', array_slice($collected, 0, 1));
        }
    }

    private function countUploadedIdentityImages(Request $request): int
    {
        if ($request->hasFile('identity_images')) {
            $images = $request->file('identity_images');

            return count(array_filter(is_array($images) ? $images : [$images]));
        }

        return count(array_filter([
            $request->file('identity_image_front'),
            $request->file('identity_image_back'),
        ]));
    }

    private function draftUploadFieldsForStep(string $step): array
    {
        return match ($step) {
            'contact_info' => ['contact_person_photo'],
            'identity_verification' => ['identity_image_front', 'identity_image_back', 'identity_images'],
            'company_information' => ['logo'],
            'company_documents' => ['company_identity_image', 'company_identity_images'],
            'service_categories', 'service_subcategories' => [],
            default => [],
        };
    }

    private function validateCompanyIdentityUploadSize(Request $request, string $step): true|JsonResponse
    {
        if ($step !== 'company_documents') {
            return true;
        }

        $maxBytes = 10 * 1024 * 1024;
        $files = array_filter([
            $request->file('company_identity_image'),
            ...($request->hasFile('company_identity_images')
                ? (is_array($request->file('company_identity_images'))
                    ? $request->file('company_identity_images')
                    : [$request->file('company_identity_images')])
                : []),
        ]);

        foreach ($files as $file) {
            if ($file && $file->getSize() > $maxBytes) {
                return response()->json(
                    response_formatter(DEFAULT_400, null, [[
                        'error_code' => 'company_identity_image',
                        'message' => translate('Company identity image must be 10 MB or less'),
                    ]]),
                    400
                );
            }
        }

        return true;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function providerRegister(Request $request): JsonResponse
    {
        $draft = null;
        $draftService = app(ProviderRegistrationDraftService::class);

        if ($request->filled('registration_token')) {
            $draft = ProviderRegistrationDraft::query()
                ->where('registration_token', $request->input('registration_token'))
                ->first();
            if ($draft) {
                $draftService->mergeDraftIntoRegistrationRequest($request, $draft);
            }
        }

        $this->normalizeIdentityImageUploads($request);

        $request->merge([
            'provider_type' => strtolower(trim((string) $request->input('provider_type', ''))),
        ]);

        if ($request->provider_type === 'individual') {
            $request->merge([
                'company_name' => null,
                'company_phone' => null,
                'company_email' => null,
                'company_identity_type' => null,
                'company_identity_number' => null,
            ]);
        }

        $zoneIds = $request->input('zone_ids', []);
        if (! is_array($zoneIds)) {
            $zoneIds = [];
        }
        $zoneIds = array_values(array_filter($zoneIds));
        if ($zoneIds === [] && $request->filled('zone_id')) {
            $request->merge(['zone_ids' => [(string) $request->input('zone_id')]]);
            $zoneIds = [(string) $request->input('zone_id')];
        }

        if (! $request->filled('contact_person_email')) {
            $request->merge(['contact_person_email' => null]);
        }

        $uploadFields = $request->provider_type === 'company'
            ? ['logo', 'contact_person_photo']
            : ['contact_person_photo'];
        $check = $this->validateUploadedFile($request, $uploadFields);
        if ($check !== true) {
            return $check;
        }

        $identityIn = 'passport,driving_license,nid';
        $allowedImageMimes = implode(',', array_column(IMAGEEXTENSION, 'key'));
        $imageMaxRule = 'max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . $allowedImageMimes;

        $rules = [
            'provider_type' => 'required|in:company,individual',

            'contact_person_name' => 'required|string|max:191',
            'contact_person_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'contact_person_email' => 'nullable|email|max:191',

            'account_email' => 'nullable|email',
            'account_phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',

            'company_address' => 'required|string',

            'contact_person_photo' => 'nullable|image|'.$imageMaxRule,

            'identity_type' => 'required|in:'.$identityIn,
            'identity_number' => 'required|string|max:191',
            'identity_images' => 'array',
            'identity_images.*' => 'image|'.$imageMaxRule,

            'latitude' => 'required',
            'longitude' => 'required',

            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'uuid',
            'zone_id' => 'nullable|uuid',
        ];

        if ($request->provider_type === 'company') {
            $rules['company_name'] = 'required|string|max:191';
            $rules['company_phone'] = 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8';
            $rules['company_email'] = 'nullable|email|max:191';
            $rules['logo'] = 'nullable|image|'.$imageMaxRule;
            $rules['company_identity_type'] = 'required|in:trade_license,company_id';
            $rules['company_identity_number'] = 'required|string|max:191';
            $rules['company_identity_images'] = 'array';
            $rules['company_identity_images.*'] = 'image|'.$imageMaxRule;
        } else {
            $rules['company_name'] = 'nullable';
            $rules['company_phone'] = 'nullable';
            $rules['company_email'] = 'nullable|email|max:191';
            $rules['logo'] = 'nullable';
            $rules['company_identity_type'] = 'nullable';
            $rules['company_identity_number'] = 'nullable';
            $rules['company_identity_images'] = 'nullable|array';
        }

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($v) use ($request, $draft, $draftService) {
            if (! $this->registrationHasUpload($request, $draft, $draftService, 'contact_person_photo')) {
                $v->errors()->add(
                    'contact_person_photo',
                    translate('The contact person photo field is required.')
                );
            }

            if ($request->provider_type === 'company' && ! $this->registrationHasUpload($request, $draft, $draftService, 'logo')) {
                $v->errors()->add('logo', translate('The logo field is required.'));
            }

            foreach (User::providerContactRegistrationErrors(
                (string) $request->contact_person_phone,
                (string) $request->contact_person_email
            ) as $field => $message) {
                $v->errors()->add($field, $message);
            }

            $identityImageCount = $this->countUploadedIdentityImages($request);
            if ($identityImageCount < 2 && $draft) {
                $identityImageCount = count($draftService->draftIdentityFilePaths($draft));
            }
            if ($identityImageCount < 2) {
                $v->errors()->add('identity_images', translate('Please upload front and back of identity document'));
            }

            if ($request->provider_type === 'company' && ! $this->registrationHasCompanyIdentityUpload($request, $draft, $draftService)) {
                $v->errors()->add('company_identity_images', translate('Please upload at least one company identity image'));
            }
        });

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $excluded = $request->input('zone_excluded_ids', []);
        if (! is_array($excluded)) {
            $excluded = [];
        }
        $leafZoneIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds(
            $request->input('zone_ids', []),
            $excluded
        );
        if ($leafZoneIds === []) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'error_code' => 'zone_ids',
                'message' => translate('Select_Zone'),
            ]]), 400);
        }

        if ($request->choose_business_plan == 'subscription_base'){
            $package = $this->subscriptionPackage->where('id',$request->selected_package_id)->ofStatus(1)->first();
            $vatPercentage      = (int)((business_config('subscription_vat', 'subscription_Setting'))->live_values ?? 0);
            if (!$package){
                return response()->json(response_formatter(DEFAULT_400, null, [["error_code" => "package", "message" => translate('Please Select valid plan')]]), 400);
            }

            $id                 = $package->id;
            $price              = $package->price;
            $name               = $package->name;
            $vatAmount          = $package->price * ($vatPercentage / 100);
            $vatWithPrice       = $price + $vatAmount;
        }

        $identityImages = $this->buildIdentityImagesFromRegistration($request, $draft, $draftService);
        $companyIdentityImages = $this->buildCompanyIdentityImagesFromRegistration($request, $draft, $draftService);

        $provider = $this->provider;
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

        $logoName = $this->resolveRegistrationFileName(
            $request,
            $draft,
            $draftService,
            'logo',
            'provider/logo/'
        );
        if ($logoName) {
            $provider->logo = $logoName;
        }

        $provider->company_address = $request->company_address;
        $provider->street = $request->filled('street') ? trim((string) $request->street) : null;
        $provider->city = $request->filled('city') ? trim((string) $request->city) : null;
        $provider->pincode = $request->filled('pincode') ? trim((string) $request->pincode) : null;

        $provider->contact_person_name = $request->contact_person_name;
        $provider->contact_person_phone = $request->contact_person_phone;
        $provider->contact_person_email = $request->contact_person_email;

        $contactPhotoName = $this->resolveRegistrationFileName(
            $request,
            $draft,
            $draftService,
            'contact_person_photo',
            'provider/contact_person_photo/'
        );
        if ($contactPhotoName) {
            $provider->contact_person_photo = $contactPhotoName;
        }

        if ($request->provider_type === 'company') {
            $provider->company_identity_type = $request->company_identity_type;
            $provider->company_identity_number = $request->company_identity_number;
            $provider->company_identity_images = $companyIdentityImages;
        } else {
            $provider->company_identity_type = null;
            $provider->company_identity_number = null;
            $provider->company_identity_images = [];
        }

        $provider->is_approved = 2;
        $provider->is_active = 0;
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
        $owner->first_name = $nameParts[0] ?? '';
        $owner->last_name = $nameParts[1] ?? '';
        $owner->email = $request->contact_person_email;
        $owner->phone = $request->contact_person_phone;
        $owner->identification_number = $request->identity_number;
        $owner->identification_type = $request->identity_type;
        $owner->identification_image = $identityImages;
        $owner->password = bcrypt(provider_default_password_plain($request->contact_person_phone));
        $owner->user_type = 'provider-admin';
        $owner->is_active = 0;

        // Phone was verified via OTP before onboarding (registration_token flow).
        if ($request->filled('registration_token')) {
            $owner->is_phone_verified = 1;
        }

        DB::transaction(function () use ($provider, $owner, $leafZoneIds, $request) {
            $owner->save();
            $provider->user_id = $owner->id;
            $provider->save();
            $owner->zones()->sync($leafZoneIds);
            $provider->zones()->sync(
                collect($leafZoneIds)->mapWithKeys(fn (string $zid) => [$zid => []])->all()
            );

            $subCategoryIds = app(ProviderRegistrationSubscriptionService::class)
                ->requestedIdsFromMixedInput($request->input('subscribed_sub_category_ids', []));
            app(ProviderRegistrationSubscriptionService::class)->syncForProvider(
                $provider,
                $leafZoneIds,
                $subCategoryIds
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
        });

        $emailStatus = business_config('email_config_status', 'email_config')->live_values;

        if ($emailStatus){
            try {
                Mail::to(User::where('user_type', 'super-admin')->value('email'))->send(new NewJoiningRequestMail($provider));
            } catch (\Exception $exception) {
                info($exception);
            }
        }

        if ($request->choose_business_plan == 'subscription_base') {
            $provider_id = $provider->id;
            if ($request->free_trial_or_payment == 'free_trial') {
                $result = $this->handleFreeTrialPackageSubscription($id, $provider_id, $price, $name);
                if (!$result){
                    return response()->json(response_formatter(DEFAULT_FAIL_200), 400);
                }
            }elseif ($request->free_trial_or_payment == 'payment') {
                app(ProviderRegistrationDraftService::class)->deleteByToken($request->input('registration_token'));
                $paymentUrl = url('payment/subscription') . '?' .
                    'provider_id=' . $provider_id . '&' .
                    'access_token=' . PaymentAccessToken::issue($owner->id) . '&' .
                    'package_id=' . $id . '&' .
                    'amount=' . $vatWithPrice . '&' .
                    'name=' . $name . '&' .
                    'package_status=' . 'subscription_purchase' . '&' .
                    http_build_query($request->all());
                return response()->json(response_formatter(PROVIDER_STORE_200, $paymentUrl), 200);
            }
        }

        app(ProviderRegistrationDraftService::class)->deleteByToken($request->input('registration_token'));

        return response()->json(response_formatter(PROVIDER_STORE_200), 200);
    }


    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user_verification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $data = DB::table('user_verifications')
            ->where('identity', $request['identity'])
            ->where(['otp' => $request['otp']])->first();

        if (isset($data)) {
            $this->user->where('phone', $request['identity'])
                ->update([
                    'is_phone_verified' => 1
                ]);

            DB::table('user_verifications')
                ->where('identity', $request['identity'])
                ->where(['otp' => $request['otp']])->delete();

            return response()->json(response_formatter(DEFAULT_VERIFIED_200), 200);
        }

        return response()->json(response_formatter(DEFAULT_404), 200);
    }

    private function registrationHasUpload(
        Request $request,
        ?ProviderRegistrationDraft $draft,
        ProviderRegistrationDraftService $draftService,
        string $field
    ): bool {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            if ($file && $file->isValid()) {
                return true;
            }
        }

        return $draft !== null && $draftService->draftFileExists($draft, $field);
    }

    private function registrationHasCompanyIdentityUpload(
        Request $request,
        ?ProviderRegistrationDraft $draft,
        ProviderRegistrationDraftService $draftService
    ): bool {
        if ($request->hasFile('company_identity_images')) {
            $images = $request->file('company_identity_images');
            foreach (is_array($images) ? $images : [$images] as $image) {
                if ($image && $image->isValid()) {
                    return true;
                }
            }
        }

        return $draft !== null && $draftService->draftCompanyIdentityFilePaths($draft) !== [];
    }

    private function resolveRegistrationFileName(
        Request $request,
        ?ProviderRegistrationDraft $draft,
        ProviderRegistrationDraftService $draftService,
        string $field,
        string $destinationDir
    ): ?string {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            if ($file && $file->isValid()) {
                return file_uploader($destinationDir, APPLICATION_IMAGE_FORMAT, $file);
            }
        }

        if ($draft === null) {
            return null;
        }

        $path = $draftService->getDraftFilePath($draft, $field);

        return $path
            ? $draftService->copyDraftFileToProviderStorage($path, $destinationDir, APPLICATION_IMAGE_FORMAT)
            : null;
    }

    /**
     * @return list<array{image: string, storage: string}>
     */
    private function buildIdentityImagesFromRegistration(
        Request $request,
        ?ProviderRegistrationDraft $draft,
        ProviderRegistrationDraftService $draftService
    ): array {
        $identityImages = [];

        if ($request->hasFile('identity_images')) {
            foreach ($request->file('identity_images') as $image) {
                if (! $image || ! $image->isValid()) {
                    continue;
                }
                $imageName = file_uploader('provider/identity/', APPLICATION_IMAGE_FORMAT, $image);
                $identityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        if (count($identityImages) >= 2 || $draft === null) {
            return $identityImages;
        }

        foreach ($draftService->draftIdentityFilePaths($draft) as $path) {
            $imageName = $draftService->copyDraftFileToProviderStorage(
                $path,
                'provider/identity/',
                APPLICATION_IMAGE_FORMAT
            );
            if ($imageName) {
                $identityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        return $identityImages;
    }

    /**
     * @return list<array{image: string, storage: string}>
     */
    private function buildCompanyIdentityImagesFromRegistration(
        Request $request,
        ?ProviderRegistrationDraft $draft,
        ProviderRegistrationDraftService $draftService
    ): array {
        $companyIdentityImages = [];

        if ($request->hasFile('company_identity_image')) {
            $image = $request->file('company_identity_image');
            if ($image && $image->isValid()) {
                $imageName = file_uploader('provider/company-identity/', APPLICATION_IMAGE_FORMAT, $image);
                $companyIdentityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        if ($request->has('company_identity_images')) {
            foreach ($request->company_identity_images as $image) {
                if (! $image || ! $image->isValid()) {
                    continue;
                }
                $imageName = file_uploader('provider/company-identity/', APPLICATION_IMAGE_FORMAT, $image);
                $companyIdentityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        if ($companyIdentityImages !== [] || $draft === null) {
            return $companyIdentityImages;
        }

        foreach ($draftService->draftCompanyIdentityFilePaths($draft) as $path) {
            $imageName = $draftService->copyDraftFileToProviderStorage(
                $path,
                'provider/company-identity/',
                APPLICATION_IMAGE_FORMAT
            );
            if ($imageName) {
                $companyIdentityImages[] = ['image' => $imageName, 'storage' => getDisk()];
            }
        }

        return $companyIdentityImages;
    }

}
