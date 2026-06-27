<?php

use Modules\BookingModule\Entities\Booking;

if (! function_exists('notification_message_variables_for_key')) {
    /**
     * @return list<string>
     */
    function notification_message_variables_for_key(string $key): array
    {
        $common = ['{{bookingId}}', '{{userName}}', '{{zoneName}}', '{{providerName}}', '{{scheduleTime}}'];
        $bookingExtras = ['{{bookingStatus}}', '{{serviceManName}}'];

        return match ($key) {
            'booking_place', 'admin_booking_created', 'booking_accepted', 'booking_complete', 'booking_schedule_time_change',
            'provider_assign', 'booking_status_change', 'booking_reminder' => array_merge($common, $bookingExtras),
            'chat_message' => array_merge($common, ['{{senderName}}']),
            'otp' => array_merge($common, ['{{otp}}']),
            'booking_edit_service_add', 'booking_edit_service_update' => array_merge($common, ['{{serviceName}}']),
            'payment_collected_company', 'payment_collected_provider', 'refund', 'payment_failed' => array_merge($common, ['{{amount}}', '{{bookingStatus}}']),
            'add_fund_wallet', 'referral_earning', 'wallet_deducted' => ['{{amount}}', '{{userName}}', '{{bookingId}}'],
            'loyalty_point', 'loyalty_point_convert' => ['{{amount}}', '{{userName}}', '{{bookingId}}'],
            'refund_bank_transfer' => array_merge($common, ['{{amount}}', '{{bookingStatus}}']),
            'customer_review_approved' => ['{{userName}}', '{{providerName}}', '{{bookingId}}'],
            'review_approved' => ['{{userName}}', '{{providerName}}', '{{bookingId}}'],
            'withdraw_request_submitted' => ['{{amount}}', '{{providerName}}'],
            'provider_removed_from_booking' => array_merge($common, $bookingExtras),
            'new_service_request_arrived', 'admin_booking_assigned', 'booking_assigned_to_provider' => array_merge($common, $bookingExtras),
            'service_request_approve', 'service_request_deny' => ['{{serviceName}}', '{{providerName}}'],
            'widthdraw_request_approve', 'widthdraw_request_deny', 'admin_payable', 'settlement_received' => ['{{amount}}', '{{providerName}}'],
            default => $common,
        };
    }
}

if (! function_exists('notification_message_preview_samples')) {
    /**
     * @return array<string, string>
     */
    function notification_message_preview_samples(): array
    {
        return [
            'bookingId' => 'BK-1024',
            'userName' => 'John Doe',
            'zoneName' => 'Downtown',
            'providerName' => 'Acme Services',
            'scheduleTime' => '2026-06-25 14:30',
            'serviceManName' => 'Alex Smith',
            'bookingStatus' => 'Ongoing',
            'serviceName' => 'AC Repair',
            'amount' => '$150.00',
            'otp' => '482910',
            'senderName' => 'Acme Services',
        ];
    }
}

if (! function_exists('preview_notification_message_text')) {
    function preview_notification_message_text(string $text, ?string $notificationKey = null): string
    {
        if ($text === '') {
            return '';
        }

        $samples = notification_message_preview_samples();
        $replaceMap = [];

        foreach ($samples as $name => $value) {
            $replaceMap['{{' . $name . '}}'] = $value;
        }

        if ($notificationKey) {
            foreach (notification_message_variables_for_key($notificationKey) as $var) {
                $name = trim($var, '{}');
                if (! isset($replaceMap[$var])) {
                    $replaceMap[$var] = $samples[$name] ?? '';
                }
            }
        }

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $text);
    }
}

if (! function_exists('resolve_booking_status_notification_key')) {
    function resolve_booking_status_notification_key(string $status, string $audience = 'customer'): ?string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending' => $audience === 'provider' ? 'new_service_request_arrived' : 'booking_place',
            'accepted' => 'booking_accepted',
            'completed' => 'booking_complete',
            'refund_request' => $audience === 'customer' ? 'refund' : null,
            default => 'booking_status_change',
        };
    }
}

if (! function_exists('should_notify_customer_booking_placed_on_status_change')) {
    /**
     * Customer "booking placed" applies to new bookings, not when a provider withdraws and the booking reopens as pending.
     */
    function should_notify_customer_booking_placed_on_status_change(
        string $newStatus,
        bool $statusChanged,
        string $previousStatus,
        bool $providerCancelledAtChanged,
        mixed $providerCancelledAt,
    ): bool {
        if ($newStatus !== 'pending') {
            return false;
        }

        if (! $statusChanged) {
            return true;
        }

        $reopenedAfterProviderWithdrawal = in_array($previousStatus, ['accepted', 'ongoing', 'pending_cancellation'], true)
            && ($providerCancelledAtChanged || $providerCancelledAt !== null);

        return ! $reopenedAfterProviderWithdrawal;
    }
}

if (! function_exists('should_notify_customer_booking_placed_on_pending_status')) {
    function should_notify_customer_booking_placed_on_pending_status(Booking $model): bool
    {
        return should_notify_customer_booking_placed_on_status_change(
            (string) $model->booking_status,
            $model->isDirty('booking_status'),
            (string) $model->getOriginal('booking_status'),
            $model->isDirty('provider_cancelled_at'),
            $model->provider_cancelled_at,
        );
    }
}

if (! function_exists('booking_provider_assignment_changed')) {
    /**
     * True when admin (or any save) is assigning or changing the booking provider.
     */
    function booking_provider_assignment_changed(Booking $model, bool $afterSave = false): bool
    {
        if (! $model->provider_id) {
            return false;
        }

        return $afterSave
            ? $model->wasChanged('provider_id')
            : $model->isDirty('provider_id');
    }
}

if (! function_exists('booking_customer_notification_key_for_accepted_status')) {
    function booking_customer_notification_key_for_accepted_status(Booking $model): string
    {
        return booking_provider_assignment_changed($model)
            ? 'provider_assign'
            : 'booking_accepted';
    }
}

if (! function_exists('booking_provider_notification_key_for_accepted_status')) {
    function booking_provider_notification_key_for_accepted_status(Booking $model): string
    {
        return booking_provider_assignment_changed($model)
            ? 'booking_assigned_to_provider'
            : 'booking_accepted';
    }
}

