<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AdminModule\Services\Maintenance\AdminCustomerDeletionService;
use Modules\AdminModule\Services\Maintenance\AdminProviderDeletionService;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class SystemMaintenanceDataResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $dbConnection = $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: '';
        $dbDatabase = $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '';

        if ($dbConnection === 'sqlite' || $dbDatabase === ':memory:') {
            $this->markTestSkipped('Full schema migrations require MySQL. Run: php artisan system-maintenance:smoke-test');
        }

        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    public function test_data_reset_page_renders_for_super_admin(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin.system-maintenance.data-reset.index'))
            ->assertOk()
            ->assertSee('Delete all providers', false)
            ->assertSee('Delete all customers', false)
            ->assertSee('data-reset-progress', false);
    }

    public function test_progress_init_requires_reset_confirmation(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.system-maintenance.data-reset.progress.init'), [
                'type' => 'providers',
                'confirm' => 'WRONG',
            ])
            ->assertStatus(422);
    }

    public function test_progress_init_returns_provider_total(): void
    {
        $admin = $this->createSuperAdmin();
        $this->seedProviders(2);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.system-maintenance.data-reset.progress.init'), [
                'type' => 'providers',
                'confirm' => 'RESET',
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'type' => 'providers',
                'total' => 2,
                'skipped' => 0,
            ]);
    }

    public function test_progressive_provider_deletion_steps_until_complete(): void
    {
        $admin = $this->createSuperAdmin();
        $this->seedProviders(2);

        $service = app(AdminProviderDeletionService::class);
        $this->assertSame(2, $service->countProviders());

        $stepOne = $service->deleteNextProvider(2, 0);
        $this->assertFalse($stepOne['complete']);
        $this->assertSame(1, $stepOne['current']);
        $this->assertSame(2, $stepOne['total']);
        $this->assertNotEmpty($stepOne['label']);
        $this->assertSame(1, $service->countProviders());

        $stepTwo = $service->deleteNextProvider(2, 1);
        $this->assertTrue($stepTwo['complete']);
        $this->assertSame(2, $stepTwo['current']);
        $this->assertSame(0, $service->countProviders());
    }

    public function test_progress_step_endpoint_deletes_all_providers_via_http(): void
    {
        $admin = $this->createSuperAdmin();
        $this->seedProviders(3);

        $init = $this->actingAs($admin)
            ->postJson(route('admin.system-maintenance.data-reset.progress.init'), [
                'type' => 'providers',
                'confirm' => 'RESET',
            ])
            ->assertOk()
            ->json();

        $this->assertSame(3, $init['total']);

        $current = 0;
        $message = null;

        for ($i = 0; $i < 5; $i++) {
            $step = $this->actingAs($admin)
                ->postJson(route('admin.system-maintenance.data-reset.progress.step'), [
                    'type' => 'providers',
                    'total' => $init['total'],
                    'current' => $current,
                ])
                ->assertOk()
                ->json();

            $this->assertTrue($step['ok']);
            $current = $step['current'];

            if ($step['complete']) {
                $message = $step['message'];
                break;
            }

            $this->assertNotEmpty($step['label']);
            $this->assertLessThanOrEqual($init['total'], $current);
        }

        $this->assertNotNull($message);
        $this->assertSame(0, app(AdminProviderDeletionService::class)->countProviders());
        $this->assertSame(3, $current);
    }

    public function test_progress_init_returns_customer_total_and_skips_linked_provider_users(): void
    {
        $admin = $this->createSuperAdmin();
        $this->seedCustomer('Alice', '1111111111');
        $this->seedCustomer('Bob', '2222222222');
        $this->seedProviderWithOwner('Linked Co', '3333333333');

        $response = $this->actingAs($admin)
            ->postJson(route('admin.system-maintenance.data-reset.progress.init'), [
                'type' => 'customers',
                'confirm' => 'RESET',
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'type' => 'customers',
                'total' => 2,
                'skipped' => 1,
            ]);
    }

    public function test_progressive_customer_deletion_removes_bookings_and_accounts(): void
    {
        $admin = $this->createSuperAdmin();
        $customer = $this->seedCustomer('Carol', '4444444444');
        $provider = $this->seedProviderWithOwner('Test Provider', '5555555555');

        Booking::query()->create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'booking_status' => 'pending',
            'is_paid' => 0,
            'payment_method' => 'cash_after_service',
            'transaction_id' => null,
            'total_booking_amount' => 100,
            'total_tax_amount' => 0,
            'total_discount_amount' => 0,
            'total_campaign_discount_amount' => 0,
            'total_coupon_discount_amount' => 0,
            'additional_charge' => 0,
            'total_referral_discount_amount' => 0,
            'total_extra_fee' => 0,
            'extra_fee_amount' => 0,
            'service_schedule' => now(),
        ]);

        DB::table('accounts')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $customer->id,
            'balance_pending' => 0,
            'received_balance' => 0,
            'account_payable' => 0,
            'account_receivable' => 0,
            'total_withdrawn' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(AdminCustomerDeletionService::class);
        $this->assertSame(1, $service->countDeletableCustomers());

        $result = $service->deleteNextCustomer(1, 0, 0);
        $this->assertTrue($result['complete']);
        $this->assertSame(1, $result['current']);

        $this->assertSame(0, User::query()->inCustomerDirectory()->count());
        $this->assertSame(0, Booking::query()->where('customer_id', $customer->id)->count());
        $this->assertSame(0, DB::table('accounts')->where('user_id', $customer->id)->count());
    }

    public function test_legacy_reset_form_rejects_provider_and_customer_bulk_delete(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post(route('admin.system-maintenance.data-reset.run'), [
                'reset_form' => 'providers',
                'confirm' => 'RESET',
            ])
            ->assertStatus(400);
    }

    private function createSuperAdmin(): User
    {
        return User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'super-admin-'.Str::random(6).'@test.local',
            'phone' => '9'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'user_type' => 'super-admin',
            'is_active' => 1,
        ]);
    }

    private function seedCustomer(string $firstName, string $phone): User
    {
        return User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => $firstName,
            'last_name' => 'Test',
            'email' => Str::slug($firstName).'-'.Str::random(4).'@customer.test',
            'phone' => $phone,
            'password' => bcrypt('password'),
            'user_type' => 'customer',
            'is_active' => 1,
        ]);
    }

    private function seedProviderWithOwner(string $companyName, string $phone): Provider
    {
        $owner = User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Owner',
            'last_name' => $companyName,
            'email' => Str::slug($companyName).'-'.Str::random(4).'@provider.test',
            'phone' => $phone,
            'password' => bcrypt('password'),
            'user_type' => 'provider-admin',
            'is_active' => 1,
        ]);

        return Provider::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'company_name' => $companyName,
            'company_phone' => $phone,
            'is_active' => 1,
        ]);
    }

  /** @return list<Provider> */
    private function seedProviders(int $count): array
    {
        $providers = [];
        for ($i = 1; $i <= $count; $i++) {
            $providers[] = $this->seedProviderWithOwner('Provider '.$i, '8'.str_pad((string) $i, 9, '0', STR_PAD_LEFT));
        }

        return $providers;
    }
}
