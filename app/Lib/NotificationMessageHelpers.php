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
            'provider_assign', 'booking_status_change', 'booking_reminder', 'booking_ignored_by_provider', 'service_location_updated' => array_merge($common, $bookingExtras),
            'chat_message' => array_merge($common, ['{{senderName}}']),
            'otp' => array_merge($common, ['{{otp}}']),
            'booking_edit_service_add', 'booking_edit_service_update' => array_merge($common, ['{{serviceName}}']),
            'payment_collected_company', 'payment_collected_provider', 'refund', 'payment_failed' => array_merge($common, ['{{amount}}', '{{bookingStatus}}']),
            'add_fund_wallet', 'referral_earning', 'referral_code_used', 'wallet_deducted' => ['{{amount}}', '{{userName}}', '{{bookingId}}'],
            'loyalty_point', 'loyalty_point_convert' => ['{{amount}}', '{{userName}}', '{{bookingId}}'],
            'refund_bank_transfer' => array_merge($common, ['{{amount}}', '{{bookingStatus}}']),
            'customer_review_approved', 'review_published' => ['{{userName}}', '{{providerName}}', '{{bookingId}}'],
            'review_approved', 'provider_review_published' => ['{{userName}}', '{{providerName}}', '{{bookingId}}'],
            'withdraw_request_submitted' => ['{{amount}}', '{{providerName}}'],
            'provider_removed_from_booking' => array_merge($common, $bookingExtras),
            'new_service_request_arrived', 'admin_booking_assigned', 'booking_assigned_to_provider' => array_merge($common, $bookingExtras),
            'service_request_approve', 'service_request_deny' => ['{{serviceName}}', '{{providerName}}'],
            'widthdraw_request_approve', 'widthdraw_request_deny', 'admin_payable', 'settlement_received', 'provider_suspend', 'provider_suspension_remove' => ['{{amount}}', '{{providerName}}'],
            'advertisement_created_by_admin', 'advertisement_approved', 'advertisement_denied', 'advertisement_paused', 'advertisement_resumed' => ['{{providerName}}'],
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

if (! function_exists('notification_default_message_templates')) {
    /**
     * Canonical push notification title + description for each message key.
     *
     * @return array<string, array<string, array{title: string, description: string}>>
     */
    function notification_default_message_templates(): array
    {
        return [
            'customer_notification' => [
                'booking_place' => [
                    'title' => 'Booking Placed – {{bookingId}}',
                    'description' => 'Hi {{userName}}, your booking {{bookingId}} is submitted in {{zoneName}}. Scheduled for {{scheduleTime}}.',
                ],
                'admin_booking_created' => [
                    'title' => 'Booking Created – {{bookingId}}',
                    'description' => 'Hi {{userName}}, an admin created booking {{bookingId}} for you with {{providerName}} on {{scheduleTime}}.',
                ],
                'booking_accepted' => [
                    'title' => 'Booking Confirmed – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{providerName}} accepted booking {{bookingId}}. Scheduled for {{scheduleTime}}.',
                ],
                'booking_complete' => [
                    'title' => 'Service Completed – {{bookingId}}',
                    'description' => 'Hi {{userName}}, booking {{bookingId}} with {{providerName}} is completed. Thank you for choosing us.',
                ],
                'booking_schedule_time_change' => [
                    'title' => 'Schedule Updated – {{bookingId}}',
                    'description' => 'Hi {{userName}}, booking {{bookingId}} with {{providerName}} is rescheduled to {{scheduleTime}}.',
                ],
                'otp' => [
                    'title' => 'Verification OTP – {{bookingId}}',
                    'description' => 'Hi {{userName}}, your OTP for booking {{bookingId}} is {{otp}}. Share it with the provider when service starts.',
                ],
                'provider_assign' => [
                    'title' => 'Provider Assigned – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{providerName}} is assigned to booking {{bookingId}} on {{scheduleTime}}.',
                ],
                'booking_status_change' => [
                    'title' => 'Booking {{bookingStatus}} – {{bookingId}}',
                    'description' => 'Hi {{userName}}, booking {{bookingId}} status is now {{bookingStatus}}. Provider: {{providerName}}.',
                ],
                'booking_reminder' => [
                    'title' => 'Upcoming Booking – {{bookingId}}',
                    'description' => 'Hi {{userName}}, reminder: booking {{bookingId}} with {{providerName}} is scheduled for {{scheduleTime}}.',
                ],
                'booking_ignored_by_provider' => [
                    'title' => 'Provider Unavailable – {{bookingId}}',
                    'description' => 'Hi {{userName}}, the provider could not take booking {{bookingId}}. We are finding another provider for you.',
                ],
                'service_location_updated' => [
                    'title' => 'Service Location Updated – {{bookingId}}',
                    'description' => 'Hi {{userName}}, the service location for booking {{bookingId}} with {{providerName}} has been updated.',
                ],
                'chat_message' => [
                    'title' => 'New Message from {{senderName}}',
                    'description' => 'Hi {{userName}}, you have a new chat message about booking {{bookingId}}.',
                ],
                'booking_edit_service_add' => [
                    'title' => 'Service Added – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{serviceName}} was added to booking {{bookingId}} with {{providerName}}.',
                ],
                'booking_edit_service_update' => [
                    'title' => 'Service Updated – {{bookingId}}',
                    'description' => 'Hi {{userName}}, services on booking {{bookingId}} with {{providerName}} were updated.',
                ],
                'payment_collected_company' => [
                    'title' => 'Payment Received – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{amount}} was received for booking {{bookingId}}. Status: {{bookingStatus}}.',
                ],
                'payment_collected_provider' => [
                    'title' => 'Payment Recorded – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{providerName}} recorded {{amount}} for booking {{bookingId}}.',
                ],
                'payment_failed' => [
                    'title' => 'Payment Failed – {{bookingId}}',
                    'description' => 'Hi {{userName}}, payment of {{amount}} for booking {{bookingId}} could not be completed. Please try again.',
                ],
                'refund' => [
                    'title' => 'Refund Processed – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{amount}} has been refunded to your wallet for booking {{bookingId}}.',
                ],
                'refund_bank_transfer' => [
                    'title' => 'Bank Refund Initiated – {{bookingId}}',
                    'description' => 'Hi {{userName}}, {{amount}} refund for booking {{bookingId}} will be sent to your bank account.',
                ],
                'add_fund_wallet' => [
                    'title' => 'Wallet Credited',
                    'description' => 'Hi {{userName}}, {{amount}} has been added to your wallet.',
                ],
                'wallet_deducted' => [
                    'title' => 'Wallet Debited',
                    'description' => 'Hi {{userName}}, {{amount}} was deducted from your wallet for booking {{bookingId}}.',
                ],
                'referral_earning' => [
                    'title' => 'Referral Reward Earned',
                    'description' => 'Hi {{userName}}, you earned {{amount}} referral reward. Booking: {{bookingId}}.',
                ],
                'referral_code_used' => [
                    'title' => 'Your Referral Code Was Used',
                    'description' => 'Hi {{userName}}, someone signed up using your referral code. You will earn rewards when they complete their first booking.',
                ],
                'loyalty_point' => [
                    'title' => 'Loyalty Points Earned',
                    'description' => 'Hi {{userName}}, you earned {{amount}} loyalty points. Booking: {{bookingId}}.',
                ],
                'loyalty_point_convert' => [
                    'title' => 'Points Converted to Wallet',
                    'description' => 'Hi {{userName}}, {{amount}} loyalty points were converted to your wallet balance.',
                ],
                'customer_review_approved' => [
                    'title' => 'You Have a New Review',
                    'description' => 'Hi {{userName}}, you have got a new review from {{providerName}} on booking {{bookingId}}.',
                ],
                'review_published' => [
                    'title' => 'Your Review Is Approved',
                    'description' => 'Hi {{userName}}, your review for {{providerName}} on booking {{bookingId}} is approved and published.',
                ],
            ],
            'provider_notification' => [
                'new_service_request_arrived' => [
                    'title' => 'New Booking Request – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, you received booking {{bookingId}} in {{zoneName}} from {{userName}}. Scheduled: {{scheduleTime}}.',
                ],
                'admin_booking_assigned' => [
                    'title' => 'Admin Assigned Booking – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, admin assigned booking {{bookingId}} to you for {{userName}} on {{scheduleTime}}.',
                ],
                'booking_assigned_to_provider' => [
                    'title' => 'Booking Assigned to You – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, booking {{bookingId}} is assigned to you. Customer: {{userName}}. Scheduled: {{scheduleTime}}.',
                ],
                'booking_accepted' => [
                    'title' => 'Booking Accepted – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, you accepted booking {{bookingId}} for {{userName}} on {{scheduleTime}}.',
                ],
                'booking_complete' => [
                    'title' => 'Booking Completed – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, booking {{bookingId}} with {{userName}} is marked completed.',
                ],
                'booking_schedule_time_change' => [
                    'title' => 'Booking Rescheduled – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, booking {{bookingId}} is rescheduled to {{scheduleTime}}.',
                ],
                'booking_status_change' => [
                    'title' => 'Booking {{bookingStatus}} – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, booking {{bookingId}} status is now {{bookingStatus}}. Customer: {{userName}}.',
                ],
                'chat_message' => [
                    'title' => 'New Chat Message',
                    'description' => 'Hi {{providerName}}, you have a new chat message about booking {{bookingId}} from {{userName}}.',
                ],
                'booking_edit_service_add' => [
                    'title' => 'Service Added – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, {{serviceName}} was added to booking {{bookingId}}.',
                ],
                'booking_edit_service_update' => [
                    'title' => 'Service Updated – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, services on booking {{bookingId}} were updated.',
                ],
                'service_request_approve' => [
                    'title' => 'Service Request Approved',
                    'description' => 'Hi {{providerName}}, your service request for {{serviceName}} has been approved.',
                ],
                'service_request_deny' => [
                    'title' => 'Service Request Denied',
                    'description' => 'Hi {{providerName}}, your service request for {{serviceName}} was not approved.',
                ],
                'payment_collected_company' => [
                    'title' => 'Payment Received – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, {{amount}} was received for booking {{bookingId}}.',
                ],
                'payment_collected_provider' => [
                    'title' => 'Payment Recorded – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, {{amount}} was recorded for booking {{bookingId}}.',
                ],
                'widthdraw_request_approve' => [
                    'title' => 'Withdrawal Approved',
                    'description' => 'Hi {{providerName}}, your withdrawal request of {{amount}} has been approved.',
                ],
                'widthdraw_request_deny' => [
                    'title' => 'Withdrawal Denied',
                    'description' => 'Hi {{providerName}}, your withdrawal request of {{amount}} was denied.',
                ],
                'settlement_received' => [
                    'title' => 'Settlement Received',
                    'description' => 'Hi {{providerName}}, {{amount}} settlement has been credited to your account.',
                ],
                'admin_payable' => [
                    'title' => 'Amount Payable to Admin',
                    'description' => 'Hi {{providerName}}, {{amount}} is recorded as payable to admin.',
                ],
                'withdraw_request_submitted' => [
                    'title' => 'Withdrawal Request Submitted',
                    'description' => 'Hi {{providerName}}, your withdrawal request of {{amount}} has been submitted.',
                ],
                'provider_removed_from_booking' => [
                    'title' => 'Removed From Booking – {{bookingId}}',
                    'description' => 'Hi {{providerName}}, you have been removed from booking {{bookingId}}. Admin may assign another provider.',
                ],
                'review_approved' => [
                    'title' => 'You Have a New Review',
                    'description' => 'Hi {{providerName}}, you have got a new review from {{userName}} on booking {{bookingId}}.',
                ],
                'provider_review_published' => [
                    'title' => 'Your Review Is Approved',
                    'description' => 'Hi {{providerName}}, your review for {{userName}} on booking {{bookingId}} is approved and published.',
                ],
                'provider_suspend' => [
                    'title' => 'Account Suspended',
                    'description' => 'Hi {{providerName}}, your provider account has been suspended. Contact admin for assistance.',
                ],
                'provider_suspension_remove' => [
                    'title' => 'Suspension Lifted',
                    'description' => 'Hi {{providerName}}, your provider account suspension has been removed. You can resume operations.',
                ],
                'advertisement_created_by_admin' => [
                    'title' => 'New Advertisement Created',
                    'description' => 'Hi {{providerName}}, admin created a new advertisement for your profile.',
                ],
                'advertisement_approved' => [
                    'title' => 'Advertisement Approved',
                    'description' => 'Hi {{providerName}}, your advertisement has been approved and is now active.',
                ],
                'advertisement_denied' => [
                    'title' => 'Advertisement Denied',
                    'description' => 'Hi {{providerName}}, your advertisement request was not approved.',
                ],
                'advertisement_paused' => [
                    'title' => 'Advertisement Paused',
                    'description' => 'Hi {{providerName}}, your advertisement has been paused.',
                ],
                'advertisement_resumed' => [
                    'title' => 'Advertisement Resumed',
                    'description' => 'Hi {{providerName}}, your advertisement is active again.',
                ],
            ],
        ];
    }
}

