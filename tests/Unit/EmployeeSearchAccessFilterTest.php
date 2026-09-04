<?php

namespace Tests\Unit;

use App\Support\EmployeeSearchAccessFilter;
use Illuminate\Support\Facades\Gate;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class EmployeeSearchAccessFilterTest extends TestCase
{
    private function demoEmployee(): ?User
    {
        try {
            return User::query()
                ->where('email', 'employee.demo@panunkaergar.com')
                ->with('roles')
                ->first();
        } catch (\Illuminate\Database\QueryException) {
            $this->markTestSkipped('Requires application database with seeded demo employee.');
        }
    }

    public function test_filter_does_not_apply_to_super_admin(): void
    {
        $filter = app(EmployeeSearchAccessFilter::class);

        $this->assertFalse($filter->applies());
        $this->assertTrue($filter->isAllowed('admin/business-settings/get-business-information'));
    }

    public function test_demo_employee_search_is_limited_to_allowed_sections(): void
    {
        $user = $this->demoEmployee();

        if (! $user) {
            $this->markTestSkipped('Demo employee user not seeded.');
        }

        $this->actingAs($user);
        $filter = app(EmployeeSearchAccessFilter::class);

        $this->assertTrue($filter->applies());
        $this->assertTrue($filter->isAllowed('admin/dashboard'));
        $this->assertTrue($filter->isAllowed('admin/booking/list?booking_status=all&service_type=all'));
        $this->assertTrue($filter->isAllowed('admin/customer/list'));
        $this->assertTrue($filter->isAllowed('admin/lead?handled_by[]=__unassigned__'));
        $this->assertTrue($filter->isAllowed('admin/my-progress?tab=daily'));
        $this->assertTrue($filter->isAllowed('admin/provider/list?status=all'));
        $this->assertTrue($filter->isAllowed('admin/provider/live-view'));
        $this->assertTrue($filter->isAllowed('admin/provider/create'));
        $this->assertTrue($filter->isAllowed('admin/provider/onboarding-request?status=onboarding'));
        $this->assertTrue($filter->isAllowed('admin/provider/showcase-approval?status=pending'));
        $this->assertTrue($filter->isAllowed('admin/provider/profile-change-request?status=pending'));

        $this->assertFalse($filter->isAllowed('admin/business-settings/get-business-information'));
        $this->assertFalse($filter->isAllowed('admin/employee/list'));
        $this->assertFalse($filter->isAllowed('admin/transaction/list?trx_type=all'));
        $this->assertFalse($filter->isAllowed('admin/discount/list'));
    }

    public function test_filter_grouped_results_removes_disallowed_items(): void
    {
        $user = $this->demoEmployee();

        if (! $user) {
            $this->markTestSkipped('Demo employee user not seeded.');
        }

        $this->actingAs($user);
        $filter = app(EmployeeSearchAccessFilter::class);

        $grouped = [
            'menu' => [
                ['uri' => 'admin/dashboard', 'page_title' => 'Dashboard'],
                ['uri' => 'admin/business-settings/get-business-information', 'page_title' => 'Settings'],
            ],
            'page' => [
                ['uri' => 'admin/customer/list', 'page_title' => 'Customers'],
                ['uri' => 'admin/role/index', 'page_title' => 'Roles'],
            ],
        ];

        $filtered = $filter->filterGroupedResults($grouped);

        $this->assertSame(['admin/dashboard'], array_column($filtered['menu'] ?? [], 'uri'));
        $this->assertSame(['admin/customer/list'], array_column($filtered['page'] ?? [], 'uri'));
        $this->assertFalse(Gate::allows('role_view'));
    }
}
