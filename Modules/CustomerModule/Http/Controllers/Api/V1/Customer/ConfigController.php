<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use App\Traits\MaintenanceModeTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\BusinessSettingsModule\Entities\BusinessPageSetting;
use Modules\BusinessSettingsModule\Entities\DataSetting;
use Modules\BusinessSettingsModule\Entities\ErrorLog;
use Modules\BusinessSettingsModule\Entities\LoginSetup;
use Modules\PaymentModule\Entities\Setting;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;
use Modules\CustomerModule\Services\CustomerApiResponseCache;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneGeometryService;

class ConfigController extends Controller
{
    use MaintenanceModeTrait;

    private $googleMap;
    private $googleMapBaseApi;

    function __construct()
    {
        $this->googleMap = business_config('google_map', 'third_party');
        $this->googleMapBaseApi = 'https://maps.googleapis.com/maps/api';
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function configuration(Request $request): JsonResponse
    {
        $locale = strtolower((string) $request->header('X-localization', app()->getLocale()));
        $content = CustomerApiResponseCache::remember(
            'customer_api_config:v2:'.$locale,
            function () {
                $response = $this->buildConfigurationResponse();
                $decoded = json_decode($response->getContent(), true);

                return is_array($decoded) ? ($decoded['content'] ?? []) : [];
            },
            CustomerApiResponseCache::CONFIG_TTL
        );
        $content['maintenance'] = $this->checkMaintenanceMode();

        $mobileAppService = app(MobileAppManagementService::class);
        $content['mobile_app_icons'] = $mobileAppService->iconsForApi()['customer'] ?? [];
        $content['mobile_app_home'] = $mobileAppService->homeSectionsForApi();

        return response()
            ->json(response_formatter(DEFAULT_200, $content), 200)
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function buildConfigurationResponse(): JsonResponse
    {
        $playstore = business_config('app_url_playstore', 'landing_button_and_links');
        $appstore = business_config('app_url_appstore', 'landing_button_and_links');

        $socialMediaSettings = login_setup('social_media_for_login');
        $getValue = json_decode($socialMediaSettings['value'], true);
        $googleSocialLogin = $getValue['google'];
        $facebookSocialLogin = $getValue['facebook'];
        $appleSocialLogin = $getValue['apple'];

        $advancedBooking = [
            'advanced_booking_restriction_value' => (int)business_config('advanced_booking_restriction_value', 'booking_setup')?->live_values,
            'advanced_booking_restriction_type' => business_config('advanced_booking_restriction_type', 'booking_setup')?->live_values,
            'cart_checkout_leniency_value' => (int)(business_config('cart_checkout_leniency_value', 'booking_setup')?->live_values ?? 0),
            'cart_checkout_leniency_type' => business_config('cart_checkout_leniency_type', 'booking_setup')?->live_values ?? 'minute',
        ];

        $companyWeekends = business_config('company_service_weekends', 'booking_setup')?->live_values;
        $companyWeekends = $companyWeekends ? json_decode($companyWeekends, true) : [];
        $companyServiceAvailability = [
            'enabled' => (int)(business_config('company_service_hours_enabled', 'booking_setup')?->live_values ?? 0),
            'start_time' => business_config('company_service_start_time', 'booking_setup')?->live_values,
            'end_time' => business_config('company_service_end_time', 'booking_setup')?->live_values,
            'weekends' => is_array($companyWeekends) ? $companyWeekends : [],
        ];

        //payment gateways
        $isPublished = 0;
        try {
            $full_data = include('Modules/Gateways/Addon/info.php');
            $isPublished = $full_data['is_published'] == 1 ? 1 : 0;
        } catch (\Exception $exception) {
        }

        $payment_gateways = collect($this->getPaymentMethods())
            ->filter(function ($query) use ($isPublished) {
                if (!$isPublished) {
                    return in_array($query['gateway'], array_column(PAYMENT_METHODS, 'key'));
                } else return $query;
            })->map(function ($query) {
                $query['label'] = ucwords(str_replace('_', ' ', $query['gateway_title']));
                return $query;
            })->values();

        $countryData = business_config('system_language', 'business_information')?->live_values;

        $country = [];

        foreach ($countryData as $item) {
            if ($item['status'] == 1) {
                $country[] = $item;
            }
        }

        $errorLogs = ErrorLog::where('redirect_url', '!=', null)->get();

        $loginOptionsValue = LoginSetup::where(['key' => 'login_options'])?->first()?->value;
        $loginOptions = json_decode($loginOptionsValue);

        $socialMediaLoginValue = LoginSetup::where(['key' => 'social_media_for_login'])?->first()?->value;
        $socialMediaLoginOptions = json_decode($socialMediaLoginValue);


        $customerLogin = [
            'login_option' => $loginOptions,
            'social_media_login_options' => $socialMediaLoginOptions
        ];

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
        $firebaseOtpStatus = (int)$firebaseOtpConfig?->live_values['status'] ?? null;

        if ($firebaseOtpStatus == 1) {
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
            'default_location' => [
                'latitude' => (business_config('address_latitude', 'business_information'))->live_values ?? 23.811842872190,
                'longitude' => (business_config('address_longitude', 'business_information'))->live_values ?? 90.66504678008192
            ],
            'maintenance' => $this->checkMaintenanceMode(),
            'business_name' => (business_config('business_name', 'business_information'))->live_values ?? null,
            'logo_full_path' => getBusinessSettingsImageFullPath(key: 'business_logo', settingType: 'business_information', path: 'business/', defaultPath: 'assets/admin-module/img/media/banner-upload-file.png'),
            'favicon_full_path' => getBusinessSettingsImageFullPath(key: 'business_favicon', settingType: 'business_information', path: 'business/', defaultPath: 'assets/admin-module/img/media/upload-file.png'),
            'country_code' => (business_config('country_code', 'business_information'))->live_values ?? null,
            'business_address' => (business_config('business_address', 'business_information'))->live_values ?? null,
            'business_phone' => (business_config('business_phone', 'business_information'))->live_values ?? null,
            'business_email' => (business_config('business_email', 'business_information'))->live_values ?? null,
            'base_url' => rtrim(url('/'), '/') . '/api/v1/',
            'currency_decimal_point' => (business_config('currency_decimal_point', 'business_information'))->live_values ?? null,
            'currency_code' => (business_config('currency_code', 'business_information'))->live_values ?? null,
            'currency_symbol' => currency_symbol() ?? '',
            'currency_symbol_position' => (business_config('currency_symbol_position', 'business_information'))->live_values ?? null,
            'about_us' => route('about-us'),
            'privacy_policy' => route('privacy-policy'),
            'terms_and_conditions' =>  route('terms-and-conditions'),
            'refund_policy' => DataSetting::where('key', 'refund_policy')->first()->is_active == '1' ? route('refund-policy') : "",
            'cancellation_policy' => DataSetting::where('key', 'cancellation_policy')->first()->is_active == '1' ? route('cancellation-policy') : "",
            'pagination_limit' => (int)pagination_limit(),
            'time_format' => (business_config('time_format', 'business_information'))->live_values ?? '24h',
            'payment_gateways' => $payment_gateways,
            'footer_text' => (business_config('footer_text', 'business_information'))->live_values ?? null,
            'cookies_text' => (business_config('cookies_text', 'business_information'))->live_values ?? null,
            'min_versions' => json_decode((business_config('customer_app_settings', 'app_settings'))->live_values ?? null),
            'app_url_playstore' => $playstore->is_active ? $playstore->live_values : null,
            'app_url_appstore' => $appstore->is_active ? $appstore->live_values : null,
            'web_url' => (business_config('web_url', 'landing_button_and_links'))->is_active == '1' ? (business_config('web_url', 'landing_button_and_links'))->live_values : null,
            'google_social_login' => (int)($googleSocialLogin ?? 0),
            'facebook_social_login' => (int)($facebookSocialLogin ?? 0),
            'apple_social_login' => (int)($appleSocialLogin ?? 0),
            'phone_number_visibility_for_chatting' => (int)((business_config('phone_number_visibility_for_chatting', 'business_information'))->live_values ?? 0),
            'wallet_status' => (int)((business_config('customer_wallet', 'customer_config'))->live_values ?? 0),
            'max_wallet_spend_per_transaction' => max_wallet_spend_per_transaction(),
            'add_to_fund_wallet' => (int)((business_config('add_to_fund_wallet', 'customer_config'))->live_values ?? 0),
            'loyalty_point_status' => (int)((business_config('customer_loyalty_point', 'customer_config'))->live_values ?? 0),
            'referral_earning_status' => (int)((business_config('customer_referral_earning', 'customer_config'))->live_values ?? 0),
            'referral_share_message_template' => (string)((business_config('referral_share_message_template', 'customer_config'))->live_values ?? ''),
            'direct_provider_booking' => (int)((business_config('direct_provider_booking', 'business_information'))->live_values ?? 0),
            'bidding_status' => (int)((business_config('bidding_status', 'bidding_system'))->live_values ?? 0),
            'in_app_call_status' => (int)((business_config('in_app_call_status', 'in_app_call_system'))?->live_values ?? 1),
            'nearby_provider_max_distance_km' => (int)((business_config('nearby_provider_max_distance_km', 'mobile_app_features'))?->live_values ?? 50),
            'phone_verification' => (((login_setup('phone_verification'))->value ?? 0 ) == 1 && $count == 1 ? 1 : 0),
            'email_verification' => (int)((login_setup('email_verification'))->value ?? 0),
            'cash_after_service' => (int)((business_config('cash_after_service', 'service_setup'))->live_values ?? 0),
            'digital_payment' => (int)((business_config('digital_payment', 'service_setup'))->live_values ?? 0),
            'wallet_payment' => (int)((business_config('wallet_payment', 'service_setup'))->live_values ?? 0),
            'social_media' => (business_config('social_media', 'landing_social_media'))->live_values ?? null,
            'otp_resend_time' => (int)(business_config('otp_resend_time', 'otp_login_setup'))?->live_values ?? null,
            'max_booking_amount' => (float)(business_config('max_booking_amount', 'booking_setup'))?->live_values ?? null,
            'min_booking_amount' => (float)(business_config('min_booking_amount', 'booking_setup'))?->live_values ?? null,
            'require_booking_upfront_payment' => require_booking_upfront_payment() ? 1 : 0,
            'booking_confirmation_amount_per_service' => booking_confirmation_amount_per_service(),
            'guest_checkout' => (int)(business_config('guest_checkout', 'service_setup'))?->live_values ?? null,
            'partial_payment' => (int)(business_config('partial_payment', 'service_setup'))?->live_values ?? null,
            'additional_charge_types' => \Modules\BusinessSettingsModule\Entities\AdditionalChargeType::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'sort_order', 'is_commissionable', 'customizable_at_booking'])
                ->values(),
            'offline_payment' => (int)(business_config('offline_payment', 'service_setup'))?->live_values ?? null,
            'partial_payment_combinator' => (string)(business_config('partial_payment_combinator', 'service_setup'))?->live_values ?? null,
            'provider_self_registration' => (int)business_config('provider_self_registration', 'provider_config')?->live_values,
            'confirm_otp_for_complete_service' => (int)business_config('booking_otp', 'booking_setup')?->live_values,
            'instant_booking' => (int)business_config('instant_booking', 'booking_setup')?->live_values,
            'schedule_booking' => (int)business_config('schedule_booking', 'booking_setup')?->live_values,
            'schedule_booking_time_restriction' => (int)business_config('schedule_booking_time_restriction', 'booking_setup')?->live_values,
            'advanced_booking' => $advancedBooking,
            'company_service_availability' => $companyServiceAvailability,
            'system_language' => $country,
            'login_setup' => $customerLogin,
            'firebase_otp_verification' => $firebaseOtpStatus,
            'forgot_password_verification_method' => $forgotPasswordVerificationMethod,
            'error_logs' => $errorLogs,
            'app_environment' => env('APP_ENV'),
            'repeat_booking' => (int)business_config('repeat_booking', 'booking_setup')?->live_values,
            'create_user_account_from_guest_info' => (int)(business_config('create_user_account_from_guest_info', 'business_information'))?->live_values ?? 0,
            'service_at_provider_place' => (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0),
            'newsletter_title' => DataSetting::where('type', 'landing_text_setup')->where('key', 'newsletter_title')->first()->value ?? '',
            'newsletter_description' => DataSetting::where('type', 'landing_text_setup')->where('key', 'newsletter_description')->first()->value ?? '',
            'business_pages' => mobile_visible_business_pages(),
            'admin_details' => customer_api_admin_details(),
            'max_image_upload_size' => uploadMaxFileSize('image'),
            'max_video_upload_size' => uploadMaxFileSize('file'),
            'map_api_key_client' => $this->googleMap?->live_values['map_api_key_client'] ?? '',
            'mobile_app_home' => app(MobileAppManagementService::class)->homeSectionsForApi(),
            'mobile_app_icons' => app(MobileAppManagementService::class)->iconsForApi()['customer'] ?? [],
        ]), 200);
    }

    public function pages(): JsonResponse
    {
        return response()->json(response_formatter(DEFAULT_200, [
            'about_us' => DataSetting::where('type', 'pages_setup')->where('key', 'about_us')->first(),
            'terms_and_conditions' => DataSetting::where('type', 'pages_setup')->where('key', 'terms_and_conditions')->first(),
            'refund_policy' => DataSetting::where('type', 'pages_setup')->where('key', 'refund_policy')->first(),
            'return_policy' => DataSetting::where('type', 'pages_setup')->where('key', 'return_policy')->first(),
            'cancellation_policy' => DataSetting::where('type', 'pages_setup')->where('key', 'cancellation_policy')->first(),
            'privacy_policy' => DataSetting::where('type', 'pages_setup')->where('key', 'privacy_policy')->first(),
//            'images' => collect([
//                'about_us' => getDataSettingsImageFullPath(key: 'about_us_image', settingType: 'pages_setup_image', path: 'page-setup/', defaultPath: asset('assets/admin-module/img/page-default.png')),
//                'terms_and_conditions' => getDataSettingsImageFullPath(key: 'terms_and_conditions_image', settingType: 'pages_setup_image', path: 'page-setup/', defaultPath: asset('assets/admin-module/img/page-default.png')),
//                'refund_policy' => getDataSettingsImageFullPath(key: 'refund_policy_image', settingType: 'pages_setup_image', path: 'page-setup/', defaultPath: asset('assets/admin-module/img/page-default.png')),
//                'return_policy' => getDataSettingsImageFullPath(key: 'return_policy_image', settingType: 'pages_setup_image', path: 'page-setup/', defaultPath: asset('assets/admin-module/img/page-default.png')),
//                'cancellation_policy' => getDataSettingsImageFullPath(key: 'cancellation_policy_image', settingType: 'pages_setup_image', path: 'page-setup/', defaultPath: asset('assets/admin-module/img/page-default.png')),
//                'privacy_policy' => getDataSettingsImageFullPath(key: 'privacy_policy_image', settingType: 'pages_setup_image', path: 'page-setup/', defaultPath: asset('assets/admin-module/img/page-default.png')),
//            ]),
        ]), 200);
    }

    public function pageDetails($key)
    {
        $page = BusinessPageSetting::where('page_key', $key)->first();
        return response()->json(response_formatter(DEFAULT_200, $page), 200);
    }


    public function getZone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $point = new Point($request->lat, $request->lng);
        $zone = app(ZoneGeometryService::class)->resolveLeafZoneForPoint($point);

        if ($zone) {
            try {
                $zone['formatted_coordinates'] = formatCoordinates($zone->coordinates);
            } catch (\Throwable $e) {
                report($e);
                $zone['formatted_coordinates'] = [];
            }

            $services = Service::withoutGlobalScope('zone_wise_data')->where('is_active', 1)->whereHas('category', function ($query) use ($zone) {
                $query->OfStatus(1)->withoutGlobalScope('zone_wise_data')->whereHas('zones', function ($query) use ($zone) {
                    $query->where('zone_id', $zone->id);
                });
            })->count();

            return response()->json(response_formatter(DEFAULT_200, [
                'zone' => $zone,
                'available_services_count' => $services,
            ]), 200);
        }

        return response()->json(response_formatter(ZONE_RESOURCE_404), 200);
    }

    public function placeApiAutocomplete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $searchText = $request->input('search_text');
        $apiKey = $this->googleMap->live_values['map_api_key_server'] ?? '';

        $url = 'https://places.googleapis.com/v1/places:autocomplete';
        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => '*',
        ];

        $response = Http::withHeaders($headers)->post($url, ['input' => $searchText]);
        $payload = $response->json();

        if ($response->successful() && empty($payload['error']) && !empty($payload['suggestions'])) {
            return response()->json(response_formatter(DEFAULT_200, $payload), 200);
        }

        return response()->json(
            response_formatter(DEFAULT_200, $this->legacyPlaceAutocomplete($searchText, $apiKey)),
            200
        );
    }

    public function distanceApi(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required',
            'origin_lng' => 'required',
            'destination_lat' => 'required',
            'destination_lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $url = 'https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix';

        $origin = [
            "waypoint" => [
                "location" => [
                    "latLng" => [
                        "latitude" =>  $request['origin_lat'],
                        "longitude" => $request['origin_lng']
                    ]
                ]
            ]
        ];

        $destination = [
            "waypoint" => [
                "location" => [
                    "latLng" => [
                        "latitude" => $request['destination_lat'],
                        "longitude" => $request['destination_lng']
                    ]
                ]
            ]
        ];

        $data = [
            "origins" => $origin,
            "destinations" => $destination,
            "travelMode" => "DRIVE",
            "routingPreference" => "TRAFFIC_AWARE"
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $this->googleMap->live_values['map_api_key_server'],
            'X-Goog-FieldMask' => '*'
        ];

        $response = Http::withHeaders($headers)->post($url, $data);

        return response()->json(response_formatter(DEFAULT_200, $response->json()), 200);
    }

    public function placeApiDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $placeId = $request['placeid'];
        $apiKey = $this->googleMap->live_values['map_api_key_server'] ?? '';

        $url = 'https://places.googleapis.com/v1/places/' . $placeId;
        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => '*',
        ];

        $response = Http::withHeaders($headers)->get($url);
        $payload = $response->json();

        if ($response->successful() && empty($payload['error']) && !empty($payload['location'])) {
            return response()->json(response_formatter(DEFAULT_200, $payload), 200);
        }

        return response()->json(
            response_formatter(DEFAULT_200, $this->legacyPlaceDetails($placeId, $apiKey)),
            200
        );
    }

    public function geocodeApi(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $apiKey = $this->googleMap?->live_values['map_api_key_server'] ?? '';
        if ($apiKey === '') {
            return response()->json(response_formatter(DEFAULT_400, null, [
                ['error_code' => 'map_api_key', 'message' => 'Google Maps API key is not configured'],
            ]), 400);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $request->lat . ',' . $request->lng . '&key=' . $apiKey);

        return response()->json(response_formatter(DEFAULT_200, $response->json()), 200);
    }

    /**
     * Legacy Places Autocomplete (maps.googleapis.com) — used when Places API (New) is unavailable.
     */
    private function legacyPlaceAutocomplete(string $searchText, string $apiKey): array
    {
        $response = Http::get($this->googleMapBaseApi . '/place/autocomplete/json', [
            'input' => $searchText,
            'key' => $apiKey,
        ]);

        $body = $response->json();
        $suggestions = [];

        foreach ($body['predictions'] ?? [] as $prediction) {
            $suggestions[] = [
                'placePrediction' => [
                    'placeId' => $prediction['place_id'] ?? '',
                    'text' => [
                        'text' => $prediction['description'] ?? '',
                    ],
                ],
            ];
        }

        return ['suggestions' => $suggestions];
    }

    /**
     * Legacy Place Details — normalized to the shape expected by mobile apps.
     */
    private function legacyPlaceDetails(string $placeId, string $apiKey): array
    {
        $response = Http::get($this->googleMapBaseApi . '/place/details/json', [
            'place_id' => $placeId,
            'key' => $apiKey,
            'fields' => 'geometry,formatted_address,address_component',
        ]);

        $result = $response->json()['result'] ?? [];
        $location = $result['geometry']['location'] ?? [];

        $addressComponents = [];
        foreach ($result['address_components'] ?? [] as $component) {
            $addressComponents[] = [
                'long_name' => $component['long_name'] ?? '',
                'short_name' => $component['short_name'] ?? '',
                'types' => $component['types'] ?? [],
            ];
        }

        return [
            'location' => [
                'latitude' => $location['lat'] ?? 0,
                'longitude' => $location['lng'] ?? 0,
            ],
            'formattedAddress' => $result['formatted_address'] ?? '',
            'address_components' => $addressComponents,
        ];
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
            $additional_data = json_decode($method->additional_data);
            if ($credentialsData && (int) ($credentialsData->status ?? 0) === 1) {
                $data[] = [
                    'gateway' => $method->key_name,
                    'gateway_image_full_path' => $gateway_image,
                    'gateway_title' => $additional_data->gateway_title,
                    'is_active' => 1,
                ];
            }
        }
        return $data;
    }

}
