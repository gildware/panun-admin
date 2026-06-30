<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AdminModule\Services\Maintenance\AdminCustomerDeletionService;
use Modules\AdminModule\Services\Maintenance\AdminProviderDeletionService;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class SystemMaintenanceDataResetSmokeTest extends Command
{
    protected $signature = 'system-maintenance:smoke-test';

    protected $description = 'Smoke-test provider/customer deletion services on tagged seed data inside a rolled-back transaction.';

    public function handle(
        AdminProviderDeletionService $providerDeletionService,
        AdminCustomerDeletionService $customerDeletionService
    ): int {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production.');

            return self::FAILURE;
        }

        $tag = 'smoke-reset-'.Str::lower(Str::random(6));
        $this->info('Running scoped data-reset smoke test ['.$tag.'] (transaction rollback)');

        DB::beginTransaction();

        try {
            [$providerIds, $customerIds] = $this->seedSmokeData($tag);
            $this->line('Seeded '.count($providerIds).' providers and '.count($customerIds).' customers.');

            $this->info('--- Provider deletion (1 of '.count($providerIds).') ---');
            foreach ($providerIds as $index => $providerId) {
                $provider = Provider::withTrashed()->with(['owner', 'servicemen'])->find($providerId);
                $this->assertTrue($provider !== null, 'Missing seeded provider '.$providerId);
                $label = $provider->company_name;
                $providerDeletionService->deleteProvider($provider);
                $this->line('  Deleted '.($index + 1).' of '.count($providerIds).': '.$label);
                $this->assertTrue(Provider::withTrashed()->find($providerId) === null, 'Provider row still exists');
            }

            $this->info('--- Customer deletion (1 of '.count($customerIds).') ---');
            foreach ($customerIds as $index => $customerId) {
                $customer = User::withTrashed()->find($customerId);
                $this->assertTrue($customer !== null, 'Missing seeded customer '.$customerId);
                $label = trim($customer->first_name.' '.$customer->last_name);
                $customerDeletionService->deleteCustomer($customer);
                $this->line('  Deleted '.($index + 1).' of '.count($customerIds).': '.$label);
                $this->assertTrue(User::withTrashed()->find($customerId) === null, 'Customer row still exists');
            }

            DB::rollBack();
            $this->info('Transaction rolled back — no permanent data changed.');
            $this->info('Smoke test passed.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Smoke test failed: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function seedSmokeData(string $tag): array
    {
        $providerIds = [];
        $customerIds = [];

        for ($i = 1; $i <= 2; $i++) {
            $owner = User::query()->create([
                'id' => (string) Str::uuid(),
                'first_name' => 'Smoke',
                'last_name' => 'Provider '.$i,
                'email' => $tag.'-provider-'.$i.'@smoke.test',
                'phone' => '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'password' => bcrypt('password'),
                'user_type' => 'provider-admin',
                'is_active' => 1,
            ]);

            $provider = new Provider;
            $provider->id = (string) Str::uuid();
            $provider->user_id = $owner->id;
            $provider->company_name = $tag.' Provider '.$i;
            $provider->company_phone = $owner->phone;
            $provider->is_active = 1;
            $provider->save();
            $providerIds[] = $provider->id;

            $customer = User::query()->create([
                'id' => (string) Str::uuid(),
                'first_name' => 'Smoke',
                'last_name' => 'Customer '.$i,
                'email' => $tag.'-customer-'.$i.'@smoke.test',
                'phone' => '6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'password' => bcrypt('password'),
                'user_type' => 'customer',
                'is_active' => 1,
            ]);
            $customerIds[] = $customer->id;

            Booking::query()->create([
                'id' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'booking_status' => 'pending',
                'is_paid' => 0,
                'payment_method' => 'cash_after_service',
                'transaction_id' => null,
                'total_booking_amount' => 50,
                'total_tax_amount' => 0,
                'total_discount_amount' => 0,
                'total_campaign_discount_amount' => 0,
                'total_coupon_discount_amount' => 0,
                'additional_charge' => 0,
                'total_referral_discount_amount' => 0,
                'total_extra_fee' => 0,
                'extra_fee_amount' => 0,
                'service_schedule' => now(),
                'service_description' => $tag.' booking',
            ]);
        }

        return [$providerIds, $customerIds];
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new \RuntimeException($message);
        }
    }
}
