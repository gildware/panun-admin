<?php

namespace Modules\BookingModule\Http\Controllers\Api\V1\Customer;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CustomerModule\Services\CustomerBookingListPayloadSlimmer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\BidModule\Entities\PostBid;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\BookingOfflinePayment;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\UserManagement\Entities\User;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\PaymentModule\Entities\OfflinePayment;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\CustomerModule\Traits\CustomerAddressTrait;
use Illuminate\Validation\Rule;
use Modules\BookingModule\Entities\BookingCustomerCancellationReason;
use Modules\BookingModule\Services\CustomerBookingCancellationService;
use Modules\BidModule\Http\Controllers\APi\V1\Customer\PostBidController;
use App\Lib\BookingInvoiceUrl;
use App\Lib\BookingTrackToken;
use App\Lib\PaymentAccessToken;
use App\Services\GuestCheckoutService;
use App\Services\GuestSessionService;

class BookingController extends Controller
{
    use BookingTrait, CustomerAddressTrait;

    private Booking $booking;
    private BookingStatusHistory $bookingStatusHistory;

    protected OfflinePayment $offlinePayment;
    private BookingRepeat $bookingRepeat;
    private bool $isCustomerLoggedIn;
    private mixed $customerUserId;

    public function __construct(Booking $booking, BookingStatusHistory $bookingStatusHistory, Request $request, OfflinePayment $offlinePayment, BookingRepeat $bookingRepeat)
    {
        $this->booking = $booking;
        $this->bookingStatusHistory = $bookingStatusHistory;
        $this->offlinePayment = $offlinePayment;
        $this->bookingRepeat = $bookingRepeat;

        $user = api_user();
        $this->isCustomerLoggedIn = (bool) $user;
        $this->customerUserId = $this->isCustomerLoggedIn ? $user->id : GuestSessionService::resolveGuestId($request);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function placeRequest(Request $request): JsonResponse
    {
        if ($reject = $this->rejectUnlessGuestBookingAllowed($request)) {
            return $reject;
        }

        $serviceAtProviderPlace = (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0);

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:' . implode(',', array_column(PAYMENT_METHODS, 'key')),
            'zone_id' => 'required|uuid',
            'service_schedule' => 'required_if:service_type,regular|nullable|date',
            'service_address_id' => is_null($request['service_address']) ? 'required' : 'nullable',

            'post_id' => 'nullable|uuid',
            'provider_id' => 'nullable|uuid',

            'guest_id' => $this->isCustomerLoggedIn ? 'nullable' : 'required|uuid',
            'service_address' => is_null($request['service_address_id']) && $request['service_location'] == 'customer' ? [
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail($attribute . ' must be a valid JSON string.');
                        return;
                    }

                    if (is_null($decoded['lat']) || $decoded['lat'] == '') $fail($attribute . ' must contain "lat" properties.');
                    if (is_null($decoded['lon']) || $decoded['lon'] == '') $fail($attribute . ' must contain "lon" properties.');
                    if (is_null($decoded['address']) || $decoded['address'] == '') $fail($attribute . ' must contain "address" properties.');
                    if (is_null($decoded['contact_person_name']) || $decoded['contact_person_name'] == '') $fail($attribute . ' must contain "contact_person_name" properties.');
                    if (is_null($decoded['contact_person_number']) || $decoded['contact_person_number'] == '') $fail($attribute . ' must contain "contact_person_number" properties.');
                    if (is_null($decoded['address_label']) || $decoded['address_label'] == '') $fail($attribute . ' must contain "address_label" properties.');
                },
            ] : '',

            'is_partial' => 'nullable|in:0,1',
            'payment_amount_type' => 'nullable|in:confirmation,full',
            'service_location' => 'required|in:customer,provider',
            function ($attribute, $value, $fail) use ($serviceAtProviderPlace) {
                if ($value == 'provider' && $serviceAtProviderPlace != 1) {
                    $fail('The selected service location cannot be "provider" because the service is not available at the provider’s place.');
                }
            },
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($this->isCustomerLoggedIn) {
            $customer = auth('api')->user();
            $manualStatus = (string) ($customer->manual_performance_status ?? '');

            if ($manualStatus === 'blacklisted') {
                return response()->json(response_formatter([
                    'response_code' => 'auth_login_401',
                    'message' => translate('Your account is blacklisted. Please contact with admin'),
                ]), 401);
            }

            if ($manualStatus === 'suspended') {
                $until = $customer->performance_suspended_until ? Carbon::parse($customer->performance_suspended_until) : null;
                if ($until && $until->isFuture()) {
                    return response()->json(response_formatter([
                        'response_code' => 'auth_login_401',
                        'message' => translate('Your account is suspended until') . ' ' . $until->format('Y-m-d H:i'),
                    ]), 401);
                }

                $customer->manual_performance_status = 'active';
                $customer->performance_suspended_until = null;
                $customer->save();
            }
        }

