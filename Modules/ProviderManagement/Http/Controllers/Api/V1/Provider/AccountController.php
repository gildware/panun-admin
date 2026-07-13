<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\UserManagement\Entities\UserVerification;
use Modules\UserManagement\Http\Controllers\Api\V1\OTPVerificationController;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\BusinessSettingsModule\Entities\SubscriptionPackage;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

class AccountController extends Controller
{
    private Provider $provider;
    private Account $account;
    private BusinessSettings $business_settings;
    private PackageSubscriber $packageSubscriber;
    private SubscriptionPackage $subscriptionPackage;
    private Transaction $transaction;

    public function __construct(Transaction $transaction, Provider $provider, Account $account, BusinessSettings $business_settings, PackageSubscriber $packageSubscriber, SubscriptionPackage $subscriptionPackage)
    {
        $this->provider = $provider;
        $this->account = $account;
        $this->business_settings = $business_settings;
        $this->packageSubscriber = $packageSubscriber;
        $this->subscriptionPackage = $subscriptionPackage;
        $this->transaction = $transaction;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {

        $tutorialOptions = [
            'business_information'    => 0,
            'service_subscription'    => 0,
            'service_availability'    => 0,
            'payment_information'     => 0,
        ];

        $vat   = (int)((business_config('subscription_vat', 'subscription_Setting'))->live_values ?? 0);
        $provider = $this->provider->with('owner', 'owner.account', 'zones')->where('user_id', $request->user()->id)->first();

        if ($provider && $provider->owner) {
            $tutorial = $provider->owner->getTutorialByPlatform('app');

            if ($tutorial && is_array($tutorial->options ?? null)) {
                $tutorialOptions = array_merge($tutorialOptions, $tutorial->options);
            }
            $provider->tutorial_options = $tutorialOptions;
        }

        provider_apply_cash_limit_suspension_for_provider($provider, sendNotifications: false);
        $provider->refresh();

        $limitStatus = provider_warning_amount_calculate_for_provider($provider);
        $provider['cash_limit_status'] = $limitStatus == false ? 'available' : $limitStatus;
        $provider['zone_ids'] = $provider->coveredLeafZoneIds();
        $bookingOverview = DB::table('bookings')->where('provider_id', $request->user()->provider->id)
            ->select('booking_status', DB::raw('count(*) as total'))
            ->groupBy('booking_status')
            ->get();

        $promotionalCosts = $this->business_settings->where('settings_type', 'promotional_setup')->get();
        $promotionalCostPercentage = [];

        $data = $promotionalCosts->where('key_name', 'discount_cost_bearer')->first()->live_values;
        $promotionalCostPercentage['discount'] = $data['provider_percentage'];

        $data = $promotionalCosts->where('key_name', 'campaign_cost_bearer')->first()->live_values;
        $promotionalCostPercentage['campaign'] = $data['provider_percentage'];

        $data = $promotionalCosts->where('key_name', 'coupon_cost_bearer')->first()->live_values;
        $promotionalCostPercentage['coupon'] = $data['provider_percentage'];

        $transactionsCount = $this->transaction
            ->whereIn('trx_type', ['subscription_purchase', 'subscription_renew', 'subscription_shift', 'subscription_refund'])
            ->where('from_user_id', $provider->id)
            ->orWhere('to_user_id', $provider->id)->count();
        $packageSubscriber = $this->packageSubscriber->where('provider_id', $provider->id)
            ->with('feature', 'limits', 'package', 'payment')
            ->first();

        $formattedPackage = null;
        $renewal = null;
        if ($packageSubscriber) {
            $formattedPackage = apiPackageSubscriber($packageSubscriber, PACKAGE_FEATURES);

            $renewal = $this->subscriptionPackage->where('id', $packageSubscriber?->subscription_package_id)->first();
        }

        $totalSubscription = 0;
        $status = 'commission_base';

        if (is_array($formattedPackage) || is_object($formattedPackage)) {
            $numberOfUses = $formattedPackage['number_of_uses'] ?? ($formattedPackage->number_of_uses ?? 0);
            $totalSubscription = $numberOfUses;
            $status = $numberOfUses < 0 ? 'commission_base' : 'subscription_base';
        }

        $packageInfo = [
            'total_subscription' => $transactionsCount,
            'status' => $status,
            'subscribed_package_details' => $formattedPackage,
            'renewal_package_details' => $renewal,
            'applicable_vat' => $vat
        ];

        $pendingChangeQuery = \Modules\ProviderManagement\Entities\ProviderChangeRequest::where('provider_id', $provider->id)
            ->where('status', \Modules\ProviderManagement\Entities\ProviderChangeRequest::STATUS_PENDING);

        $hasPendingProfileChanges = (clone $pendingChangeQuery)->exists();
        $hasPendingBrandingChanges = (clone $pendingChangeQuery)
            ->where('change_type', 'branding')
            ->exists();

        $pendingBrandingPreview = $hasPendingBrandingChanges
            ? app(\Modules\ProviderManagement\Services\ProviderProfileChangeRequestService::class)
                ->pendingBrandingPreviewUrls($provider->id)
            : ['logo_url' => null, 'cover_url' => null];

        return response()->json(response_formatter(DEFAULT_200, [
            'provider_info' => $provider,
            'booking_overview' => $bookingOverview,
            'promotional_cost_percentage' => $promotionalCostPercentage,
            'subscription_info' => $packageInfo,
            'has_pending_profile_changes' => $hasPendingProfileChanges,
            'has_pending_branding_changes' => $hasPendingBrandingChanges,
            'pending_branding_preview' => $pendingBrandingPreview,
            'can_use_advertisement' => providerCanUseAdvertisement($provider->id),
        ]), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function accountEdit(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner')->find($request->user()->id);
        if (isset($provider)) {
            return response()->json(response_formatter(DEFAULT_200, $provider), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function accountUpdate(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner')->find($request->user()->id);
        $providerType = $provider?->provider_type ?? 'company';
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'contact_person_phone' => [
                'required',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:8',
                User::uniquePhoneAmongUserTypesRule((string) $provider->user_id, PROVIDER_USER_TYPES),
            ],
            'contact_person_email' => 'required|email|unique:users,email,' . $provider->user_id,

            'password' => 'string|min:8',
            'confirm_password' => 'same:password',
            'account_first_name' => 'required',
            'account_last_name' => 'required',
            // Account (owner) phone/email are derived from contact person details.
            'account_phone' => 'nullable',

            'company_name' => $providerType === 'company' ? 'required' : 'nullable',
            'company_phone' => $providerType === 'company'
                ? 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8'
                : 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'company_address' => 'required',
            'logo' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        if ($providerType === 'company') {
            $provider->company_name = $request->company_name;
            $provider->company_phone = $request->company_phone;
        } else {
            $provider->company_name = $request->contact_person_name;
            $provider->company_phone = $request->contact_person_phone;
            $provider->company_email = $request->contact_person_email;
        }
        if ($request->has('logo')) {
            $provider->logo = file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }
        $provider->company_address = $request->company_address;
        $provider->contact_person_name = $request->contact_person_name;
        $provider->contact_person_phone = $request->contact_person_phone;
        $provider->contact_person_email = $request->contact_person_email;

        $owner = $provider->owner()->first();
        $owner->first_name = $request->account_first_name;
        $owner->last_name = $request->account_last_name;
        // Account (owner) info defaults to contact person details.
        $owner->email = $request->contact_person_email;
        $owner->phone = $request->contact_person_phone;
        if ($request->has('password')) {
            $owner->password = bcrypt($request->password);
        }
        $owner->user_type = 'provider-admin';

        DB::transaction(function () use ($provider, $owner, $request) {
            $owner->save();
            $provider->save();
        });

        return response()->json(response_formatter(PROVIDER_STORE_200), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function commissionInfo(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner')->where('user_id',$request->user()->id)->first();
        if (isset($provider)) {
            return response()->json(response_formatter(DEFAULT_200, [
                'commission_status' => $provider['commission_status'],
                'commission_percentage' => $provider['commission_percentage']
            ]), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    public static function phoneChangeVerifiedCacheKey(string $userId, string $phone): string
    {
        return 'provider_phone_change_verified:' . $userId . ':' . User::normalizeContactPhoneDigits($phone);
    }

    /**
     * Check contact phone/email uniqueness before profile update (excludes current account).
     */
    public function verifyContactUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_person_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'contact_person_email' => 'nullable|email|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $phone = trim((string) $request->input('contact_person_phone'));
        $email = trim((string) $request->input('contact_person_email', ''));

        $errors = [];
        foreach (User::providerContactUpdateErrors($phone, $email, (string) $request->user()->id) as $field => $message) {
            $errors[] = ['error_code' => $field, 'message' => $message];
        }

        if ($errors !== []) {
            return response()->json(response_formatter(DEFAULT_400, null, $errors), 400);
        }

        return response()->json(response_formatter(DEFAULT_200), 200);
    }

    /**
     * Send OTP when changing contact phone on profile.
     */
    public function sendPhoneChangeOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_person_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $phone = trim((string) $request->input('contact_person_phone'));

        if (User::providerContactUpdateErrors($phone, '', (string) $request->user()->id) !== []) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'error_code' => 'contact_person_phone',
                'message' => translate('The contact person phone has already been taken.'),
            ]]), 400);
        }

        return app(OTPVerificationController::class)->check(new Request([
            'identity' => $phone,
            'identity_type' => 'phone',
            'check_user' => 0,
        ]));
    }

    /**
     * Verify OTP after a contact phone change request.
     */
    public function verifyPhoneChangeOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_person_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'otp' => 'required|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $phone = trim((string) $request->input('contact_person_phone'));
        $userId = (string) $request->user()->id;

        if (User::providerContactUpdateErrors($phone, '', $userId) !== []) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'error_code' => 'contact_person_phone',
                'message' => translate('The contact person phone has already been taken.'),
            ]]), 400);
        }

        $tempBlockTime = business_config('temporary_otp_block_time', 'otp_login_setup')->test_values ?? 600;
        $verify = UserVerification::where(['identity' => $phone, 'otp' => $request->input('otp')])->first();

        if (! $verify) {
            return app(OTPVerificationController::class)->providerLoginOtpFailed($request);
        }

        if (isset($verify->temp_block_time) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime) {
            $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();

            return response()->json(response_formatter([
                'response_code' => translate('auth_login_401'),
                'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans(),
            ]), 403);
        }

        $verify->delete();
        Cache::put(self::phoneChangeVerifiedCacheKey($userId, $phone), true, now()->addMinutes(15));

        return response()->json(response_formatter(DEFAULT_200), 200);
    }
}
