<?php

namespace Tests\Unit;

use App\Support\AdminWorkspace;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class AdminWorkspaceEmployeeAccessTest extends TestCase
{
    public function test_employee_role_cannot_open_settings_or_accounts(): void
    {
        $this->actingAs($this->userWithRole('admin-employee', 'Employee'));

        $this->assertTrue(AdminWorkspace::isOperationsEmployee());
        $this->assertFalse(AdminWorkspace::userCanEnter(AdminWorkspace::SETTINGS));
        $this->assertFalse(AdminWorkspace::userCanEnter(AdminWorkspace::ACCOUNTS));
        $this->assertTrue(AdminWorkspace::userCanEnter(AdminWorkspace::HR));
        $this->assertTrue(AdminWorkspace::userCanEnter(AdminWorkspace::TRAINING));
    }

    public function test_super_admin_can_still_open_accounts(): void
    {
        $this->actingAs($this->userWithRole('super-admin', 'Super admin'));

        $this->assertFalse(AdminWorkspace::isOperationsEmployee());
        $this->assertTrue(AdminWorkspace::userCanEnter(AdminWorkspace::ACCOUNTS));
    }

    public function test_accounts_manager_is_not_treated_as_an_operations_employee(): void
    {
        $this->actingAs($this->userWithRole('admin-employee', 'Accounts Manager'));

        $this->assertFalse(AdminWorkspace::isOperationsEmployee());
    }

    private function userWithRole(string $userType, string $roleName): User
    {
        $role = new Role();
        $role->role_name = $roleName;

        $user = new User([
            'id' => 'user-'.$roleName,
            'user_type' => $userType,
        ]);
        $user->setRelation('roles', collect([$role]));

        return $user;
    }
}
