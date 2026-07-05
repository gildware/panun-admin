<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReferralE2eTest extends TestCase
{
    public function test_referral_e2e_command_passes(): void
    {
        $dbConnection = $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: '';
        $dbDatabase = $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '';

        if ($dbConnection === 'sqlite' || $dbDatabase === ':memory:') {
            $this->markTestSkipped(
                'Full referral E2E requires MySQL. Run: php artisan referral:e2e'
            );
        }

        $exitCode = Artisan::call('referral:e2e');

        $this->assertSame(0, $exitCode, trim(Artisan::output()));
    }
}
