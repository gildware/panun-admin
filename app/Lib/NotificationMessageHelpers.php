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
            'loyalty_point' => ['{{amount}}', '{{userName}}', '{{bookingId}}'],
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
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
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
            if ($user?->fcm_token && $user->is_active && $title) {
                device_notification($user->fcm_token, $title, $description, null, $booking->id, 'booking', null, $user->id, $data, null, $repeatOrRegular);
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'provider') && $booking->provider_id) {
            $key = 'admin_booking_assigned';
            $provider = $booking->provider?->owner;
            $title = get_push_notification_message($key, 'provider_notification', $provider?->current_language_key);
            $description = get_push_notification_description($key, 'provider_notification', $provider?->current_language_key);
            if ($provider?->fcm_token && $title && sendDeviceNotificationPermission($booking->provider_id)) {
                device_notification($provider->fcm_token, $title, $description, null, $booking->id, 'booking', null, null, $data, null, $repeatOrRegular);
            }
        }
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
            if ($user?->fcm_token && $title) {
                device_notification($user->fcm_token, $title, $description, null, $booking->id, 'booking', null, $user->id, $data, null, $repeatOrRegular);
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'provider')) {
            $provider = $booking->provider?->owner;
            $title = get_push_notification_message($key, 'provider_notification', $provider?->current_language_key);
            $description = get_push_notification_description($key, 'provider_notification', $provider?->current_language_key);
            if ($provider?->fcm_token && $title) {
                device_notification($provider->fcm_token, $title, $description, null, $booking->id, 'booking', null, null, $data, null, $repeatOrRegular);
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
            if ($user?->fcm_token && $user?->is_active && $title) {
                device_notification(
                    $user->fcm_token,
                    $title,
                    $description,
                    null,
                    $booking->id,
                    'booking',
                    null,
                    $user->id,
                    $data,
                    null,
                    $repeatOrRegular
                );
            }
        }

        if (isNotificationActive(null, 'booking', 'notification', 'provider') && $booking->provider_id) {
            $provider = $booking->provider?->owner;
            $title = get_push_notification_message($key, 'provider_notification', $provider?->current_language_key);
            $description = get_push_notification_description($key, 'provider_notification', $provider?->current_language_key);
            if ($provider?->fcm_token && $title && sendDeviceNotificationPermission($booking->provider_id)) {
                device_notification(
                    $provider->fcm_token,
                    $title,
                    $description,
                    null,
                    $booking->id,
                    'booking',
                    null,
                    null,
                    $data,
                    null,
                    $repeatOrRegular
                );
            }
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
        if (! $user?->fcm_token || ! $user->is_active) {
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

        device_notification(
            $user->fcm_token,
            $title,
            $description,
            null,
            $bookingId,
            NOTIFICATION_TYPE['wallet'] ?? 'wallet',
            null,
            $user->id,
            $data
        );
    }
}

if (! function_exists('send_customer_wallet_deducted_notification')) {
    function send_customer_wallet_deducted_notification(
        \Modules\UserManagement\Entities\User $user,
        float $amount,
        ?string $bookingId = null
    ): void {
        if (! $user->fcm_token || ! $user->is_active || $amount <= 0) {
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

        device_notification(
            $user->fcm_token,
            with_currency_symbol($amount) . ' ' . $title,
            $description,
            null,
            $bookingId,
            NOTIFICATION_TYPE['wallet'] ?? 'wallet',
            null,
            $user->id,
            $data
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
        if (! $owner?->fcm_token || ! $owner->is_active) {
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

        device_notification(
            $owner->fcm_token,
            with_currency_symbol($amount) . ' ' . $title,
            $description,
            null,
            null,
            'admin_pay',
            null,
            $owner->id,
            $data
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
        if (! $user?->fcm_token || ! $user->is_active || ! $title) {
            return;
        }

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
            'schedule_time' => $booking->service_schedule
                ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
                : '',
        ];

        device_notification(
            $user->fcm_token,
            $title,
            $description,
            null,
            $booking->id,
            'booking',
            null,
            $user->id,
            $data,
            null,
            $repeatOrRegular
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
