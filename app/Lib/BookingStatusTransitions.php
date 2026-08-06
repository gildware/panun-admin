<?php

/**
 * Admin booking status workflow: allowed transitions only.
 *
 * @see BOOKING_STATUSES in Constant.php
 */

if (! function_exists('booking_provider_cancel_allowed_statuses')) {
    /**
     * Booking statuses from which a provider may initiate cancellation.
     *
     * @return list<string>
     */
    function booking_provider_cancel_allowed_statuses(): array
    {
        return ['pending', 'accepted'];
    }
}

if (! function_exists('booking_provider_may_cancel_booking')) {
    function booking_provider_may_cancel_booking(?string $currentStatus): bool
    {
        return in_array(strtolower(trim((string) $currentStatus)), booking_provider_cancel_allowed_statuses(), true);
    }
}

if (! function_exists('booking_provider_cancel_blocked_response')) {
    /**
     * @return array{response_code: string, message: string}
     */
    function booking_provider_cancel_blocked_response(?string $currentStatus): array
    {
        $current = strtolower(trim((string) $currentStatus));

        return match ($current) {
            'ongoing' => BOOKING_ALREADY_ONGOING,
            'completed' => BOOKING_ALREADY_COMPLETED,
            'pending_cancellation' => BOOKING_PENDING_CANCELLATION_200,
            'canceled', 'cancelled' => BOOKING_ALREADY_CANCELED_200,
            default => PROVIDER_CANNOT_CANCEL_BOOKING_STATUS_200,
        };
    }
}

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
            'pending_cancellation' => [],
            // Ongoing: hold is "after visit" (same on_hold status); no direct cancel — use complete / hold / special settlement first.
            'ongoing' => ['on_hold', 'completed'],
            'on_hold' => ['accepted', 'ongoing', 'canceled'],
            'canceled', 'cancelled' => ['pending'],
            'completed', 'refunded' => [],
            default => [],
        };
    }
}

