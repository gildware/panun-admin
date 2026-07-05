<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\CustomerModule\Services\CustomerReferralEarningService;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class ReferralE2eTest extends Command
{
    protected $signature = 'referral:e2e {--keep-data : Leave seeded rows in the database (default: rollback transaction)}';

    protected $description = 'E2E test: referral registration, pending reward, referral-earning API, and wallet release on first booking';

    private ?User $referrer = null;

    private ?User $referee = null;

    private ?Booking $booking = null;

    private float $rewardAmount = 0.0;

    private float $referrerPendingBefore = 0.0;

    private float $referrerWalletBefore = 0.0;

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production.');

            return self::FAILURE;
        }

        $tag = 'referral-e2e-'.Str::lower(Str::random(6));
        $this->info("Referral E2E [{$tag}]");

        $shouldKeep = $this->option('keep-data');
        $passed = 0;
        $failed = 0;

        if (! $shouldKeep) {
            DB::beginTransaction();
        }

        try {
            $scenarios = [
                'referral_config_enabled' => fn () => $this->testReferralConfigEnabled(),
                'customer_config_api_exposes_referral' => fn () => $this->testCustomerConfigApi(),
                'invalid_referral_code_rejected' => fn () => $this->testInvalidReferralCodeRejected(),
                'registration_with_valid_referral_code' => fn () => $this->testRegistrationWithValidReferralCode($tag),
                'referrer_pending_balance_increases' => fn () => $this->testReferrerPendingBalanceIncreases(),
                'referral_earning_api_lists_pending_referee' => fn () => $this->testReferralEarningApiListsPendingReferee(),
                'first_booking_completion_releases_wallet' => fn () => $this->testFirstBookingCompletionReleasesWallet(),
                'referral_earning_api_shows_completed_reward' => fn () => $this->testReferralEarningApiShowsCompletedReward(),
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

            if (! $shouldKeep) {
                DB::rollBack();
                $this->line('');
                $this->info('E2E transaction rolled back.');
            } else {
                DB::commit();
                $this->line('');
                $this->info('E2E data left in database (--keep-data).');
            }

            $this->line('');
            $this->info("Passed: {$passed} | Failed: {$failed}");

            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            if (! $shouldKeep) {
                DB::rollBack();
            }
            $this->error('E2E aborted: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    private function testReferralConfigEnabled(): string
    {
        /** @var CustomerReferralEarningService $service */
        $service = app(CustomerReferralEarningService::class);

        if (! $service->isEnabled()) {
            throw new \RuntimeException('customer_referral_earning is disabled in business settings.');
        }

        $this->rewardAmount = $service->referralRewardAmount();
        if ($this->rewardAmount <= 0) {
            throw new \RuntimeException('referral_value_per_currency_unit must be greater than 0.');
        }

        return "enabled, reward={$this->rewardAmount}";
    }

    private function testCustomerConfigApi(): string
    {
        $response = $this->dispatchApiRequest('GET', '/api/v1/customer/config/', []);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Config API HTTP '.$response->getStatusCode());
        }

        $payload = json_decode($response->getContent(), true);
        $status = (int) ($payload['content']['referral_earning_status'] ?? 0);
        if ($status !== 1) {
            throw new \RuntimeException('Config API referral_earning_status is not 1.');
        }

        return 'referral_earning_status=1';
    }

    private function testInvalidReferralCodeRejected(): string
    {
        $phone = '8'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $response = $this->dispatchApiRequest('POST', '/api/v1/user/verification/registration-with-otp', [
            'first_name' => 'Invalid',
            'last_name' => 'Referral',
            'phone' => $phone,
            'referral_code' => 'INVALIDCODE123',
        ]);

        if ($response->getStatusCode() !== 404) {
            throw new \RuntimeException('Expected HTTP 404, got '.$response->getStatusCode());
        }

        $payload = json_decode($response->getContent(), true);
        if (($payload['response_code'] ?? '') !== 'referral_code_400') {
            throw new \RuntimeException('Expected referral_code_400 response code.');
        }

        return 'invalid code rejected with referral_code_400';
    }

    private function testRegistrationWithValidReferralCode(string $tag): string
    {
        $this->referrer = User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'E2E',
            'last_name' => 'Referrer',
            'email' => $tag.'-referrer@referral-e2e.test',
            'phone' => '6'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'user_type' => 'customer',
            'is_active' => 1,
            'is_phone_verified' => 1,
            'customer_app_access' => true,
            'current_language_key' => 'en',
        ])->fresh();

        $this->referrerPendingBefore = (float) ($this->referrer->account?->balance_pending ?? 0);
        $this->referrerWalletBefore = (float) ($this->referrer->wallet_balance ?? 0);

        $phone = '8'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $response = $this->dispatchApiRequest('POST', '/api/v1/user/verification/registration-with-otp', [
            'first_name' => 'E2E',
            'last_name' => 'Referee',
            'email' => $tag.'-referee@referral-e2e.test',
            'phone' => $phone,
            'referral_code' => $this->referrer->ref_code,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Registration HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        $this->referee = User::query()
            ->where('phone', 'like', '%'.substr($phone, -10))
            ->where('user_type', 'customer')
            ->latest('created_at')
            ->first();

        if (! $this->referee) {
            throw new \RuntimeException('Referee user was not created.');
        }

        if ((string) $this->referee->referred_by !== (string) $this->referrer->id) {
            throw new \RuntimeException('Referee referred_by does not match referrer.');
        }

        return "referee={$this->referee->id} linked to code {$this->referrer->ref_code}";
    }

    private function testReferrerPendingBalanceIncreases(): string
    {
        $this->assertReferrerLoaded();

        $pending = (float) Account::query()->where('user_id', $this->referrer->id)->value('balance_pending');
        $expected = $this->referrerPendingBefore + $this->rewardAmount;

        if (round($pending, 2) !== round($expected, 2)) {
            throw new \RuntimeException("Expected pending {$expected}, got {$pending}");
        }

        $txnCount = Transaction::query()
            ->where('to_user_id', $this->referrer->id)
            ->where('trx_type', TRX_TYPE['referral_earning'])
            ->where('credit', '>', 0)
            ->count();

        if ($txnCount < 1) {
            throw new \RuntimeException('Expected referral_earning credit transaction on registration.');
        }

        return "pending balance +{$this->rewardAmount}";
    }

    private function testReferralEarningApiListsPendingReferee(): string
    {
        $this->assertReferrerLoaded();
        $this->assertRefereeLoaded();

        $response = $this->dispatchApiRequest(
            'GET',
            '/api/v1/customer/referral-earning?limit=10&offset=1',
            [],
            $this->referrer->createToken(CUSTOMER_PANEL_ACCESS)->accessToken
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Referral earning API HTTP '.$response->getStatusCode());
        }

        $payload = json_decode($response->getContent(), true);
        $content = $payload['content'] ?? [];

        if ((int) ($content['total_referred'] ?? 0) < 1) {
            throw new \RuntimeException('API total_referred is 0.');
        }

        if ((int) ($content['pending_first_booking'] ?? 0) < 1) {
            throw new \RuntimeException('API pending_first_booking is 0.');
        }

        $users = $content['referred_users']['data'] ?? [];
        $match = collect($users)->firstWhere('id', $this->referee->id);
        if (! $match) {
            throw new \RuntimeException('Referee not found in referred_users list.');
        }

        if (($match['has_completed_first_booking'] ?? true) === true) {
            throw new \RuntimeException('Referee should still be pending first booking.');
        }

        return 'referee listed with pending reward';
    }

    private function testFirstBookingCompletionReleasesWallet(): string
    {
        $this->assertReferrerLoaded();
        $this->assertRefereeLoaded();

        $zone = Zone::query()->first();
        if (! $zone) {
            throw new \RuntimeException('No zone found for booking seed.');
        }

        $this->booking = Booking::query()->create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->referee->id,
            'zone_id' => $zone->id,
            'booking_status' => 'accepted',
            'is_paid' => 0,
            'payment_method' => 'cash_after_service',
            'total_booking_amount' => 100,
            'total_tax_amount' => 0,
            'total_discount_amount' => 0,
            'service_schedule' => now()->addDay(),
            'service_description' => 'referral-e2e booking',
            'allow_complete_without_full_payment' => 1,
            'is_guest' => 0,
        ]);

        $this->booking->booking_status = 'completed';
        $this->booking->save();

        $this->referrer->refresh();
        $expectedWallet = $this->referrerWalletBefore + $this->rewardAmount;
        if (round((float) $this->referrer->wallet_balance, 2) !== round($expectedWallet, 2)) {
            throw new \RuntimeException(
                'Expected wallet '.$expectedWallet.', got '.$this->referrer->wallet_balance
            );
        }

        $pending = (float) Account::query()->where('user_id', $this->referrer->id)->value('balance_pending');
        if (round($pending, 2) !== round($this->referrerPendingBefore, 2)) {
            throw new \RuntimeException(
                'Expected pending to return to '.$this->referrerPendingBefore.', got '.$pending
            );
        }

        return "wallet credited +{$this->rewardAmount}, pending restored";
    }

    private function testReferralEarningApiShowsCompletedReward(): string
    {
        $this->assertReferrerLoaded();
        $this->assertRefereeLoaded();

        $response = $this->dispatchApiRequest(
            'GET',
            '/api/v1/customer/referral-earning?limit=10&offset=1',
            [],
            $this->referrer->createToken(CUSTOMER_PANEL_ACCESS)->accessToken
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Referral earning API HTTP '.$response->getStatusCode());
        }

        $payload = json_decode($response->getContent(), true);
        $content = $payload['content'] ?? [];

        if ((int) ($content['completed_first_booking'] ?? 0) < 1) {
            throw new \RuntimeException('API completed_first_booking is 0.');
        }

        if ((float) ($content['total_earned'] ?? 0) < $this->rewardAmount) {
            throw new \RuntimeException('API total_earned is lower than reward amount.');
        }

        $users = $content['referred_users']['data'] ?? [];
        $match = collect($users)->firstWhere('id', $this->referee->id);
        if (! $match || ($match['has_completed_first_booking'] ?? false) !== true) {
            throw new \RuntimeException('Referee not marked completed in referred_users list.');
        }

        if ((float) ($match['earned_amount'] ?? 0) < $this->rewardAmount) {
            throw new \RuntimeException('Referee earned_amount not updated.');
        }

        return 'completed referral reflected in API summary and list';
    }

    private function dispatchApiRequest(string $method, string $uri, array $payload, ?string $token = null): \Symfony\Component\HttpFoundation\Response
    {
        auth()->forgetGuards();

        $server = [
            'HTTP_Accept' => 'application/json',
            'HTTP_Content-Type' => 'application/json',
        ];

        if ($token) {
            $server['HTTP_Authorization'] = 'Bearer '.$token;
        }

        $kernel = app(Kernel::class);
        $request = Request::create($uri, $method, $payload, [], [], $server);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        auth()->forgetGuards();

        return $response;
    }

    private function assertReferrerLoaded(): void
    {
        if (! $this->referrer) {
            throw new \RuntimeException('Referrer not seeded — prior scenario failed.');
        }
    }

    private function assertRefereeLoaded(): void
    {
        if (! $this->referee) {
            throw new \RuntimeException('Referee not seeded — prior scenario failed.');
        }
    }
}