        $newUserInfo = null;
        // Additional validation and register for new_user_info
        if ($request->has('new_user_info') && !empty($request->get('new_user_info')) && !$this->isCustomerLoggedIn) {
            $newUserInfo = json_decode($request['new_user_info'], true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($newUserInfo)) {
                return response()->json(response_formatter(DEFAULT_400, null, 'Invalid new_user_info format'), 400);
            }

            $newUserValidator = Validator::make($newUserInfo, [
                'first_name' => 'required',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                'password' => 'required|min:8',
            ]);

            if ($newUserValidator->fails()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($newUserValidator)), 400);
            }
        }

        $customerUserId = $this->customerUserId;

        if (is_null($request['service_address_id'])) {
            $request['service_address_id'] = $this->add_address(json_decode($request['service_address']), null, !$this->isCustomerLoggedIn, $request->service_location);
        }

        $minimumBookingAmount = (float)(business_config('min_booking_amount', 'booking_setup'))?->live_values;
        $totalBookingAmount = cart_total($customerUserId) + getServiceFee($customerUserId);

        if (!isset($request['post_id']) && $minimumBookingAmount > 0 && $totalBookingAmount < $minimumBookingAmount) {
            return response()->json(response_formatter(MINIMUM_BOOKING_AMOUNT_200), 200);
        }

        if (!isset($request['post_id']) && require_booking_upfront_payment()) {
            if ($request['payment_method'] === 'cash_after_service') {
                return response()->json(response_formatter(DEFAULT_400, null, [
                    ['error_code' => 'payment', 'message' => translate('Booking requires upfront payment. Please pay confirmation or full amount.')],
                ]), 400);
            }

            $paymentAmountType = $request['payment_amount_type'] ?? '';
            if (!in_array($paymentAmountType, ['confirmation', 'full'], true)) {
                return response()->json(response_formatter(DEFAULT_400, null, [
                    ['error_code' => 'payment_amount_type', 'message' => translate('Please select confirmation or full payment.')],
                ]), 400);
            }

            $payAmount = resolve_checkout_payment_amount($customerUserId, $paymentAmountType);
            if ($payAmount <= 0) {
                return response()->json(response_formatter(DEFAULT_400, null, [
                    ['error_code' => 'payment', 'message' => translate('Invalid payment amount.')],
                ]), 400);
            }
        }

        if ($request['payment_method'] == 'wallet_payment') {
            if (! customer_wallet_feature_enabled() || ! wallet_payment_feature_enabled()) {
                return response()->json(response_formatter(DEFAULT_400, null, [
                    ['error_code' => 'payment', 'message' => translate('Wallet payment is not available.')],
                ]), 400);
            }
            if (!isset($request['post_id'])) {
                $walletPayType = require_booking_upfront_payment()
                    ? ($request['payment_amount_type'] ?? 'full')
                    : 'full';
                $walletRequired = resolve_checkout_payment_amount($customerUserId, $walletPayType);
                $user = User::find($customerUserId);
                if (isset($user) && $user->wallet_balance < $walletRequired) {
                    return response()->json(response_formatter(INSUFFICIENT_WALLET_BALANCE_400), 400);
                }
                if (! $request['is_partial'] && wallet_spend_exceeds_per_transaction_limit($walletRequired)) {
                    return response()->json(response_formatter(WALLET_MAX_SPEND_PER_TRANSACTION_400), 400);
                }
                $response = $this->placeBookingRequest(userId: $customerUserId, request: $request, transactionId: 'wallet_payment', newUserInfo: $newUserInfo);
            } else {
                $postBid = PostBid::with(['post.service.category', 'post.service.subCategory'])
                    ->where('post_id', $request['post_id'])
                    ->where('provider_id', $request['provider_id'])
                    ->first();

                $bidService = $postBid?->post?->service;
                $bidTaxPct = effective_service_tax_percentage($bidService);

                $data = [
                    'payment_method' => $request['payment_method'],
                    'zone_id' => $request['zone_id'],
                    'service_tax' => $bidTaxPct,
                    'provider_id' => $postBid->provider_id,
                    'price' => $postBid->offered_price,
                    'service_schedule' => !is_null($request['booking_schedule']) ? $request['booking_schedule'] : $postBid->post->booking_schedule,
                    'service_id' => $postBid->post->service_id,
                    'category_id' => $postBid->post->category_id,
                    'sub_category_id' => $postBid->post->category_id,
                    'service_address_id' => !is_null($request['service_address_id']) ? $request['service_address_id'] : $postBid->post->service_address_id,
                    'is_partial' => $request['is_partial']
                ];

                $user = User::find($customerUserId);
                $tax = round(($postBid->offered_price * $bidTaxPct) / 100, 2);
                $bidWalletAmount = (float) $postBid->offered_price + $tax;
                if (isset($user) && $user->wallet_balance < $bidWalletAmount) {
                    return response()->json(response_formatter(INSUFFICIENT_WALLET_BALANCE_400), 400);
                }
                if (! $request['is_partial'] && wallet_spend_exceeds_per_transaction_limit($bidWalletAmount)) {
                    return response()->json(response_formatter(WALLET_MAX_SPEND_PER_TRANSACTION_400), 400);
                }

                $response = $this->placeBookingRequestForBidding($customerUserId, $request, 'wallet_payment', $data);

                if ($response['flag'] == 'success') {
                    PostBidController::acceptPostBidOffer($postBid->id, $response['booking_id']);
                }
            }

        } elseif ($request['payment_method'] == 'offline_payment') {
            if (!isset($request['post_id'])) {
                $response = $this->placeBookingRequest($customerUserId, $request, 'offline-payment', newUserInfo: $newUserInfo, isGuest: !$this->isCustomerLoggedIn);
            } else {
                $postBid = PostBid::with(['post.service.category', 'post.service.subCategory'])
                    ->where('post_id', $request['post_id'])
                    ->where('provider_id', $request['provider_id'])
                    ->first();

                $bidTaxPctOffline = effective_service_tax_percentage($postBid?->post?->service);

                $data = [
                    'payment_method' => $request['payment_method'],
                    'zone_id' => $request['zone_id'],
                    'service_tax' => $bidTaxPctOffline,
                    'provider_id' => $postBid->provider_id,
                    'price' => $postBid->offered_price,
                    'service_schedule' => !is_null($request['booking_schedule']) ? $request['booking_schedule'] : $postBid->post->booking_schedule,
                    'service_id' => $postBid->post->service_id,
                    'category_id' => $postBid->post->category_id,
                    'sub_category_id' => $postBid->post->category_id,
                    'service_address_id' => !is_null($request['service_address_id']) ? $request['service_address_id'] : $postBid->post->service_address_id,
                    'is_partial' => $request['is_partial']
                ];

                $response = $this->placeBookingRequestForBidding($customerUserId, $request, 'offline_payment', $data);

                if ($response['flag'] == 'success') {
                    PostBidController::acceptPostBidOffer($postBid->id, $response['booking_id']);
                }
            }
        } else {
            if ($request['service_type'] == 'repeat'){
                $response = $this->placeRepeatBookingRequest($customerUserId, $request, 'cash-payment', newUserInfo: $newUserInfo, isGuest: !$this->isCustomerLoggedIn);
            }else{
                $response = $this->placeBookingRequest($customerUserId, $request, 'cash-payment', newUserInfo: $newUserInfo, isGuest: !$this->isCustomerLoggedIn);
            }
        }

        if ($response['flag'] == 'success') {
            return response()->json(response_formatter(BOOKING_PLACE_SUCCESS_200, $response), 200);
        }

        if (($response['message'] ?? '') === 'wallet_max_spend_per_transaction') {
            return response()->json(response_formatter(WALLET_MAX_SPEND_PER_TRANSACTION_400), 400);
        }

        return response()->json(response_formatter(BOOKING_PLACE_FAIL_200), 200);
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'booking_status' => 'required|in:' . implode(',', booking_api_list_filter_status_keys()),
            'service_type' => 'required|in:all,regular,repeat',
            'string' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bookings = $this->booking
            ->with([
                'extra_services:id,booking_id,total',
                'repeat:id,booking_id,readable_id,booking_status,service_schedule',
            ])
            ->with(['customizeBooking:id,booking_id'])
            ->where(['customer_id' => $request->user()->id])
            ->search(base64_decode($request['string']), ['readable_id'])
            ->when($request['booking_status'] != 'all', function ($query) use ($request) {
                $query->applyBookingListStatusTab($request['booking_status']);
            })
            ->when($request['service_type'] != 'all', function ($query) use ($request) {
                return $query->ofRepeatBookingStatus($request['service_type'] === 'repeat' ? 1 : ($request['service_type'] === 'regular' ? 0 : null));
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        $countBase = $this->booking->newQuery()
            ->where(['customer_id' => $request->user()->id])
            ->when($request['service_type'] != 'all', function ($query) use ($request) {
                return $query->ofRepeatBookingStatus($request['service_type'] === 'repeat' ? 1 : ($request['service_type'] === 'regular' ? 0 : null));
            });

        $bookings_count = booking_api_list_status_tab_counts($countBase, []);

        foreach ($bookings as $booking) {
            if ($booking->repeat->isNotEmpty()) {
                $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                    $parts = explode('-', $repeat->readable_id);
                    $suffix = end($parts);
                    return $this->readableIdToNumber($suffix);
                });
                $booking->repeats = $sortedRepeats->values()->toArray();
            }
            $booking->is_customize_booking = $booking->customizeBooking ? 1 : 0;

            $listDisplayTotal = get_customer_booking_list_display_total($booking);
            $originalGrandTotal = round((float) get_booking_total_amount($booking), 2);
            $booking->setAttribute('list_display_total', $listDisplayTotal);
            $booking->setAttribute('payable_grand_total', $originalGrandTotal);
            booking_append_customer_api_ui_fields($booking);

            unset($booking->repeat);
            unset($booking->customizeBooking);
            unset($booking->extra_services);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'bookings_count' => $bookings_count,
            'bookings' => CustomerBookingListPayloadSlimmer::slimPaginator($bookings),
        ]), 200);
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = $this->booking
            ->where(['customer_id' => $request->user()->id])
            ->with([
                'detail.service',
                'schedule_histories.user',
                'status_histories.user',
                'status_histories.holdReopenReason',
                'change_logs.changedBy',
                'customer',
                'provider',
                'category',
                'subCategory:id,name',
                'serviceman.user',
                'booking_partial_payments',
                'booking_offline_payments',
                'extra_services',
                'repeat.scheduleHistories',
                'repeat.repeatHistories'
            ])
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('readable_id', $id);
            })
            ->first();

        if (isset($booking)) {
            $offlinePayment = $booking->booking_offline_payments?->first();

            if ($offlinePayment) {
                $booking->booking_offline_payment_method = $offlinePayment->method_name;
                $booking->booking_offline_payment = collect($offlinePayment->customer_information)->map(function ($value, $key) {
                    return ["key" => $key, "value" => $value];
                })->values()->all();

                $booking->offline_payment_id = $offlinePayment->offline_payment_id ?? null;
                $booking->offline_payment_status = $offlinePayment->payment_status ?? null;
                $booking->offline_payment_denied_note = $offlinePayment->denied_note ?? null;
            }

            $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

            unset($booking->booking_offline_payments, $booking->service_address_location);

            if (isset($booking->provider)){
                $booking->provider->chatEligibility = chatEligibility($booking->provider_id);
            }

            if ($booking->repeat->isNotEmpty()) {
                $repeatHistoryCollection = $booking->repeat->flatMap(function ($repeat) {
                    return $repeat->repeatHistories->map(function ($history) {
                        $history->log_details = json_decode($history->log_details);
                        return $history;
                    });
                });

                $booking['repeatHistory'] = $repeatHistoryCollection->toArray();
                $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                    $parts = explode('-', $repeat->readable_id);
                    $suffix = end($parts);
                    $repeat['service_address'] = json_decode($repeat->service_address_location);
                    unset($repeat->service_address_location);
                    return $this->readableIdToNumber($suffix);
                });
                $booking['repeats'] = $sortedRepeats->values()->toArray();

                $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'accepted');
                if (!$nextService) {
                    $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'pending');
                }

                $booking['nextService'] = $nextService;
                $booking['time'] = max(collect($booking['repeats'])->pluck('service_schedule')->flatten()->toArray());
                $booking['startDate'] = min(collect($booking['repeats'])->pluck('service_schedule')->flatten()->toArray());
                $booking['endDate'] = max(collect($booking['repeats'])->pluck('service_schedule')->flatten()->toArray());
                $booking['totalCount'] = count($booking['repeats']);
                $booking['bookingType'] = $booking['repeats'][0]['booking_type'];

                if ($booking['bookingType'] == 'weekly') {
                    $booking['weekNames'] = collect($booking['repeats'])
                        ->pluck('service_schedule')
                        ->map(function ($schedule) {
                            return \Carbon\Carbon::parse($schedule)->format('l');
                        })
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                }

                $booking['completedCount'] = collect($booking['repeats'])->where('booking_status', 'completed')->count();
                $booking['canceledCount'] = collect($booking['repeats'])->where('booking_status', 'canceled')->count();

                unset($booking->repeat);
                $booking['repeats'] = array_map(function($repeat) {
                    if (isset($repeat['repeat_histories'])) {
                        unset($repeat['repeat_histories']);
                    }
                    return $repeat;
                }, $booking['repeats']);
            }

            $booking->is_customize_booking = $booking->customizeBooking ? 1 : 0;
            unset($booking->customizeBooking);

            booking_append_customer_api_financial_fields($booking);
            booking_append_customer_api_ui_fields($booking);
            booking_attach_api_change_logs($booking);

            return response()->json(response_formatter(DEFAULT_200, $booking), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 204);
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function singleDetails(Request $request, string $id): JsonResponse
    {
        $booking = $this->bookingRepeat->with([
            'detail.service',
            'scheduleHistories.user',
            'statusHistories.user',
            'booking.customer',
            'booking.booking_partial_payments',
            'booking.booking_offline_payments',
            'booking.extra_services',
            'provider',
            'serviceman.user',
        ])->where(['id' => $id])->first();

        $booking->booking->service_address = $booking->booking->service_address_location != null ? json_decode($booking->booking->service_address_location) : $booking->booking->service_address;

        if (isset($booking)) {
            if (isset($booking->provider)){
                $booking->provider->chatEligibility = chatEligibility($booking->provider_id);
            }
            booking_append_customer_api_financial_fields($booking);
            booking_append_customer_api_ui_fields($booking);
            booking_attach_api_change_logs($booking, (string) $booking->id);
            return response()->json(response_formatter(DEFAULT_200, $booking), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 204);
    }
    /**
     * Show the specified resource.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function track(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'track_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if (!BookingTrackToken::validate($request['track_token'], (string) $id, (string) $request['phone'])) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $phone = (string) $request['phone'];
        $booking = $this->booking
            ->with(['detail.service', 'schedule_histories.user', 'status_histories.user', 'change_logs.changedBy', 'customer', 'provider', 'zone', 'serviceman.user', 'service_address'])
            ->where(['readable_id' => $id])
            ->first();

        if ($booking === null || !BookingTrackToken::phoneMatches($this->bookingContactNumber($booking), $phone)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        unset($booking->service_address_location);

        booking_attach_api_change_logs($booking);

        return response()->json(response_formatter(DEFAULT_200, $booking), 200);
    }

    public function trackAccessToken(Request $request, string $readableId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $phone = (string) $request['phone'];
        $booking = $this->booking
            ->with('service_address')
            ->where('readable_id', $readableId)
            ->first();

        if ($booking === null || !BookingTrackToken::phoneMatches($this->bookingContactNumber($booking), $phone)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'track_token' => BookingTrackToken::issue($readableId, $phone),
            'expires_in_minutes' => 1440,
        ]), 200);
    }

    /**
     * Active cancellation reasons for customer-initiated booking cancellations.
     */
    public function customerCancellationReasons(Request $request): JsonResponse
    {
        $reasons = BookingCustomerCancellationReason::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json(response_formatter(DEFAULT_200, $reasons), 200);
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @param string $booking_id
     * @return JsonResponse
     */
    public function statusUpdate(Request $request, string $booking_id): JsonResponse
    {
        $booking = $this->booking->where('id', $booking_id)->where('customer_id', $request->user()->id)->first();

        $refundBreakdown = $booking instanceof Booking
            ? get_booking_customer_refund_channel_breakdown($booking)
            : [
                'wallet_paid' => 0.0,
                'digital_paid' => 0.0,
                'requires_digital_refund_choice' => false,
            ];
        $requiresRefundMethod = (float) ($refundBreakdown['digital_paid'] ?? 0) > 0.009;

        $validator = Validator::make($request->all(), [
            'booking_status' => 'required|in:canceled',
            'booking_customer_cancellation_reason_id' => [
                'required',
                'integer',
                Rule::exists('booking_customer_cancellation_reasons', 'id')->where(fn ($q) => $q->where('is_active', 1)),
            ],
            'status_change_remarks' => 'nullable|string|max:2000',
            'refund_method' => $requiresRefundMethod
                ? 'required|in:wallet,transfer'
                : 'nullable|in:wallet,transfer',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($booking === null) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        if ($booking->booking_status == 'accepted' && $request['booking_status'] == 'canceled') {
            return response()->json(response_formatter(BOOKING_ALREADY_ACCEPTED), 200);
        }

        if ($booking->booking_status == 'ongoing' && $request['booking_status'] == 'canceled') {
            return response()->json(response_formatter(BOOKING_ALREADY_ONGOING), 200);
        }

        if ($booking->booking_status == 'completed' && $request['booking_status'] == 'canceled') {
            return response()->json(response_formatter(BOOKING_ALREADY_COMPLETED), 200);
        }

        try {
            $booking = app(CustomerBookingCancellationService::class)->cancelParentBooking(
                $booking,
                $request->user(),
                (int) $request->input('booking_customer_cancellation_reason_id'),
                $request->input('status_change_remarks'),
                $request->input('refund_method'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(response_formatter(DEFAULT_400, null, ['refund_method' => [$e->getMessage()]]), 400);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            if ($message === translate('Booking_already_accepted')) {
                return response()->json(response_formatter(BOOKING_ALREADY_ACCEPTED), 200);
            }
            if ($message === translate('Booking_already_ongoing')) {
                return response()->json(response_formatter(BOOKING_ALREADY_ONGOING), 200);
            }
            if ($message === translate('Booking_already_completed')) {
                return response()->json(response_formatter(BOOKING_ALREADY_COMPLETED), 200);
            }
            if ($message === translate('Booking_already_canceled')) {
                return response()->json(response_formatter(BOOKING_ALREADY_CANCELED_200), 200);
            }

            return response()->json(response_formatter(DEFAULT_400, null, ['booking_status' => [$message]]), 400);
        }

        return response()->json(response_formatter(BOOKING_STATUS_UPDATE_SUCCESS_200, $booking), 200);
    }

    /**
     * @param Request $request
     * @param string $repeatId
     * @return JsonResponse
     */
    public function singleBookingCancel(Request $request, string $repeatId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_customer_cancellation_reason_id' => [
                'required',
                'integer',
                Rule::exists('booking_customer_cancellation_reasons', 'id')->where(fn ($q) => $q->where('is_active', 1)),
            ],
            'status_change_remarks' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customerId = $request->user()->id;
        $repeat = $this->bookingRepeat->where('id', $repeatId)->first();
        if ($repeat === null) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        $booking = $this->booking->where('id', $repeat->booking_id)->where('customer_id', $customerId)->first();
        if ($booking === null) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        if ((string) ($repeat->booking_status ?? '') === 'canceled') {
            return response()->json(response_formatter(BOOKING_ALREADY_CANCELED_200), 200);
        }

        try {
            app(CustomerBookingCancellationService::class)->cancelRepeatBooking(
                $booking,
                $repeat,
                $request->user(),
                (int) $request->input('booking_customer_cancellation_reason_id'),
                $request->input('status_change_remarks'),
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === translate('Booking_already_canceled')) {
                return response()->json(response_formatter(BOOKING_ALREADY_CANCELED_200), 200);
            }

            return response()->json(response_formatter(DEFAULT_400, null, ['booking_status' => [$e->getMessage()]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function storeOfflinePaymentData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offline_payment_id' => 'required',
            'customer_information' => 'required',
            'booking_id' => 'required',
            'is_partial' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($reject = $this->assertBookingAccess($request)) {
            return $reject;
        }

        // Retrieve booking
        $booking = $this->booking->find($request->booking_id);
        if (!$booking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        if ($reject = $this->assertBookingOwnership($booking)) {
            return $reject;
        }

        $offlinePaymentData = $this->offlinePayment->find($request['offline_payment_id']);
        if (!$offlinePaymentData) {
            return response()->json(response_formatter(DEFAULT_400, null, 'Invalid offline payment ID.'), 400);
        }

        $fields = array_column($offlinePaymentData->customer_information, 'field_name');
        $customerInformation = (array)json_decode(base64_decode($request['customer_information']))[0];

        foreach ($fields as $field) {
            if (!key_exists($field, $customerInformation)) {
                return response()->json(response_formatter(DEFAULT_400, $fields, null), 400);
            }
        }

        // Handle partial payment if applicable
        if ($request->is_partial) {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(response_formatter(DEFAULT_401), 401);
            }
            $walletBalance = $user->wallet_balance;

            if ($walletBalance <= 0 || $walletBalance >= $booking->total_booking_amount) {
                return response()->json(response_formatter(DEFAULT_400, null, 'Invalid partial payment data.'), 400);
            }

            $paidAmount = cap_wallet_spend_for_single_transaction((float) $walletBalance);
            $dueAmount = $booking->total_booking_amount - $paidAmount;

            // Save wallet payment
            BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => 'wallet',
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ]);

            // Save remaining payment
            BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => 'offline_payment',
                'paid_amount' => $dueAmount,
                'due_amount' => 0,
            ]);

            placeBookingTransactionForPartialDigital($booking);
        }

        // Check if the booking_id already exists
        $existingPayment = BookingOfflinePayment::where('booking_id', $request->booking_id)->first();

        $customerInformation = (array)json_decode(base64_decode($request['customer_information']))[0];

        if ($existingPayment) {
            // If it exists, update with new data
            $existingPayment->offline_payment_id = $request['offline_payment_id'];
            $existingPayment->method_name = OfflinePayment::find($request['offline_payment_id'])?->method_name;
            $existingPayment->customer_information = $customerInformation;
            $existingPayment->payment_status = 'pending';
            $existingPayment->save();
        } else {
            // If no existing record, create a new one
            $bookingOfflinePayment = new BookingOfflinePayment();
            $bookingOfflinePayment->booking_id = $request->booking_id;
            $bookingOfflinePayment->offline_payment_id = $request['offline_payment_id'];
            $bookingOfflinePayment->method_name = OfflinePayment::find($request['offline_payment_id'])?->method_name;
            $bookingOfflinePayment->customer_information = $customerInformation;
            $bookingOfflinePayment->payment_status = 'pending';
            $bookingOfflinePayment->save();
        }

        $booking->update(['payment_method' => 'offline_payment']);

        return response()->json(response_formatter(OFFLINE_PAYMENT_SUCCESS_200), 200);
    }

    public function switchPaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required',
            'payment_method' => 'required',
            'offline_payment_id' => 'required_if:payment_method,offline_payment',
            'customer_information' => 'required_if:payment_method,offline_payment',
            'is_partial' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($reject = $this->assertBookingAccess($request)) {
            return $reject;
        }

        // Retrieve booking
        $booking = $this->booking->find($request->booking_id);
        if (!$booking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        if ($reject = $this->assertBookingOwnership($booking)) {
            return $reject;
        }

        // Handle partial payment if applicable
        if ($request->is_partial) {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(response_formatter(DEFAULT_401), 401);
            }
            $walletBalance = $user->wallet_balance;

            if ($walletBalance <= 0 || $walletBalance >= $booking->total_booking_amount) {
                return response()->json(response_formatter(DEFAULT_400, null, 'Invalid partial payment data.'), 400);
            }

            $paidAmount = cap_wallet_spend_for_single_transaction((float) $walletBalance);
            $dueAmount = $booking->total_booking_amount - $paidAmount;

            // Save wallet payment
            BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => 'wallet',
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ]);

            // Save remaining payment
            BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => 'digital',
                'paid_amount' => $dueAmount,
                'due_amount' => 0,
            ]);
        }

        // Handle payment method updates
        if ($request->payment_method == 'cash_after_service') {
            $booking->update(['payment_method' => 'cash_after_service', 'transaction_id' => 'cash-payment', 'is_verified' => 1]);
            if ($booking->booking_partial_payments->isNotEmpty()) {
                // Delete rows where `paid_with` is not 'wallet'
                $booking->booking_partial_payments()
                    ->where('paid_with', '!=', 'wallet')
                    ->delete();
            }
            if ($request->is_partial) {
                placeBookingTransactionForPartialCas($booking);
            }

        } elseif ($request->payment_method == 'wallet_payment') {
            if (! customer_wallet_feature_enabled() || ! wallet_payment_feature_enabled()) {
                return response()->json(response_formatter(DEFAULT_400, null, [
                    ['error_code' => 'payment', 'message' => translate('Wallet payment is not available.')],
                ]), 400);
            }
            $walletAmount = round((float) booking_digital_payment_ledger_amount($booking), 2);
            if (wallet_spend_exceeds_per_transaction_limit($walletAmount)) {
                return response()->json(response_formatter(WALLET_MAX_SPEND_PER_TRANSACTION_400), 400);
            }
            $booking->update(['payment_method' => 'wallet_payment', 'transaction_id' => 'wallet-payment']);
            placeBookingTransactionForWalletPayment($booking);

        }
        else {
            return response()->json(response_formatter(DEFAULT_400, null, 'Invalid payment method.'), 400);
        }

        return response()->json(response_formatter(PAYMENT_METHOD_UPDATE_200), 200);
    }

    public function digitalPaymentBookingResponse(Request $request): JsonResponse|array
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'access_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $payment_info = PaymentRequest::where('transaction_id', $request->transaction_id)->first();

        if (!$payment_info) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        if (! $this->assertPaymentResponseAuthorized($request, $payment_info)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $additional_data = json_decode($payment_info->additional_data, true);
        $additional_data = is_array($additional_data) ? $additional_data : [];

        $booking_repeat_id = $additional_data['booking_repeat_id'] ?? null;
        $register_new_customer = $additional_data['register_new_customer'] ?? 0;
        $new_user_phone = $register_new_customer == 1 ? ($additional_data['phone'] ?? null) : null;

        $booking = null;
        $booking_id = null;
        $bookingIds = [];
        $readableIds = [];
        if (isset($payment_info) && $payment_info->attribute_id != null) {
            $booking = Booking::where('readable_id', $payment_info->attribute_id)->first();
            $booking_id = $booking ? $booking->id : null;
        }

        $transactionBookings = Booking::query()
            ->where('transaction_id', $payment_info->transaction_id)
            ->orderBy('created_at')
            ->get(['id', 'readable_id']);

        if ($transactionBookings->isNotEmpty()) {
            $bookingIds = $transactionBookings->pluck('id')->filter()->values()->all();
            $readableIds = $transactionBookings->pluck('readable_id')->filter()->values()->all();
            $booking_id = $booking_id ?? ($bookingIds[0] ?? null);
        } elseif ($booking_id) {
            $bookingIds = [$booking_id];
            if (! empty($booking?->readable_id)) {
                $readableIds = [$booking->readable_id];
            }
        }

        $loginToken = null;
        if ($register_new_customer == 1 && $new_user_phone != null) {
            $alreadyClaimed = ! empty($additional_data['login_token_claimed']);

            $user = User::findByContactPhoneScoped($new_user_phone, CUSTOMER_USER_TYPES);
            if (! $user && ! $alreadyClaimed) {
                $user = new User();
                $user->first_name = $additional_data['first_name'] ?? '';
                $user->last_name = '';
                $user->phone = $new_user_phone;
                $user->password = bcrypt($additional_data['password'] ?? Str::random(16));
                $user->user_type = 'customer';
                $user->customer_app_access = true;
                $user->is_active = 1;
                $user->save();

                grant_customer_welcome_bonus($user);
            }

            if ($user && $booking) {
                $booking->customer_id = $user->id;
                $booking->is_guest = 0;
                $booking->save();
            }

            if ($user && ! $alreadyClaimed) {
                $loginToken = $user->createToken('CUSTOMER_PANEL_ACCESS')->accessToken;
                $additional_data['login_token_claimed'] = 1;
                $additional_data['login_token_user_id'] = $user->id;
                $payment_info->additional_data = json_encode($additional_data);
                $payment_info->save();
            }
        }

        $response = [
            'booking_id' => $booking_id,
            'booking_ids' => $bookingIds,
            'readable_ids' => $readableIds,
            'booking_repeat_id' => $booking_repeat_id,
            'new_user_phone' => $new_user_phone,
            'login_token' => $loginToken,
        ];

        return response()->json(response_formatter(DEFAULT_200, $response), 200);
    }

    private function assertPaymentResponseAuthorized(Request $request, PaymentRequest $paymentRequest): bool
    {
        $payerId = (string) $paymentRequest->payer_id;
        if ($payerId === '') {
            return false;
        }

        $user = api_user();
        if ($user && (string) $user->id === $payerId) {
            return true;
        }

        $tokenSubject = PaymentAccessToken::resolve($request->input('access_token'));

        return $tokenSubject !== null && (string) $tokenSubject === $payerId;
    }

    private function rejectUnlessGuestBookingAllowed(Request $request): ?JsonResponse
    {
        if ($reject = GuestCheckoutService::rejectIfRequiresLogin($this->isCustomerLoggedIn)) {
            return $reject;
        }

        return GuestSessionService::rejectIfInvalid($request, $this->isCustomerLoggedIn, $this->customerUserId);
    }

    private function assertBookingAccess(Request $request): ?JsonResponse
    {
        if ($this->isCustomerLoggedIn) {
            return null;
        }

        if ($reject = GuestCheckoutService::rejectIfRequiresLogin(false)) {
            return $reject;
        }

        $validator = Validator::make($request->all(), [
            'guest_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_401, null, error_processor($validator)), 401);
        }

        if ($request['guest_id'] !== $this->customerUserId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return GuestSessionService::rejectIfInvalid($request, false, $this->customerUserId);
    }

    private function assertBookingOwnership(Booking $booking): ?JsonResponse
    {
        if (!$this->customerUserId || (string) $booking->customer_id !== (string) $this->customerUserId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return null;
    }

    public function invoiceUrl(Request $request, string $booking_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lang' => 'required|string|max:10',
            'variant' => 'nullable|in:regular,repeat,single',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $booking = $this->booking->find($booking_id);
        if (!$booking) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if ($reject = $this->assertBookingOwnership($booking)) {
            return $reject;
        }

        $variant = $request->input('variant', 'regular');

        return response()->json(response_formatter(DEFAULT_200, [
            'url' => BookingInvoiceUrl::customer($booking_id, $request->lang, $variant),
        ]), 200);
    }

    private function bookingContactNumber(Booking $booking): ?string
    {
        $contactNumber = $booking->service_address?->contact_person_number;
        if ($contactNumber !== null && $contactNumber !== '') {
            return $contactNumber;
        }

        if ($booking->service_address_location === null || $booking->service_address_location === '') {
            return null;
        }

        $location = is_string($booking->service_address_location)
            ? json_decode($booking->service_address_location, true)
            : $booking->service_address_location;

        return is_array($location) ? ($location['contact_person_number'] ?? null) : null;
    }

}