if (! function_exists('booking_skip_provider_assignment_notification_in_updated')) {
    /**
     * Avoid duplicate provider-assignment pushes when accepted + provider_id change are saved together.
     */
    function booking_skip_provider_assignment_notification_in_updated(Booking $model): bool
    {
        if ((string) ($model->booking_status ?? '') !== 'accepted') {
            return false;
        }

        if (! booking_provider_assignment_changed($model, true)) {
            return false;
        }

        return $model->wasChanged('booking_status');
    }
}

if (! function_exists('notification_trigger_scenarios_for_key')) {
    /**
     * @return array{summary: string, scenarios: list<string>, recipient: string, module: string, wired: bool}|null
     */
    function notification_trigger_scenarios_for_key(string $key, string $settingsType): ?array
    {
        $isCustomer = $settingsType === 'customer_notification';
        $recipient = $isCustomer ? 'Customer' : 'Provider';

        $scenarios = match ($key) {
            'booking_place' => $isCustomer ? [
                'summary' => 'Sent when a new booking is created for the customer.',
                'scenarios' => [
                    'Customer places a booking from the mobile app.',
                    'Repeat booking series is initiated (first occurrence).',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'admin_booking_created' => $isCustomer ? [
                'summary' => 'Sent when admin creates a booking on behalf of the customer (already accepted).',
                'scenarios' => [
                    'Admin uses Add New Booking with a provider assigned.',
                    'Admin creates a follow-up booking from a completed visit.',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'booking_accepted' => [
                'summary' => 'Sent when the booking status changes to Accepted.',
                'scenarios' => $isCustomer ? [
                    'Provider accepts the booking from the provider app.',
                    'Admin changes booking status to Accepted.',
                ] : [
                    'Provider accepts the booking (confirmation to provider).',
                    'Admin assigns/accepts booking on behalf of provider.',
                ],
                'recipient' => $recipient,
                'module' => 'Bookings',
                'wired' => true,
            ],

            'booking_complete' => [
                'summary' => 'Sent when the booking is marked Completed.',
                'scenarios' => [
                    'Provider marks job complete from the app.',
                    'Admin changes status to Completed after full payment rules pass.',
                    'Repeat visit row is completed.',
                ],
                'recipient' => $recipient,
                'module' => 'Bookings',
                'wired' => true,
            ],

            'booking_schedule_time_change' => [
                'summary' => 'Sent when the service schedule date/time is updated.',
                'scenarios' => [
                    'Admin reschedules the booking.',
                    'Provider or customer flow updates service_schedule on the booking.',
                ],
                'recipient' => $recipient,
                'module' => 'Bookings',
                'wired' => true,
            ],

            'otp' => $isCustomer ? [
                'summary' => 'Sent when the provider shares the booking confirmation OTP with the customer.',
                'scenarios' => [
                    'Provider taps “Send OTP” from the provider app during an ongoing visit.',
                    'OTP verification is enabled in booking setup.',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'provider_assign' => $isCustomer ? [
                'summary' => 'Sent when a provider is assigned or reassigned to the booking.',
                'scenarios' => [
                    'Admin assigns or changes provider on the booking.',
                    'Booking is reassigned to a different provider.',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'booking_status_change' => [
                'summary' => 'Sent for booking status changes that do not have a dedicated message.',
                'scenarios' => [
                    'Status changes to Ongoing.',
                    'Status changes to Canceled.',
                    'Status changes to On hold or other non-standard statuses.',
                ],
                'recipient' => $recipient,
                'module' => 'Bookings',
                'wired' => true,
            ],

            'new_service_request_arrived' => ! $isCustomer ? [
                'summary' => 'Sent when a new pending booking is available for the provider.',
                'scenarios' => [
                    'Customer places a new booking in the provider’s zone.',
                    'Booking is verified and providers in zone are notified.',
                ],
                'recipient' => 'Provider',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'admin_booking_assigned' => ! $isCustomer ? [
                'summary' => 'Sent when admin creates a booking already assigned and accepted for this provider.',
                'scenarios' => [
                    'Admin uses Add New Booking and selects this provider.',
                    'Admin creates a follow-up booking assigned to this provider.',
                ],
                'recipient' => 'Provider',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'booking_assigned_to_provider' => ! $isCustomer ? [
                'summary' => 'Sent when admin directly assigns or reassigns a booking to this provider.',
                'scenarios' => [
                    'Admin changes provider on an existing booking.',
                    'Booking is reassigned from one provider to another.',
                ],
                'recipient' => 'Provider',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'booking_edit_service_add' => [
                'summary' => 'Sent when a service, extra service, or spare part is added to the booking.',
                'scenarios' => [
                    'Admin adds a line item during booking edit.',
                    'Provider adds an extra service or spare part on-site.',
                ],
                'recipient' => $recipient,
                'module' => 'Service Updates',
                'wired' => true,
            ],

            'booking_edit_service_update' => [
                'summary' => 'Sent when an existing booking line item is updated or removed.',
                'scenarios' => [
                    'Service quantity is increased or decreased.',
                    'Service, extra, or spare part is removed from the booking.',
                    'Admin edits an existing booking line.',
                ],
                'recipient' => $recipient,
                'module' => 'Service Updates',
                'wired' => true,
            ],

            'service_request_approve' => ! $isCustomer ? [
                'summary' => 'Sent when admin approves a provider’s new service request.',
                'scenarios' => [
                    'Provider submits a service for approval and admin approves it.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

            'service_request_deny' => ! $isCustomer ? [
                'summary' => 'Sent when admin rejects a provider’s new service request.',
                'scenarios' => [
                    'Provider submits a service for approval and admin denies it.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

            'payment_collected_company' => [
                'summary' => 'Sent when customer payment is recorded as received by the company.',
                'scenarios' => [
                    'Admin adds payment on booking details with “Company” as receiver.',
                    'Customer pays due balance digitally to company.',
                    'Advance/checkout payment is attributed to company.',
                ],
                'recipient' => $recipient,
                'module' => 'Payments',
                'wired' => true,
            ],

            'payment_collected_provider' => [
                'summary' => 'Sent when customer payment is recorded as received by the provider.',
                'scenarios' => [
                    'Provider records cash received from customer on-site.',
                    'Admin adds payment with “Provider” as receiver during ongoing job.',
                ],
                'recipient' => $recipient,
                'module' => 'Payments',
                'wired' => true,
            ],

            'payment_failed' => $isCustomer ? [
                'summary' => 'Sent when a customer payment attempt fails.',
                'scenarios' => [
                    'Wallet top-up payment fails at the gateway.',
                    'Booking checkout digital payment fails or is cancelled.',
                    'Repeat booking or due-balance online payment fails.',
                ],
                'recipient' => 'Customer',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'refund' => $isCustomer ? [
                'summary' => 'Sent when a refund is credited to the customer.',
                'scenarios' => [
                    'Admin refunds a canceled booking to the customer wallet.',
                    'Admin/system triggers wallet refund transaction for the booking.',
                ],
                'recipient' => 'Customer',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'add_fund_wallet' => $isCustomer ? [
                'summary' => 'Sent when funds are added to the customer wallet.',
                'scenarios' => [
                    'Customer tops up wallet via payment gateway.',
                    'Admin adds fund to customer wallet (including bonus if applicable).',
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
                'wired' => true,
            ] : null,

            'wallet_deducted' => $isCustomer ? [
                'summary' => 'Sent when amount is deducted from the customer wallet.',
                'scenarios' => [
                    'Booking is paid fully or partially from wallet.',
                    'Checkout uses wallet balance for advance or due payment.',
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
                'wired' => true,
            ] : null,

            'referral_earning' => $isCustomer ? [
                'summary' => 'Sent when the customer earns a referral reward.',
                'scenarios' => [
                    'Referred user completes first booking and referrer earns.',
                    'Referral earning is credited after booking completion.',
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
                'wired' => true,
            ] : null,

            'loyalty_point' => $isCustomer ? [
                'summary' => 'Sent when loyalty points are credited after a completed booking.',
                'scenarios' => [
                    'Booking is completed and loyalty points are calculated.',
                    'Loyalty program is enabled in customer settings.',
                    'Admin manually credits loyalty points to a customer.',
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
                'wired' => true,
            ] : null,

            'loyalty_point_convert' => $isCustomer ? [
                'summary' => 'Sent when a customer converts loyalty points to wallet balance.',
                'scenarios' => [
                    'Customer transfers loyalty points to wallet from the app.',
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
                'wired' => true,
            ] : null,

            'refund_bank_transfer' => $isCustomer ? [
                'summary' => 'Sent when admin records a bank transfer refund on a canceled booking.',
                'scenarios' => [
                    'Admin records an outbound ledger refund with a bank transaction reference.',
                ],
                'recipient' => 'Customer',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'customer_review_approved' => $isCustomer ? [
                'summary' => 'Sent when admin approves a provider review of the customer.',
                'scenarios' => [
                    'Admin approves a provider-submitted customer review.',
                ],
                'recipient' => 'Customer',
                'module' => 'Review',
                'wired' => true,
            ] : null,

            'review_approved' => ! $isCustomer ? [
                'summary' => 'Sent when admin approves a customer review of the provider.',
                'scenarios' => [
                    'Admin approves a customer-submitted service review.',
                ],
                'recipient' => 'Provider',
                'module' => 'Review',
                'wired' => true,
            ] : null,

            'withdraw_request_submitted' => ! $isCustomer ? [
                'summary' => 'Sent when a provider submits a withdraw request.',
                'scenarios' => [
                    'Provider requests withdrawal from wallet in the app or provider panel.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'provider_removed_from_booking' => ! $isCustomer ? [
                'summary' => 'Sent when a provider is removed from a booking.',
                'scenarios' => [
                    'Provider withdraws from an accepted booking.',
                    'Admin removes or reassigns the provider on a booking.',
                ],
                'recipient' => 'Provider',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'widthdraw_request_approve' => ! $isCustomer ? [
                'summary' => 'Sent when admin approves a provider withdraw request.',
                'scenarios' => [
                    'Provider requests withdrawal and admin approves it.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'widthdraw_request_deny' => ! $isCustomer ? [
                'summary' => 'Sent when admin denies a provider withdraw request.',
                'scenarios' => [
                    'Provider requests withdrawal and admin rejects it.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'admin_payable' => ! $isCustomer ? [
                'summary' => 'Sent when admin pays/settles an amount owed to the provider.',
                'scenarios' => [
                    'Admin records a payout to provider account.',
                    'Settlement payment is processed to provider wallet/account.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'settlement_received' => ! $isCustomer ? [
                'summary' => 'Sent when the company records a settlement payout to the provider.',
                'scenarios' => [
                    'Admin pays provider from the provider payment tab.',
                    'Ledger payout is recorded for booking settlement.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'booking_reminder' => $isCustomer ? [
                'summary' => 'Sent before the scheduled service time as a reminder.',
                'scenarios' => [
                    'Automated reminder runs about one hour before service_schedule.',
                    'Customer has an accepted or ongoing booking coming up.',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'chat_message' => [
                'summary' => 'Sent when a new in-app chat message arrives.',
                'scenarios' => $isCustomer ? [
                    'Provider or admin sends a message in the booking chat.',
                    'Customer receives a reply in the conversation channel.',
                ] : [
                    'Customer or admin sends a message in the booking chat.',
                    'Provider receives a reply in the conversation channel.',
                ],
                'recipient' => $recipient,
                'module' => 'Bookings',
                'wired' => true,
            ],

            default => null,
        };

        return is_array($scenarios) ? $scenarios : null;
    }
}

if (! function_exists('notification_trigger_recommendations')) {
    /**
     * @return list<array{key: string, value: string, audience: string, reason: string}>
     */
    function notification_trigger_recommendations(): array
    {
        return [];
    }
}

if (! function_exists('notification_definition_for_key')) {
    /**
     * @return array{key: string, value: string, category?: string}|null
     */
    function notification_definition_for_key(string $key): ?array
    {
        foreach (array_merge(NOTIFICATION_FOR_USER, NOTIFICATION_FOR_PROVIDER) as $definition) {
            if (($definition['key'] ?? '') === $key) {
                return $definition;
            }
        }

        return null;
    }
}

if (! function_exists('notification_scenario_registry')) {
    /**
     * Scenario-based notification map: trigger actor/action → audiences and message keys.
     *
     * @return list<array{
     *     id: string,
     *     module: string,
     *     title: string,
     *     trigger_actor: string,
     *     trigger_action: string,
     *     audiences: list<array{
     *         audience: string,
     *         channel: string,
     *         key: string|null,
     *         settings_type: string|null,
     *         wired: bool,
     *         note?: string
     *     }>
     * }>
     */
    function notification_scenario_registry(): array
    {
        return [
            // --- Booking Creation ---
            [
                'id' => 'booking_create_customer_with_provider',
                'module' => 'booking_creation',
                'title' => 'Customer books a service and selects a provider',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Places booking from the customer app with a provider selected',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_place', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'new_service_request_arrived', 'settings_type' => 'provider_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'New booking inbox alert'],
                ],
            ],
            [
                'id' => 'booking_create_customer_auto_provider',
                'module' => 'booking_creation',
                'title' => 'Customer books and lets Panun Kaergar choose a provider',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Places booking without selecting a provider',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_place', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'New booking inbox alert'],
                ],
            ],
            [
                'id' => 'booking_create_admin',
                'module' => 'booking_creation',
                'title' => 'Admin creates a booking from the admin panel',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Creates booking with customer and provider assigned',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'admin_booking_created', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'admin_booking_assigned', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],

            // --- Booking Update ---
            [
                'id' => 'booking_admin_assign_provider',
                'module' => 'booking_update',
                'title' => 'Admin assigns a provider to a booking',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Assigns or changes provider on the booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'provider_assign', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_assigned_to_provider', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_provider_cancel',
                'module' => 'booking_update',
                'title' => 'Provider rejects or cancels a booking',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Rejects pending booking or withdraws from accepted booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true, 'note' => 'Uses withdraw-specific copy when provider withdraws'],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'provider_removed_from_booking', 'settings_type' => 'provider_notification', 'wired' => true, 'note' => 'Confirmation to provider after withdrawal'],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Provider withdrawal inbox alert'],
                ],
            ],
            [
                'id' => 'booking_provider_ongoing',
                'module' => 'booking_update',
                'title' => 'Provider changes booking status to ongoing',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Marks booking as ongoing from the provider app',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'provider_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when booking goes ongoing'],
                ],
            ],
            [
                'id' => 'booking_admin_remove_provider',
                'module' => 'booking_update',
                'title' => 'Admin removes a provider from a booking',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Clears provider assignment on the booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'provider_removed_from_booking', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_customer_cancel',
                'module' => 'booking_update',
                'title' => 'Customer cancels a booking',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Cancels booking from the customer app',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'provider_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when booking goes ongoing'],
                ],
            ],
            [
                'id' => 'booking_admin_edit',
                'module' => 'booking_update',
                'title' => 'Admin makes changes to a booking',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Edits booking details, services, or line items',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_edit_service_add', 'settings_type' => 'customer_notification', 'wired' => true, 'note' => 'Service add/update keys'],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_edit_service_add', 'settings_type' => 'provider_notification', 'wired' => true, 'note' => 'Service add/update keys'],
                ],
            ],
            [
                'id' => 'booking_admin_cancel',
                'module' => 'booking_update',
                'title' => 'Admin cancels a booking',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Cancels booking from the admin panel',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_schedule_change',
                'module' => 'booking_update',
                'title' => 'Admin changes the booking schedule',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Updates service schedule date or time',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_schedule_time_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_schedule_time_change', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],

            // --- Payments ---
            [
                'id' => 'payment_provider_records',
                'module' => 'payments',
                'title' => 'Provider records a payment on a booking',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Records cash or payment received on-site',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'payment_collected_provider', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'payment_collected_provider', 'settings_type' => 'provider_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when booking goes ongoing'],
                ],
            ],
            [
                'id' => 'payment_customer_app_company',
                'module' => 'payments',
                'title' => 'Customer pays from the app to the company',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Completes digital payment to company',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'payment_collected_company', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'payment_collected_company', 'settings_type' => 'provider_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when booking goes ongoing'],
                ],
            ],
            [
                'id' => 'payment_admin_records',
                'module' => 'payments',
                'title' => 'Admin records a payment on a booking',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Adds payment on booking details (company or provider receiver)',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'payment_collected_company', 'settings_type' => 'customer_notification', 'wired' => true, 'note' => 'Company or provider key based on receiver'],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'payment_collected_provider', 'settings_type' => 'provider_notification', 'wired' => true, 'note' => 'Company or provider key based on receiver'],
                ],
            ],

            // --- Provider Payments ---
            [
                'id' => 'provider_withdraw_request',
                'module' => 'provider_payments',
                'title' => 'Provider submits a withdraw request',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Requests withdrawal from wallet',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Withdraw request inbox alert'],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'withdraw_request_submitted', 'settings_type' => 'provider_notification', 'wired' => true, 'note' => 'Provider confirmation after submit'],
                ],
            ],
            [
                'id' => 'provider_withdraw_approved',
                'module' => 'provider_payments',
                'title' => 'Admin approves a withdraw request',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Approves provider withdrawal request',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'widthdraw_request_approve', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'provider_withdraw_settled',
                'module' => 'provider_payments',
                'title' => 'Admin settles a withdraw request (payout)',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Marks withdraw request as settled / paid out',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'settlement_received', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'admin_collect_from_provider',
                'module' => 'provider_payments',
                'title' => 'Admin collects payment from provider',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Records cash collection from provider',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'admin_payable', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'admin_pay_provider',
                'module' => 'provider_payments',
                'title' => 'Admin adds payment to provider (settlement payout)',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Records payout / settlement to provider',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'settlement_received', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],

            // --- Review ---
            [
                'id' => 'review_customer_to_provider_approved',
                'module' => 'review',
                'title' => 'Admin approves customer review of provider',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Approves service review submitted by customer',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'review_approved', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'review_provider_to_customer_approved',
                'module' => 'review',
                'title' => 'Admin approves provider review of customer',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Approves customer review submitted by provider',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'customer_review_approved', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],

            // --- Loyalty Points ---
            [
                'id' => 'loyalty_booking_completed',
                'module' => 'loyalty_points',
                'title' => 'Customer earns loyalty points after booking completion',
                'trigger_actor' => 'system',
                'trigger_action' => 'Credits loyalty points when booking is completed',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'loyalty_point', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'loyalty_convert_to_wallet',
                'module' => 'loyalty_points',
                'title' => 'Customer converts loyalty points to wallet',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Transfers loyalty points to wallet balance',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'loyalty_point_convert', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'loyalty_admin_adds',
                'module' => 'loyalty_points',
                'title' => 'Admin adds loyalty points to customer',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Manually credits loyalty points',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'loyalty_point', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'loyalty_referral_earned',
                'module' => 'loyalty_points',
                'title' => 'Customer earns referral reward',
                'trigger_actor' => 'system',
                'trigger_action' => 'Credits referral earning after referred user completes first booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'referral_earning', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],

            // --- Refund ---
            [
                'id' => 'refund_wallet',
                'module' => 'refund',
                'title' => 'Admin refunds amount to customer wallet',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Records wallet refund on canceled booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'refund', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'refund_bank_transfer',
                'module' => 'refund',
                'title' => 'Admin refunds via bank transfer to customer',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Records bank transfer refund on canceled booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'refund_bank_transfer', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
        ];
    }
}

if (! function_exists('group_notification_scenarios_by_module')) {
    /**
     * @return array<string, list<array>>
     */
    function group_notification_scenarios_by_module(?array $scenarios = null): array
    {
        $scenarios ??= notification_scenario_registry();
        $grouped = [];

        foreach ($scenarios as $scenario) {
            $module = $scenario['module'] ?? 'other';
            $grouped[$module][] = $scenario;
        }

        $ordered = [];
        foreach (array_keys(NOTIFICATION_SCENARIO_MODULE_LABELS) as $module) {
            if (! empty($grouped[$module])) {
                $ordered[$module] = $grouped[$module];
            }
        }

        if (! empty($grouped['other'])) {
            $ordered['other'] = $grouped['other'];
        }

        return $ordered;
    }
}

if (! function_exists('notification_scenario_audience_message_label')) {
    function notification_scenario_audience_message_label(?string $key): string
    {
        if (! $key) {
            return translate('Admin_inbox_only');
        }

        $definition = notification_definition_for_key($key);

        return $definition['value'] ?? ucwords(str_replace('_', ' ', $key));
    }
}

if (! function_exists('persist_transactional_push_notification')) {
    /**
     * Save a transactional push to the inbox tables mobile apps read (push_notifications + push_notification_users).
     */
    function persist_transactional_push_notification(
        string $title,
        string $description,
        string $audience,
        string $userId,
        ?string $zoneId = null,
        ?string $notificationType = null,
        mixed $bookingId = null,
        ?string $bookingType = null,
        ?string $repeatType = null,
    ): void {
        try {
            $pushNotification = new \Modules\PromotionManagement\Entities\PushNotification();
            $pushNotification->title = $title;
            $pushNotification->description = $description;
            $pushNotification->to_users = [$audience];
            $pushNotification->zone_ids = $zoneId ? [$zoneId] : [];
            $pushNotification->is_active = 1;
            $pushNotification->notification_type = $notificationType;
            $pushNotification->booking_id = is_string($bookingId) && $bookingId !== '' ? $bookingId : null;
            $pushNotification->booking_type = $bookingType;
            $pushNotification->repeat_type = $repeatType;
            $pushNotification->save();

            $pushNotificationUser = new \Modules\PromotionManagement\Entities\PushNotificationUser();
            $pushNotificationUser->push_notification_id = $pushNotification->id;
            $pushNotificationUser->user_id = $userId;
            $pushNotificationUser->read_at = null;
            $pushNotificationUser->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to persist transactional push notification', [
                'audience' => $audience,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (! function_exists('scenario_push_notification')) {
    /**
     * Send FCM push and persist to the in-app notification inbox for the recipient.
     */
    function scenario_push_notification(
        ?string $fcmToken,
        string $title,
        string $description,
        mixed $bookingId = null,
        string $type = 'booking',
        ?string $userId = null,
        mixed $data = null,
        ?string $bookingType = null,
        ?string $bookingStatusOverride = null,
        ?string $inboxAudience = null,
        ?string $zoneId = null,
        mixed $image = null,
        mixed $advertisementId = null,
    ): void {
        if ($title === '') {
            return;
        }

        $formattedTitle = text_variable_data_format($title, $bookingId, $type, $data, $bookingType);
        if (is_array($formattedTitle)) {
            $formattedTitle = $title;
        }
        $formattedBody = format_push_notification_body($description, $bookingId, $type, $data, $bookingType);

        if ($userId && $inboxAudience) {
            persist_transactional_push_notification(
                (string) $formattedTitle,
                (string) $formattedBody,
                $inboxAudience,
                $userId,
                $zoneId,
                $type,
                is_string($bookingId) || is_numeric($bookingId) ? (string) $bookingId : null,
                $bookingType,
                null
            );
        }

        if ($fcmToken) {
            device_notification(
                $fcmToken,
                $title,
                $description,
                $image,
                $bookingId,
                $type,
                null,
                $userId,
                $data,
                $advertisementId,
                $bookingType,
                null,
                null,
                null,
                $bookingStatusOverride
            );
        }
    }
}

if (! function_exists('send_admin_booking_created_notifications')) {
    function send_admin_booking_created_notifications(Booking $booking): void
    {
        $bookingNotificationStatus = business_config('booking', 'notification_settings')->live_values;
        if (! ($bookingNotificationStatus['push_notification_booking'] ?? false)) {
            return;
        }

        $booking->loadMissing(['customer', 'provider.owner', 'zone']);

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_id' => $booking->readable_id ?? $booking->id,
            'user_name' => trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? '')),
            'zone_name' => $booking->zone?->name ?? '',
            'provider_name' => $booking->provider?->company_name ?? $booking->provider?->contact_person_name ?? '',
            'schedule_time' => $booking->service_schedule
                ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
                : '',
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
        ];

        if (isNotificationActive(null, 'booking', 'notification', 'user')) {
            $key = 'admin_booking_created';
            $user = $booking->customer;
            $title = get_push_notification_message($key, 'customer_notification', $user?->current_language_key);
            $description = get_push_notification_description($key, 'customer_notification', $user?->current_language_key);
            if ($user && $title && $user->is_active) {
                scenario_push_notification(
                    $user->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $user->id,
                    $data,
                    $repeatOrRegular,
                    (string) ($booking->booking_status ?? 'pending'),
                    'customer',
                    $booking->zone_id
                );
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'provider') && $booking->provider_id) {
            $key = 'admin_booking_assigned';
            $providerOwner = $booking->provider?->owner;
            $title = get_push_notification_message($key, 'provider_notification', $providerOwner?->current_language_key);
            $description = get_push_notification_description($key, 'provider_notification', $providerOwner?->current_language_key);
            if ($providerOwner && $title && sendDeviceNotificationPermission($booking->provider_id)) {
                scenario_push_notification(
                    $providerOwner->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $providerOwner->id,
                    $data,
                    $repeatOrRegular,
                    (string) ($booking->booking_status ?? 'pending'),
                    'provider-admin',
                    $booking->zone_id
                );
            }
        }
    }
}

if (! function_exists('send_booking_new_service_request_to_assigned_provider')) {
    /**
     * Notify the provider explicitly chosen by the customer on a new booking.
     * Runs on BookingRequested (after commit) — do not gate on subscription booking limits.
     */
    function send_booking_new_service_request_to_assigned_provider(Booking $booking): void
    {
        if (! booking_push_notifications_enabled() || ! $booking->provider_id) {
            return;
        }

        $booking->loadMissing(['provider.owner', 'customer', 'zone']);
        $provider = $booking->provider;
        $owner = $provider?->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'booking', 'notification', 'provider')) {
            return;
        }

        $maximumBookingAmount = (float) ((business_config('max_booking_amount', 'booking_setup'))?->live_values ?? 0);
        $bookingGrandForCap = get_booking_total_amount($booking);
        $underAutoAcceptCap = $maximumBookingAmount > 0 && $bookingGrandForCap < $maximumBookingAmount;

        $messageKey = ($booking->payment_method === 'cash_after_service'
            && $underAutoAcceptCap
            && (string) ($booking->booking_status ?? '') !== 'pending')
            ? 'booking_accepted'
            : 'new_service_request_arrived';

        $title = get_push_notification_message($messageKey, 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description($messageKey, 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_id' => $booking->readable_id ?? $booking->id,
            'user_name' => trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? '')),
            'zone_name' => $booking->zone?->name ?? '',
            'provider_name' => $provider?->company_name ?? $provider?->contact_person_name ?? '',
            'schedule_time' => $booking->service_schedule
                ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
                : '',
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
        ];

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $booking->id,
            'booking',
            $owner->id,
            $data,
            $repeatOrRegular,
            (string) ($booking->booking_status ?? 'pending'),
            'provider-admin',
            $booking->zone_id
        );
    }
}

if (! function_exists('send_booking_edit_service_add_notifications')) {
    function send_booking_edit_service_add_notifications(Booking $booking, string $serviceName): void
    {
        $bookingNotificationStatus = business_config('booking', 'notification_settings')->live_values;
        if (! ($bookingNotificationStatus['push_notification_booking'] ?? false)) {
            return;
        }

        $booking->loadMissing(['customer', 'provider.owner', 'serviceman.user']);

        $key = 'booking_edit_service_add';
        $data = ['service_name' => $serviceName];
        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';

        if (isNotificationActive(null, 'booking', 'notification', 'user')) {
            $user = $booking->customer;
            $title = get_push_notification_message($key, 'customer_notification', $user?->current_language_key);
            $description = get_push_notification_description($key, 'customer_notification', $user?->current_language_key);
            if ($user && $title) {
                scenario_push_notification(
                    $user->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $user->id,
                    $data,
                    $repeatOrRegular,
                    null,
                    'customer',
                    $booking->zone_id
                );
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'provider')) {
            $providerOwner = $booking->provider?->owner;
            $title = get_push_notification_message($key, 'provider_notification', $providerOwner?->current_language_key);
            $description = get_push_notification_description($key, 'provider_notification', $providerOwner?->current_language_key);
            if ($providerOwner && $title) {
                scenario_push_notification(
                    $providerOwner->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $providerOwner->id,
                    $data,
                    $repeatOrRegular,
                    null,
                    'provider-admin',
                    $booking->zone_id
                );
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'serviceman')) {
            $serviceman = $booking->serviceman?->user;
            $title = get_push_notification_message($key, 'serviceman_notification', $serviceman?->current_language_key);
            $description = get_push_notification_description($key, 'serviceman_notification', $serviceman?->current_language_key);
            if ($serviceman?->fcm_token && $title) {
                device_notification($serviceman->fcm_token, $title, $description, null, $booking->id, 'booking', null, null, $data, null, $repeatOrRegular);
            }
        }
    }
}

if (! function_exists('send_booking_payment_collected_notifications')) {
    function send_booking_payment_collected_notifications(Booking $booking, float $amount, string $receivedBy): void
    {
        $config = business_config('booking', 'notification_settings');
        if (! ($config->live_values['push_notification_booking'] ?? false)) {
            return;
        }

        $receivedBy = strtolower(trim($receivedBy));
        $key = $receivedBy === 'provider' ? 'payment_collected_provider' : 'payment_collected_company';
        $data = [
            'amount' => with_currency_symbol($amount),
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
        ];

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';

        if (isNotificationActive(null, 'booking', 'notification', 'user')) {
            $user = $booking->customer;
            $title = get_push_notification_message($key, 'customer_notification', $user?->current_language_key);
            $description = get_push_notification_description($key, 'customer_notification', $user?->current_language_key);
            if ($user && $user->is_active && $title) {
                scenario_push_notification(
                    $user->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $user->id,
                    $data,
                    $repeatOrRegular,
                    null,
                    'customer',
                    $booking->zone_id
                );
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'provider') && $booking->provider_id) {
            $providerOwner = $booking->provider?->owner;
            $title = get_push_notification_message($key, 'provider_notification', $providerOwner?->current_language_key);
            $description = get_push_notification_description($key, 'provider_notification', $providerOwner?->current_language_key);
            if ($providerOwner && $title && sendDeviceNotificationPermission($booking->provider_id)) {
                scenario_push_notification(
                    $providerOwner->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $providerOwner->id,
                    $data,
                    $repeatOrRegular,
                    null,
                    'provider-admin',
                    $booking->zone_id
                );
            }
        }

        if (function_exists('admin_inbox_notify_booking_payment')) {
            admin_inbox_notify_booking_payment($booking, $amount, $receivedBy);
        }
    }
}

if (! function_exists('send_customer_payment_failed_notification')) {
    function send_customer_payment_failed_notification(
        string|int $customerUserId,
        ?float $amount = null,
        ?string $bookingId = null
    ): void {
        $user = \Modules\UserManagement\Entities\User::find($customerUserId);
        if (! $user || ! $user->is_active) {
            return;
        }

        $permission = isNotificationActive(null, 'wallet', 'notification', 'user')
            || isNotificationActive(null, 'booking', 'notification', 'user');
        $title = get_push_notification_message('payment_failed', 'customer_notification', $user->current_language_key);
        $description = get_push_notification_description('payment_failed', 'customer_notification', $user->current_language_key);
        if (! $title || ! $permission) {
            return;
        }

        $data = [
            'amount' => $amount !== null ? with_currency_symbol($amount) : '',
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'booking_id' => $bookingId ?? '',
        ];

        scenario_push_notification(
            $user->fcm_token,
            $title,
            $description,
            $bookingId,
            NOTIFICATION_TYPE['wallet'] ?? 'wallet',
            $user->id,
            $data,
            null,
            null,
            'customer',
            config('zone_id')
        );
    }
}

if (! function_exists('send_customer_wallet_deducted_notification')) {
    function send_customer_wallet_deducted_notification(
        \Modules\UserManagement\Entities\User $user,
        float $amount,
        ?string $bookingId = null
    ): void {
        if (! $user->is_active || $amount <= 0) {
            return;
        }

        // Booking checkout already sends booking_place; skip a second wallet push.
        if ($bookingId) {
            return;
        }

        $permission = isNotificationActive(null, 'wallet', 'notification', 'user');
        $title = get_push_notification_message('wallet_deducted', 'customer_notification', $user->current_language_key);
        $description = get_push_notification_description('wallet_deducted', 'customer_notification', $user->current_language_key);
        if (! $title || ! $permission) {
            return;
        }

        $booking = $bookingId ? Booking::find($bookingId) : null;
        $data = [
            'amount' => with_currency_symbol($amount),
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'booking_id' => $booking?->readable_id ?? '',
        ];

        scenario_push_notification(
            $user->fcm_token,
            with_currency_symbol($amount) . ' ' . $title,
            $description,
            $bookingId,
            NOTIFICATION_TYPE['wallet'] ?? 'wallet',
            $user->id,
            $data,
            null,
            null,
            'customer',
            $booking?->zone_id ?? config('zone_id')
        );
    }
}

if (! function_exists('send_provider_settlement_received_notification')) {
    function send_provider_settlement_received_notification(
        \Modules\ProviderManagement\Entities\Provider|string|null $provider,
        float $amount
    ): void {
        if ($amount <= 0) {
            return;
        }

        if (is_string($provider)) {
            $provider = \Modules\ProviderManagement\Entities\Provider::with('owner')->find($provider);
        } elseif ($provider && ! $provider->relationLoaded('owner')) {
            $provider->load('owner');
        }

        $owner = $provider?->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'wallet', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message('settlement_received', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('settlement_received', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'amount' => with_currency_symbol($amount),
            'provider_name' => $provider?->company_name ?? '',
        ];

        scenario_push_notification(
            $owner->fcm_token,
            with_currency_symbol($amount) . ' ' . $title,
            $description,
            null,
            'admin_pay',
            $owner->id,
            $data,
            null,
            null,
            'provider-admin',
            $provider?->zone_id
        );
    }
}

if (! function_exists('send_booking_reminder_notification')) {
    function send_booking_reminder_notification(Booking $booking): void
    {
        $config = business_config('booking', 'notification_settings');
        if (! ($config->live_values['push_notification_booking'] ?? false)) {
            return;
        }

        if (! isNotificationActive(null, 'booking', 'notification', 'user')) {
            return;
        }

        $user = $booking->customer;
        $title = get_push_notification_message('booking_reminder', 'customer_notification', $user?->current_language_key);
        $description = get_push_notification_description('booking_reminder', 'customer_notification', $user?->current_language_key);
        if (! $user || ! $user->is_active || ! $title) {
            return;
        }

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
            'schedule_time' => $booking->service_schedule
                ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
                : '',
        ];

        scenario_push_notification(
            $user->fcm_token,
            $title,
            $description,
            $booking->id,
            'booking',
            $user->id,
            $data,
            $repeatOrRegular,
            null,
            'customer',
            $booking->zone_id
        );
    }
}

if (! function_exists('chat_message_notification_settings_type')) {
    function chat_message_notification_settings_type(\Modules\UserManagement\Entities\User $user): ?string
    {
        return match ($user->user_type) {
            'customer' => 'customer_notification',
            'provider-admin', 'provider-employee' => 'provider_notification',
            default => null,
        };
    }
}

if (! function_exists('send_chat_message_push_notification')) {
    function send_chat_message_push_notification(
        \Modules\UserManagement\Entities\User $toUser,
        string $channelId,
        ?string $senderName,
        ?string $senderImage,
        ?string $senderPhone,
        ?string $senderType
    ): void {
        $settingsType = chat_message_notification_settings_type($toUser);
        if (! $settingsType) {
            return;
        }

        $audience = $settingsType === 'customer_notification' ? 'user' : 'provider';
        $providerId = $toUser->provider?->id ?? null;
        if (! isNotificationActive($providerId, 'chatting', 'notification', $audience)) {
            return;
        }

        if (! $toUser->fcm_token) {
            return;
        }

        $title = get_push_notification_message('chat_message', $settingsType, $toUser->current_language_key);
        $description = get_push_notification_description('chat_message', $settingsType, $toUser->current_language_key);
        if (! $title) {
            $title = translate('New message has been arrived');
        }

        device_notification_for_chatting(
            $toUser->fcm_token,
            $title,
            $description,
            null,
            $channelId,
            $senderName,
            $senderImage,
            $senderPhone,
            $senderType,
            'chatting'
        );
    }
}

if (! function_exists('booking_push_notifications_enabled')) {
    function booking_push_notifications_enabled(): bool
    {
        $config = business_config('booking', 'notification_settings');

        return (bool) ($config->live_values['push_notification_booking'] ?? false);
    }
}

if (! function_exists('review_push_notifications_enabled')) {
    function review_push_notifications_enabled(): bool
    {
        $config = business_config('rating_review', 'notification_settings');

        return (bool) ($config->live_values['push_notification_rating_review'] ?? false);
    }
}

if (! function_exists('send_customer_loyalty_point_notification')) {
    function send_customer_loyalty_point_notification(
        \Modules\UserManagement\Entities\User $user,
        float $points,
        string $messageKey = 'loyalty_point',
        ?string $bookingId = null,
    ): void {
        if ($points <= 0 || ! $user->is_active) {
            return;
        }

        if (! isNotificationActive(null, 'loyality_point', 'notification', 'user')) {
            return;
        }

        $title = get_push_notification_message($messageKey, 'customer_notification', $user->current_language_key);
        $description = get_push_notification_description($messageKey, 'customer_notification', $user->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'amount' => with_decimal_point($points),
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'booking_id' => $bookingId ?? '',
        ];

        scenario_push_notification(
            $user->fcm_token,
            with_decimal_point($points) . ' ' . $title,
            $description,
            $bookingId,
            'loyalty_point',
            $user->id,
            $data,
            null,
            null,
            'customer',
            config('zone_id')
        );
    }
}

if (! function_exists('send_customer_refund_notification')) {
    function send_customer_refund_notification(Booking $booking, float $amount, string $messageKey = 'refund'): void
    {
        if ($amount <= 0 || ! booking_push_notifications_enabled()) {
            return;
        }

        $booking->loadMissing('customer');
        $user = $booking->customer;
        if (! $user || ! $user->is_active || ! isNotificationActive(null, 'booking', 'notification', 'user')) {
            return;
        }

        $title = get_push_notification_message($messageKey, 'customer_notification', $user->current_language_key);
        $description = get_push_notification_description($messageKey, 'customer_notification', $user->current_language_key);
        if (! $title) {
            return;
        }

        scenario_push_notification(
            $user->fcm_token,
            with_currency_symbol($amount) . ' ' . $title,
            $description,
            $booking->id,
            'booking',
            $user->id,
            [
                'amount' => with_currency_symbol($amount),
                'booking_id' => $booking->readable_id ?? $booking->id,
            ],
            null,
            null,
            'customer',
            $booking->zone_id
        );
    }
}

if (! function_exists('send_review_approved_to_provider_notification')) {
    function send_review_approved_to_provider_notification(\Modules\ReviewModule\Entities\Review $review): void
    {
        if (! review_push_notifications_enabled()) {
            return;
        }

        $review->loadMissing(['provider.owner', 'customer', 'booking']);
        $provider = $review->provider;
        $owner = $provider?->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'rating_review', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message('review_approved', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('review_approved', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'user_name' => trim(($review->customer?->first_name ?? '') . ' ' . ($review->customer?->last_name ?? '')),
            'provider_name' => $provider?->company_name ?? '',
            'booking_id' => $review->booking?->readable_id ?? $review->booking_id ?? '',
        ];

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $review->booking_id,
            'review',
            $owner->id,
            $data,
            null,
            null,
            'provider-admin',
            $provider?->zone_id
        );
    }
}

if (! function_exists('send_review_approved_to_customer_notification')) {
    function send_review_approved_to_customer_notification(\Modules\ReviewModule\Entities\ProviderCustomerReview $review): void
    {
        if (! review_push_notifications_enabled()) {
            return;
        }

        $review->loadMissing(['customer', 'provider', 'booking']);
        $customer = $review->customer;
        if (! $customer || ! $customer->is_active) {
            return;
        }

        if (! isNotificationActive(null, 'rating_review', 'notification', 'user')) {
            return;
        }

        $title = get_push_notification_message('customer_review_approved', 'customer_notification', $customer->current_language_key);
        $description = get_push_notification_description('customer_review_approved', 'customer_notification', $customer->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'user_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'provider_name' => $review->provider?->company_name ?? '',
            'booking_id' => $review->booking?->readable_id ?? $review->booking_id ?? '',
        ];

        scenario_push_notification(
            $customer->fcm_token,
            $title,
            $description,
            $review->booking_id,
            'review',
            $customer->id,
            $data,
            null,
            null,
            'customer',
            $review->booking?->zone_id ?? config('zone_id')
        );
    }
}

if (! function_exists('send_provider_withdraw_request_submitted_notification')) {
    function send_provider_withdraw_request_submitted_notification(\Modules\ProviderManagement\Entities\WithdrawRequest $withdrawRequest): void
    {
        $withdrawRequest->loadMissing('user.provider');
        $provider = $withdrawRequest->user?->provider;
        $owner = $provider?->owner ?? $withdrawRequest->user;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'wallet', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message('withdraw_request_submitted', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('withdraw_request_submitted', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            null,
            'withdraw',
            $owner->id,
            [
                'amount' => with_currency_symbol($withdrawRequest->amount),
                'provider_name' => $provider?->company_name ?? '',
            ],
            null,
            null,
            'provider-admin',
            $provider?->zone_id
        );
    }
}

if (! function_exists('send_provider_removed_from_booking_notification')) {
    function send_provider_removed_from_booking_notification(Booking $booking, string $removedProviderId): void
    {
        if (! booking_push_notifications_enabled()) {
            return;
        }

        $provider = \Modules\ProviderManagement\Entities\Provider::with('owner')->find($removedProviderId);
        $owner = $provider?->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'booking', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message('provider_removed_from_booking', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('provider_removed_from_booking', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            $title = translate('Provider_withdrew_from_booking_title');
        }
        if (! $description) {
            $description = translate('Provider_withdrew_from_booking_provider_message');
        }

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $booking->id,
            'booking',
            $owner->id,
            [
                'booking_id' => $booking->readable_id ?? $booking->id,
                'provider_name' => $provider?->company_name ?? '',
            ],
            $repeatOrRegular,
            'removed',
            'provider-admin',
            $booking->zone_id
        );
    }
}

if (! function_exists('send_booking_provider_reassignment_notifications')) {
    function send_booking_provider_reassignment_notifications(
        Booking $booking,
        ?string $oldProviderId,
        ?string $newProviderId,
    ): void {
        if (! booking_push_notifications_enabled()) {
            return;
        }

        $booking->loadMissing(['customer', 'provider.owner', 'zone']);
        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_id' => $booking->readable_id ?? $booking->id,
            'user_name' => trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? '')),
            'zone_name' => $booking->zone?->name ?? '',
            'provider_name' => $booking->provider?->company_name ?? $booking->provider?->contact_person_name ?? '',
            'schedule_time' => $booking->service_schedule
                ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
                : '',
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
        ];

        if ($oldProviderId && (string) $oldProviderId !== (string) $newProviderId) {
            send_provider_removed_from_booking_notification($booking, (string) $oldProviderId);
        }

        if ($newProviderId && isNotificationActive(null, 'booking', 'notification', 'user')) {
            $user = $booking->customer;
            $title = get_push_notification_message('provider_assign', 'customer_notification', $user?->current_language_key);
            $description = get_push_notification_description('provider_assign', 'customer_notification', $user?->current_language_key);
            if ($user && $user->is_active && $title) {
                scenario_push_notification(
                    $user->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $user->id,
                    $data,
                    $repeatOrRegular,
                    null,
                    'customer',
                    $booking->zone_id
                );
            }
        }

        if ($newProviderId && isNotificationActive(null, 'booking', 'notification', 'provider')) {
            $providerOwner = $booking->provider?->owner;
            $title = get_push_notification_message('booking_assigned_to_provider', 'provider_notification', $providerOwner?->current_language_key);
            $description = get_push_notification_description('booking_assigned_to_provider', 'provider_notification', $providerOwner?->current_language_key);
            if ($providerOwner && $title && sendDeviceNotificationPermission($newProviderId)) {
                scenario_push_notification(
                    $providerOwner->fcm_token,
                    $title,
                    $description,
                    $booking->id,
                    'booking',
                    $providerOwner->id,
                    $data,
                    $repeatOrRegular,
                    null,
                    'provider-admin',
                    $booking->zone_id
                );
            }
        }
    }
}
