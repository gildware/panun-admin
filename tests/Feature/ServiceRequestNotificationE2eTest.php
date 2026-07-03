<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ServiceRequestNotificationE2eTest extends TestCase
{
    public function test_service_request_notification_e2e_command_passes(): void
    {
        $dbConnection = $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: '';
        $dbDatabase = $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '';

        if ($dbConnection === 'sqlite' || $dbDatabase === ':memory:') {
            $this->markTestSkipped(
                'Full E2E requires MySQL. Run: php artisan service-request:notifications-e2e'
            );
        }

        $exitCode = Artisan::call('service-request:notifications-e2e');

        $this->assertSame(0, $exitCode, trim(Artisan::output()));
    }
}
