<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\BookingModule\Entities\Booking;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;

/**
 * Live snapshot of the logged-in customer for prompts and tools (cart, bookings, addresses).
 */
class MobileAppAiCustomerSnapshotService
{
    public function __construct(
        protected MobileAppAiCartService $cartService,
    ) {}

    /**
     * Trusted context block appended to the system prompt every turn.
     */
    public function promptBlockForUser(User $user): string
    {
        $data = $this->build($user);
        $lines = [
            '### This customer\'s account right now (trusted — use for answers; act on their behalf)',
            'You are their **in-app agent**: you can book, change cart, reschedule cart visits, check bookings, quote cart totals, and app help — same as they can in the app.',
        ];

        if (($data['profile_line'] ?? '') !== '') {
            $lines[] = $data['profile_line'];
        }

        $lines[] = $data['cart_line'];
        if (($data['cart_items_detail'] ?? '') !== '') {
            $lines[] = $data['cart_items_detail'];
        }

        $lines[] = $data['bookings_line'];
        if (($data['bookings_detail'] ?? '') !== '') {
            $lines[] = $data['bookings_detail'];
        }

        $lines[] = $data['addresses_line'];
        if (($data['addresses_detail'] ?? '') !== '') {
            $lines[] = $data['addresses_detail'];
        }

        if (($data['wizard_line'] ?? '') !== '') {
            $lines[] = $data['wizard_line'];
        }

        $lines[] = '**You can:** book services, view/change cart (remove, keep one, clear, reschedule), check bookings, apply coupons, accept bids — use tools for every action; confirm before destructive cart changes.';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        $profile = $name !== '' ? '**Profile:** '.$name : '**Profile:** logged-in customer';

        $cart = $this->cartService->cartSummaryForUser($user);
        $count = (int) ($cart['item_count'] ?? 0);
        $total = (float) ($cart['cart_total'] ?? 0);
        $cartLine = $count === 0
            ? '**Cart:** empty'
            : '**Cart:** '.$count.' item(s), estimated total ₹'.number_format($total, 2);

        $itemLines = [];
        foreach (array_slice($cart['items'] ?? [], 0, 8) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $itemLines[] = $this->formatCartItemLine($item);
        }
        $cartDetail = $itemLines !== [] ? implode("\n", $itemLines) : '';

        $bookings = $this->recentBookings($user, 6);
        $bookingsLine = $bookings === []
            ? '**Recent bookings:** none yet'
            : '**Recent bookings:** '.count($bookings).' on account';
        $bookingDetail = [];
        foreach ($bookings as $b) {
            $bookingDetail[] = '• **'.($b['readable_id'] ?? 'Booking').'** — '
                .($b['service_name'] ?? 'Service').' · '.($b['status_label'] ?? '').' · '.($b['when'] ?? '');
        }

        $addresses = $this->savedAddresses($user, 5);
        $addrLine = $addresses === []
            ? '**Saved addresses:** none — add in app (Home → location)'
            : '**Saved addresses:** '.count($addresses).' saved';
        $addrDetail = [];
        foreach ($addresses as $a) {
            $addrDetail[] = '• '.($a['display'] ?? $a['address'] ?? 'Address');
        }

        $wizardLine = $this->wizardLine($user);

        return [
            'profile_line' => $profile,
            'cart_line' => $cartLine,
            'cart_items_detail' => $cartDetail,
            'cart_count' => $count,
            'cart_total' => $total,
            'bookings_line' => $bookingsLine,
            'bookings_detail' => $bookingDetail !== [] ? implode("\n", $bookingDetail) : '',
            'booking_count' => count($bookings),
            'addresses_line' => $addrLine,
            'addresses_detail' => $addrDetail !== [] ? implode("\n", $addrDetail) : '',
            'address_count' => count($addresses),
            'wizard_line' => $wizardLine,
            'items' => $cart['items'] ?? [],
            'bookings' => $bookings,
            'addresses' => $addresses,
        ];
    }

    public function buildFallbackHint(User $user): string
    {
        return $this->buildAccountAwareFallback($user);
    }

    public static function softClarifyFallback(): string
    {
        return 'I can help with your cart, bookings, bidding requests, or booking a new service. Could you tell me a little more about what you\'d like to do?';
    }

    public function buildAccountAwareFallback(User $user): string
    {
        return MobileAppAiReplyStyle::clampReply(self::softClarifyFallback());
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function formatCartItemLine(array $item): string
    {
        $name = (string) ($item['service_name'] ?? 'Service');
        $total = number_format((float) ($item['line_total'] ?? 0), 2);
        $when = (string) ($item['schedule_label'] ?? '');
        $addr = (string) ($item['address_short'] ?? '');
        $extra = trim($when.($addr !== '' ? ' · '.$addr : ''));

        return '• '.$name.' — ₹'.$total.($extra !== '' ? ' ('.$extra.')' : '');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentBookings(User $user, int $limit): array
    {
        $rows = Booking::query()
            ->where('customer_id', $user->id)
            ->with(['detail.service:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $b) {
            $status = (string) ($b->booking_status ?? '');
            $serviceName = 'Service';
            $firstDetail = $b->detail->first();
            if ($firstDetail !== null && $firstDetail->service !== null) {
                $serviceName = (string) ($firstDetail->service->name ?? $serviceName);
            }
            $out[] = [
                'readable_id' => (string) ($b->readable_id ?? ''),
                'service_name' => $serviceName,
                'status_label' => $this->humanBookingStatus($status),
                'when' => $b->service_schedule
                    ? Carbon::parse($b->service_schedule)->format('j M, g:i A')
                    : 'Schedule TBC',
                'is_paid' => (bool) $b->is_paid,
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, string>>
     */
    private function savedAddresses(User $user, int $limit): array
    {
        $rows = UserAddress::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'address', 'address_label', 'city']);

        $out = [];
        foreach ($rows as $a) {
            $line = trim((string) ($a->address ?? ''));
            $label = trim((string) ($a->address_label ?? ''));
            $out[] = [
                'service_address_id' => (string) $a->id,
                'display' => ($label !== '' ? $label.' — ' : '').$line,
                'address' => $line,
            ];
        }

        return $out;
    }

    private function wizardLine(User $user): string
    {
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if (! $conversation || ! is_array($conversation->booking_draft)) {
            return '';
        }
        $step = (string) ($conversation->booking_draft['step'] ?? '');
        if ($step === '' || $step === 'idle' || $step === 'done') {
            return '';
        }

        $bookingSteps = ['service_query', 'service_triage', 'service_confirm', 'service', 'variation', 'schedule', 'address', 'provider', 'ready'];
        if (in_array($step, $bookingSteps, true)) {
            return '**Booking wizard active** (step: '.$step.') — use **manage_app_booking** only. Do **not** call cart tools unless they explicitly ask about cart (mera cart, cart se hatao). Short answers like *AC ki* / *haan* continue the wizard.';
        }

        return '**In-progress AI flow:** '.$step.' — continue that flow unless they cancel.';
    }

    private function humanBookingStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'ongoing' => 'In progress',
            'completed' => 'Completed',
            'canceled', 'cancelled' => 'Cancelled',
            default => $status !== '' ? ucfirst($status) : 'Unknown',
        };
    }
}
