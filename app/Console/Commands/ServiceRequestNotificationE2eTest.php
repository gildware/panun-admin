<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\UserNotification;
use Modules\PromotionManagement\Entities\PushNotificationUser;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\ServiceRequest;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceRequestController as AdminServiceRequestController;
use Modules\UserManagement\Entities\User;

class ServiceRequestNotificationE2eTest extends Command
{
    protected $signature = 'service-request:notifications-e2e {--keep-data : Leave seeded rows in the database (default: rollback transaction)}';

    protected $description = 'E2E test: customer/provider service-request submission and admin approve/deny notifications';

    private ?\Illuminate\Support\Carbon $startedAt = null;

    /** @var list<string> */
    private array $createdServiceRequestIds = [];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production.');

            return self::FAILURE;
        }

        $this->startedAt = now();
        ensure_notification_channel_setups();

        $tag = 'sr-e2e-'.Str::lower(Str::random(6));
        $this->info("Service request notification E2E [{$tag}]");

        $useTransaction = ! $this->option('keep-data');
        if ($useTransaction) {
            DB::beginTransaction();
        }

        $passed = 0;
        $failed = 0;

        try {
            $admin = User::query()
                ->whereIn('user_type', ADMIN_USER_TYPES)
                ->where('is_active', 1)
                ->first();
            if (! $admin) {
                throw new \RuntimeException('No active admin user found.');
            }

            $customer = $this->seedCustomer($tag);
            $provider = $this->seedProvider($tag);

            $scenarios = [
                'customer_submission_notifies_admin_and_customer' => fn () => $this->testCustomerSubmission($customer),
                'customer_approve_notifies_customer' => fn () => $this->testCustomerStatusChange($admin, $customer, true),
                'customer_deny_notifies_customer' => fn () => $this->testCustomerStatusChange($admin, $customer, false),
                'provider_submission_notifies_admin_and_provider' => fn () => $this->testProviderSubmission($provider),
                'provider_approve_notifies_provider' => fn () => $this->testProviderStatusChange($admin, $provider, true),
                'provider_deny_notifies_provider' => fn () => $this->testProviderStatusChange($admin, $provider, false),
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

            if ($useTransaction) {
                DB::rollBack();
                $this->line('');
                $this->info('Transaction rolled back — no permanent data changed.');
            }

            $this->line('');
            $this->info("Passed: {$passed} | Failed: {$failed}");

            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            if ($useTransaction) {
                DB::rollBack();
            }
            $this->error('E2E aborted: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    private function testCustomerSubmission(User $customer): string
    {
        $adminBefore = $this->adminInboxCount();
        $customerBefore = $this->pushInboxCount($customer->id);

        $response = $this->postCustomerApi(
            $customer,
            '/api/v1/customer/service/request/make',
            [
                'category_id' => '',
                'service_name' => 'E2E Customer Service '.now()->format('His'),
                'service_description' => 'Customer submission E2E test',
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Customer API returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        $serviceRequest = ServiceRequest::query()
            ->where('user_id', $customer->id)
            ->latest()
            ->first();
        if (! $serviceRequest) {
            throw new \RuntimeException('Service request row was not created for customer.');
        }
        $this->createdServiceRequestIds[] = (string) $serviceRequest->id;

        $adminDelta = $this->adminInboxCount() - $adminBefore;
        $customerDelta = $this->pushInboxCount($customer->id) - $customerBefore;

        if ($adminDelta < 1) {
            throw new \RuntimeException("Expected admin inbox notification, got delta {$adminDelta}");
        }
        if ($customerDelta < 1) {
            throw new \RuntimeException("Expected customer push inbox row, got delta {$customerDelta}");
        }

        $adminRow = UserNotification::query()
            ->where('reference_type', 'service_request_submitted')
            ->where('reference_id', (string) $serviceRequest->id)
            ->latest()
            ->first();
        if (! $adminRow || ! str_contains((string) $adminRow->body, 'E2E Customer')) {
            throw new \RuntimeException('Admin inbox row missing expected service request reference.');
        }

        return "admin inbox +{$adminDelta}, customer inbox +{$customerDelta}";
    }

    private function testCustomerStatusChange(User $admin, User $customer, bool $approve): string
    {
        $serviceRequest = ServiceRequest::query()->create([
            'category_id' => null,
            'service_name' => 'E2E Customer Status '.($approve ? 'Approve' : 'Deny').' '.now()->format('His'),
            'service_description' => 'Status change E2E',
            'status' => 'pending',
            'user_id' => $customer->id,
        ]);
        $this->createdServiceRequestIds[] = (string) $serviceRequest->id;

        $before = $this->pushInboxCount($customer->id);
        $this->callAdminStatusUpdate($admin, $serviceRequest, $approve);
        $delta = $this->pushInboxCount($customer->id) - $before;

        if ($delta < 1) {
            throw new \RuntimeException('Expected customer status push inbox row, got delta '.$delta);
        }

        $serviceRequest->refresh();
        $expectedStatus = $approve ? 'approved' : 'denied';
        if ($serviceRequest->status !== $expectedStatus) {
            throw new \RuntimeException("Expected status {$expectedStatus}, got {$serviceRequest->status}");
        }

        return ($approve ? 'approved' : 'denied')." → customer inbox +{$delta}";
    }

    private function testProviderSubmission(Provider $provider): string
    {
        $owner = User::query()->find($provider->user_id);
        if (! $owner || ! $owner->is_active) {
            throw new \RuntimeException('Provider owner missing.');
        }

        $adminBefore = $this->adminInboxCount();
        $providerBefore = $this->pushInboxCount($owner->id);
        $requestsBefore = ServiceRequest::query()->where('user_id', $owner->id)->count();

        $response = $this->postProviderApi(
            $owner,
            '/api/v1/provider/service-request',
            [
                'category_id' => '',
                'service_name' => 'E2E Provider Service '.now()->format('His'),
                'service_description' => 'Provider submission E2E test',
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Provider API returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        $requestsAfter = ServiceRequest::query()->where('user_id', $owner->id)->count();
        if ($requestsAfter <= $requestsBefore) {
            $latestAny = ServiceRequest::query()->latest()->first();
            $latestUserId = $latestAny?->user_id ?? 'none';
            throw new \RuntimeException(
                'Service request row was not created for provider owner '.$owner->id
                .". Before={$requestsBefore}, after={$requestsAfter}, latest_any_user={$latestUserId}. Response: "
                .$response->getContent()
            );
        }

        $serviceRequest = ServiceRequest::query()
            ->where('user_id', $owner->id)
            ->latest()
            ->first();
        if (! $serviceRequest) {
            throw new \RuntimeException('Service request row missing after successful provider API call.');
        }
        $this->createdServiceRequestIds[] = (string) $serviceRequest->id;

        $adminDelta = $this->adminInboxCount() - $adminBefore;
        $providerDelta = $this->pushInboxCount($owner->id) - $providerBefore;

        if ($adminDelta < 1 || $providerDelta < 1) {
            throw new \RuntimeException("Expected admin + provider inbox rows; admin delta {$adminDelta}, provider delta {$providerDelta}");
        }

        return "admin inbox +{$adminDelta}, provider inbox +{$providerDelta}";
    }

    private function testProviderStatusChange(User $admin, Provider $provider, bool $approve): string
    {
        $owner = User::query()->find($provider->user_id);
        if (! $owner || ! $owner->is_active) {
            throw new \RuntimeException('Provider owner missing.');
        }

        $serviceRequest = ServiceRequest::query()->create([
            'category_id' => null,
            'service_name' => 'E2E Provider Status '.($approve ? 'Approve' : 'Deny').' '.now()->format('His'),
            'service_description' => 'Provider status E2E',
            'status' => 'pending',
            'user_id' => $owner->id,
        ]);
        $this->createdServiceRequestIds[] = (string) $serviceRequest->id;

        $before = $this->pushInboxCount($owner->id);
        $this->callAdminStatusUpdate($admin, $serviceRequest, $approve);
        $delta = $this->pushInboxCount($owner->id) - $before;

        if ($delta < 1) {
            throw new \RuntimeException('Expected provider status push inbox row, got delta '.$delta);
        }

        return ($approve ? 'approved' : 'denied')." → provider inbox +{$delta}";
    }

    private function callAdminStatusUpdate(User $admin, ServiceRequest $serviceRequest, bool $approve): void
    {
        $request = Request::create(
            route('admin.service.request.update', [$serviceRequest->id]),
            'POST',
            [
                'review_status' => $approve ? '1' : '0',
                'admin_feedback' => $approve ? 'Approved in E2E test' : 'Denied in E2E test',
            ]
        );
        $request->setUserResolver(fn () => $admin);

        app(AdminServiceRequestController::class)->updateStatus($serviceRequest->id, $request);
    }

    private function postCustomerApi(User $customer, string $uri, array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $token = $customer->createToken(CUSTOMER_PANEL_ACCESS)->accessToken;

        return $this->dispatchApiRequest('POST', $uri, $payload, $token);
    }

    private function postProviderApi(User $owner, string $uri, array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $token = $owner->createToken(PROVIDER_PANEL_ACCESS)->accessToken;

        return $this->dispatchApiRequest('POST', $uri, $payload, $token);
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

    private function adminInboxCount(): int
    {
        return UserNotification::query()
            ->where('type', UserNotification::TYPE_SERVICE_REQUEST)
            ->where('created_at', '>=', $this->startedAt ?? now()->subMinute())
            ->count();
    }

    private function pushInboxCount(string $userId): int
    {
        return PushNotificationUser::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $this->startedAt ?? now()->subMinute())
            ->count();
    }

    private function seedCustomer(string $tag): User
    {
        return User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'E2E',
            'last_name' => 'Customer',
            'email' => $tag.'-customer@e2e.test',
            'phone' => '6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'user_type' => 'customer',
            'is_active' => 1,
            'current_language_key' => 'en',
        ]);
    }

    private function seedProvider(string $tag): Provider
    {
        $owner = User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'E2E',
            'last_name' => 'Provider',
            'email' => $tag.'-provider@e2e.test',
            'phone' => '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'user_type' => 'provider-admin',
            'is_active' => 1,
            'current_language_key' => 'en',
        ]);

        $provider = new Provider;
        $provider->id = (string) Str::uuid();
        $provider->user_id = $owner->id;
        $provider->company_name = $tag.' Provider Co';
        $provider->company_phone = $owner->phone;
        $provider->is_active = 1;
        $provider->is_approved = 1;
        $provider->save();

        return $provider->fresh(['owner', 'user']);
    }
}
