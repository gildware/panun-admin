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
            'accepted' => ['pending', 'ongoing', 'canceled'],
            'ongoing' => ['on_hold', 'canceled', 'completed'],
            'on_hold' => ['ongoing', 'canceled'],
            'completed', 'canceled', 'refunded' => [],
            default => [],
        };
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