if (! function_exists('get_notification_default_message')) {
    /**
     * @return array{title: string, description: string}|null
     */
    function get_notification_default_message(string $key, string $settingsType): ?array
    {
        return notification_default_message_templates()[$settingsType][$key] ?? null;
    }
}

if (! function_exists('sync_notification_default_messages')) {
    /**
     * Update business_settings notification message rows to canonical templates.
     *
     * @return array{updated: int, skipped: int}
     */
    function sync_notification_default_messages(bool $force = true): array
    {
        $updated = 0;
        $skipped = 0;
        $templates = notification_default_message_templates();

        $pairs = [];
        foreach (NOTIFICATION_FOR_USER as $notification) {
            $pairs[] = ['key' => $notification['key'], 'settings_type' => 'customer_notification'];
        }
        foreach (NOTIFICATION_FOR_PROVIDER as $notification) {
            $pairs[] = ['key' => $notification['key'], 'settings_type' => 'provider_notification'];
        }

        foreach ($pairs as $pair) {
            $keyName = $pair['key'];
            $settingsType = $pair['settings_type'];
            $template = $templates[$settingsType][$keyName] ?? null;

            if (! $template) {
                $skipped++;

                continue;
            }

            $record = \Modules\BusinessSettingsModule\Entities\BusinessSettings::query()
                ->where(['key_name' => $keyName, 'settings_type' => $settingsType])
                ->first();

            if (! $record && ! $force) {
                $skipped++;

                continue;
            }

            $liveValues = [
                $keyName . '_status' => '1',
                $keyName . '_message' => $template['title'],
                $keyName . '_description' => $template['description'],
            ];

            if ($record) {
                $existing = is_array($record->live_values)
                    ? $record->live_values
                    : (array) json_decode((string) $record->live_values, true);
                $liveValues[$keyName . '_status'] = $existing[$keyName . '_status'] ?? '1';
            }

            \Modules\BusinessSettingsModule\Entities\BusinessSettings::updateOrCreate(
                ['key_name' => $keyName, 'settings_type' => $settingsType],
                [
                    'key_name' => $keyName,
                    'live_values' => $liveValues,
                    'test_values' => $liveValues,
                    'settings_type' => $settingsType,
                    'mode' => 'live',
                    'is_active' => 1,
                ]
            );

            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }
}

if (! function_exists('sync_review_notification_default_messages')) {
    /**
     * Refresh review notification templates in business_settings.
     *
     * @return array{updated: int, skipped: int}
     */
    function sync_review_notification_default_messages(): array
    {
        $reviewKeys = [
            ['key' => 'review_published', 'settings_type' => 'customer_notification'],
            ['key' => 'customer_review_approved', 'settings_type' => 'customer_notification'],
            ['key' => 'review_approved', 'settings_type' => 'provider_notification'],
            ['key' => 'provider_review_published', 'settings_type' => 'provider_notification'],
        ];

        $updated = 0;
        $skipped = 0;
        $templates = notification_default_message_templates();

        foreach ($reviewKeys as $pair) {
            $keyName = $pair['key'];
            $settingsType = $pair['settings_type'];
            $template = $templates[$settingsType][$keyName] ?? null;

            if (! $template) {
                $skipped++;

                continue;
            }

            // Always enable all four review keys — each approval sends two distinct alerts
            // (recipient: new review received, author: your review is published).
            $liveValues = [
                $keyName . '_status' => '1',
                $keyName . '_message' => $template['title'],
                $keyName . '_description' => $template['description'],
            ];

            \Modules\BusinessSettingsModule\Entities\BusinessSettings::updateOrCreate(
                ['key_name' => $keyName, 'settings_type' => $settingsType],
                [
                    'key_name' => $keyName,
                    'live_values' => $liveValues,
                    'test_values' => $liveValues,
                    'settings_type' => $settingsType,
                    'mode' => 'live',
                    'is_active' => 1,
                ]
            );

            $updated++;
        }

        ensure_notification_channel_setups();

        return ['updated' => $updated, 'skipped' => $skipped];
    }
}

if (! function_exists('notification_scenario_message_keys')) {
    /**
     * Unique message keys referenced by the scenario registry (push audiences only).
     *
     * @return list<array{key: string, settings_type: string}>
     */
    function notification_scenario_message_keys(): array
    {
        $keys = [];
        $seen = [];

        foreach (notification_scenario_registry() as $scenario) {
            foreach ($scenario['audiences'] as $audience) {
                $key = $audience['key'] ?? null;
                $settingsType = $audience['settings_type'] ?? null;

                if (! $key || ! $settingsType) {
                    continue;
                }

                $signature = $settingsType . ':' . $key;
                if (isset($seen[$signature])) {
                    continue;
                }

                $seen[$signature] = true;
                $keys[] = ['key' => $key, 'settings_type' => $settingsType];
            }
        }

        return $keys;
    }
}

if (! function_exists('validate_notification_scenario_messages')) {
    /**
     * @return list<array{key: string, settings_type: string, issues: list<string>}>
     */
    function validate_notification_scenario_messages(): array
    {
        $issues = [];

        foreach (notification_scenario_message_keys() as $entry) {
            $key = $entry['key'];
            $settingsType = $entry['settings_type'];
            $template = get_notification_default_message($key, $settingsType);
            $entryIssues = [];

            if (! $template) {
                $entryIssues[] = 'Missing default template';
            } else {
                foreach (['title', 'description'] as $field) {
                    if (trim($template[$field]) === '') {
                        $entryIssues[] = "Empty default {$field}";
                    }
                }
            }

            $record = \Modules\BusinessSettingsModule\Entities\BusinessSettings::query()
                ->where(['key_name' => $key, 'settings_type' => $settingsType])
                ->first();

            if (! $record) {
                $entryIssues[] = 'Not seeded in business_settings';
            } else {
                $values = is_array($record->live_values)
                    ? $record->live_values
                    : (array) json_decode((string) $record->live_values, true);

                $title = trim((string) ($values[$key . '_message'] ?? ''));
                $description = trim((string) ($values[$key . '_description'] ?? ''));

                if ($title === '') {
                    $entryIssues[] = 'Empty title in database';
                }
                if ($description === '') {
                    $entryIssues[] = 'Empty description in database';
                }
                if ($title !== '' && ! str_contains($title, '{{') && strlen($title) < 10) {
                    $entryIssues[] = 'Title looks like a legacy label without variables';
                }
            }

            if ($entryIssues !== []) {
                $issues[] = [
                    'key' => $key,
                    'settings_type' => $settingsType,
                    'issues' => $entryIssues,
                ];
            }
        }

        return $issues;
    }
}

if (! function_exists('notification_config_keys_missing_from_scenarios')) {
    /**
     * Returns notification keys from NOTIFICATION_FOR_* that have no scenario registry row.
     *
     * @return list<array{key: string, settings_type: string}>
     */
    function notification_config_keys_missing_from_scenarios(): array
    {
        $covered = [];
        foreach (notification_scenario_message_keys() as $entry) {
            $covered[$entry['settings_type'] . ':' . $entry['key']] = true;
        }

        $missing = [];
        foreach (NOTIFICATION_FOR_USER as $notification) {
            $signature = 'customer_notification:' . $notification['key'];
            if (! isset($covered[$signature])) {
                $missing[] = ['key' => $notification['key'], 'settings_type' => 'customer_notification'];
            }
        }
        foreach (NOTIFICATION_FOR_PROVIDER as $notification) {
            $signature = 'provider_notification:' . $notification['key'];
            if (! isset($covered[$signature])) {
                $missing[] = ['key' => $notification['key'], 'settings_type' => 'provider_notification'];
            }
        }

        return $missing;
    }
}

if (! function_exists('ensure_notification_channel_setups')) {
    /**
     * Ensure notification channel toggles exist for wallet/review scenarios.
     */
    function ensure_notification_channel_setups(): void
    {
        $defaults = [
            [
                'user_type' => 'provider',
                'key' => 'wallet',
                'title' => 'Wallet',
                'sub_title' => 'Choose how the provider will get notified of withdraw requests, settlements, and wallet updates',
                'value' => ['email' => null, 'notification' => 1, 'sms' => null],
            ],
            [
                'user_type' => 'provider',
                'key' => 'rating_review',
                'title' => 'Review',
                'sub_title' => 'Choose how the provider will get notified about customer reviews (new review received or review published)',
                'value' => ['email' => null, 'notification' => 1, 'sms' => null],
            ],
            [
                'user_type' => 'user',
                'key' => 'rating_review',
                'title' => 'Review',
                'sub_title' => 'Choose how the customer will get notified about provider reviews (new review received or review published)',
                'value' => ['email' => null, 'notification' => 1, 'sms' => null],
            ],
            [
                'user_type' => 'provider',
                'key' => 'advertisement',
                'title' => 'Advertisement',
                'sub_title' => 'Choose how the provider will get notified about advertisement updates',
                'value' => ['email' => null, 'notification' => 1, 'sms' => null],
            ],
        ];

        foreach ($defaults as $row) {
            \Modules\BusinessSettingsModule\Entities\NotificationSetup::query()->firstOrCreate(
                ['user_type' => $row['user_type'], 'key' => $row['key']],
                [
                    'title' => $row['title'],
                    'sub_title' => $row['sub_title'],
                    'value' => json_encode($row['value']),
                ]
            );
        }
    }
}

if (! function_exists('notification_scenario_trigger_map')) {
    /**
     * Code-path verification map: each scenario → needles that must exist in app/ or Modules/.
     *
     * @return array<string, array{module: string, checks: list<array{label: string, needles: list<string>}>}>
     */
    function notification_scenario_trigger_map(): array
    {
        return [
            // Booking Creation (3)
            'booking_create_customer_with_provider' => [
                'module' => 'booking_creation',
                'checks' => [
                    ['label' => 'Customer booking placed push', 'needles' => ["get_push_notification_message('booking_place'", 'SendBookingRequestEmail']],
                    ['label' => 'Provider new request push', 'needles' => ['send_booking_new_service_request_to_assigned_provider', "get_push_notification_message('new_service_request_arrived'"]],
                    ['label' => 'Admin new booking inbox', 'needles' => ['CreateAdminBookingNotification']],
                ],
            ],
            'booking_create_customer_auto_provider' => [
                'module' => 'booking_creation',
                'checks' => [
                    ['label' => 'Customer booking placed push', 'needles' => ["get_push_notification_message('booking_place'", 'SendBookingRequestEmail']],
                    ['label' => 'Admin new booking inbox', 'needles' => ['CreateAdminBookingNotification']],
                ],
            ],
            'booking_create_admin' => [
                'module' => 'booking_creation',
                'checks' => [
                    ['label' => 'Admin-created booking notifications', 'needles' => ['send_admin_booking_created_notifications', "get_push_notification_message('admin_booking_created'", "get_push_notification_message('admin_booking_assigned'"]],
                ],
            ],

            // Booking Update (13)
            'booking_admin_assign_provider' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Customer provider assigned', 'needles' => ["'provider_assign'", 'booking_assigned_to_provider']],
                    ['label' => 'Provider assignment on save', 'needles' => ['send_booking_provider_reassignment_notifications', 'booking_assigned_to_provider']],
                ],
            ],
            'booking_provider_accept' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Accepted status notification keys', 'needles' => ['booking_customer_notification_key_for_accepted_status', 'booking_provider_notification_key_for_accepted_status', "'booking_accepted'"]],
                    ['label' => 'Provider accept API', 'needles' => ['requestAccept', "booking_status = 'accepted'"]],
                ],
            ],
            'booking_provider_cancel' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Provider withdrawal push', 'needles' => ['send_provider_removed_from_booking_notification', "'provider_removed_from_booking'"]],
                    ['label' => 'Admin withdrawal inbox', 'needles' => ['CreateAdminProviderWithdrawalNotification', 'ProviderWithdrewFromBooking']],
                ],
            ],
            'booking_provider_ongoing' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Ongoing status push', 'needles' => ["booking_status == 'ongoing'", "'booking_status_change'"]],
                    ['label' => 'Admin ongoing inbox', 'needles' => ['admin_inbox_notify_booking_ongoing']],
                ],
            ],
            'booking_admin_remove_provider' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Provider removed notification', 'needles' => ['send_provider_removed_from_booking_notification']],
                ],
            ],
            'booking_customer_cancel' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Cancel status push', 'needles' => ["booking_status == 'canceled'", "'booking_status_change'"]],
                    ['label' => 'Admin cancel inbox', 'needles' => ['admin_inbox_notify_booking_customer_canceled', 'CustomerBookingCancellationService']],
                ],
            ],
            'booking_admin_edit' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Service add notification', 'needles' => ['send_booking_edit_service_add_notifications', "'booking_edit_service_add'"]],
                ],
            ],
            'booking_edit_service_update' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Service update notification', 'needles' => ["'booking_edit_service_update'", 'BookingTrait.php']],
                ],
            ],
            'booking_complete' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Completed status push', 'needles' => ["booking_status == 'completed'", "'booking_complete'"]],
                ],
            ],
            'booking_otp_sent' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'OTP push to customer', 'needles' => ["get_push_notification_message('otp'", 'notificationSend']],
                ],
            ],
            'booking_reminder' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Reminder command + sender', 'needles' => ['SendBookingReminderNotifications', 'send_booking_reminder_notification', "'booking_reminder'"]],
                ],
            ],
            'booking_admin_cancel' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Admin cancel status push', 'needles' => ["booking_status == 'canceled'", "'booking_status_change'"]],
                ],
            ],
            'booking_schedule_change' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Schedule change push', 'needles' => ["'booking_schedule_time_change'", 'service_schedule']],
                ],
            ],
            'booking_on_hold' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'On hold status push', 'needles' => ["booking_status == 'on_hold'", "'booking_status_change'"]],
                ],
            ],
            'booking_pending_cancellation' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Pending cancellation push', 'needles' => ["booking_status == 'pending_cancellation'", "'booking_status_change'"]],
                ],
            ],
            'booking_refund_request_status' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Refund request status push', 'needles' => ["booking_status == 'refund_request'", "'refund'"]],
                ],
            ],
            'booking_ignored_by_provider' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Booking ignored sender', 'needles' => ['send_booking_ignored_by_provider_notification', "'booking_ignored_by_provider'"]],
                ],
            ],
            'booking_service_location_updated' => [
                'module' => 'booking_update',
                'checks' => [
                    ['label' => 'Service location sender', 'needles' => ['send_booking_service_location_updated_notification', "'service_location_updated'"]],
                ],
            ],

            // Payments (4)
            'payment_provider_records' => [
                'module' => 'payments',
                'checks' => [
                    ['label' => 'Payment collected sender', 'needles' => ['send_booking_payment_collected_notifications', "'payment_collected_provider'"]],
                    ['label' => 'Admin payment inbox', 'needles' => ['admin_inbox_notify_booking_payment']],
                ],
            ],
            'payment_customer_app_company' => [
                'module' => 'payments',
                'checks' => [
                    ['label' => 'Company payment sender', 'needles' => ['send_booking_payment_collected_notifications', "'payment_collected_company'"]],
                ],
            ],
            'payment_admin_records' => [
                'module' => 'payments',
                'checks' => [
                    ['label' => 'Admin payment recording', 'needles' => ['send_booking_payment_collected_notifications']],
                ],
            ],
            'payment_failed' => [
                'module' => 'payments',
                'checks' => [
                    ['label' => 'Payment failed sender', 'needles' => ['send_customer_payment_failed_notification', "'payment_failed'"]],
                ],
            ],

            // Provider Payments (6)
            'provider_withdraw_request' => [
                'module' => 'provider_payments',
                'checks' => [
                    ['label' => 'Withdraw submitted push', 'needles' => ['send_provider_withdraw_request_submitted_notification', "'withdraw_request_submitted'"]],
                    ['label' => 'Admin withdraw inbox', 'needles' => ['admin_inbox_notify_withdraw_request']],
                ],
            ],
            'provider_withdraw_approved' => [
                'module' => 'provider_payments',
                'checks' => [
                    ['label' => 'Withdraw approved push', 'needles' => ["get_push_notification_message('widthdraw_request_approve'"]],
                ],
            ],
            'provider_withdraw_denied' => [
                'module' => 'provider_payments',
                'checks' => [
                    ['label' => 'Withdraw denied push', 'needles' => ["get_push_notification_message('widthdraw_request_deny'"]],
                ],
            ],
            'provider_withdraw_settled' => [
                'module' => 'provider_payments',
                'checks' => [
                    ['label' => 'Withdraw settled push', 'needles' => ['send_provider_withdraw_settled_notification', "'settlement_received'"]],
                ],
            ],
            'admin_collect_from_provider' => [
                'module' => 'provider_payments',
                'checks' => [
                    ['label' => 'Admin payable push', 'needles' => ["get_push_notification_message('admin_payable'", 'PayToAdminHook']],
                ],
            ],
            'admin_pay_provider' => [
                'module' => 'provider_payments',
                'checks' => [
                    ['label' => 'Provider settlement payout', 'needles' => ['send_provider_settlement_received_notification']],
                ],
            ],

            // Review (2)
            'review_customer_to_provider_approved' => [
                'module' => 'review',
                'checks' => [
                    ['label' => 'Provider new review received', 'needles' => ['send_review_approved_to_provider_notification', "'review_approved'"]],
                    ['label' => 'Customer review published', 'needles' => ['send_review_published_to_customer_notification', "'review_published'"]],
                ],
            ],
            'review_provider_to_customer_approved' => [
                'module' => 'review',
                'checks' => [
                    ['label' => 'Customer new review received', 'needles' => ['send_review_approved_to_customer_notification', "'customer_review_approved'"]],
                    ['label' => 'Provider review published', 'needles' => ['send_provider_review_published_notification', "'provider_review_published'"]],
                ],
            ],

            // Loyalty Points (4)
            'loyalty_booking_completed' => [
                'module' => 'loyalty_points',
                'checks' => [
                    ['label' => 'Loyalty after completion', 'needles' => ['send_customer_loyalty_point_notification', "'loyalty_point'"]],
                ],
            ],
            'loyalty_convert_to_wallet' => [
                'module' => 'loyalty_points',
                'checks' => [
                    ['label' => 'Points convert push', 'needles' => ["send_customer_loyalty_point_notification", "'loyalty_point_convert'"]],
                ],
            ],
            'loyalty_admin_adds' => [
                'module' => 'loyalty_points',
                'checks' => [
                    ['label' => 'Admin loyalty credit', 'needles' => ['send_customer_loyalty_point_notification']],
                ],
            ],
            'loyalty_referral_earned' => [
                'module' => 'loyalty_points',
                'checks' => [
                    ['label' => 'Referral earning push', 'needles' => ["get_push_notification_message('referral_earning'", 'BookingTrait.php']],
                ],
            ],
            'referral_code_used' => [
                'module' => 'loyalty_points',
                'checks' => [
                    ['label' => 'Referral code used sender', 'needles' => ['send_referral_code_used_notification', "'referral_code_used'"]],
                ],
            ],

            // Wallet (2)
            'wallet_customer_topup' => [
                'module' => 'wallet',
                'checks' => [
                    ['label' => 'Wallet top-up push', 'needles' => ["get_push_notification_message('add_fund_wallet'", 'AddFundHook.php']],
                ],
            ],
            'wallet_customer_deducted' => [
                'module' => 'wallet',
                'checks' => [
                    ['label' => 'Wallet deduct sender', 'needles' => ['send_customer_wallet_deducted_notification', "'wallet_deducted'"]],
                ],
            ],

            // Refund (2)
            'refund_wallet' => [
                'module' => 'refund',
                'checks' => [
                    ['label' => 'Wallet refund sender', 'needles' => ['send_customer_refund_notification', "'refund'"]],
                ],
            ],
            'refund_bank_transfer' => [
                'module' => 'refund',
                'checks' => [
                    ['label' => 'Bank refund sender', 'needles' => ['send_customer_refund_notification', "'refund_bank_transfer'"]],
                ],
            ],

            // Communication (3)
            'chat_new_message' => [
                'module' => 'communication',
                'checks' => [
                    ['label' => 'Booking chat push sender', 'needles' => ['send_chat_message_push_notification', "'chat_message'"]],
                ],
            ],
            'chat_admin_customer_message' => [
                'module' => 'communication',
                'checks' => [
                    ['label' => 'Admin to customer chat push', 'needles' => ['send_chat_message_push_notification', "'chat_message'"]],
                    ['label' => 'Customer to admin inbox', 'needles' => ['admin_inbox_notify_chat_message']],
                ],
            ],
            'chat_admin_provider_message' => [
                'module' => 'communication',
                'checks' => [
                    ['label' => 'Admin to provider chat push', 'needles' => ['send_chat_message_push_notification', "'chat_message'"]],
                    ['label' => 'Provider to admin inbox', 'needles' => ['admin_inbox_notify_chat_message']],
                ],
            ],

            // Service Requests (2)
            'service_request_approved' => [
                'module' => 'service_requests',
                'checks' => [
                    ['label' => 'Service approved push', 'needles' => ["get_push_notification_message('service_request_approve'", 'ServiceRequestController.php']],
                ],
            ],
            'service_request_denied' => [
                'module' => 'service_requests',
                'checks' => [
                    ['label' => 'Service denied push', 'needles' => ["get_push_notification_message('service_request_deny'", 'ServiceRequestController.php']],
                ],
            ],

            // Provider Account (2)
            'provider_suspended' => [
                'module' => 'provider_account',
                'checks' => [
                    ['label' => 'Provider suspend sender', 'needles' => ['send_provider_suspended_notification', "'provider_suspend'"]],
                ],
            ],
            'provider_suspension_removed' => [
                'module' => 'provider_account',
                'checks' => [
                    ['label' => 'Suspension removed sender', 'needles' => ['send_provider_suspension_removed_notification', "'provider_suspension_remove'"]],
                ],
            ],

            // Advertisement (7)
            'advertisement_created_by_admin' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Ad created push', 'needles' => ['send_advertisement_push_notification', "'advertisement_created_by_admin'"]],
                ],
            ],
            'advertisement_approved' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Ad approved push', 'needles' => ['send_advertisement_push_notification', "'advertisement_approved'"]],
                ],
            ],
            'advertisement_denied' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Ad denied push', 'needles' => ['send_advertisement_push_notification', "'advertisement_denied'"]],
                ],
            ],
            'advertisement_paused' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Ad paused push', 'needles' => ['send_advertisement_push_notification', "'advertisement_paused'"]],
                ],
            ],
            'advertisement_resumed' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Ad resumed push', 'needles' => ['send_advertisement_push_notification', "'advertisement_resumed'"]],
                ],
            ],
            'advertisement_paused_by_provider' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Provider ad paused inbox', 'needles' => ['admin_inbox_notify_advertisement_paused_by_provider']],
                ],
            ],
            'advertisement_resumed_by_provider' => [
                'module' => 'advertisement',
                'checks' => [
                    ['label' => 'Provider ad resumed inbox', 'needles' => ['admin_inbox_notify_advertisement_resumed_by_provider']],
                ],
            ],

            // Admin Alerts (5)
            'admin_alert_provider_registration' => [
                'module' => 'admin_alerts',
                'checks' => [
                    ['label' => 'Provider registration inbox', 'needles' => ['admin_inbox_notify_provider_request']],
                ],
            ],
            'admin_alert_withdraw_request' => [
                'module' => 'admin_alerts',
                'checks' => [
                    ['label' => 'Withdraw request inbox', 'needles' => ['admin_inbox_notify_withdraw_request']],
                ],
            ],
            'admin_alert_booking_payment' => [
                'module' => 'admin_alerts',
                'checks' => [
                    ['label' => 'Booking payment inbox', 'needles' => ['admin_inbox_notify_booking_payment']],
                ],
            ],
            'admin_alert_booking_ongoing' => [
                'module' => 'admin_alerts',
                'checks' => [
                    ['label' => 'Booking ongoing inbox', 'needles' => ['admin_inbox_notify_booking_ongoing']],
                ],
            ],
            'admin_alert_customer_cancel' => [
                'module' => 'admin_alerts',
                'checks' => [
                    ['label' => 'Customer cancel inbox', 'needles' => ['admin_inbox_notify_booking_customer_canceled']],
                ],
            ],
        ];
    }
}