if (! function_exists('booking_admin_can_restore_canceled_to_pending')) {
    /**
     * Whether a canceled booking may be restored to pending (no refunds issued, no financial settlement).
     *
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat|object|null  $booking
     */
    function booking_admin_can_restore_canceled_to_pending($booking): bool
    {
        if (! ($booking instanceof \Modules\BookingModule\Entities\Booking
            || $booking instanceof \Modules\BookingModule\Entities\BookingRepeat)) {
            return false;
        }

        $status = strtolower(trim((string) ($booking->booking_status ?? '')));
        if (! in_array($status, ['canceled', 'cancelled'], true)) {
            return false;
        }

        if (trim((string) ($booking->settlement_outcome ?? '')) !== '') {
            return false;
        }

        $bookingId = (string) ($booking->id ?? '');
        if ($bookingId !== '' && function_exists('get_booking_refund_display_totals')) {
            $refundTotals = get_booking_refund_display_totals($booking);
            if (round((float) ($refundTotals['refunded_total'] ?? 0), 2) > 0.009) {
                return false;
            }
        }

        return true;
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

        // Hold after visit (on_hold following ongoing): resume work, cancel, or use special settlement — not accept.
        if ($current === 'on_hold'
            && ($booking instanceof \Modules\BookingModule\Entities\Booking
                || $booking instanceof \Modules\BookingModule\Entities\BookingRepeat)
            && booking_on_hold_is_after_visit_from_ongoing($booking)) {
            $next = ['ongoing', 'canceled'];
        }

        // Open reopen ticket: hide Completed until unlock and disallow plain "Cancel booking".
        // Cancellation must be done via the reopen flow (Dispute and close → refund split + cancel).
        if ($booking instanceof \Modules\BookingModule\Entities\Booking && $booking->adminMustConfigureReopenBeforeComplete()) {
            return array_values(array_filter($next, fn ($s) => !in_array($s, ['completed', 'canceled', 'cancelled'], true)));
        }

        if (in_array($current, ['canceled', 'cancelled'], true)
            && ! booking_admin_can_restore_canceled_to_pending($booking)) {
            return [];
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

if (! function_exists('booking_has_assigned_provider')) {
    /**
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat|object|null  $booking
     */
    function booking_has_assigned_provider($booking): bool
    {
        if ($booking instanceof \Modules\BookingModule\Entities\Booking
            || $booking instanceof \Modules\BookingModule\Entities\BookingRepeat) {
            return ! empty($booking->provider_id);
        }

        return false;
    }
}

if (! function_exists('booking_status_requires_assigned_provider')) {
    function booking_status_requires_assigned_provider(string $status): bool
    {
        return in_array(strtolower(trim($status)), ['accepted', 'ongoing', 'pending_cancellation'], true);
    }
}

if (! function_exists('booking_admin_provider_assigned_for_status')) {
    /**
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat|object|null  $booking
     */
    function booking_admin_provider_assigned_for_status($booking, string $status): bool
    {
        if (! booking_status_requires_assigned_provider($status)) {
            return true;
        }

        return booking_has_assigned_provider($booking);
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

        if (! booking_admin_provider_assigned_for_status($booking, $to)) {
            return false;
        }

        $allowed = booking_admin_allowed_next_statuses_for_booking($booking, $from);

        return in_array($to, $allowed, true);
    }
}

if (! function_exists('booking_service_schedule_calendar_allows_mark_ongoing')) {
    /**
     * Whether the scheduled service calendar day allows marking the visit as ongoing.
     * Time-of-day is ignored: any time on the scheduled date (in app timezone) is allowed; earlier calendar days are not.
     *
     * @param  mixed  $serviceSchedule  Stored booking/repeat schedule (datetime string or null).
     */
    function booking_service_schedule_calendar_allows_mark_ongoing($serviceSchedule): bool
    {
        if ($serviceSchedule === null || $serviceSchedule === '') {
            return true;
        }
        try {
            $schedDay = \Carbon\Carbon::parse($serviceSchedule)->startOfDay();
            $today = now()->startOfDay();
        } catch (\Throwable $e) {
            return false;
        }

        return ! $today->lt($schedDay);
    }
}

if (! function_exists('booking_can_mark_ongoing_by_service_schedule')) {
    /**
     * Server-side guard for transitions to "ongoing": not before the scheduled service date (calendar day).
     *
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat  $model
     * @param  list<string>|null  $repeatStatusesChecked  For repeated parent bookings, which repeat rows must pass the date check
     *                                                     (should match the statuses your update loop touches; default matches admin bulk update).
     */
    function booking_can_mark_ongoing_by_service_schedule($model, ?array $repeatStatusesChecked = null): bool
    {
        if ($model instanceof \Modules\BookingModule\Entities\BookingRepeat) {
            return booking_service_schedule_calendar_allows_mark_ongoing($model->service_schedule ?? null);
        }
        if ($model instanceof \Modules\BookingModule\Entities\Booking) {
            if ((int) ($model->is_repeated ?? 0) !== 0) {
                $statuses = $repeatStatusesChecked ?? ['pending', 'accepted', 'ongoing', 'on_hold'];
                $repeats = $model->repeat()->whereIn('booking_status', $statuses)->get();
                if ($repeats->isEmpty()) {
                    return booking_service_schedule_calendar_allows_mark_ongoing($model->service_schedule ?? null);
                }
                foreach ($repeats as $repeat) {
                    if (! booking_service_schedule_calendar_allows_mark_ongoing($repeat->service_schedule ?? null)) {
                        return false;
                    }
                }

                return true;
            }

            return booking_service_schedule_calendar_allows_mark_ongoing($model->service_schedule ?? null);
        }

        return true;
    }
}

if (! function_exists('booking_admin_can_dispute_and_close')) {
    /**
     * Admin "Dispute and close" is for ongoing, hold-after-visit, or an open reopen ticket (including zero customer payment).
     */
    function booking_admin_can_dispute_and_close($booking): bool
    {
        if (! $booking instanceof \Modules\BookingModule\Entities\Booking) {
            return false;
        }
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            return false;
        }
        if ($booking->isOpenReopenTicket()) {
            return true;
        }
        if (strtolower(trim((string) ($booking->booking_status ?? ''))) === 'ongoing') {
            return true;
        }

        return booking_on_hold_is_after_visit_from_ongoing($booking);
    }
}
