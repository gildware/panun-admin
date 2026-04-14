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
            'pending' => ['accepted', 'canceled'],
            'accepted' => ['ongoing', 'on_hold', 'canceled'],
            // Ongoing: hold is "after visit" (same on_hold status); no direct cancel — use complete / hold / special settlement first.
            'ongoing' => ['on_hold', 'completed'],
            'on_hold' => ['accepted', 'ongoing', 'canceled'],
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
            || ($booking instanceof \Modules\BookingModule\Entities\BookingRepeat)
            ? (string) ($booking->booking_status ?? '')
            : '';
        $current = strtolower(trim((string) ($current ?? $statusFromBooking)));
        $next = booking_admin_allowed_next_statuses($current);

        // Hold after visit (on_hold following ongoing): resume work or configure special settlement — not accept/cancel.
        if ($current === 'on_hold'
            && ($booking instanceof \Modules\BookingModule\Entities\Booking
                || $booking instanceof \Modules\BookingModule\Entities\BookingRepeat)
            && booking_on_hold_is_after_visit_from_ongoing($booking)) {
            $next = ['ongoing'];
        }

        // Open reopen ticket: hide Completed until unlock and disallow plain "Cancel booking".
        // Cancellation must be done via the reopen flow (Dispute and close → refund split + cancel).
        if ($booking instanceof \Modules\BookingModule\Entities\Booking && $booking->adminMustConfigureReopenBeforeComplete()) {
            return array_values(array_filter($next, fn ($s) => !in_array($s, ['completed', 'canceled', 'cancelled'], true)));
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