if (! function_exists('audit_notification_scenario_triggers')) {
    /**
     * @return array{passed: int, failed: int, results: list<array{scenario_id: string, module: string, label: string, ok: bool, missing: list<string>}>}
     */
    function audit_notification_scenario_triggers(?string $codebase = null): array
    {
        if ($codebase === null) {
            $root = dirname(__DIR__, 2);
            $dirs = [$root . '/app', $root . '/Modules'];
            $chunks = [];
            foreach ($dirs as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
                $php = new RegexIterator($iterator, '/\.php$/');
                foreach ($php as $file) {
                    $chunks[] = (string) file_get_contents($file->getPathname());
                }
            }
            $codebase = implode("\n", $chunks);
        }

        $map = notification_scenario_trigger_map();
        $registryIds = array_column(notification_scenario_registry(), 'id');
        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($registryIds as $scenarioId) {
            if (! isset($map[$scenarioId])) {
                $results[] = [
                    'scenario_id' => $scenarioId,
                    'module' => 'unknown',
                    'label' => 'Trigger map entry',
                    'ok' => false,
                    'missing' => ['No trigger map defined for scenario'],
                ];
                $failed++;

                continue;
            }

            foreach ($map[$scenarioId]['checks'] as $check) {
                $missing = [];
                foreach ($check['needles'] as $needle) {
                    if (! str_contains($codebase, $needle)) {
                        $missing[] = $needle;
                    }
                }
                $ok = $missing === [];
                $results[] = [
                    'scenario_id' => $scenarioId,
                    'module' => $map[$scenarioId]['module'],
                    'label' => $check['label'],
                    'ok' => $ok,
                    'missing' => $missing,
                ];
                $ok ? $passed++ : $failed++;
            }
        }

        return ['passed' => $passed, 'failed' => $failed, 'results' => $results];
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
                'summary' => 'Sent to the customer when they receive a new review from a provider.',
                'scenarios' => [
                    'Admin approves a provider-submitted review of the customer.',
                ],
                'recipient' => 'Customer (received review)',
                'module' => 'Review',
                'wired' => true,
            ] : null,

            'review_published' => $isCustomer ? [
                'summary' => 'Sent to the customer when their review of a provider is approved.',
                'scenarios' => [
                    'Admin approves a customer-submitted service review.',
                ],
                'recipient' => 'Customer (wrote review)',
                'module' => 'Review',
                'wired' => true,
            ] : null,

            'review_approved' => ! $isCustomer ? [
                'summary' => 'Sent to the provider when they receive a new review from a customer.',
                'scenarios' => [
                    'Admin approves a customer-submitted service review.',
                ],
                'recipient' => 'Provider (received review)',
                'module' => 'Review',
                'wired' => true,
            ] : null,

            'provider_review_published' => ! $isCustomer ? [
                'summary' => 'Sent to the provider when their review of a customer is approved.',
                'scenarios' => [
                    'Admin approves a provider-submitted review of the customer.',
                ],
                'recipient' => 'Provider (wrote review)',
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
                    'Provider sends a message in the booking chat.',
                    'Admin sends a message in the direct support conversation.',
                    'Customer receives a reply in any conversation channel.',
                ] : [
                    'Customer sends a message in the booking chat.',
                    'Admin sends a message in the direct support conversation.',
                    'Provider receives a reply in any conversation channel.',
                ],
                'recipient' => $recipient,
                'module' => 'Communication',
                'wired' => true,
            ],

            'booking_ignored_by_provider' => $isCustomer ? [
                'summary' => 'Sent when the assigned provider ignores the booking request.',
                'scenarios' => [
                    'Provider ignores booking from the provider panel.',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'service_location_updated' => $isCustomer ? [
                'summary' => 'Sent when the service address is updated on a booking.',
                'scenarios' => [
                    'Provider updates service location from the app.',
                    'Admin updates service location on booking details.',
                ],
                'recipient' => 'Customer',
                'module' => 'Bookings',
                'wired' => true,
            ] : null,

            'referral_code_used' => $isCustomer ? [
                'summary' => 'Sent when a new customer registers using the referrer\'s code.',
                'scenarios' => [
                    'New customer signs up with a valid referral code.',
                ],
                'recipient' => 'Customer',
                'module' => 'Wallet and Loyalty',
                'wired' => true,
            ] : null,

            'provider_suspend' => ! $isCustomer ? [
                'summary' => 'Sent when the provider account is suspended.',
                'scenarios' => [
                    'Admin suspends provider from the admin panel.',
                    'Cash-in-hand limit triggers automatic suspension.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'provider_suspension_remove' => ! $isCustomer ? [
                'summary' => 'Sent when provider suspension is lifted.',
                'scenarios' => [
                    'Admin unsuspends provider from the admin panel.',
                    'Provider settles payable balance and suspension clears.',
                ],
                'recipient' => 'Provider',
                'module' => 'Payments',
                'wired' => true,
            ] : null,

            'advertisement_created_by_admin' => ! $isCustomer ? [
                'summary' => 'Sent when admin creates an approved advertisement for the provider.',
                'scenarios' => [
                    'Admin creates advertisement with auto-approved status.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

            'advertisement_approved' => ! $isCustomer ? [
                'summary' => 'Sent when an advertisement request is approved.',
                'scenarios' => [
                    'Admin approves pending provider advertisement.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

            'advertisement_denied' => ! $isCustomer ? [
                'summary' => 'Sent when an advertisement request is denied.',
                'scenarios' => [
                    'Admin denies pending provider advertisement.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

            'advertisement_paused' => ! $isCustomer ? [
                'summary' => 'Sent when an active advertisement is paused.',
                'scenarios' => [
                    'Admin pauses provider advertisement.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

            'advertisement_resumed' => ! $isCustomer ? [
                'summary' => 'Sent when a paused advertisement is resumed.',
                'scenarios' => [
                    'Admin resumes provider advertisement.',
                ],
                'recipient' => 'Provider',
                'module' => 'Service Updates',
                'wired' => true,
            ] : null,

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
                'id' => 'booking_provider_accept',
                'module' => 'booking_update',
                'title' => 'Provider accepts a booking from the app',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Accepts pending booking from the provider app',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_accepted', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_accepted', 'settings_type' => 'provider_notification', 'wired' => true],
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
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when customer cancels'],
                ],
            ],
            [
                'id' => 'booking_admin_edit',
                'module' => 'booking_update',
                'title' => 'Admin or provider adds a service to a booking',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Adds a service, extra, or spare part to the booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_edit_service_add', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_edit_service_add', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_edit_service_update',
                'module' => 'booking_update',
                'title' => 'Admin or provider updates or removes a booking service',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Changes service quantity, updates a line item, or removes a service',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_edit_service_update', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_edit_service_update', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_complete',
                'module' => 'booking_update',
                'title' => 'Booking is marked completed',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider or admin marks the booking as completed',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_complete', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_complete', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_otp_sent',
                'module' => 'booking_update',
                'title' => 'Provider sends booking OTP to customer',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Shares verification OTP from the provider app during visit',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'otp', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_reminder',
                'module' => 'booking_update',
                'title' => 'Upcoming booking reminder',
                'trigger_actor' => 'system',
                'trigger_action' => 'Scheduled reminder before the booking service time',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_reminder', 'settings_type' => 'customer_notification', 'wired' => true],
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
            [
                'id' => 'booking_on_hold',
                'module' => 'booking_update',
                'title' => 'Booking is put on hold after visit',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Marks booking as on hold from ongoing or accepted visit',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_pending_cancellation',
                'module' => 'booking_update',
                'title' => 'Customer requests booking cancellation',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Submits a pending cancellation request on an active booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'booking_status_change', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_refund_request_status',
                'module' => 'booking_update',
                'title' => 'Booking moves to refund request status',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Customer or system sets booking status to refund request',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'refund', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_ignored_by_provider',
                'module' => 'booking_update',
                'title' => 'Provider ignores a booking request',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider ignores assigned booking from the provider panel',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'booking_ignored_by_provider', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'booking_service_location_updated',
                'module' => 'booking_update',
                'title' => 'Service location is updated on a booking',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider or admin updates the customer service address on the booking',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'service_location_updated', 'settings_type' => 'customer_notification', 'wired' => true],
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
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when provider records payment'],
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
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true, 'note' => 'Admin inbox when customer pays company'],
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
            [
                'id' => 'payment_failed',
                'module' => 'payments',
                'title' => 'Customer payment attempt fails',
                'trigger_actor' => 'system',
                'trigger_action' => 'Digital payment or wallet top-up fails at checkout',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'payment_failed', 'settings_type' => 'customer_notification', 'wired' => true],
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
                'id' => 'provider_withdraw_denied',
                'module' => 'provider_payments',
                'title' => 'Admin denies a withdraw request',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Rejects provider withdrawal request',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'widthdraw_request_deny', 'settings_type' => 'provider_notification', 'wired' => true],
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
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'review_approved', 'settings_type' => 'provider_notification', 'wired' => true, 'note' => 'Provider: you have got a new review from customer'],
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'review_published', 'settings_type' => 'customer_notification', 'wired' => true, 'note' => 'Customer: your review is approved'],
                ],
            ],
            [
                'id' => 'review_provider_to_customer_approved',
                'module' => 'review',
                'title' => 'Admin approves provider review of customer',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Approves customer review submitted by provider',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'customer_review_approved', 'settings_type' => 'customer_notification', 'wired' => true, 'note' => 'Customer: you have got a new review from provider'],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'provider_review_published', 'settings_type' => 'provider_notification', 'wired' => true, 'note' => 'Provider: your review is approved'],
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
            [
                'id' => 'referral_code_used',
                'module' => 'loyalty_points',
                'title' => 'Referral code used on new signup',
                'trigger_actor' => 'customer',
                'trigger_action' => 'New customer registers with an existing referral code',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'referral_code_used', 'settings_type' => 'customer_notification', 'wired' => true],
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

            // --- Wallet ---
            [
                'id' => 'wallet_customer_topup',
                'module' => 'wallet',
                'title' => 'Customer wallet is credited',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Tops up wallet via payment gateway or admin adds funds',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'add_fund_wallet', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'wallet_customer_deducted',
                'module' => 'wallet',
                'title' => 'Customer wallet is debited for a booking',
                'trigger_actor' => 'system',
                'trigger_action' => 'Booking checkout or due payment uses wallet balance',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'wallet_deducted', 'settings_type' => 'customer_notification', 'wired' => true],
                ],
            ],

            // --- Communication ---
            [
                'id' => 'chat_new_message',
                'module' => 'communication',
                'title' => 'New chat message on a booking',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Customer or provider sends a chat message in the booking conversation',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'chat_message', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'chat_message', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'chat_admin_customer_message',
                'module' => 'communication',
                'title' => 'New chat message between admin and customer',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Admin or customer sends a message in the direct support conversation',
                'audiences' => [
                    ['audience' => 'customer', 'channel' => 'push', 'key' => 'chat_message', 'settings_type' => 'customer_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],
            [
                'id' => 'chat_admin_provider_message',
                'module' => 'communication',
                'title' => 'New chat message between admin and provider',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Admin or provider sends a message in the direct support conversation',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'chat_message', 'settings_type' => 'provider_notification', 'wired' => true],
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],

            // --- Service Requests ---
            [
                'id' => 'service_request_approved',
                'module' => 'service_requests',
                'title' => 'Admin approves provider service request',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Approves a new service submitted by the provider',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'service_request_approve', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'service_request_denied',
                'module' => 'service_requests',
                'title' => 'Admin denies provider service request',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Rejects a new service submitted by the provider',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'service_request_deny', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],

            // --- Provider Account ---
            [
                'id' => 'provider_suspended',
                'module' => 'provider_account',
                'title' => 'Provider account is suspended',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Admin suspends provider or cash limit auto-suspension triggers',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'provider_suspend', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'provider_suspension_removed',
                'module' => 'provider_account',
                'title' => 'Provider suspension is lifted',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Admin unsuspends provider or provider settles payable balance',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'provider_suspension_remove', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],

            // --- Advertisement ---
            [
                'id' => 'advertisement_created_by_admin',
                'module' => 'advertisement',
                'title' => 'Admin creates advertisement for provider',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Creates and auto-approves provider advertisement',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'advertisement_created_by_admin', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'advertisement_approved',
                'module' => 'advertisement',
                'title' => 'Advertisement is approved',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Approves provider advertisement request',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'advertisement_approved', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'advertisement_denied',
                'module' => 'advertisement',
                'title' => 'Advertisement is denied',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Denies provider advertisement request',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'advertisement_denied', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'advertisement_paused',
                'module' => 'advertisement',
                'title' => 'Admin pauses advertisement',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Admin pauses an active provider advertisement',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'advertisement_paused', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'advertisement_resumed',
                'module' => 'advertisement',
                'title' => 'Admin resumes advertisement',
                'trigger_actor' => 'admin',
                'trigger_action' => 'Admin resumes a paused provider advertisement',
                'audiences' => [
                    ['audience' => 'provider', 'channel' => 'push', 'key' => 'advertisement_resumed', 'settings_type' => 'provider_notification', 'wired' => true],
                ],
            ],
            [
                'id' => 'advertisement_paused_by_provider',
                'module' => 'advertisement',
                'title' => 'Provider pauses their advertisement',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider pauses an active advertisement from app or panel',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],
            [
                'id' => 'advertisement_resumed_by_provider',
                'module' => 'advertisement',
                'title' => 'Provider resumes their advertisement',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider resumes a paused advertisement from app or panel',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],

            // --- Admin Alerts ---
            [
                'id' => 'admin_alert_provider_registration',
                'module' => 'admin_alerts',
                'title' => 'New provider registration request',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider completes registration and awaits admin approval',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],
            [
                'id' => 'admin_alert_withdraw_request',
                'module' => 'admin_alerts',
                'title' => 'Provider withdraw request submitted',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider submits withdraw request for admin review',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],
            [
                'id' => 'admin_alert_booking_payment',
                'module' => 'admin_alerts',
                'title' => 'Booking payment recorded',
                'trigger_actor' => 'system',
                'trigger_action' => 'Customer or provider payment is recorded on a booking',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],
            [
                'id' => 'admin_alert_booking_ongoing',
                'module' => 'admin_alerts',
                'title' => 'Booking marked ongoing',
                'trigger_actor' => 'provider',
                'trigger_action' => 'Provider marks booking as ongoing',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
                ],
            ],
            [
                'id' => 'admin_alert_customer_cancel',
                'module' => 'admin_alerts',
                'title' => 'Customer cancelled booking',
                'trigger_actor' => 'customer',
                'trigger_action' => 'Customer cancels booking from the app',
                'audiences' => [
                    ['audience' => 'admin', 'channel' => 'inbox', 'key' => null, 'settings_type' => null, 'wired' => true],
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

if (! function_exists('normalize_notification_booking_id')) {
    function normalize_notification_booking_id(mixed $bookingId): ?string
    {
        if ($bookingId === null || $bookingId === '') {
            return null;
        }

        $normalized = trim((string) $bookingId);

        return $normalized !== '' ? $normalized : null;
    }
}

if (! function_exists('notification_readable_booking_id')) {
    function notification_readable_booking_id(?string $bookingId): string
    {
        if (! filled($bookingId)) {
            return '';
        }

        $booking = Booking::query()->find($bookingId)
            ?? \Modules\BookingModule\Entities\BookingRepeat::query()->find($bookingId);

        return (string) ($booking?->readable_id ?? $bookingId);
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
    ): ?string {
        try {
            $pushNotification = new \Modules\PromotionManagement\Entities\PushNotification();
            $pushNotification->title = $title;
            $pushNotification->description = $description;
            $pushNotification->to_users = [$audience];
            $pushNotification->zone_ids = $zoneId ? [$zoneId] : [];
            $pushNotification->is_active = 1;
            $pushNotification->notification_type = $notificationType;
            $pushNotification->booking_id = normalize_notification_booking_id($bookingId);
            $pushNotification->booking_type = $bookingType;
            $pushNotification->repeat_type = $repeatType;
            $pushNotification->save();

            $pushNotificationUser = new \Modules\PromotionManagement\Entities\PushNotificationUser();
            $pushNotificationUser->push_notification_id = $pushNotification->id;
            $pushNotificationUser->user_id = $userId;
            $pushNotificationUser->read_at = null;
            $pushNotificationUser->save();

            return (string) $pushNotification->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to persist transactional push notification', [
                'audience' => $audience,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}

if (! function_exists('merge_notification_template_data')) {
    /**
     * @param  array<string, mixed>|object|null  $data
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    function merge_notification_template_data(mixed $data, array $overrides = []): array
    {
        $merged = is_object($data) ? (array) $data : (is_array($data) ? $data : []);

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }
}

if (! function_exists('booking_notification_template_data')) {
    /**
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    function booking_notification_template_data(Booking $booking, array $extras = []): array
    {
        $booking->loadMissing(['customer', 'provider', 'zone', 'serviceman.user']);

        return merge_notification_template_data([
            'booking_id' => $booking->readable_id ?? $booking->id,
            'user_name' => trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? '')),
            'zone_name' => $booking->zone?->name ?? '',
            'provider_name' => $booking->provider?->company_name ?? $booking->provider?->contact_person_name ?? '',
            'schedule_time' => $booking->service_schedule
                ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
                : '',
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
            'service_man_name' => trim(($booking->serviceman?->user?->first_name ?? '') . ' ' . ($booking->serviceman?->user?->last_name ?? '')),
        ], $extras);
    }
}

if (! function_exists('booking_repeat_notification_template_data')) {
    /**
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    function booking_repeat_notification_template_data(\Modules\BookingModule\Entities\BookingRepeat $repeat, array $extras = []): array
    {
        $repeat->loadMissing(['booking.customer', 'booking.zone', 'provider', 'serviceman.user']);

        $parent = $repeat->booking;

        return merge_notification_template_data([
            'booking_id' => (string) ($repeat->readable_id ?? $parent?->readable_id ?? $repeat->id),
            'user_name' => trim(($parent?->customer?->first_name ?? '') . ' ' . ($parent?->customer?->last_name ?? '')),
            'zone_name' => $parent?->zone?->name ?? '',
            'provider_name' => $repeat->provider?->company_name ?? $repeat->provider?->contact_person_name ?? '',
            'schedule_time' => $repeat->service_schedule
                ? \Carbon\Carbon::parse($repeat->service_schedule)->format('Y-m-d H:i')
                : '',
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($repeat->booking_status ?? ''))),
            'service_man_name' => trim(($repeat->serviceman?->user?->first_name ?? '') . ' ' . ($repeat->serviceman?->user?->last_name ?? '')),
        ], $extras);
    }
}

if (! function_exists('scenario_push_notification')) {
    /**
     * Send FCM push and persist to the in-app notification inbox for the recipient.
     */
    function scenario_push_notification(
        ?string $fcmToken,
        string $title,
        ?string $description = '',
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

        $description = (string) ($description ?? '');

        $data = merge_notification_template_data($data, $bookingStatusOverride !== null && $bookingStatusOverride !== ''
            ? ['booking_status' => ucfirst(str_replace('_', ' ', $bookingStatusOverride))]
            : []);

        $formattedTitle = text_variable_data_format($title, $bookingId, $type, $data, $bookingType);
        if (is_array($formattedTitle)) {
            $formattedTitle = $title;
        }
        $formattedBody = format_push_notification_body($description, $bookingId, $type, $data, $bookingType);

        $pushNotificationId = null;
        if ($userId && $inboxAudience) {
            $pushNotificationId = persist_transactional_push_notification(
                (string) $formattedTitle,
                (string) $formattedBody,
                $inboxAudience,
                $userId,
                $zoneId,
                $type,
                normalize_notification_booking_id($bookingId),
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
                $bookingStatusOverride,
                $pushNotificationId
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
            'booking_id' => notification_readable_booking_id($bookingId),
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
            'booking_id' => notification_readable_booking_id($bookingId),
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

        $channel = \Modules\ChattingModule\Entities\ChannelList::query()->find($channelId);
        $bookingUuid = null;
        if ($channel && (string) ($channel->reference_type ?? '') === 'booking_id' && filled($channel->reference_id)) {
            $bookingUuid = (string) $channel->reference_id;
        }

        $bookingReadableId = '';
        if ($bookingUuid) {
            $bookingReadableId = notification_readable_booking_id($bookingUuid);
        }

        $templateData = [
            'sender_name' => trim((string) ($senderName ?? '')),
            'user_name' => trim(($toUser->first_name ?? '') . ' ' . ($toUser->last_name ?? '')),
            'booking_id' => $bookingReadableId,
        ];

        $title = text_variable_data_format($title, $bookingUuid, 'booking', $templateData);
        $description = text_variable_data_format((string) $description, $bookingUuid, 'booking', $templateData);

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

        $bookingReadableId = notification_readable_booking_id($bookingId);

        $data = [
            'amount' => with_decimal_point($points),
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'booking_id' => $bookingReadableId,
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
            'booking_id' => notification_readable_booking_id((string) $review->booking_id),
        ];

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $review->booking_id,
            'review',
            $owner->id,
            $data,
            'review_approved',
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
            'booking_id' => notification_readable_booking_id((string) $review->booking_id),
        ];

        scenario_push_notification(
            $customer->fcm_token,
            $title,
            $description,
            $review->booking_id,
            'review',
            $customer->id,
            $data,
            'customer_review_approved',
            null,
            'customer',
            $review->booking?->zone_id ?? config('zone_id')
        );
    }
}

if (! function_exists('send_review_published_to_customer_notification')) {
    function send_review_published_to_customer_notification(\Modules\ReviewModule\Entities\Review $review): void
    {
        if (! review_push_notifications_enabled()) {
            return;
        }

        $review->loadMissing(['provider', 'customer', 'booking']);
        $customer = $review->customer;
        if (! $customer || ! $customer->is_active) {
            return;
        }

        if (! isNotificationActive(null, 'rating_review', 'notification', 'user')) {
            return;
        }

        $title = get_push_notification_message('review_published', 'customer_notification', $customer->current_language_key);
        $description = get_push_notification_description('review_published', 'customer_notification', $customer->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'user_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'provider_name' => $review->provider?->company_name ?? '',
            'booking_id' => notification_readable_booking_id((string) $review->booking_id),
        ];

        scenario_push_notification(
            $customer->fcm_token,
            $title,
            $description,
            $review->booking_id,
            'review',
            $customer->id,
            $data,
            'review_published',
            null,
            'customer',
            $review->booking?->zone_id ?? config('zone_id')
        );
    }
}

if (! function_exists('send_provider_review_published_notification')) {
    function send_provider_review_published_notification(\Modules\ReviewModule\Entities\ProviderCustomerReview $review): void
    {
        if (! review_push_notifications_enabled()) {
            return;
        }

        $review->loadMissing(['customer', 'provider.owner', 'booking']);
        $provider = $review->provider;
        $owner = $provider?->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'rating_review', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message('provider_review_published', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('provider_review_published', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'user_name' => trim(($review->customer?->first_name ?? '') . ' ' . ($review->customer?->last_name ?? '')),
            'provider_name' => $provider?->company_name ?? '',
            'booking_id' => notification_readable_booking_id((string) $review->booking_id),
        ];

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $review->booking_id,
            'review',
            $owner->id,
            $data,
            'provider_review_published',
            null,
            'provider-admin',
            $provider?->zone_id
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

if (! function_exists('send_provider_withdraw_settled_notification')) {
    function send_provider_withdraw_settled_notification(\Modules\ProviderManagement\Entities\WithdrawRequest $withdrawRequest): void
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

        $title = get_push_notification_message('settlement_received', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('settlement_received', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        $data = [
            'amount' => with_currency_symbol($withdrawRequest->amount),
            'provider_name' => $provider?->company_name ?? '',
        ];
        if (trim((string) ($withdrawRequest->transaction_id ?? '')) !== '') {
            $data['transaction_id'] = (string) $withdrawRequest->transaction_id;
        }

        scenario_push_notification(
            $owner->fcm_token,
            with_currency_symbol($withdrawRequest->amount) . ' ' . $title,
            $description,
            null,
            'withdraw',
            $owner->id,
            $data,
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

if (! function_exists('send_booking_ignored_by_provider_notification')) {
    function send_booking_ignored_by_provider_notification(Booking $booking): void
    {
        if (! booking_push_notifications_enabled()) {
            return;
        }

        $booking->loadMissing(['customer', 'provider', 'zone']);
        $user = $booking->customer;
        if (! $user || ! $user->is_active || ! isNotificationActive(null, 'booking', 'notification', 'user')) {
            return;
        }

        $key = 'booking_ignored_by_provider';
        $title = get_push_notification_message($key, 'customer_notification', $user->current_language_key);
        $description = get_push_notification_description($key, 'customer_notification', $user->current_language_key);
        if (! $title) {
            return;
        }

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_id' => $booking->readable_id ?? $booking->id,
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'provider_name' => $booking->provider?->company_name ?? '',
            'booking_status' => ucfirst(str_replace('_', ' ', (string) ($booking->booking_status ?? ''))),
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

if (! function_exists('send_booking_service_location_updated_notification')) {
    function send_booking_service_location_updated_notification(Booking $booking): void
    {
        if (! booking_push_notifications_enabled()) {
            return;
        }

        $booking->loadMissing(['customer', 'provider', 'zone']);
        $user = $booking->customer;
        if (! $user || ! $user->is_active || ! isNotificationActive(null, 'booking', 'notification', 'user')) {
            return;
        }

        $key = 'service_location_updated';
        $title = get_push_notification_message($key, 'customer_notification', $user->current_language_key);
        $description = get_push_notification_description($key, 'customer_notification', $user->current_language_key);
        if (! $title) {
            return;
        }

        $repeatOrRegular = (int) ($booking->is_repeated ?? 0) ? 'repeat' : 'regular';
        $data = [
            'booking_id' => $booking->readable_id ?? $booking->id,
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'provider_name' => $booking->provider?->company_name ?? '',
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

if (! function_exists('send_provider_suspended_notification')) {
    function send_provider_suspended_notification(
        \Modules\ProviderManagement\Entities\Provider $provider,
        bool $requireTransactionToggle = false
    ): void {
        $provider->loadMissing('owner');
        $owner = $provider->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if ($requireTransactionToggle && ! isNotificationActive($provider->id, 'transaction', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message('provider_suspend', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('provider_suspend', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $provider->id,
            'suspend',
            $owner->id,
            ['provider_name' => $provider->company_name ?? ''],
            null,
            null,
            'provider-admin',
            $provider->zone_id
        );
    }
}

if (! function_exists('send_provider_suspension_removed_notification')) {
    function send_provider_suspension_removed_notification(\Modules\ProviderManagement\Entities\Provider $provider): void
    {
        $provider->loadMissing('owner');
        $owner = $provider->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        $title = get_push_notification_message('provider_suspension_remove', 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description('provider_suspension_remove', 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            $provider->id,
            'suspend',
            $owner->id,
            ['provider_name' => $provider->company_name ?? ''],
            null,
            null,
            'provider-admin',
            $provider->zone_id
        );
    }
}

if (! function_exists('send_referral_code_used_notification')) {
    function send_referral_code_used_notification(\Modules\UserManagement\Entities\User $referrer): void
    {
        if (! $referrer->is_active || ! isNotificationActive(null, 'refer_earn', 'notification', 'user')) {
            return;
        }

        $title = get_push_notification_message('referral_code_used', 'customer_notification', $referrer->current_language_key);
        $description = get_push_notification_description('referral_code_used', 'customer_notification', $referrer->current_language_key);
        if (! $title) {
            return;
        }

        scenario_push_notification(
            $referrer->fcm_token,
            $title,
            $description,
            null,
            'general',
            $referrer->id,
            ['user_name' => trim(($referrer->first_name ?? '') . ' ' . ($referrer->last_name ?? ''))],
            null,
            null,
            'customer',
            config('zone_id')
        );
    }
}

if (! function_exists('send_advertisement_push_notification')) {
    function send_advertisement_push_notification(
        string $messageKey,
        \Modules\PromotionManagement\Entities\Advertisement $advertisement
    ): void {
        $advertisement->loadMissing('provider.owner');
        $provider = $advertisement->provider;
        $owner = $provider?->owner;
        if (! $owner || ! $owner->is_active) {
            return;
        }

        if (! isNotificationActive($provider?->id, 'advertisement', 'notification', 'provider')) {
            return;
        }

        $title = get_push_notification_message($messageKey, 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description($messageKey, 'provider_notification', $owner->current_language_key);
        if (! $title) {
            return;
        }

        scenario_push_notification(
            $owner->fcm_token,
            $title,
            $description,
            null,
            'advertisement',
            $owner->id,
            ['provider_name' => $provider?->company_name ?? ''],
            null,
            null,
            'provider-admin',
            $provider?->zone_id
        );
    }
}

if (! function_exists('resolve_in_app_call_caller_name')) {
    function resolve_in_app_call_caller_name(\Modules\UserManagement\Entities\User $caller): string
    {
        $caller->loadMissing('provider');

        if ($caller->user_type === 'provider-admin' && ! empty($caller->provider?->company_name)) {
            return (string) $caller->provider->company_name;
        }

        $name = trim(($caller->first_name ?? '').' '.($caller->last_name ?? ''));

        return $name !== '' ? $name : translate('Someone_is_calling_you');
    }
}

if (! function_exists('send_in_app_call_push_notification')) {
    function send_in_app_call_push_notification(
        \Modules\UserManagement\Entities\User $toUser,
        \Modules\InAppCallModule\Entities\InAppCall $call,
        \Modules\UserManagement\Entities\User $caller,
    ): void {
        if (! $toUser->fcm_token) {
            return;
        }

        $callerName = resolve_in_app_call_caller_name($caller);
        $title = translate('Incoming_call');
        $description = $callerName;

        device_notification_for_in_app_call(
            $toUser->fcm_token,
            $title,
            $description,
            $call->id,
            $call->channel_id,
            $call->agora_channel_name,
            $callerName,
            $caller->profile_image,
            $caller->phone,
            $caller->user_type,
            'incoming_call'
        );
    }
}

if (! function_exists('send_in_app_call_status_push_notification')) {
    function send_in_app_call_status_push_notification(
        \Modules\UserManagement\Entities\User $toUser,
        \Modules\InAppCallModule\Entities\InAppCall $call,
        string $eventType,
    ): void {
        if (! $toUser->fcm_token) {
            return;
        }

        $title = match ($eventType) {
            'call_accepted' => translate('Call_accepted'),
            'call_declined' => translate('Call_declined'),
            'call_ended' => translate('Call_ended'),
            'call_cancelled' => translate('Call_cancelled'),
            'call_missed' => translate('Missed_call'),
            default => translate('Call_update'),
        };

        device_notification_for_in_app_call(
            $toUser->fcm_token,
            $title,
            '',
            $call->id,
            $call->channel_id,
            $call->agora_channel_name,
            '',
            null,
            null,
            null,
            $eventType
        );
    }
}
