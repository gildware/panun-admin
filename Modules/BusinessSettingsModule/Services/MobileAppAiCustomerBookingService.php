<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;

/**
 * Customer booking lookups scoped to logged-in user (not phone-only).
 */
class MobileAppAiCustomerBookingService
{
    /**
     * @return array<string, mixed>
     */
    public function countSummaryForUser(User $user): array
    {
        $total = (int) Booking::query()->where('customer_id', $user->id)->count();

        if ($total === 0) {
            return [
                'ok' => true,
                'count' => 0,
                'customer_message' => "You don't have any bookings on your account yet.\n\nTell me what service you need and I can help you **book** one.",
                'ui' => [
                    'type' => 'assistant_actions',
                    'layout' => 'actions',
                    'actions' => [
                        ['action' => 'start_booking', 'label' => 'Book a service', 'style' => 'primary', 'icon' => 'home_repair_service'],
                    ],
                ],
            ];
        }

        $active = (int) Booking::query()
            ->where('customer_id', $user->id)
            ->whereNotIn('booking_status', ['canceled', 'completed'])
            ->count();

        $message = 'You have **'.$total.'** booking'.($total === 1 ? '' : 's').' on your account';
        if ($active > 0 && $active < $total) {
            $message .= ' (**'.$active.'** still active)';
        }
        $message .= '. Open **Bookings** in the app for full details.';

        return [
            'ok' => true,
            'count' => $total,
            'active_count' => $active,
            'customer_message' => $message,
            'ui' => [
                'type' => 'assistant_actions',
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'booking_status', 'label' => 'My bookings', 'style' => 'outline', 'icon' => 'event'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function latestSummaryForUser(User $user): array
    {
        $booking = Booking::query()
            ->where('customer_id', $user->id)
            ->with(['provider', 'detail.service'])
            ->orderByDesc('created_at')
            ->first();

        if ($booking === null) {
            return $this->countSummaryForUser($user);
        }

        $serviceLabel = $this->serviceLabelForBooking($booking);
        $statusLabel = $this->humanStatus((string) ($booking->booking_status ?? ''));
        $when = $booking->service_schedule
            ? Carbon::parse($booking->service_schedule)->format('j M Y, g:i A')
            : 'Schedule pending';

        return [
            'ok' => true,
            'customer_message' => 'Your latest booking is **'.($booking->readable_id ?? 'Booking').'** — '
                .$serviceLabel."\n".$statusLabel.' · '.$when,
            'ui' => $this->buildStatusListUi([[
                'readable_id' => (string) ($booking->readable_id ?? ''),
                'status_label' => $statusLabel,
                'service_name' => $serviceLabel,
                'service_schedule' => $when,
            ]]),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function listForUser(User $user, array $args = []): array
    {
        $limit = min(15, max(1, (int) ($args['limit'] ?? 8)));

        $rows = Booking::query()
            ->where('customer_id', $user->id)
            ->with(['provider', 'detail.service'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'ok' => true,
                'count' => 0,
                'bookings' => [],
                'customer_message' => "I don't see any bookings on your account yet.\n\nWould you like to **book a service**? Tell me what you need and I'll guide you through it.",
                'ui' => [
                    'type' => 'assistant_actions',
                    'layout' => 'actions',
                    'actions' => [
                        ['action' => 'start_booking', 'label' => 'Book a service', 'style' => 'primary', 'icon' => 'home_repair_service'],
                    ],
                ],
            ];
        }

        $bookings = [];
        $lines = [];
        foreach ($rows as $b) {
            $serviceLabel = $this->serviceLabelForBooking($b);
            $statusLabel = $this->humanStatus((string) ($b->booking_status ?? ''));
            $when = $b->service_schedule
                ? Carbon::parse($b->service_schedule)->format('j M Y, g:i A')
                : 'Schedule pending';
            $providerLabel = $this->providerLabel($b);

            $bookings[] = [
                'readable_id' => (string) ($b->readable_id ?? ''),
                'status' => (string) ($b->booking_status ?? ''),
                'status_label' => $statusLabel,
                'service_name' => $serviceLabel,
                'service_schedule' => $when,
                'provider_name' => $providerLabel,
                'is_paid' => (bool) $b->is_paid,
            ];

            $lines[] = '• **'.($b->readable_id ?? 'Booking').'** — '.$serviceLabel
                ."\n  ".$statusLabel.' · '.$when
                .($providerLabel !== '' ? "\n  Provider: ".$providerLabel : '');
        }

        return [
            'ok' => true,
            'count' => count($bookings),
            'bookings' => $bookings,
            'customer_message' => "Here are your recent bookings:\n\n".implode("\n\n", $lines)
                ."\n\nTap one below for details, or tell me a **booking reference** (e.g. PK…).",
            'ui' => $this->buildStatusListUi($bookings),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function statusByReference(User $user, array $args): array
    {
        $ref = $this->normalizeReference((string) ($args['booking_reference'] ?? $args['reference'] ?? ''));
        if ($ref === '') {
            return [
                'ok' => false,
                'customer_message' => 'Please share your **booking reference** (for example PK07MAR26001) so I can look it up.',
            ];
        }

        $booking = Booking::query()
            ->where('customer_id', $user->id)
            ->whereRaw('LOWER(TRIM(readable_id)) = LOWER(?)', [$ref])
            ->with(['provider', 'detail.service'])
            ->first();

        if (! $booking) {
            return [
                'ok' => false,
                'customer_message' => "I couldn't find booking **{$ref}** on your account.\n\nPlease check the reference from your Booking tab or SMS, and try again.",
            ];
        }

        $serviceLabel = $this->serviceLabelForBooking($booking);
        $statusLabel = $this->humanStatus((string) ($booking->booking_status ?? ''));
        $when = $booking->service_schedule
            ? Carbon::parse($booking->service_schedule)->format('l, j M Y \a\t g:i A')
            : 'To be confirmed';
        $providerLabel = $this->providerLabel($booking);
        $paid = (bool) $booking->is_paid;

        $msg = "**{$ref}**\n\n"
            ."• Service: {$serviceLabel}\n"
            ."• Status: {$statusLabel}\n"
            ."• Visit: {$when}\n"
            .($providerLabel !== '' ? "• Provider: {$providerLabel}\n" : '')
            .'• Payment: '.($paid ? 'Paid' : 'Pending')
            ."\n\nOpen the **Booking** tab for live updates and actions.";

        return [
            'ok' => true,
            'booking' => [
                'readable_id' => $ref,
                'status' => (string) ($booking->booking_status ?? ''),
                'status_label' => $statusLabel,
                'service_name' => $serviceLabel,
                'service_schedule' => $when,
                'provider_name' => $providerLabel,
                'is_paid' => $paid,
            ],
            'customer_message' => $msg,
            'ui' => [
                'type' => 'booking_status_detail',
                'layout' => 'summary',
                'summary_lines' => [
                    ['label' => 'Reference', 'value' => $ref],
                    ['label' => 'Service', 'value' => $serviceLabel],
                    ['label' => 'Status', 'value' => $statusLabel],
                    ['label' => 'Visit', 'value' => $when],
                    ['label' => 'Provider', 'value' => $providerLabel !== '' ? $providerLabel : 'Assigned soon'],
                    ['label' => 'Payment', 'value' => $paid ? 'Paid' : 'Pending'],
                ],
                'footer_actions' => [
                    ['action' => 'open_bookings', 'label' => 'Open Booking tab', 'style' => 'primary', 'icon' => 'event'],
                ],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $bookings
     * @return array<string, mixed>
     */
    private function buildStatusListUi(array $bookings): array
    {
        $cards = [];
        foreach ($bookings as $b) {
            $ref = (string) ($b['readable_id'] ?? '');
            $cards[] = [
                'choice' => $ref,
                'title' => $ref,
                'subtitle' => ($b['status_label'] ?? '').' · '.($b['service_name'] ?? ''),
                'icon' => 'event',
            ];
        }

        return [
            'type' => 'booking_status_list',
            'layout' => 'cards',
            'title' => 'Your bookings',
            'subtitle' => 'Tap a booking for details',
            'cards' => $cards,
            'footer_actions' => [
                ['action' => 'open_bookings', 'label' => 'Open Booking tab', 'style' => 'outline', 'icon' => 'event'],
                ['action' => 'start_booking', 'label' => 'Book another service', 'style' => 'text', 'icon' => 'home_repair_service'],
            ],
        ];
    }

    private function serviceLabelForBooking(Booking $booking): string
    {
        $detail = $booking->detail->first();
        $name = trim((string) ($detail?->service?->name ?? ''));

        return $name !== '' ? $name : 'Service booking';
    }

    private function providerLabel(Booking $booking): string
    {
        $label = trim((string) ($booking->provider?->company_name ?? ''));
        if ($label === '') {
            $label = trim((string) ($booking->provider?->contact_person_name ?? ''));
        }

        return $label;
    }

    private function humanStatus(string $code): string
    {
        $c = strtolower(str_replace('cancelled', 'canceled', trim($code)));

        return match ($c) {
            'pending' => 'Pending — awaiting confirmation',
            'accepted' => 'Accepted — provider confirmed',
            'ongoing' => 'Ongoing — visit in progress',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
            'refunded' => 'Refunded',
            'on_hold' => 'On hold',
            default => ucfirst(str_replace('_', ' ', $c)),
        };
    }

    private function normalizeReference(string $raw): string
    {
        $s = trim($raw);
        $s = ltrim($s, '#');
        $s = preg_replace('/\s+/', '', $s) ?? $s;

        return trim($s);
    }
}
