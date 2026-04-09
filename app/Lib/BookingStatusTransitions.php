<?php

/**
 * Admin booking status workflow: allowed transitions only.
 *
 * @see BOOKING_STATUSES in Constant.php
 */

if (! function_exists('booking_admin_allowed_next_statuses')) {
    /**
     * Target statuses the admin may set from the current status (empty = no dropdown / no pills except reopen flows).
     *
     * @return list<string>
     */
    function booking_admin_allowed_next_statuses(string $current): array
    {
        $current = strtolower(trim($current));

        return match ($current) {
            'pending' => ['accepted'],
            'accepted' => ['pending', 'ongoing', 'on_hold'],
            'ongoing' => ['on_hold', 'completed'],
            'on_hold' => ['ongoing'],
            'completed', 'canceled', 'refunded' => [],
            default => [],
        };
    }
}

if (! function_exists('booking_admin_allowed_next_statuses_for_booking')) {
    /**
     * Same as {@see booking_admin_allowed_next_statuses} but hides "completed" while an open reopen ticket
     * has not yet chosen the Resolved scenario (unlock).
     *
     * @param  \Modules\BookingModule\Entities\Booking|object|null  $booking
     * @return list<string>
     */
    function booking_admin_allowed_next_statuses_for_booking($booking, ?string $current = null): array
    {
        $statusFromBooking = ($booking instanceof \Modules\BookingModule\Entities\Booking)
            ? (string) ($booking->booking_status ?? '')
            : '';
        $current = strtolower(trim((string) ($current ?? $statusFromBooking)));
        $next = booking_admin_allowed_next_statuses($current);

        if ($booking instanceof \Modules\BookingModule\Entities\Booking && $booking->adminMustConfigureReopenBeforeComplete()) {
            return array_values(array_filter($next, fn ($s) => $s !== 'completed'));
        }

        return $next;
    }
}

if (! function_exists('booking_admin_status_transition_allowed')) {
    function booking_admin_status_transition_allowed(string $from, string $to): bool
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));

        if ($from === $to) {
            return false;
        }

        return in_array($to, booking_admin_allowed_next_statuses($from), true);
    }
}

if (! function_exists('booking_admin_status_transition_allowed_for_booking')) {
    function booking_admin_status_transition_allowed_for_booking($booking, string $from, string $to): bool
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));

        if ($from === $to) {
            return false;
        }

        $allowed = booking_admin_allowed_next_statuses_for_booking($booking, $from);

        return in_array($to, $allowed, true);
    }
}
