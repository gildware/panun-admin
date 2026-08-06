<?php

namespace Tests\Unit;

use Modules\AdminModule\Services\ImpersonationService;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class ImpersonationServiceTest extends TestCase
{
    public function test_can_start_only_for_super_admin_not_already_impersonating(): void
    {
        $service = app(ImpersonationService::class);

        $superAdmin = new User(['user_type' => 'super-admin']);
        $employee = new User(['user_type' => 'admin-employee']);

        $this->assertTrue($service->canStart($superAdmin));
        $this->assertFalse($service->canStart($employee));
    }

    public function test_is_active_tracks_session_keys(): void
    {
        $service = app(ImpersonationService::class);

        $this->assertFalse($service->isActive());

        session([
            ImpersonationService::SESSION_FLAG => true,
            ImpersonationService::SESSION_IMPERSONATOR_ID => 'admin-id',
        ]);

        $this->assertTrue($service->isActive());
    }
}
