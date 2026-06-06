<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

/**
 * Adds services to the logged-in customer's cart (same pricing rules as the mobile cart API).
 */
class MobileAppAiCartService
{
    public function __construct(
        protected MobileAppAiBookingFlowService $bookingFlow,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function addServiceForUser(User $user, array $args): array
    {
        $validation = $this->bookingFlow->validateCartPayload($args);
        if (! ($validation['ok'] ?? false)) {
            return $validation;
        }

        $serviceId = trim((string) ($args['service_id'] ?? ''));
        $variantKey = trim((string) ($args['variant_key'] ?? ''));
        $categoryId = trim((string) ($args['category_id'] ?? ''));
        $subCategoryId = trim((string) ($args['sub_category_id'] ?? ''));
        $zoneId = trim((string) ($args['zone_id'] ?? ''));
        $normalizedSchedule = (string) ($validation['normalized_schedule'] ?? '');
        $addressId = (int) $args['service_address_id'];

        $quantity = min(1000, max(1, (int) ($args['quantity'] ?? 1)));
        $letCompanyChoose = filter_var($args['let_company_choose_provider'] ?? false, FILTER_VALIDATE_BOOL);
        $providerId = trim((string) ($args['provider_id'] ?? ''));
        if ($letCompanyChoose || $providerId === '' || strtolower($providerId) === 'null') {
            $providerId = null;
        }

        if ($providerId !== null && ! nextBookingEligibility($providerId)) {
            return ['ok' => false, 'error' => 'provider_not_available_for_booking'];
        }

        $variation = Variation::firstForBookingZone($serviceId, $variantKey, $zoneId, true);
        if ($variation === null) {
            return ['ok' => false, 'error' => 'service_not_available_in_zone'];
        }

        $service = Service::query()->with(['category', 'subCategory'])->find($serviceId);
        if (!$service || (int) $service->is_active !== 1) {
            return ['ok' => false, 'error' => 'service_not_found'];
        }

        $customerId = (string) $user->id;

        $existing = $this->findCartLine(
            $customerId,
            $serviceId,
            $variantKey,
            $zoneId,
            $addressId,
            $normalizedSchedule
        );

        $cart = $existing ?? new Cart();
        $basicDiscount = basic_discount_calculation($service, $variation->price * $quantity);
        $campaignDiscount = campaign_discount_calculation($service, $variation->price * $quantity);
        $subtotal = round($variation->price * $quantity, 2);
        $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
        $tax = round((($variation->price * $quantity - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);
        $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
        $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;

        $cart->provider_id = $providerId;
        $cart->customer_id = $customerId;
        $cart->service_id = $serviceId;
        $cart->category_id = $categoryId;
        $cart->sub_category_id = $subCategoryId;
        $cart->variant_key = $variantKey;
        $cart->quantity = $quantity;
        $cart->service_cost = $variation->price;
        $cart->discount_amount = $basicDiscount;
        $cart->campaign_discount = $campaignDiscount;
        $cart->coupon_discount = 0;
        $cart->coupon_code = null;
        $cart->is_guest = false;
        $cart->tax_amount = round($tax, 2);
        $cart->total_cost = round($subtotal - $basicDiscount - $campaignDiscount + $tax, 2);
        $cart->zone_id = $zoneId;
        if ($addressId !== null) {
            $cart->service_address_id = $addressId;
        }
        if ($normalizedSchedule !== null) {
            $cart->service_schedule = $normalizedSchedule;
        }
        $cart->save();

        $this->bookingFlow->persistCartServiceInfo($customerId, $zoneId, $addressId, $normalizedSchedule);

        $brand = WhatsAppAiPromptBuilder::resolveBrandName();
        $providerNote = $providerId === null
            ? $brand.' will assign a provider after checkout.'
            : 'Provider selected for this visit.';

        return [
            'ok' => true,
            'cart_line_id' => (string) $cart->id,
            'service_name' => (string) $service->name,
            'variant_key' => $variantKey,
            'quantity' => $quantity,
            'line_total' => (float) $cart->total_cost,
            'visit_schedule' => $normalizedSchedule,
            'provider_note' => $providerNote,
            'cart_updated' => true,
            'assistant_instruction' => 'Summarize what was booked (service, variation, address, date/time, provider choice). Tell the customer to open **Cart** on Home, review details, then **checkout and pay**. '.$providerNote.' Booking is not confirmed until payment completes.',
            'app_navigation_hint' => [
                'screen' => 'cart',
                'steps' => ['Tap Cart on Home', 'Confirm address and visit time', 'Proceed to checkout and pay'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cartSummaryForUser(User $user): array
    {
        $customerId = (string) $user->id;
        $lines = Cart::query()
            ->where('customer_id', $customerId)
            ->where('is_guest', false)
            ->with(['service:id,name', 'serviceAddress:id,address,address_label'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $items = $lines->map(static function (Cart $c): array {
            $schedule = $c->service_schedule;
            $addr = $c->serviceAddress;
            $addrShort = '';
            if ($addr !== null) {
                $label = trim((string) ($addr->address_label ?? ''));
                $line = trim((string) ($addr->address ?? ''));
                $addrShort = $label !== '' ? $label : (mb_strlen($line) > 48 ? mb_substr($line, 0, 45).'…' : $line);
            }

            return [
                'cart_line_id' => (string) $c->id,
                'service_name' => (string) ($c->service?->name ?? $c->variant_key),
                'variant_key' => (string) $c->variant_key,
                'quantity' => (int) $c->quantity,
                'line_total' => (float) $c->total_cost,
                'zone_id' => (string) ($c->zone_id ?? ''),
                'service_schedule' => $schedule?->format('Y-m-d H:i:s'),
                'schedule_label' => $schedule ? $schedule->format('j M, g:i A') : '',
                'address_short' => $addrShort,
                'service_address_id' => $c->service_address_id ? (int) $c->service_address_id : null,
            ];
        })->values()->all();

        $total = round($lines->sum('total_cost'), 2);

        $info = CartServiceInfo::query()->where('customer_id', $customerId)->first();

        return [
            'ok' => true,
            'item_count' => count($items),
            'cart_total' => $total,
            'items' => $items,
            'cart_service_info' => $info ? [
                'zone_id' => (string) ($info->zone_id ?? ''),
                'service_address_id' => (int) ($info->service_address_id ?? 0),
                'service_schedule' => $info->service_schedule
                    ? date('Y-m-d H:i:s', strtotime((string) $info->service_schedule))
                    : null,
            ] : null,
        ];
    }

    private function findCartLine(
        string $customerUserId,
        string $serviceId,
        string $variantKey,
        string $zoneId,
        ?int $serviceAddressId,
        ?string $serviceSchedule
    ): ?Cart {
        $query = Cart::query()->where([
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
