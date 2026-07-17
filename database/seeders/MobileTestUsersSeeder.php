<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Dedicated Customer + Provider app accounts for Apple / Play Store review OTP login.
 *
 * These phones always use OTP 123456 (see APPLE_REVIEW_OTP_PHONES), even when
 * USE_DUMMY_OTP=false. Other numbers still require real SMS when dummy OTP is off.
 *
 * Run: php artisan db:seed --class=MobileTestUsersSeeder
 *
 * Customer app phone: +919999000001
 * Provider app phone: +919999000002
 * OTP (both):         123456
 */
class MobileTestUsersSeeder extends Seeder
{
    public const CUSTOMER_PHONE = '+919999000001';
    public const PROVIDER_PHONE = '+919999000002';
    public const OTP_HINT = '123456';

    public function run(): void
    {
        if (!Schema::hasTable('users')) {
            $this->command?->warn('users table missing; skip mobile test users.');

            return;
        }

        $customer = $this->ensureCustomer();
        $providerOwner = $this->ensureProvider();

        $this->command?->info('Mobile Apple-review test users ready:');
        $this->command?->info('  Customer app → phone '.self::CUSTOMER_PHONE.'  OTP '.self::OTP_HINT.'  ('.$customer->first_name.' '.$customer->last_name.')');
        $this->command?->info('  Provider app → phone '.self::PROVIDER_PHONE.'  OTP '.self::OTP_HINT.'  ('.$providerOwner->first_name.' '.$providerOwner->last_name.')');
        $this->command?->info('  These phones always use OTP '.self::OTP_HINT.' even when USE_DUMMY_OTP=false.');
    }

    private function ensureCustomer(): User
    {
        $user = User::withTrashed()
            ->where('user_type', 'customer')
            ->where('phone', self::CUSTOMER_PHONE)
            ->first();

        if (!$user) {
            $user = new User;
            $user->id = (string) Str::uuid();
            $user->user_type = 'customer';
            $user->phone = self::CUSTOMER_PHONE;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->first_name = 'Test';
        $user->last_name = 'Customer';
        $user->email = 'test.customer@panunkaergar.local';
        $user->password = Hash::make('12345678');
        $user->is_active = 1;
        $user->is_phone_verified = 1;
        $user->phone_verified_at = now();
        $user->customer_app_access = 1;
        $user->current_language_key = 'en';
        $user->save();

        return $user->fresh();
    }

    private function ensureProvider(): User
    {
        $owner = User::withTrashed()
            ->where('user_type', 'provider-admin')
            ->where('phone', self::PROVIDER_PHONE)
            ->first();

        if (!$owner) {
            $owner = new User;
            $owner->id = (string) Str::uuid();
            $owner->user_type = 'provider-admin';
            $owner->phone = self::PROVIDER_PHONE;
        }

        if ($owner->trashed()) {
            $owner->restore();
        }

        $owner->first_name = 'Test';
        $owner->last_name = 'Provider';
        $owner->email = 'test.provider@panunkaergar.local';
        $owner->password = Hash::make('12345678');
        $owner->is_active = 1;
        $owner->is_phone_verified = 1;
        $owner->phone_verified_at = now();
        $owner->customer_app_access = 0;
        $owner->current_language_key = 'en';
        $owner->save();

        $zoneId = Zone::query()->ofStatus(1)->orderBy('name')->value('id');

        $provider = Provider::withTrashed()->where('user_id', $owner->id)->first();
        if (!$provider) {
            $provider = new Provider;
            $provider->id = (string) Str::uuid();
            $provider->user_id = $owner->id;
        }

        if ($provider->trashed()) {
            $provider->restore();
        }

        $provider->provider_type = 'individual';
        $provider->company_name = 'Test Provider Co';
        $provider->company_phone = self::PROVIDER_PHONE;
        $provider->company_email = 'test.provider@panunkaergar.local';
        $provider->company_address = 'Test Address';
        $provider->contact_person_name = 'Test Provider';
        $provider->contact_person_phone = self::PROVIDER_PHONE;
        $provider->contact_person_email = 'test.provider@panunkaergar.local';
        $provider->is_active = 1;
        $provider->is_approved = 1;
        $provider->is_suspended = 0;
        $provider->service_availability = 1;
        $provider->app_availability = 1;
        $provider->is_active_for_jobs = 1;
        $provider->performance_status = 'active';
        if ($zoneId) {
            $provider->zone_id = $zoneId;
        }
        $provider->save();

        if ($zoneId && Schema::hasTable('provider_zones')) {
            $exists = DB::table('provider_zones')
                ->where('provider_id', $provider->id)
                ->where('zone_id', $zoneId)
                ->exists();

            if (!$exists) {
                DB::table('provider_zones')->insert([
                    'provider_id' => $provider->id,
                    'zone_id' => $zoneId,
                ]);
            }
        }

        return $owner->fresh(['provider']);
    }
}
