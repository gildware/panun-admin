<?php

namespace Modules\CartModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\CartModule\Traits\AddedToCartTrait;
use Modules\CartModule\Traits\CartTrait;
use Modules\PromotionManagement\Entities\Coupon;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\Guest;
use Modules\UserManagement\Entities\User;
use Ramsey\Uuid\Uuid;

class CartController extends Controller
{
    private Cart $cart;
    private Service $service;
    private Variation $variation;
    private User $user;

    private Booking $booking;
    private Coupon $coupon;
    private Provider $provider;
    private Guest $guest;
    private bool $isCustomerLoggedIn;
    private mixed $customerUserId;

    use CartTrait, AddedToCartTrait;

    public function __construct(Cart $cart, Service $service, Variation $variation, User $user, Provider $provider, Guest $guest, Request $request, Booking $booking, Coupon $coupon)
    {
        $this->cart = $cart;
        $this->service = $service;
        $this->variation = $variation;
        $this->user = $user;
        $this->provider = $provider;
        $this->guest = $guest;
        $this->booking = $booking;
        $this->coupon = $coupon;

        $this->isCustomerLoggedIn = (bool)auth('api')->user();
        $this->customerUserId = $this->isCustomerLoggedIn ? auth('api')->user()->id : $request['guest_id'];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'nullable|uuid',
            'service_id' => 'required|uuid',
            'category_id' => 'required|uuid',
            'sub_category_id' => 'required|uuid',
            'variant_key' => 'required',
            'quantity' => 'required|numeric|min:1|max:1000',
            'guest_id' => $this->isCustomerLoggedIn ? 'nullable' : 'required|uuid',
            'zone_id' => 'nullable|uuid',
            'service_address_id' => 'nullable|integer|exists:user_addresses,id',
            'service_schedule' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customerUserId = $this->customerUserId;

        $this->addedToCartUpdate($customerUserId, $request['service_id'], !$this->isCustomerLoggedIn);

        if ($request['provider_id']){
            $nextBookingEligibility = nextBookingEligibility($request['provider_id']);
            if (!$nextBookingEligibility){
                return response()->json(response_formatter(BOOKING_ELIGIBILITY_FOR_BOOKING), 400);
            }
        }

        $zoneId = $request->filled('zone_id') ? $request['zone_id'] : Config::get('zone_id');
        if (! $zoneId) {
            return response()->json(response_formatter(ZONE_404), 401);
        }

        $variation = Variation::firstForBookingZone(
            (string) $request['service_id'],
            (string) $request['variant_key'],
            (string) $zoneId,
            true
        );

        if ($variation !== null) {
            $service = $this->service->with(['category', 'subCategory'])->find($request['service_id']);

            $normalizedSchedule = $request->filled('service_schedule')
                ? date('Y-m-d H:i:s', strtotime($request['service_schedule']))
                : null;

            $existingCart = $this->findCartLineForBooking(
                $customerUserId,
                (string) $request['service_id'],
                (string) $request['variant_key'],
                (string) $zoneId,
                $request->filled('service_address_id') ? (int) $request['service_address_id'] : null,
                $normalizedSchedule
            );

            $cart = $existingCart ?? new Cart();
            $quantity = (int) $request['quantity'];

            $basicDiscount = basic_discount_calculation($service, $variation->price * $quantity);
            $campaignDiscount = campaign_discount_calculation($service, $variation->price * $quantity);
            $subtotal = round($variation->price * $quantity, 2);

            $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;

            $tax = round((($variation->price * $quantity - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);

            //between normal discount & campaign discount, greater one will be calculated
            $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
            $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;

            $cart->provider_id = $request['provider_id'];
            $cart->customer_id = $customerUserId;
            $cart->service_id = $request['service_id'];
            $cart->category_id = $request['category_id'];
            $cart->sub_category_id = $request['sub_category_id'];
            $cart->variant_key = $request['variant_key'];
            $cart->quantity = $quantity;
            $cart->service_cost = $variation->price;
            $cart->discount_amount = $basicDiscount;
            $cart->campaign_discount = $campaignDiscount;
            $cart->coupon_discount = 0;
            $cart->coupon_code = null;
            $cart->is_guest = !$this->isCustomerLoggedIn;
            $cart->tax_amount = round($tax, 2);
            $cart->total_cost = round($subtotal - $basicDiscount - $campaignDiscount + $tax, 2);
            $cart->zone_id = $zoneId;
            if ($request->filled('service_address_id')) {
                $cart->service_address_id = $request['service_address_id'];
            }
            if ($normalizedSchedule !== null) {
                $cart->service_schedule = $normalizedSchedule;
            }
            $cart->save();

            if (!$this->isCustomerLoggedIn) {
                $guest = $this->guest;
                $guest->ip_address = $request->ip();
                $guest->guest_id = $request->guest_id;
                $guest->save();
            }

            return response()->json(response_formatter(DEFAULT_STORE_200), 200);
        }

        return response()->json(response_formatter(DEFAULT_404), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'guest_id' => $this->isCustomerLoggedIn ? 'nullable' : 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customerUserId = $this->customerUserId;
        $cartItems = $this->queryCustomerCart($customerUserId)->get();

        return response()->json(response_formatter(DEFAULT_200, $this->buildCartListContent($customerUserId, $cartItems)), 200);
    }

    /**
     * Store service address and schedule for the current cart session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOtherInfo(Request $request): JsonResponse
    {
        $payload = $request->all();
        if ($this->isCustomerLoggedIn) {
            unset($payload['guest_id']);
        }

        $validator = Validator::make($payload, [
            'zone_id' => 'required|uuid',
            'service_address_id' => 'required|integer|exists:user_addresses,id',
            'service_schedule' => 'required|date_format:Y-m-d H:i:s',
            'guest_id' => $this->isCustomerLoggedIn ? 'nullable' : 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customerUserId = $this->customerUserId;

        CartServiceInfo::updateOrCreate(
            ['customer_id' => $customerUserId],
            [
                'zone_id' => $request['zone_id'],
                'service_address_id' => $request['service_address_id'],
                'service_schedule' => date('Y-m-d H:i:s', strtotime($request['service_schedule'])),
            ]
        );

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateQty(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $this->isCustomerLoggedIn ? 'nullable' : 'required|uuid',
            'quantity' => 'required|numeric|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->updateCartQuantity($id, $request['quantity']);

        $customerUserId = $this->customerUserId;
        $cartItems = $this->queryCustomerCart($customerUserId)->get();
        $content = $this->buildCartListContent($customerUserId, $cartItems);
        unset($content['cart_service_info']);

        return response()->json(response_formatter(DEFAULT_UPDATE_200, $content), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProvider(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'nullable|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerId = $request->has('provider_id') ? $request->provider_id : null;

        if ($providerId){
            $nextBookingEligibility = nextBookingEligibility($providerId);
            if (!$nextBookingEligibility){
                return response()->json(response_formatter(BOOKING_ELIGIBILITY_FOR_BOOKING), 400);
            }
        }

        $this->cart
            ->where('customer_id', $this->customerUserId)
            ->update(['provider_id' => $providerId]);

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function remove(Request $request, string $id): JsonResponse
    {
        $cart = $this->cart->where(['id' => $id])->first();

        if (!isset($cart)) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        $this->cart->where('id', $id)->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function emptyCart(Request $request): JsonResponse
    {
        $cart = $this->cart->where(['customer_id' => $this->customerUserId]);
        if ($cart->count() == 0) return response()->json(response_formatter(DEFAULT_204), 204);
        $cart->delete();
        CartServiceInfo::where('customer_id', $this->customerUserId)->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function rebookAddToCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $this->isCustomerLoggedIn ? 'nullable' : 'required|uuid',
            'booking_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customerUserId = $this->customerUserId;

        $booking = $this->booking->where('id', $request->booking_id)->first();

        $allItemsInCart = $this->checkIfAllItemsInCart($booking->detail, $customerUserId);
        if ($allItemsInCart) {
            return response()->json(response_formatter(DEFAULT_CART_ALREADY_ADDED_200), 200);
        }

        $provider = $this->provider
            ->where('id', $booking?->provider?->id)
            ->ofStatus(1)
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->where('zone_id', $request->header('zoneid'))
            ->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
            })
            ->first();

        if (!Cart::where('sub_category_id', $booking->sub_category_id)->where('customer_id', $customerUserId)->exists()) {
            Cart::where('customer_id', $customerUserId)->delete();
        }

        foreach ($booking->detail as $detail) {

            $serviceData = $this->service->where('id', $detail->service_id)->active()->first();

            if ($serviceData) {
                $this->addedToCartUpdate($customerUserId, $detail->service_id, !$this->isCustomerLoggedIn);

                $zoneId = Config::get('zone_id');
                $variation = $zoneId
                    ? Variation::firstForBookingZone(
                        (string) $detail->service_id,
                        (string) $detail->variant_key,
                        (string) $zoneId,
                        true
                    )
                    : null;

                if ($variation !== null) {
                    DB::transaction(function () use ($detail, $customerUserId, $variation, $provider, $booking, $request) {
                        $service = $this->service->with(['category', 'subCategory'])->find($detail->service_id);

                        $checkCart = $this->cart->where([
                            'service_id' => $detail->service_id,
                            'variant_key' => $detail->variant_key,
                            'customer_id' => $customerUserId])->first();

                        $cart = $checkCart ?? new Cart();
                        $quantity = $detail->quantity;

                        //calculation
                        $basicDiscount = basic_discount_calculation($service, $variation->price * $quantity);
                        $campaignDiscount = campaign_discount_calculation($service, $variation->price * $quantity);
                        $subtotal = round($variation->price * $quantity, 2);

                        $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;

                        $tax = round((($variation->price * $quantity - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);

                        //between normal discount & campaign discount, greater one will be calculated
                        $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
                        $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;

                        $cart->provider_id = $provider?->id ?? null;
                        $cart->customer_id = $customerUserId;
                        $cart->service_id = $detail->service_id;
                        $cart->category_id = $booking->category_id;
                        $cart->sub_category_id = $booking->sub_category_id;
                        $cart->variant_key = $detail->variant_key;
                        $cart->quantity = $quantity;
                        $cart->service_cost = $variation->price;
                        $cart->discount_amount = $basicDiscount;
                        $cart->campaign_discount = $campaignDiscount;
                        $cart->coupon_discount = 0;
                        $cart->coupon_code = null;
                        $cart->is_guest = !$this->isCustomerLoggedIn;
                        $cart->tax_amount = round($tax, 2);
                        $cart->total_cost = round($subtotal - $basicDiscount - $campaignDiscount + $tax, 2);
                        $cart->save();

                        if (!$this->isCustomerLoggedIn) {
                            $guest = $this->guest;
                            $guest->ip_address = $request->ip();
                            $guest->guest_id = $request->guest_id;
                            $guest->save();
                        }

                    });
                }

            }
        }

        return response()->json(response_formatter(DEFAULT_CART_STORE_200), 200);
    }

    private function queryCustomerCart(string $customerUserId)
    {
        return $this->cart
            ->with([
                'customer',
                'provider.owner',
                'category',
                'sub_category',
                'service.category',
                'service.subCategory',
                'serviceAddress',
            ])
            ->where(['customer_id' => $customerUserId])
            ->latest();
    }

    private function enrichCartItems(iterable $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            if ($cartItem?->coupon_code && $cartItem?->coupon_id) {
                $coupon = $this->coupon->where('id', $cartItem->coupon_id)->first();

                $repeatCount = $this->booking->where('customer_id', $this->customerUserId)
                    ->where('coupon_code', $coupon['coupon_code'])
                    ->get()
                    ->sum(function ($booking) use ($coupon) {
                        $repeatCount = $booking->repeat()
                            ->where('coupon_code', $coupon['coupon_code'])
                            ->count();

                        return $repeatCount > 0 ? $repeatCount : 1;
                    });
                $cartItem['used_count'] = $repeatCount;
                $cartItem['remaining_uses'] = max(0, $coupon->discount->limit_per_user - $repeatCount);
            }
            if ($cartItem?->provider) {
                $providerId = optional($cartItem->provider)->id;
                $timeSchedule = provider_config('time_schedule', 'service_schedule', $providerId)->live_values ?? '';
                $weekEnds = provider_config('weekends', 'service_schedule', $providerId)->live_values ?? '';
                $weekEnds = json_decode($weekEnds);
                $timeSchedule = json_decode($timeSchedule);
                $serviceLocation = provider_config('service_location', 'provider_config', $providerId)->live_values ?? '';
                $serviceLocations = json_decode($serviceLocation);
                $cartItem->provider->weekends = $weekEnds ?? [];
                $cartItem->provider->service_locations = $serviceLocations ?? [];
                $cartItem->provider->time_schedule = $timeSchedule ?? null;
                $cartItem->provider->nextBookingEligibility = nextBookingEligibility($providerId);
                $cartItem->provider->scheduleBookingEligibility = scheduleBookingEligibility($providerId);
            }

            $service = $cartItem->service ?? null;
            $basisExTax = max(0.0, (float) ($cartItem->total_cost ?? 0) - (float) ($cartItem->tax_amount ?? 0));
            $itemAdditionalCharges = compute_additional_charges_for_service_basis($basisExTax, $service);
            $cartItem['additional_charges'] = [
                'total' => $itemAdditionalCharges['total'],
                'lines' => $itemAdditionalCharges['lines'],
            ];
        }
    }

    private function buildCartListContent(string $customerUserId, $cartItems): array
    {
        $this->enrichCartItems($cartItems);

        $walletBalance = $this->user->find($customerUserId)?->wallet_balance ?? 0;
        $acComputed = compute_additional_charges_for_cart_items($cartItems);
        $additionalCharge = $acComputed['total'];

        $totalTax = collect($cartItems)->sum('tax_amount');
        $totalCost = collect($cartItems)->sum('total_cost');

        $referralAmount = $this->referralEarningEligiblityCheck($customerUserId, $totalCost - $totalTax);
        $totalCost -= $referralAmount;
        $totalCost += $additionalCharge;

        $cartServiceInfo = CartServiceInfo::where('customer_id', $customerUserId)->first();
        $itemCount = collect($cartItems)->count();

        return [
            'total_cost' => $totalCost,
            'referral_amount' => $referralAmount,
            'wallet_balance' => with_decimal_point($walletBalance),
            'additional_charges' => [
                'total' => $additionalCharge,
                'lines' => $acComputed['lines'],
            ],
            'cart' => [
                'current_page' => 1,
                'data' => $cartItems,
                'total' => $itemCount,
                'per_page' => max($itemCount, 1),
                'last_page' => 1,
            ],
            'cart_service_info' => $cartServiceInfo,
        ];
    }

    private function checkIfAllItemsInCart($details, $customerUserId): bool
    {
        foreach ($details as $detail) {
            $checkCart = $this->cart->where([
                'service_id' => $detail->service_id,
                'variant_key' => $detail->variant_key,
                'customer_id' => $customerUserId,
            ])->exists();

            if (!$checkCart) {
                return false;
            }
        }

        return true;
    }

    /**
     * Match cart lines by service, variation, zone, address, and schedule (one booking per unique combination).
     */
    private function findCartLineForBooking(
        string $customerUserId,
        string $serviceId,
        string $variantKey,
        string $zoneId,
        ?int $serviceAddressId,
        ?string $serviceSchedule
    ): ?Cart {
        $query = $this->cart->where([
            'service_id' => $serviceId,
            'variant_key' => $variantKey,
            'customer_id' => $customerUserId,
            'zone_id' => $zoneId,
        ]);

        if ($serviceAddressId !== null) {
            $query->where('service_address_id', $serviceAddressId);
        } else {
            $query->whereNull('service_address_id');
        }

        if ($serviceSchedule !== null) {
            $query->where('service_schedule', $serviceSchedule);
        } else {
            $query->whereNull('service_schedule');
        }

        return $query->first();
    }
}
