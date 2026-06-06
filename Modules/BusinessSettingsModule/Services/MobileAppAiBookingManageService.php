<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;

class MobileAppAiBookingManageService
{
    public function __construct(
        protected MobileAppAiCatalogSearchService $catalogSearch,
    ) {}

    public static function looksLikeCancelBooking(string $text): bool
    {
        return (bool) preg_match('/\b(cancel\s+(?:my\s+)?booking|cancel\s+order)\b/iu', $text);
    }

    public static function looksLikeRebook(string $text): bool
    {
        return (bool) preg_match('/\b(rebook|book\s+again|repeat\s+booking|order\s+again)\b/iu', $text);
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function buildCancelConfirm(User $user, string $text): array
    {
        $booking = $this->resolveBookingFromText($user, $text);
        if (! $booking instanceof Booking) {
            return [
                'ok' => false,
                'customer_message' => 'I could not find that booking. Say **my bookings** or cancel with the booking reference from your list.',
            ];
        }

        if (in_array($booking->booking_status, ['ongoing', 'completed', 'canceled'], true)) {
            return [
                'ok' => false,
                'customer_message' => 'This booking is **'.$booking->booking_status.'** and cannot be canceled from chat.',
            ];
        }

        $label = $booking->readable_id ?? substr((string) $booking->id, 0, 8);

        return [
            'ok' => true,
            'customer_message' => 'Cancel booking **#'.$label.'** ('.$booking->booking_status.')? This cannot be undone.',
            'ui' => MobileAppAiConfirmUi::confirmCancel('booking_cancel_confirm', 'booking_cancel', $text),
            'pending' => ['booking_id' => (string) $booking->id],
        ];
    }

    /**
     * @param  array<string, mixed>  $pending
     * @return array{ok: bool, customer_message: string}
     */
    public function executeCancel(User $user, array $pending): array
    {
        $bookingId = (string) ($pending['booking_id'] ?? '');
        $booking = Booking::query()
            ->with('repeat')
            ->where('id', $bookingId)
            ->where('customer_id', (string) $user->id)
            ->first();

        if (! $booking) {
            return ['ok' => false, 'customer_message' => 'Booking not found.'];
        }

        if ($booking->booking_status === 'accepted') {
            return ['ok' => false, 'customer_message' => 'This booking was already accepted by a provider — cancel from **My bookings** in the app if needed.'];
        }
        if (in_array($booking->booking_status, ['ongoing', 'completed', 'canceled'], true)) {
            return ['ok' => false, 'customer_message' => 'Cannot cancel — status is **'.$booking->booking_status.'**.'];
        }

        $booking->booking_status = 'canceled';
        $history = new BookingStatusHistory;
        $history->booking_id = $booking->id;
        $history->changed_by = (string) $user->id;
        $history->booking_status = 'canceled';

        DB::transaction(function () use ($booking, $history): void {
            $booking->save();
            $history->save();
            if ($booking->repeat->isNotEmpty()) {
                foreach ($booking->repeat as $repeat) {
                    $repeat->booking_status = 'canceled';
                    $repeat->save();
                }
            }
        });

        return ['ok' => true, 'customer_message' => 'Booking canceled. You can **rebook** the same service anytime.'];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function rebookToCart(User $user, string $text): array
    {
        $booking = $this->resolveBookingFromText($user, $text);
        if (! $booking instanceof Booking) {
            $booking = Booking::query()
                ->where('customer_id', (string) $user->id)
                ->orderByDesc('created_at')
                ->with('detail')
                ->first();
        }

        if (! $booking) {
            return ['ok' => false, 'customer_message' => 'No past booking found to rebook.'];
        }

        $zoneId = $this->catalogSearch->resolveCustomerZoneId($user);
        if ($zoneId === null) {
            return ['ok' => false, 'customer_message' => 'Add a saved address with a zone before rebooking.'];
        }

        $customerId = (string) $user->id;
        $added = 0;
        Config::set('zone_id', $zoneId);

        $provider = Provider::query()
            ->where('id', $booking->provider_id)
            ->ofStatus(1)
            ->where('zone_id', $zoneId)
            ->first();

        if (! Cart::query()->where('sub_category_id', $booking->sub_category_id)->where('customer_id', $customerId)->exists()) {
            Cart::query()->where('customer_id', $customerId)->delete();
            CartServiceInfo::query()->where('customer_id', $customerId)->delete();
        }

        foreach ($booking->detail as $detail) {
            $service = Service::query()->where('id', $detail->service_id)->where('is_active', 1)->first();
            if (! $service) {
                continue;
            }
            $variation = Variation::firstForBookingZone(
                (string) $detail->service_id,
                (string) $detail->variant_key,
                $zoneId,
                true
            );
            if ($variation === null) {
                continue;
            }

            $qty = (int) $detail->quantity;
            $basicDiscount = basic_discount_calculation($service, $variation->price * $qty);
            $campaignDiscount = campaign_discount_calculation($service, $variation->price * $qty);
            $subtotal = round($variation->price * $qty, 2);
            $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
            $tax = round((($variation->price * $qty - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);
            $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
            $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;

            $cart = Cart::query()->firstOrNew([
                'service_id' => $detail->service_id,
                'variant_key' => $detail->variant_key,
                'customer_id' => $customerId,
            ]);
            $cart->provider_id = $provider?->id;
            $cart->category_id = $booking->category_id;
            $cart->sub_category_id = $booking->sub_category_id;
            $cart->service_cost = $variation->price;
            $cart->quantity = $qty;
            $cart->discount_amount = $basicDiscount;
            $cart->campaign_discount = $campaignDiscount;
            $cart->tax_amount = round($tax, 2);
            $cart->total_cost = round($subtotal - $basicDiscount - $campaignDiscount + $tax, 2);
            $cart->is_guest = false;
            $cart->save();
            $added++;
        }

        if ($added === 0) {
            return ['ok' => false, 'customer_message' => 'Could not add items from that booking — services may be unavailable in your zone.'];
        }

        return [
            'ok' => true,
            'customer_message' => 'Added **'.$added.'** item(s) from your past booking to the cart. Open **Cart** to set schedule and pay.',
            'ui' => [
                'type' => 'cart_ready',
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'open_cart', 'label' => 'Open cart & pay', 'style' => 'primary', 'icon' => 'shopping_cart'],
                ],
            ],
        ];
    }

    private function resolveBookingFromText(User $user, string $text): ?Booking
    {
        if (preg_match('/\b#?(\d{4,})\b/u', $text, $m)) {
            $readable = $m[1];
            $found = Booking::query()
                ->where('customer_id', (string) $user->id)
                ->where('readable_id', $readable)
                ->first();
            if ($found) {
                return $found;
            }
        }

        return Booking::query()
            ->where('customer_id', (string) $user->id)
            ->whereNotIn('booking_status', ['canceled'])
            ->orderByDesc('created_at')
            ->first();
    }
}
