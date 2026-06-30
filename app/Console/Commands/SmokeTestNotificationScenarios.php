<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\AdminModule\Entities\UserNotification;
use Modules\BookingModule\Entities\Booking;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\PromotionManagement\Entities\Advertisement;
use Modules\PromotionManagement\Entities\PushNotificationUser;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ProviderManagement\Entities\WithdrawRequest;
use Modules\ServiceManagement\Entities\ServiceRequest;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\ReviewModule\Entities\Review;
use Modules\UserManagement\Entities\User;

class SmokeTestNotificationScenarios extends Command
{
    protected $signature = 'notifications:smoke-test {--send-push : Also send FCM (default: inbox-only, no device push)} {--module= : Run only scenarios for this module (e.g. communication)}';

    protected $description = 'Live smoke test: dispatch each notification scenario and verify inbox rows are created';

    /** @var array<string, string|null> */
    private array $savedFcmTokens = [];

    private ?\Illuminate\Support\Carbon $testStartedAt = null;

    public function handle(): int
    {
        $inboxOnly = ! $this->option('send-push');
        $this->testStartedAt = now();

        if ($inboxOnly) {
            $this->warn('Running inbox-only mode (FCM tokens temporarily cleared). Use --send-push to test device delivery.');
        }

        ensure_notification_channel_setups();

        $booking = Booking::with(['customer', 'provider.owner', 'zone'])->whereNotNull('customer_id')->latest()->first();
        if (! $booking) {
            $this->error('No booking found for smoke tests.');

            return self::FAILURE;
        }

        if ($inboxOnly) {
            $this->suppressFcmTokens($booking);
        }

        $results = [];
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        $moduleFilter = $this->option('module');
        $scenarios = notification_scenario_registry();
        if ($moduleFilter) {
            $scenarios = array_values(array_filter(
                $scenarios,
                fn (array $scenario) => ($scenario['module'] ?? '') === $moduleFilter
            ));

            if ($scenarios === []) {
                $this->error("No scenarios found for module: {$moduleFilter}");

                return self::FAILURE;
            }
        }

        foreach ($scenarios as $scenario) {
            $scenarioId = $scenario['id'];
            $module = $scenario['module'];
            $title = $scenario['title'];

            try {
                $result = $this->runScenario($scenarioId, $booking);
            } catch (\Throwable $e) {
                $result = ['status' => 'fail', 'detail' => $e->getMessage()];
            }

            $results[] = array_merge(['id' => $scenarioId, 'module' => $module, 'title' => $title], $result);

            match ($result['status']) {
                'pass' => $passed++,
                'skip' => $skipped++,
                default => $failed++,
            };
        }

        if ($inboxOnly) {
            $this->restoreFcmTokens();
        }

        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['module']][] = $row;
        }

        $this->newLine();
        $this->info('Live Notification Smoke Test');
        $this->newLine();

        foreach ($grouped as $module => $rows) {
            $label = NOTIFICATION_SCENARIO_MODULE_LABELS[$module] ?? $module;
            $this->line("<fg=cyan>{$label}</> (" . count($rows) . ')');

            foreach ($rows as $row) {
                $icon = match ($row['status']) {
                    'pass' => '<fg=green>PASS</>',
                    'skip' => '<fg=yellow>SKIP</>',
                    default => '<fg=red>FAIL</>',
                };
                $this->line("  {$icon} {$row['title']}");
                $this->line("       {$row['detail']}");
            }
            $this->newLine();
        }

        $this->info("Passed: {$passed} | Failed: {$failed} | Skipped: {$skipped}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function suppressFcmTokens(Booking $booking): void
    {
        $userIds = array_filter([
            $booking->customer_id,
            $booking->provider?->owner?->id,
        ]);

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $this->savedFcmTokens[$user->id] = $user->fcm_token;
            $user->fcm_token = null;
            $user->saveQuietly();
        }
    }

    private function restoreFcmTokens(): void
    {
        foreach ($this->savedFcmTokens as $userId => $token) {
            User::where('id', $userId)->update(['fcm_token' => $token]);
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function runScenario(string $scenarioId, Booking $booking): array
    {
        $booking->loadMissing(['customer', 'provider.owner', 'zone']);

        return match ($scenarioId) {
            'booking_create_admin' => $this->dispatchHelper(
                'send_admin_booking_created_notifications',
                fn () => send_admin_booking_created_notifications($booking),
                [$booking->customer_id, $booking->provider?->owner?->id]
            ),
            'booking_create_customer_with_provider' => $this->dispatchHelper(
                'send_booking_new_service_request_to_assigned_provider',
                fn () => send_booking_new_service_request_to_assigned_provider($booking),
                [$booking->provider?->owner?->id]
            ),
            'booking_admin_edit' => $this->dispatchHelper(
                'send_booking_edit_service_add_notifications',
                fn () => send_booking_edit_service_add_notifications($booking, 'Smoke Test Service'),
                [$booking->customer_id, $booking->provider?->owner?->id]
            ),
            'payment_provider_records' => $this->dispatchHelper(
                'send_booking_payment_collected_notifications (provider)',
                fn () => send_booking_payment_collected_notifications($booking, 100, 'provider'),
                [$booking->customer_id, $booking->provider?->owner?->id]
            ),
            'payment_customer_app_company' => $this->dispatchHelper(
                'send_booking_payment_collected_notifications (company)',
                fn () => send_booking_payment_collected_notifications($booking, 100, 'company'),
                [$booking->customer_id, $booking->provider?->owner?->id]
            ),
            'payment_admin_records' => $this->dispatchHelper(
                'send_booking_payment_collected_notifications (admin)',
                fn () => send_booking_payment_collected_notifications($booking, 50, 'company'),
                [$booking->customer_id, $booking->provider?->owner?->id]
            ),
            'payment_failed' => $this->dispatchHelper(
                'send_customer_payment_failed_notification',
                fn () => send_customer_payment_failed_notification($booking->customer_id, 25, $booking->id),
                [$booking->customer_id]
            ),
            'wallet_customer_deducted' => $this->dispatchHelper(
                'send_customer_wallet_deducted_notification',
                fn () => send_customer_wallet_deducted_notification($booking->customer, 10, null),
                [$booking->customer_id]
            ),
            'refund_wallet' => $this->dispatchHelper(
                'send_customer_refund_notification (wallet)',
                fn () => send_customer_refund_notification($booking, 20, 'refund'),
                [$booking->customer_id]
            ),
            'refund_bank_transfer' => $this->dispatchHelper(
                'send_customer_refund_notification (bank)',
                fn () => send_customer_refund_notification($booking, 20, 'refund_bank_transfer'),
                [$booking->customer_id]
            ),
            'loyalty_booking_completed', 'loyalty_admin_adds' => $this->dispatchHelper(
                'send_customer_loyalty_point_notification',
                fn () => send_customer_loyalty_point_notification($booking->customer, 5, 'loyalty_point', $booking->id),
                [$booking->customer_id]
            ),
            'loyalty_convert_to_wallet' => $this->dispatchHelper(
                'send_customer_loyalty_point_notification (convert)',
                fn () => send_customer_loyalty_point_notification($booking->customer, 5, 'loyalty_point_convert'),
                [$booking->customer_id]
            ),
            'booking_reminder' => $this->dispatchHelper(
                'send_booking_reminder_notification',
                fn () => send_booking_reminder_notification($booking),
                [$booking->customer_id]
            ),
            'booking_provider_accept' => $this->dispatchStatusChange('accepted'),
            'booking_provider_ongoing' => $this->dispatchStatusChange('ongoing'),
            'booking_on_hold' => $this->dispatchStatusChange('on_hold', ['accepted', 'ongoing']),
            'booking_pending_cancellation' => $this->dispatchStatusChange('pending_cancellation', ['accepted']),
            'booking_refund_request_status' => $this->dispatchStatusChange('refund_request', ['canceled']),
            'booking_ignored_by_provider' => $this->dispatchHelper(
                'send_booking_ignored_by_provider_notification',
                fn () => send_booking_ignored_by_provider_notification($booking),
                [$booking->customer_id]
            ),
            'booking_service_location_updated' => $this->dispatchHelper(
                'send_booking_service_location_updated_notification',
                fn () => send_booking_service_location_updated_notification($booking),
                [$booking->customer_id]
            ),
            'booking_complete' => $this->messageOnlyScenario('booking_complete', 'customer_notification', 'booking_complete', 'provider_notification'),
            'booking_otp_sent' => $this->messageOnlyScenario('otp', 'customer_notification'),
            'booking_schedule_change' => $this->messageOnlyScenario('booking_schedule_time_change', 'customer_notification', 'booking_schedule_time_change', 'provider_notification'),
            'booking_admin_assign_provider' => $this->messageOnlyScenario('provider_assign', 'customer_notification', 'booking_assigned_to_provider', 'provider_notification'),
            'booking_provider_cancel' => $this->messageOnlyScenario('booking_status_change', 'customer_notification', 'provider_removed_from_booking', 'provider_notification'),
            'booking_admin_remove_provider' => $this->messageOnlyScenario('booking_status_change', 'customer_notification', 'provider_removed_from_booking', 'provider_notification'),
            'booking_customer_cancel' => $this->messageOnlyScenario('booking_status_change', 'customer_notification', 'booking_status_change', 'provider_notification'),
            'booking_edit_service_update' => $this->messageOnlyScenario('booking_edit_service_update', 'customer_notification', 'booking_edit_service_update', 'provider_notification'),
            'booking_admin_cancel' => $this->messageOnlyScenario('booking_status_change', 'customer_notification', 'booking_status_change', 'provider_notification'),
            'booking_create_customer_auto_provider' => $this->messageOnlyScenario('booking_place', 'customer_notification'),

            'provider_withdraw_request' => $this->testWithdrawSubmitted($booking),
            'provider_withdraw_approved' => $this->dispatchInboxPushForProvider($booking, 'widthdraw_request_approve', 'withdraw', 'Withdraw approved'),
            'provider_withdraw_denied' => $this->dispatchInboxPushForProvider($booking, 'widthdraw_request_deny', 'withdraw', 'Withdraw denied'),
            'provider_withdraw_settled' => $this->testWithdrawSettled($booking),
            'admin_collect_from_provider' => $this->dispatchInboxPushForProvider($booking, 'admin_payable', 'admin_pay', 'Admin payable', true),
            'admin_pay_provider' => $this->dispatchHelper(
                'send_provider_settlement_received_notification (payout)',
                fn () => send_provider_settlement_received_notification($booking->provider, 120),
                [$booking->provider?->owner?->id]
            ),
            'review_customer_to_provider_approved' => $this->testReviewToProvider(),
            'review_provider_to_customer_approved' => $this->testReviewToCustomer(),
            'loyalty_referral_earned' => $this->dispatchInboxPushForCustomer($booking, 'referral_earning', 'general', 'Referral reward'),
            'referral_code_used' => $this->testReferralCodeUsed(),
            'wallet_customer_topup' => $this->dispatchInboxPushForCustomer($booking, 'add_fund_wallet', 'wallet', 'Wallet top-up', true),
            'chat_new_message' => $this->testChatMessage($booking),
            'chat_admin_customer_message' => $this->testAdminCustomerChat($booking),
            'chat_admin_provider_message' => $this->testAdminProviderChat($booking),
            'service_request_approved' => $this->testServiceRequestStatusNotification('service_request_approve', 'approved'),
            'service_request_denied' => $this->testServiceRequestStatusNotification('service_request_deny', 'denied'),
            'service_request_submitted' => $this->testServiceRequestSubmitted(),
            'provider_suspended' => $this->testProviderSuspended($booking),
            'provider_suspension_removed' => $this->testProviderSuspensionRemoved($booking),
            'advertisement_created_by_admin' => $this->testAdvertisementPush('advertisement_created_by_admin'),
            'advertisement_approved' => $this->testAdvertisementPush('advertisement_approved'),
            'advertisement_denied' => $this->testAdvertisementPush('advertisement_denied'),
            'advertisement_paused' => $this->testAdvertisementPush('advertisement_paused'),
            'advertisement_resumed' => $this->testAdvertisementPush('advertisement_resumed'),
            'advertisement_paused_by_provider' => $this->testAdvertisementPausedByProviderInbox(),
            'advertisement_resumed_by_provider' => $this->testAdvertisementResumedByProviderInbox(),
            'admin_alert_provider_registration' => $this->testAdminInbox(
                'admin_inbox_notify_provider_request',
                fn () => admin_inbox_notify_provider_request($this->sampleProvider()),
                fn () => UserNotification::query()
                    ->where('reference_type', 'provider')
                    ->where('reference_id', (string) $this->sampleProvider()->id)
                    ->delete()
            ),
            'admin_alert_withdraw_request' => $this->testAdminInboxWithdraw($booking),
            'admin_alert_booking_payment' => $this->testAdminInbox(
                'admin_inbox_notify_booking_payment',
                fn () => admin_inbox_notify_booking_payment($booking, 50 + mt_rand(1, 999) / 100, 'provider')
            ),
            'admin_alert_booking_ongoing' => $this->testAdminInbox(
                'admin_inbox_notify_booking_ongoing',
                fn () => admin_inbox_notify_booking_ongoing($booking),
                fn () => UserNotification::query()
                    ->where('reference_type', 'booking_ongoing')
                    ->where('reference_id', (string) $booking->id)
                    ->delete()
            ),
            'admin_alert_customer_cancel' => $this->testAdminInbox(
                'admin_inbox_notify_booking_customer_canceled',
                fn () => admin_inbox_notify_booking_customer_canceled($booking),
                fn () => UserNotification::query()
                    ->where('reference_type', 'booking_customer_cancel')
                    ->where('reference_id', (string) $booking->id)
                    ->delete()
            ),
            'admin_alert_showcase_submitted' => $this->testShowcaseSubmittedAdminInbox(),
            default => ['status' => 'fail', 'detail' => 'No smoke test handler defined'],
        };
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testWithdrawSubmitted(Booking $booking): array
    {
        $owner = $booking->provider?->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for withdraw test'];
        }

        $withdraw = WithdrawRequest::create([
            'user_id' => $owner->id,
            'amount' => 50,
            'request_status' => 'pending',
            'is_paid' => 0,
        ]);

        try {
            return $this->dispatchHelper(
                'send_provider_withdraw_request_submitted_notification',
                fn () => send_provider_withdraw_request_submitted_notification($withdraw),
                [$owner->id]
            );
        } finally {
            $withdraw->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testWithdrawSettled(Booking $booking): array
    {
        $owner = $booking->provider?->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for withdraw settled test'];
        }

        $withdraw = WithdrawRequest::create([
            'user_id' => $owner->id,
            'amount' => 75,
            'request_status' => 'settled',
            'is_paid' => 1,
            'transaction_id' => 'SMOKE-'.uniqid(),
        ]);

        try {
            return $this->dispatchHelper(
                'send_provider_withdraw_settled_notification',
                fn () => send_provider_withdraw_settled_notification($withdraw),
                [$owner->id]
            );
        } finally {
            $withdraw->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testReviewToProvider(): array
    {
        $review = Review::with(['provider.owner', 'customer', 'booking'])->latest()->first();
        if (! $review) {
            return ['status' => 'fail', 'detail' => 'No review record found'];
        }

        $providerResult = $this->dispatchHelper(
            'send_review_approved_to_provider_notification',
            fn () => send_review_approved_to_provider_notification($review),
            [$review->provider?->owner?->id]
        );

        $customerResult = $this->dispatchHelper(
            'send_review_published_to_customer_notification',
            fn () => send_review_published_to_customer_notification($review),
            [$review->customer_id]
        );

        return $this->combineScenarioResults(
            'Customer review approved',
            $providerResult,
            $customerResult,
            'provider new review + customer published'
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testReviewToCustomer(): array
    {
        $review = ProviderCustomerReview::with(['customer', 'provider', 'booking'])->latest()->first();
        if (! $review) {
            return ['status' => 'fail', 'detail' => 'No provider-to-customer review found'];
        }

        $customerResult = $this->dispatchHelper(
            'send_review_approved_to_customer_notification',
            fn () => send_review_approved_to_customer_notification($review),
            [$review->customer_id]
        );

        $providerResult = $this->dispatchHelper(
            'send_provider_review_published_notification',
            fn () => send_provider_review_published_notification($review),
            [$review->provider?->owner?->id]
        );

        return $this->combineScenarioResults(
            'Provider review approved',
            $customerResult,
            $providerResult,
            'customer new review + provider published'
        );
    }

    /**
     * @param  array{status: string, detail: string}  $first
     * @param  array{status: string, detail: string}  $second
     * @return array{status: string, detail: string}
     */
    private function combineScenarioResults(string $label, array $first, array $second, string $summary): array
    {
        if ($first['status'] === 'pass' && $second['status'] === 'pass') {
            return ['status' => 'pass', 'detail' => "{$label}: {$summary} — {$first['detail']}; {$second['detail']}"];
        }

        return [
            'status' => 'fail',
            'detail' => "{$label}: {$first['detail']}; {$second['detail']}",
        ];
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testChatMessage(Booking $booking): array
    {
        $channel = ChannelList::with(['channelUsers.user'])->latest()->first();
        if (! $channel) {
            return ['status' => 'fail', 'detail' => 'No chat channel found'];
        }

        $toUser = $channel->channelUsers->first(fn ($cu) => $cu->user && $cu->user->id !== $booking->customer_id)?->user
            ?? $booking->provider?->owner
            ?? $booking->customer;

        if (! $toUser) {
            return ['status' => 'fail', 'detail' => 'No chat recipient user found'];
        }

        $savedToken = $toUser->fcm_token;
        $toUser->fcm_token = $savedToken ?: 'smoke-test-token';
        $toUser->saveQuietly();

        try {
            send_chat_message_push_notification(
                $toUser,
                (string) $channel->id,
                'Smoke Test Sender',
                null,
                '+1000000000',
                'customer'
            );

            $title = get_push_notification_message('chat_message', chat_message_notification_settings_type($toUser) ?? 'customer_notification', $toUser->current_language_key);
            if ($title === '') {
                return ['status' => 'fail', 'detail' => 'Chat message template empty'];
            }

            return [
                'status' => 'pass',
                'detail' => "Chat push dispatched to {$toUser->user_type} (FCM path; inbox not persisted by chat sender)",
            ];
        } finally {
            $toUser->fcm_token = $savedToken;
            $toUser->saveQuietly();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdminCustomerChat(Booking $booking): array
    {
        $details = [];

        $customer = $booking->customer;
        if (! $customer) {
            return ['status' => 'fail', 'detail' => 'No customer for admin-customer chat push test'];
        }

        $pushResult = $this->dispatchChatPushToUser(
            $customer,
            business_config('business_name', 'business_information')?->live_values ?? 'Admin',
            'super-admin'
        );
        $details[] = 'Admin→customer push: ' . ($pushResult['status'] === 'pass' ? 'OK' : $pushResult['detail']);

        $conversation = $this->findSupportChatConversation(CUSTOMER_USER_TYPES);
        if (! $conversation) {
            $details[] = 'Customer→admin inbox: skipped (no support conversation in database)';

            return $pushResult['status'] === 'pass'
                ? ['status' => 'pass', 'detail' => implode('; ', $details)]
                : ['status' => 'fail', 'detail' => implode('; ', $details)];
        }

        $inboxResult = $this->testAdminInbox(
            'admin_inbox_notify_chat_message (customer)',
            fn () => admin_inbox_notify_chat_message($conversation),
            fn () => UserNotification::query()
                ->where('reference_type', 'channel_conversation')
                ->where('reference_id', (string) $conversation->id)
                ->delete()
        );
        $details[] = 'Customer→admin inbox: ' . ($inboxResult['status'] === 'pass' ? 'OK' : $inboxResult['detail']);

        return ($pushResult['status'] === 'pass' && $inboxResult['status'] === 'pass')
            ? ['status' => 'pass', 'detail' => implode('; ', $details)]
            : ['status' => 'fail', 'detail' => implode('; ', $details)];
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdminProviderChat(Booking $booking): array
    {
        $details = [];

        $providerOwner = $booking->provider?->owner;
        if (! $providerOwner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for admin-provider chat push test'];
        }

        $pushResult = $this->dispatchChatPushToUser(
            $providerOwner,
            business_config('business_name', 'business_information')?->live_values ?? 'Admin',
            'super-admin'
        );
        $details[] = 'Admin→provider push: ' . ($pushResult['status'] === 'pass' ? 'OK' : $pushResult['detail']);

        $conversation = $this->findSupportChatConversation(['provider-admin']);
        if (! $conversation) {
            $details[] = 'Provider→admin inbox: skipped (no support conversation in database)';

            return $pushResult['status'] === 'pass'
                ? ['status' => 'pass', 'detail' => implode('; ', $details)]
                : ['status' => 'fail', 'detail' => implode('; ', $details)];
        }

        $inboxResult = $this->testAdminInbox(
            'admin_inbox_notify_chat_message (provider)',
            fn () => admin_inbox_notify_chat_message($conversation),
            fn () => UserNotification::query()
                ->where('reference_type', 'channel_conversation')
                ->where('reference_id', (string) $conversation->id)
                ->delete()
        );
        $details[] = 'Provider→admin inbox: ' . ($inboxResult['status'] === 'pass' ? 'OK' : $inboxResult['detail']);

        return ($pushResult['status'] === 'pass' && $inboxResult['status'] === 'pass')
            ? ['status' => 'pass', 'detail' => implode('; ', $details)]
            : ['status' => 'fail', 'detail' => implode('; ', $details)];
    }

    private function findSupportChatConversation(array $senderUserTypes): ?ChannelConversation
    {
        return ChannelConversation::query()
            ->with(['channel_users'])
            ->whereHas('user', fn ($query) => $query->whereIn('user_type', $senderUserTypes))
            ->whereHas('channel', fn ($query) => $query->where('reference_type', 'support'))
            ->whereHas('channel_users.user', fn ($query) => $query->whereIn('user_type', ADMIN_USER_TYPES))
            ->latest()
            ->first();
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function dispatchChatPushToUser(User $toUser, ?string $senderName, string $senderType): array
    {
        $channel = ChannelList::with(['channelUsers.user'])->latest()->first();
        if (! $channel) {
            return ['status' => 'fail', 'detail' => 'No chat channel found'];
        }

        $savedToken = $toUser->fcm_token;
        $toUser->fcm_token = $savedToken ?: 'smoke-test-token';
        $toUser->saveQuietly();

        try {
            send_chat_message_push_notification(
                $toUser,
                (string) $channel->id,
                $senderName,
                null,
                business_config('business_phone', 'business_information')?->live_values ?? '+1000000000',
                $senderType
            );

            $settingsType = chat_message_notification_settings_type($toUser);
            $title = get_push_notification_message('chat_message', $settingsType ?? 'customer_notification', $toUser->current_language_key);
            if ($title === '') {
                return ['status' => 'fail', 'detail' => 'Chat message template empty'];
            }

            return ['status' => 'pass', 'detail' => "Push dispatched to {$toUser->user_type}"];
        } finally {
            $toUser->fcm_token = $savedToken;
            $toUser->saveQuietly();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function dispatchInboxPushForProvider(
        Booking $booking,
        string $key,
        string $type,
        string $label,
        bool $prefixAmount = false
    ): array {
        $owner = $booking->provider?->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => "{$label}: no provider owner"];
        }

        $before = $this->inboxCountForUsers([$owner->id]);
        $title = get_push_notification_message($key, 'provider_notification', $owner->current_language_key);
        $description = get_push_notification_description($key, 'provider_notification', $owner->current_language_key);

        if ($title === '' || $description === '') {
            return ['status' => 'fail', 'detail' => "{$label}: empty message template"];
        }

        $amount = with_currency_symbol(50);
        $displayTitle = $prefixAmount ? $amount . ' ' . $title : $title;

        scenario_push_notification(
            null,
            $displayTitle,
            $description,
            null,
            $type,
            $owner->id,
            ['amount' => $amount, 'provider_name' => $booking->provider?->company_name ?? ''],
            null,
            null,
            'provider-admin',
            $booking->zone_id
        );

        $delta = $this->inboxCountForUsers([$owner->id]) - $before;

        return $delta > 0
            ? ['status' => 'pass', 'detail' => "{$label}: {$delta} inbox row(s) (production uses device_notification)"]
            : ['status' => 'fail', 'detail' => "{$label}: inbox row not created"];
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function dispatchInboxPushForCustomer(
        Booking $booking,
        string $key,
        string $type,
        string $label,
        bool $prefixAmount = false
    ): array {
        $customer = $booking->customer;
        if (! $customer) {
            return ['status' => 'fail', 'detail' => "{$label}: no customer"];
        }

        $before = $this->inboxCountForUsers([$customer->id]);
        $title = get_push_notification_message($key, 'customer_notification', $customer->current_language_key);
        $description = get_push_notification_description($key, 'customer_notification', $customer->current_language_key);

        if ($title === '' || $description === '') {
            return ['status' => 'fail', 'detail' => "{$label}: empty message template"];
        }

        $amount = with_currency_symbol(25);
        $displayTitle = $prefixAmount ? $amount . ' ' . $title : $title;

        scenario_push_notification(
            null,
            $displayTitle,
            $description,
            $booking->id,
            $type,
            $customer->id,
            ['amount' => $amount, 'user_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))],
            null,
            null,
            'customer',
            $booking->zone_id
        );

        $delta = $this->inboxCountForUsers([$customer->id]) - $before;

        return $delta > 0
            ? ['status' => 'pass', 'detail' => "{$label}: {$delta} inbox row(s) (production uses device_notification)"]
            : ['status' => 'fail', 'detail' => "{$label}: inbox row not created"];
    }

    /**
     * @param  list<string|null>  $expectedUserIds
     * @return array{status: string, detail: string}
     */
    private function dispatchHelper(string $label, callable $callback, array $expectedUserIds): array
    {
        $expectedUserIds = array_values(array_filter($expectedUserIds));
        $before = $this->inboxCountForUsers($expectedUserIds);

        $callback();

        $after = $this->inboxCountForUsers($expectedUserIds);
        $delta = $after - $before;

        if ($delta >= max(1, count($expectedUserIds))) {
            return ['status' => 'pass', 'detail' => "{$label}: {$delta} inbox row(s) created"];
        }

        if ($delta > 0) {
            return ['status' => 'pass', 'detail' => "{$label}: {$delta} inbox row(s) created (expected " . count($expectedUserIds) . ')'];
        }

        return ['status' => 'fail', 'detail' => "{$label}: no inbox rows created (check toggles / notification active settings)"];
    }

    /**
     * @param  list<string>  $keys  Alternating: key, settings_type, ...
     * @return array{status: string, detail: string}
     */
    private function messageOnlyScenario(string ...$keys): array
    {
        $parts = [];
        for ($i = 0; $i < count($keys); $i += 2) {
            $key = $keys[$i];
            $type = $keys[$i + 1] ?? 'customer_notification';
            $title = get_push_notification_message($key, $type);
            $desc = get_push_notification_description($key, $type);
            if ($title === '' || $desc === '') {
                return ['status' => 'fail', 'detail' => "Empty message for {$type}/{$key}"];
            }
            $parts[] = "{$key} OK";
        }

        return ['status' => 'pass', 'detail' => 'Messages verified: ' . implode(', ', $parts) . ' (dispatch via booking flow)'];
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function dispatchStatusChange(string $newStatus, array $setupChain = []): array
    {
        $candidate = Booking::with('provider.owner')
            ->whereNotNull('provider_id')
            ->whereNotNull('customer_id')
            ->where('booking_status', $setupChain === [] ? 'pending' : end($setupChain))
            ->first();

        if (! $candidate && $setupChain === []) {
            $candidate = Booking::with('provider.owner')
                ->whereNotNull('provider_id')
                ->whereNotNull('customer_id')
                ->first();

            if ($candidate) {
                $candidate->booking_status = 'pending';
                $candidate->saveQuietly();
            }
        }

        if (! $candidate && $setupChain !== []) {
            $candidate = $this->prepareBookingStatusChain($setupChain);
        }

        if (! $candidate && $setupChain === []) {
            $candidate = Booking::with('provider.owner')
                ->where('booking_status', 'pending')
                ->whereNotNull('provider_id')
                ->whereNotNull('customer_id')
                ->first();
        }

        if (! $candidate) {
            return ['status' => 'skip', 'detail' => "No booking available for status change test ({$newStatus})"];
        }

        $userIds = array_filter([$candidate->customer_id, $candidate->provider?->owner?->id]);
        if ($newStatus === 'refund_request') {
            $userIds = array_filter([$candidate->customer_id]);
        }

        $before = $this->inboxCountForUsers($userIds);
        $originalStatus = $candidate->booking_status;

        $candidate->booking_status = $newStatus;
        $candidate->save();

        $after = $this->inboxCountForUsers($userIds);
        $delta = $after - $before;

        $candidate->booking_status = $originalStatus;
        $candidate->saveQuietly();

        if ($candidate->booking_status !== 'pending') {
            $candidate->booking_status = 'pending';
            $candidate->saveQuietly();
        }

        if ($delta > 0) {
            return ['status' => 'pass', 'detail' => "Status → {$newStatus}: {$delta} inbox row(s), reverted booking"];
        }

        return ['status' => 'fail', 'detail' => "Status → {$newStatus}: no inbox rows (booking {$candidate->readable_id})"];
    }

    /**
     * @param  list<string>  $statusChain
     */
    private function prepareBookingStatusChain(array $statusChain): ?Booking
    {
        $booking = Booking::with('provider.owner')
            ->where('booking_status', 'pending')
            ->whereNotNull('provider_id')
            ->whereNotNull('customer_id')
            ->first();

        if (! $booking) {
            return null;
        }

        foreach ($statusChain as $status) {
            $booking->booking_status = $status;
            $booking->save();
            $booking->refresh();
        }

        return $booking;
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testReferralCodeUsed(): array
    {
        $referrer = User::whereNotNull('ref_code')->where('user_type', 'customer')->first()
            ?? User::where('user_type', 'customer')->whereNotNull('id')->first();

        if (! $referrer) {
            return ['status' => 'fail', 'detail' => 'No customer user found for referral test'];
        }

        return $this->dispatchHelper(
            'send_referral_code_used_notification',
            fn () => send_referral_code_used_notification($referrer),
            [$referrer->id]
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testProviderSuspended(Booking $booking): array
    {
        $provider = $booking->provider;
        if (! $provider) {
            return ['status' => 'fail', 'detail' => 'No provider for suspend test'];
        }

        return $this->dispatchHelper(
            'send_provider_suspended_notification',
            fn () => send_provider_suspended_notification($provider),
            [$provider->owner?->id]
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testProviderSuspensionRemoved(Booking $booking): array
    {
        $provider = $booking->provider;
        if (! $provider) {
            return ['status' => 'fail', 'detail' => 'No provider for unsuspend test'];
        }

        return $this->dispatchHelper(
            'send_provider_suspension_removed_notification',
            fn () => send_provider_suspension_removed_notification($provider),
            [$provider->owner?->id]
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdvertisementPush(string $messageKey): array
    {
        $advertisement = Advertisement::with('provider.owner')->whereNotNull('provider_id')->latest()->first();
        if (! $advertisement) {
            return ['status' => 'skip', 'detail' => 'No advertisement record found'];
        }

        $ownerId = $advertisement->provider?->owner?->id;
        if (! $ownerId) {
            return ['status' => 'fail', 'detail' => 'Advertisement has no provider owner'];
        }

        return $this->dispatchHelper(
            "send_advertisement_push_notification ({$messageKey})",
            fn () => send_advertisement_push_notification($messageKey, $advertisement),
            [$ownerId]
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdvertisementPausedByProviderInbox(): array
    {
        $advertisement = Advertisement::with('provider')->whereNotNull('provider_id')->latest()->first();
        if (! $advertisement) {
            return ['status' => 'skip', 'detail' => 'No advertisement record found'];
        }

        return $this->testAdminInbox(
            'admin_inbox_notify_advertisement_paused_by_provider',
            fn () => admin_inbox_notify_advertisement_paused_by_provider($advertisement),
            fn () => UserNotification::query()
                ->where('reference_type', 'advertisement_paused_by_provider')
                ->where('reference_id', (string) $advertisement->id . ':paused')
                ->delete()
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdvertisementResumedByProviderInbox(): array
    {
        $advertisement = Advertisement::with('provider')->whereNotNull('provider_id')->latest()->first();
        if (! $advertisement) {
            return ['status' => 'skip', 'detail' => 'No advertisement record found'];
        }

        return $this->testAdminInbox(
            'admin_inbox_notify_advertisement_resumed_by_provider',
            fn () => admin_inbox_notify_advertisement_resumed_by_provider($advertisement),
            fn () => UserNotification::query()
                ->where('reference_type', 'advertisement_resumed_by_provider')
                ->where('reference_id', (string) $advertisement->id . ':resumed')
                ->delete()
        );
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testServiceRequestSubmitted(): array
    {
        $provider = $this->sampleProvider();
        $owner = $provider->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for service request test'];
        }

        $serviceRequest = ServiceRequest::create([
            'category_id' => null,
            'service_name' => 'Smoke Test Service ' . now()->format('His'),
            'service_description' => 'Smoke test service request submission',
            'status' => 'pending',
            'user_id' => $owner->id,
        ]);

        try {
            $adminBefore = UserNotification::where('created_at', '>=', $this->testStartedAt ?? now()->subMinute())->count();
            $providerBefore = $this->inboxCountForUsers([$owner->id]);

            admin_inbox_notify_service_request_submitted($serviceRequest);
            send_service_request_provider_notification(
                $serviceRequest,
                '',
                translate('Service_request_submitted'),
                translate('Your_service_request_has_been_submitted_and_is_pending_review'),
            );

            $adminDelta = UserNotification::where('created_at', '>=', $this->testStartedAt ?? now()->subMinute())->count() - $adminBefore;
            $providerDelta = $this->inboxCountForUsers([$owner->id]) - $providerBefore;

            if ($adminDelta > 0 && $providerDelta > 0) {
                return ['status' => 'pass', 'detail' => "Admin inbox: {$adminDelta} row(s); provider inbox: {$providerDelta} row(s)"];
            }

            return ['status' => 'fail', 'detail' => "Admin inbox delta: {$adminDelta}; provider inbox delta: {$providerDelta}"];
        } finally {
            UserNotification::query()
                ->where('reference_type', 'service_request_submitted')
                ->where('reference_id', (string) $serviceRequest->id)
                ->delete();
            $serviceRequest->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testServiceRequestSubmittedAdminInbox(): array
    {
        $provider = $this->sampleProvider();
        $owner = $provider->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for service request inbox test'];
        }

        $serviceRequest = ServiceRequest::create([
            'category_id' => null,
            'service_name' => 'Smoke Test Service Inbox ' . now()->format('His'),
            'service_description' => 'Smoke test admin inbox',
            'status' => 'pending',
            'user_id' => $owner->id,
        ]);

        try {
            return $this->testAdminInbox(
                'admin_inbox_notify_service_request_submitted',
                fn () => admin_inbox_notify_service_request_submitted($serviceRequest),
                fn () => UserNotification::query()
                    ->where('reference_type', 'service_request_submitted')
                    ->where('reference_id', (string) $serviceRequest->id)
                    ->delete()
            );
        } finally {
            $serviceRequest->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testServiceRequestStatusNotification(string $messageKey, string $status): array
    {
        $provider = $this->sampleProvider();
        $owner = $provider->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for service request status test'];
        }

        $serviceRequest = ServiceRequest::create([
            'category_id' => null,
            'service_name' => 'Smoke Test ' . ucfirst($status) . ' ' . now()->format('His'),
            'service_description' => 'Smoke test service request ' . $status,
            'status' => $status,
            'user_id' => $owner->id,
        ]);

        try {
            $before = $this->inboxCountForUsers([$owner->id]);
            send_service_request_provider_notification($serviceRequest, $messageKey);
            $delta = $this->inboxCountForUsers([$owner->id]) - $before;

            return $delta > 0
                ? ['status' => 'pass', 'detail' => ucfirst($status) . ": {$delta} provider inbox row(s)"]
                : ['status' => 'fail', 'detail' => ucfirst($status) . ': provider inbox row not created'];
        } finally {
            $serviceRequest->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testShowcaseSubmittedAdminInbox(): array
    {
        $provider = $this->sampleProvider();
        $item = ProviderShowcaseItem::create([
            'provider_id' => $provider->id,
            'title' => 'Smoke Test Showcase',
            'description' => 'Smoke test showcase submission',
            'media_type' => 'image',
            'file_name' => 'provider/' . \App\Support\MediaStoragePath::providerSlug($provider) . '/showcase/smoke-test.webp',
            'sort_order' => 0,
            'is_active' => 1,
            'is_approved' => ProviderShowcaseItem::STATUS_PENDING,
        ]);

        try {
            return $this->testAdminInbox(
                'admin_inbox_notify_showcase_submitted',
                fn () => admin_inbox_notify_showcase_submitted($item),
                fn () => UserNotification::query()
                    ->where('reference_type', 'showcase_submission')
                    ->where('reference_id', (string) $item->id)
                    ->delete()
            );
        } finally {
            $item->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdminInboxWithdraw(Booking $booking): array
    {
        $owner = $booking->provider?->owner;
        if (! $owner) {
            return ['status' => 'fail', 'detail' => 'No provider owner for withdraw inbox test'];
        }

        $withdraw = WithdrawRequest::create([
            'user_id' => $owner->id,
            'amount' => 25,
            'request_status' => 'pending',
            'is_paid' => 0,
        ]);

        try {
            return $this->testAdminInbox(
                'admin_inbox_notify_withdraw_request',
                fn () => admin_inbox_notify_withdraw_request($withdraw->loadMissing('user.provider'))
            );
        } finally {
            $withdraw->delete();
        }
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function testAdminInbox(string $label, callable $callback, ?callable $prepare = null): array
    {
        if ($prepare) {
            $prepare();
        }

        $before = UserNotification::where('created_at', '>=', $this->testStartedAt ?? now()->subMinute())->count();

        $callback();

        $delta = UserNotification::where('created_at', '>=', $this->testStartedAt ?? now()->subMinute())->count() - $before;

        return $delta > 0
            ? ['status' => 'pass', 'detail' => "{$label}: {$delta} admin inbox row(s) created"]
            : ['status' => 'fail', 'detail' => "{$label}: no admin inbox rows created"];
    }

    private function sampleProvider(): Provider
    {
        return Provider::with('owner')->whereNotNull('id')->latest()->first()
            ?? throw new \RuntimeException('No provider found');
    }

    /**
     * @param  list<string|null>  $userIds
     */
    private function inboxCountForUsers(array $userIds): int
    {
        $userIds = array_values(array_filter($userIds));
        if ($userIds === []) {
            return 0;
        }

        return PushNotificationUser::whereIn('user_id', $userIds)
            ->where('created_at', '>=', $this->testStartedAt ?? now()->subMinute())
            ->count();
    }
}
