<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use App\Traits\MaintenanceModeTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Entities\BusinessPageSetting;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;
use Modules\CustomerModule\Services\CustomerApiResponseCache;
use Modules\BusinessSettingsModule\Entities\LoginSetup;
use Modules\PaymentModule\Entities\Setting;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class ConfigController extends Controller
{
    use MaintenanceModeTrait;
    private $google_map;

    public function __construct()
    {
        $this->google_map = business_config('google_map', 'third_party');
    }

    public function getRoutes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_latitude' => 'required',
            'origin_longitude' => 'required',
            'destination_latitude' => 'required',
            'destination_longitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $distance = get_distance(
            [$request['origin_latitude'], $request['origin_longitude']],
            [$request['destination_latitude'], $request['destination_longitude']]
        );
        $distance = ($distance) ? number_format($distance, 2) . ' km' : null;

        return response()->json(response_formatter(DEFAULT_200, $distance), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function config(Request $request): JsonResponse
    {
        $locale = strtolower((string) $request->header('X-localization', app()->getLocale()));
        $content = CustomerApiResponseCache::remember(
            'provider_api_config:v1:'.$locale,
            function () {
                $response = $this->buildConfigResponse();
                $decoded = json_decode($response->getContent(), true);

                return is_array($decoded) ? ($decoded['content'] ?? []) : [];
            },
            CustomerApiResponseCache::CONFIG_TTL
        );
        $content['maintenance'] = $this->checkMaintenanceMode();
        $content['mobile_app_icons'] = $this->providerMobileAppIconsForApi();

        return response()
            ->json(response_formatter(DEFAULT_200, $content), 200)
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function buildConfigResponse(): JsonResponse
    {
        $advancedBooking =  [
            'advanced_booking_restriction_value' => (int) business_config('advanced_booking_restriction_value', 'booking_setup')?->live_values,
            'advanced_booking_restriction_type' => business_config('advanced_booking_restriction_type', 'booking_setup')?->live_values,
            'cart_checkout_leniency_value' => (int) (business_config('cart_checkout_leniency_value', 'booking_setup')?->live_values ?? 0),
            'cart_checkout_leniency_type' => business_config('cart_checkout_leniency_type', 'booking_setup')?->live_values ?? 'minute',
        ];

        //payment gateways
        $isPublished = 0;
        try {
            $fullData = include('Modules/Gateways/Addon/info.php');
            $isPublished = $fullData['is_published'] == 1 ? 1 : 0;
        } catch (\Exception $exception) {}

        $payment_gateways = collect($this->getPaymentMethods())
            ->filter(function ($query) use ($isPublished) {
                if (!$isPublished) {
                    return in_array($query['gateway'], array_column(PAYMENT_METHODS, 'key'));
                } else return $query;
            })->map(function ($query) {
                $query['label'] = ucwords(str_replace('_', ' ', $query['gateway']));
                return $query;
            })->values();

        $countryData = business_config('system_language', 'business_information')?->live_values;

        $country = [];

        foreach ($countryData as $key => $item) {
            $item['full_name'] = get_language_name($item['code']);
            $country[$key] = $item;
        }

        $loginOptionsValue = LoginSetup::where(['key' => 'login_options'])?->first()?->value;
        $loginOptions = json_decode($loginOptionsValue);

        $freeTrialType = ((business_config('free_trial_type', 'subscription_Setting'))->live_values ?? null);
        $freeTrialPeriod = (int)((business_config('free_trial_period', 'subscription_Setting'))->live_values ?? 0);

        if ($freeTrialType == 'month') {
            $freeTrialPeriod = (int) floor($freeTrialPeriod / 30);
        }

        $dataValues = Setting::where('settings_type', 'sms_config')->get();
        $count = 0;
        foreach ($dataValues as $gateway) {
            $status = $gateway?->live_values['status'] ?? 0;
            if ($status == 1) {
                $count = 1;
            }
        }
        $emailConfig = business_config('email_config_status', 'email_config')?->live_values;
        $firebaseOtpConfig = business_config('firebase_otp_verification', 'third_party');
        $firebaseOtpStatus =(int) $firebaseOtpConfig?->live_values['status'] ?? null;

        if ($firebaseOtpStatus == 1){
            $count = 1;
        }

        if (use_dummy_login_otp()) {
            $firebaseOtpStatus = 0;
            if ($count < 1) {
                $count = 1;
            }
        }

        $forgotPasswordVerificationMethod = [
            'phone' => $count,
            'email' => $emailConfig
        ];


        return response()->json(response_formatter(DEFAULT_200, [
            'maintenance' => $this->checkMaintenanceMode(),
            'free_trial_status' => (int)((business_config('free_trial_period', 'subscription_Setting'))->is_active ?? 0),
            'free_trial_period' => $freeTrialPeriod,
            'free_trial_type' => $freeTrialType,
            'deadline_warning' => (int)((business_config('deadline_warning', 'subscription_Setting'))->live_values ?? null),
            'deadline_warning_message' => ((business_config('deadline_warning_message', 'subscription_Setting'))->live_values ?? null),
            'usage_time' => (int)((business_config('usage_time', 'subscription_Setting'))->live_values ?? null),
            'commission_base' => (int)((business_config('provider_commision', 'provider_config'))->live_values ?? null),
            'subscription_base' => (int)((business_config('provider_subscription', 'provider_config'))->live_values ?? null),
            'provider_can_cancel_booking' => (int)((business_config('provider_can_cancel_booking', 'provider_config'))->live_values ?? null),
            'provider_self_registration' => (int)((business_config('provider_self_registration', 'provider_config'))->live_values ?? null),
            'provider_self_delete' => (int)((business_config('provider_self_delete', 'provider_config'))->live_values ?? null),
            'min_payable_amount' => (int)((business_config('min_payable_amount', 'provider_config'))->live_values ?? null),
            'provider_can_edit_booking' => (int)((business_config('provider_can_edit_booking', 'provider_config'))->live_values ?? null),
            'currency_symbol_position' => (business_config('currency_symbol_position', 'business_information'))->live_values ?? null,
            'business_name' => (business_config('business_name', 'business_information'))->live_values ?? null,
            'logo_full_path' => getBusinessSettingsImageFullPath(key: 'business_logo', settingType: 'business_information', path: 'business/',  defaultPath : 'assets/admin-module/img/media/banner-upload-file.png'),
            'favicon_full_path' =>  getBusinessSettingsImageFullPath(key: 'business_favicon', settingType: 'business_information', path: 'business/',  defaultPath : 'assets/admin-module/img/media/upload-file.png'),
            'country_code' => (business_config('country_code', 'business_information'))->live_values ?? null,
            'business_address' => (business_config('business_address', 'business_information'))->live_values ?? null,
            'business_phone' => (business_config('business_phone', 'business_information'))->live_values ?? null,
            'business_email' => (business_config('business_email', 'business_information'))->live_values ?? null,
            'base_url' => rtrim(url('/'), '/') . '/api/v1/',
            'currency_decimal_point' => (business_config('currency_decimal_point', 'business_information'))->live_values ?? null,
            'currency_code' => (business_config('currency_code', 'business_information'))->live_values ?? null,
            'currency_symbol' => currency_symbol() ?? '',
            'about_us' => route('about-us'),
            'privacy_policy' => route('privacy-policy'),
            'terms_and_conditions' => (business_config('terms_and_conditions', 'pages_setup'))->is_active ? route('terms-and-conditions') : "",
            'refund_policy' => (business_config('refund_policy', 'pages_setup'))->is_active ? route('refund-policy') : "",
            'cancellation_policy' => (business_config('cancellation_policy', 'pages_setup'))->is_active ? route('cancellation-policy') : "",
            'default_location' => ['default' => [
                'lat' => (business_config('address_latitude', 'business_information'))->live_values ?? 23.811842872190,
                'lon' => (business_config('address_longitude', 'business_information'))->live_values ?? 90.66504678008192
            ]],
            'pagination_limit' => (int)pagination_limit(),
            'time_format' => (business_config('time_format', 'business_information'))->live_values ?? '24h',
            'max_cash_in_hand_limit_provider' => (business_config('max_cash_in_hand_limit_provider', 'provider_config'))->live_values ?? 0,
            'suspend_on_exceed_cash_limit_provider' => (business_config('suspend_on_exceed_cash_limit_provider', 'provider_config'))->live_values ?? 0,
            'default_commission' => (business_config('default_commission', 'business_information'))->live_values,
            'footer_text' => (business_config('footer_text', 'business_information'))->live_values ?? null,
            'min_versions' => json_decode((business_config('provider_app_settings', 'app_settings'))->live_values ?? null),
            'minimum_withdraw_amount' => business_config('minimum_withdraw_amount', 'business_information') ? ((float)(business_config('minimum_withdraw_amount', 'business_information'))->live_values ?? null) : null,
            'maximum_withdraw_amount' => business_config('maximum_withdraw_amount', 'business_information') ? ((float)(business_config('maximum_withdraw_amount', 'business_information'))->live_values ?? null) : null,
            'phone_number_visibility_for_chatting' => (int)((business_config('phone_number_visibility_for_chatting', 'business_information'))->live_values ?? 0),
            'bid_offers_visibility_for_providers' => (int)((business_config('bid_offers_visibility_for_providers', 'bidding_system'))->live_values ?? 0),
            'bidding_status' => (int)((business_config('bidding_status', 'bidding_system'))->live_values ?? 0),
            'advertisement_status' => (int)((business_config('advertisement_status', 'provider_config'))->live_values ?? 0),
            'advertisement_minimum_bookings' => (int)((business_config('advertisement_minimum_bookings', 'provider_config'))->live_values ?? 0),
            'in_app_call_status' => (int)((business_config('in_app_call_status', 'in_app_call_system'))?->live_values ?? 1),
            'digital_payment' => (int)((business_config('digital_payment', 'service_setup'))->live_values ?? 0),
            'phone_verification' => (((login_setup('phone_verification'))->value ?? 0 ) == 1 && $count == 1 ? 1 : 0),
            'email_verification' => (int)((login_setup('email_verification'))->value ?? 0),
            'login_setup' => [
                'login_option' => $loginOptions,
            ],
            'otp_resend_time' => (int)(business_config('otp_resend_time', 'otp_login_setup'))?->live_values ?? null,
            'booking_otp_verification' => (int)(business_config('booking_otp', 'booking_setup'))->live_values ?? null,
            'service_complete_photo_evidence' => (int)(business_config('service_complete_photo_evidence', 'booking_setup'))?->live_values ?? null,
            'additional_charge_types' => \Modules\BusinessSettingsModule\Entities\AdditionalChargeType::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'sort_order', 'is_commissionable', 'customizable_at_booking'])
                ->values(),
            'payment_gateways' => $payment_gateways,
            'system_language' => $country,
            'instant_booking' => (int) business_config('instant_booking', 'booking_setup')?->live_values,
            'schedule_booking' => (int) business_config('schedule_booking', 'booking_setup')?->live_values,
            'schedule_booking_time_restriction' => (int) business_config('schedule_booking_time_restriction', 'booking_setup')?->live_values,
            'advanced_booking' => $advancedBooking,
            'firebase_otp_verification' => $firebaseOtpStatus,
            'forgot_password_verification_method' => $forgotPasswordVerificationMethod,
            'provider_can_reply_review' => (int) business_config('provider_can_reply_review', 'provider_config')?->live_values,
            'app_environment' => env('APP_ENV'),
            'service_at_provider_place' => (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0),
            'business_pages' => mobile_visible_business_pages(),
            'serviceman_can_cancel_booking' => (int)((business_config('serviceman_can_cancel_booking', 'serviceman_config'))->live_values ?? 0),
            'serviceman_can_edit_booking' => (int)((business_config('serviceman_can_edit_booking', 'serviceman_config'))->live_values ?? 0 ),
            'max_image_upload_size' => uploadMaxFileSize('image'),
            'max_video_upload_size' => uploadMaxFileSize('file'),
            'mobile_app_icons' => $this->providerMobileAppIconsForApi(),
        ]), 200);
    }

    /**
     * @return array<string, array{light: ?string, dark: ?string}>
     */
    private function providerMobileAppIconsForApi(): array
    {
        $mobileAppIcons = app(MobileAppManagementService::class)->iconsForApi();
        $providerMobileAppIcons = $mobileAppIcons['provider'] ?? [];
        if (isset($mobileAppIcons['customer']['customer_app_logo'])) {
            $providerMobileAppIcons['customer_app_logo'] = $mobileAppIcons['customer']['customer_app_logo'];
        }

        return $providerMobileAppIcons;
    }

    public function pageDetails($key)
    {
        $page = BusinessPageSetting::where('page_key', $key)->first();
        return response()->json(response_formatter(DEFAULT_200, $page), 200);
    }

    private function getPaymentMethods(): array
    {
        // Check if the addon_settings table exists
        if (!Schema::hasTable('addon_settings')) {
            return [];
        }

        $methods = DB::table('addon_settings')
            ->where('settings_type', 'payment_config')
            ->where('is_active', 1)
            ->get();
        $env = env('APP_ENV') == 'live' ? 'live' : 'test';
        $credentials = $env . '_values';

        $data = [];
        foreach ($methods as $method) {
            $gateway_image = getPaymentGatewayImageFullPath(key: $method->key_name, settingsType: $method->settings_type, defaultPath: null);
            $credentialsData = json_decode($method->$credentials);
            $additionalData = json_decode($method->additional_data);
            if ($credentialsData && (int) ($credentialsData->status ?? 0) === 1) {
                $data[] = [
                    'gateway' => $method->key_name,
                    'gateway_image_full_path' => $gateway_image,
                    'gateway_title' => $additionalData->gateway_title,
                    'is_active' => 1,
                ];
            }
        }
        return $data;
    }
}
