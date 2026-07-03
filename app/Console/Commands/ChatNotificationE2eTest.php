<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\UserNotification;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\NotificationSetup;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\ChattingModule\Entities\ChannelUser;
use Modules\ChattingModule\Http\Controllers\Web\Admin\ChattingController as AdminChattingController;
use Modules\PromotionManagement\Entities\PushNotificationDeliveryLog;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserFcmDevice;
use Ramsey\Uuid\Uuid;

class ChatNotificationE2eTest extends Command
{
    protected $signature = 'chat:notifications-e2e {--keep-data : Leave seeded rows in the database (default: rollback transaction)}';

    protected $description = 'E2E test: chat message push notifications (customer, provider, admin) and admin inbox';

    private ?\Illuminate\Support\Carbon $startedAt = null;

    /** @var list<string> */
    private array $createdUserIds = [];

    /** @var list<string> */
    private array $createdChannelIds = [];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production.');

            return self::FAILURE;
        }

        $this->startedAt = now();
        $tag = 'chat-e2e-'.Str::lower(Str::random(6));
        $this->info("Chat notification E2E [{$tag}]");

        $shouldCleanup = ! $this->option('keep-data');

        $passed = 0;
        $failed = 0;

        try {
            $this->seedFakeFcmHttp();
            $this->ensureChatNotificationPrerequisites();

            $admin = User::query()
                ->where('user_type', 'super-admin')
                ->where('is_active', 1)
                ->first();
            if (! $admin) {
                throw new \RuntimeException('No active super-admin user found.');
            }

            $customer = $this->seedCustomer($tag);
            $providerOwner = $this->seedProviderOwner($tag);
            $sharedPhone = '9'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $customerNoDevice = $this->seedCustomer($tag.'-nodev', $sharedPhone);
            $customerWithDevice = $this->seedCustomer($tag.'-dev', $sharedPhone);

            $this->registerFcmDevice($customer, $tag.'-customer-fcm');
            $this->registerFcmDevice($providerOwner, $tag.'-provider-fcm');
            $this->registerFcmDevice($customerWithDevice, $tag.'-shared-fcm');

            $bookingChannel = $this->createChannel(
                $customer->id,
                $providerOwner->id,
                'booking_id',
                (string) Str::uuid()
            );
            $customerSupportChannel = $this->createChannel(
                $customer->id,
                $admin->id,
                'support_customer',
                ''
            );
            $providerSupportChannel = $this->createChannel(
                $providerOwner->id,
                $admin->id,
                'support_provider',
                ''
            );
            $fanOutChannel = $this->createChannel(
                $customerNoDevice->id,
                $providerOwner->id,
                'booking_id',
                (string) Str::uuid()
            );

            $scenarios = [
                'booking_customer_to_provider_push' => fn () => $this->testBookingCustomerToProvider($customer, $providerOwner, $bookingChannel),
                'booking_provider_to_customer_push' => fn () => $this->testBookingProviderToCustomer($providerOwner, $customer, $bookingChannel),
                'support_admin_to_customer_push' => fn () => $this->testAdminToRecipientPush($admin, $customer, $customerSupportChannel),
                'support_admin_to_provider_push' => fn () => $this->testAdminToRecipientPush($admin, $providerOwner, $providerSupportChannel),
                'support_customer_to_admin_inbox' => fn () => $this->testCustomerToAdminInbox($customer, $admin, $customerSupportChannel),
                'support_provider_to_admin_inbox' => fn () => $this->testProviderToAdminInbox($providerOwner, $admin, $providerSupportChannel),
                'phone_fan_out_customer_push' => fn () => $this->testPhoneFanOutCustomer($providerOwner, $customerWithDevice, $fanOutChannel),
            ];

            foreach ($scenarios as $name => $runner) {
                $this->line('');
                $this->info($name);
                try {
                    $detail = $runner();
                    $this->line("  <fg=green>PASS</> {$detail}");
                    $passed++;
                } catch (\Throwable $e) {
                    $this->line("  <fg=red>FAIL</> {$e->getMessage()}");
                    $failed++;
                }
            }

            if ($shouldCleanup) {
                $this->cleanupSeededData();
                $this->line('');
                $this->info('Seeded E2E data cleaned up.');
            } else {
                $this->line('');
                $this->info('E2E data left in database (--keep-data).');
            }

            $this->line('');
            $this->info("Passed: {$passed} | Failed: {$failed}");

            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            if ($shouldCleanup) {
                $this->cleanupSeededData();
            }
            $this->error('E2E aborted: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    private function cleanupSeededData(): void
    {
        $since = $this->startedAt ?? now()->subHour();

        PushNotificationDeliveryLog::query()
            ->where('notification_type', 'chatting')
            ->where('created_at', '>=', $since)
            ->where(function ($query) {
                $query->whereIn('user_id', $this->createdUserIds)
                    ->orWhere('fcm_token_preview', 'like', 'chat-e2e%');
            })
            ->delete();

        UserNotification::query()
            ->where('type', UserNotification::TYPE_CHAT_MESSAGE)
            ->where('created_at', '>=', $since)
            ->delete();

        if ($this->createdChannelIds !== []) {
            foreach ($this->createdChannelIds as $channelId) {
                \Modules\ChattingModule\Entities\ChannelConversation::query()
                    ->where('channel_id', $channelId)
                    ->delete();
                ChannelUser::query()->where('channel_id', $channelId)->delete();
                ChannelList::query()->where('id', $channelId)->delete();
            }
        }

        if ($this->createdUserIds !== []) {
            UserFcmDevice::query()->whereIn('user_id', $this->createdUserIds)->delete();
            Provider::query()->whereIn('user_id', $this->createdUserIds)->delete();
            User::query()->whereIn('id', $this->createdUserIds)->delete();
        }
    }

    private function testBookingCustomerToProvider(User $customer, User $provider, ChannelList $channel): string
    {
        $before = $this->chatDeliveryLogCount($provider->id);

        $response = $this->postCustomerApi($customer, '/api/v1/customer/chat/send-message', [
            'channel_id' => $channel->id,
            'message' => 'E2E customer → provider '.now()->format('His'),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Customer send-message HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        $delta = $this->chatDeliveryLogCount($provider->id) - $before;
        if ($delta < 1) {
            throw new \RuntimeException("Expected provider chat delivery log, got delta {$delta}");
        }

        $log = $this->latestChatLogForUser($provider->id);
        if (! $log || $log->status !== 'sent') {
            throw new \RuntimeException('Provider delivery log missing or not sent');
        }
        if ((string) $log->title === '') {
            throw new \RuntimeException('Provider delivery log has empty title');
        }

        return "provider delivery log +{$delta}, title=\"{$log->title}\"";
    }

    private function testBookingProviderToCustomer(User $provider, User $customer, ChannelList $channel): string
    {
        $before = $this->chatDeliveryLogCount($customer->id);

        $response = $this->postProviderApi($provider, '/api/v1/provider/chat/send-message', [
            'channel_id' => $channel->id,
            'message' => 'E2E provider → customer '.now()->format('His'),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Provider send-message HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        $delta = $this->chatDeliveryLogCount($customer->id) - $before;
        if ($delta < 1) {
            throw new \RuntimeException("Expected customer chat delivery log, got delta {$delta}");
        }

        return "customer delivery log +{$delta}";
    }

    private function testAdminToRecipientPush(User $admin, User $recipient, ChannelList $channel): string
    {
        $before = $this->chatDeliveryLogCount($recipient->id);

        $this->callAdminSendMessage($admin, (string) $channel->id, 'E2E admin → '.$recipient->user_type.' '.now()->format('His'));

        $delta = $this->chatDeliveryLogCount($recipient->id) - $before;
        if ($delta < 1) {
            throw new \RuntimeException("Expected {$recipient->user_type} chat delivery log, got delta {$delta}");
        }

        return "{$recipient->user_type} delivery log +{$delta}";
    }

    private function testCustomerToAdminInbox(User $customer, User $admin, ChannelList $channel): string
    {
        $before = $this->adminChatInboxCount($admin->id);

        $response = $this->postCustomerApi($customer, '/api/v1/customer/chat/send-message', [
            'channel_id' => $channel->id,
            'message' => 'E2E customer → admin inbox '.now()->format('His'),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Customer support send HTTP '.$response->getStatusCode());
        }

        $delta = $this->adminChatInboxCount($admin->id) - $before;
        if ($delta < 1) {
            throw new \RuntimeException("Expected admin inbox row, got delta {$delta}");
        }

        return "admin inbox +{$delta}";
    }

    private function testProviderToAdminInbox(User $provider, User $admin, ChannelList $channel): string
    {
        $before = $this->adminChatInboxCount($admin->id);

        $response = $this->postProviderApi($provider, '/api/v1/provider/chat/send-message', [
            'channel_id' => $channel->id,
            'message' => 'E2E provider → admin inbox '.now()->format('His'),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Provider support send HTTP '.$response->getStatusCode());
        }

        $delta = $this->adminChatInboxCount($admin->id) - $before;
        if ($delta < 1) {
            throw new \RuntimeException("Expected admin inbox row, got delta {$delta}");
        }

        return "admin inbox +{$delta}";
    }

    private function testPhoneFanOutCustomer(User $provider, User $deviceHolder, ChannelList $channel): string
    {
        $before = $this->chatDeliveryLogCount($deviceHolder->id);

        $response = $this->postProviderApi($provider, '/api/v1/provider/chat/send-message', [
            'channel_id' => $channel->id,
            'message' => 'E2E phone fan-out '.now()->format('His'),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Provider fan-out send HTTP '.$response->getStatusCode());
        }

        $delta = $this->chatDeliveryLogCount($deviceHolder->id) - $before;
        if ($delta < 1) {
            throw new \RuntimeException("Expected phone fan-out delivery log on sibling customer, got delta {$delta}");
        }

        return "sibling customer delivery log +{$delta} (shared phone fan-out)";
    }

    private function callAdminSendMessage(User $admin, string $channelId, string $message): void
    {
        $request = Request::create(
            route('admin.chat.send-message'),
            'POST',
            [
                'channel_id' => $channelId,
                'message' => $message,
            ]
        );
        $request->setUserResolver(fn () => $admin);

        app(AdminChattingController::class)->sendMessage($request);
    }

    private function postCustomerApi(User $customer, string $uri, array $payload): \Symfony\Component\HttpFoundation\Response
    {
        return $this->dispatchApiRequest('POST', $uri, $payload, $customer->createToken(CUSTOMER_PANEL_ACCESS)->accessToken);
    }

    private function postProviderApi(User $provider, string $uri, array $payload): \Symfony\Component\HttpFoundation\Response
    {
        return $this->dispatchApiRequest('POST', $uri, $payload, $provider->createToken(PROVIDER_PANEL_ACCESS)->accessToken);
    }

    private function dispatchApiRequest(string $method, string $uri, array $payload, string $token): \Symfony\Component\HttpFoundation\Response
    {
        auth()->forgetGuards();

        $kernel = app(Kernel::class);
        $request = Request::create($uri, $method, $payload, [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        auth()->forgetGuards();

        return $response;
    }

    private function chatDeliveryLogCount(string $userId): int
    {
        return PushNotificationDeliveryLog::query()
            ->where('user_id', $userId)
            ->where('notification_type', 'chatting')
            ->where('created_at', '>=', $this->startedAt ?? now()->subMinute())
            ->count();
    }

    private function latestChatLogForUser(string $userId): ?PushNotificationDeliveryLog
    {
        return PushNotificationDeliveryLog::query()
            ->where('user_id', $userId)
            ->where('notification_type', 'chatting')
            ->where('created_at', '>=', $this->startedAt ?? now()->subMinute())
            ->latest()
            ->first();
    }

    private function adminChatInboxCount(string $adminId): int
    {
        return UserNotification::query()
            ->where('user_id', $adminId)
            ->where('type', UserNotification::TYPE_CHAT_MESSAGE)
            ->where('created_at', '>=', $this->startedAt ?? now()->subMinute())
            ->count();
    }

    private function createChannel(string $userA, string $userB, string $referenceType, ?string $referenceId): ChannelList
    {
        $channel = ChannelList::query()->create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId ?: null,
        ]);
        $this->createdChannelIds[] = (string) $channel->id;

        foreach ([$userA, $userB] as $userId) {
            ChannelUser::query()->create([
                'id' => (string) Uuid::uuid4(),
                'channel_id' => $channel->id,
                'user_id' => $userId,
                'is_read' => 1,
                'read_at' => now(),
            ]);
        }

        return $channel->fresh();
    }

    private function registerFcmDevice(User $user, string $token): void
    {
        register_user_fcm_device(
            (string) $user->id,
            $token,
            'e2e-device-'.$user->id,
            'android',
            'E2E Device',
            'Test',
            '14'
        );
    }

    private function seedCustomer(string $tag, ?string $phone = null): User
    {
        $user = User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'E2E',
            'last_name' => 'Customer',
            'email' => $tag.'@chat-e2e.test',
            'phone' => $phone ?? ('6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT)),
            'password' => bcrypt('password'),
            'user_type' => 'customer',
            'is_active' => 1,
            'current_language_key' => 'en',
        ]);
        $this->createdUserIds[] = $user->id;

        return $user;
    }

    private function seedProviderOwner(string $tag): User
    {
        $owner = User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'E2E',
            'last_name' => 'Provider',
            'email' => $tag.'-provider@chat-e2e.test',
            'phone' => '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'user_type' => 'provider-admin',
            'is_active' => 1,
            'current_language_key' => 'en',
        ]);
        $this->createdUserIds[] = $owner->id;

        $provider = new Provider;
        $provider->id = (string) Str::uuid();
        $provider->user_id = $owner->id;
        $provider->company_name = $tag.' Provider Co';
        $provider->company_phone = $owner->phone;
        $provider->is_active = 1;
        $provider->is_approved = 1;
        $provider->save();

        return $owner->fresh(['provider']);
    }

    private function ensureChatNotificationPrerequisites(): void
    {
        ensure_notification_channel_setups();

        foreach ([
            ['user_type' => 'user', 'key' => 'chatting'],
            ['user_type' => 'provider', 'key' => 'chatting'],
        ] as $row) {
            NotificationSetup::query()->firstOrCreate(
                ['user_type' => $row['user_type'], 'key' => $row['key']],
                [
                    'title' => 'Chatting',
                    'sub_title' => 'E2E chat notifications',
                    'value' => json_encode(['email' => null, 'notification' => 1, 'sms' => null]),
                ]
            );
        }

        foreach (['customer_notification', 'provider_notification'] as $settingsType) {
            BusinessSettings::query()->updateOrCreate(
                [
                    'key_name' => 'chat_message',
                    'settings_type' => $settingsType,
                ],
                [
                    'live_values' => [
                        'chat_message_status' => 1,
                        'chat_message_message' => $settingsType === 'customer_notification'
                            ? 'New Message from {{senderName}}'
                            : 'New Chat Message',
                        'chat_message_description' => 'You have a new chat message.',
                    ],
                    'test_values' => [],
                    'mode' => 'live',
                    'is_active' => 1,
                ]
            );
        }

        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'business_name', 'settings_type' => 'business_information'],
            [
                'live_values' => 'Panun Kaergar E2E',
                'test_values' => [],
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    private function seedFakeFcmHttp(): void
    {
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($res === false) {
            throw new \RuntimeException('Failed to generate test RSA key for FCM E2E.');
        }

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);

        $serviceAccount = json_encode([
            'project_id' => 'chat-e2e-test-project',
            'client_email' => 'firebase-adminsdk@chat-e2e-test.iam.gserviceaccount.com',
            'private_key' => $privateKey,
        ]);

        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'push_notification', 'settings_type' => 'third_party'],
            [
                'live_values' => ['service_file_content' => $serviceAccount],
                'test_values' => ['service_file_content' => $serviceAccount],
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'e2e-fake-access-token'], 200),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/chat-e2e/messages/e2e'], 200),
        ]);
    }
}
