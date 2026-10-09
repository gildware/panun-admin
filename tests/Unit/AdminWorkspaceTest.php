<?php

namespace Tests\Unit;

use App\Support\AdminWorkspace;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AdminWorkspaceTest extends TestCase
{
    public function test_daily_pages_keep_their_workspace(): void
    {
        $this->assertSame(AdminWorkspace::HR, AdminWorkspace::detect(Request::create('/admin/hr', 'GET')));
        $this->assertSame(AdminWorkspace::HR, AdminWorkspace::detect(Request::create('/admin/people', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/my-progress', 'GET')));
        $this->assertSame(AdminWorkspace::TRAINING, AdminWorkspace::detect(Request::create('/admin/process-guides', 'GET')));
        $this->assertSame(AdminWorkspace::TRAINING, AdminWorkspace::detect(Request::create('/admin/dashboard/business-system', 'GET')));
        $this->assertSame(AdminWorkspace::HR, AdminWorkspace::detect(Request::create('/admin/dashboard/people', 'GET')));
        $this->assertSame(AdminWorkspace::HR, AdminWorkspace::detect(Request::create('/admin/dashboard/hr', 'GET')));
        $this->assertSame(AdminWorkspace::HR, AdminWorkspace::detect(Request::create('/admin/employee/list', 'GET')));
        $this->assertSame(AdminWorkspace::HR, AdminWorkspace::detect(Request::create('/admin/role/index', 'GET')));
        $this->assertSame(AdminWorkspace::TRAINING, AdminWorkspace::detect(Request::create('/admin/dashboard/training', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/dashboard', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/lead', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/booking/list', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/reports/booking', 'GET')));
    }

    public function test_more_pages_do_not_change_workspace(): void
    {
        $this->assertNull(AdminWorkspace::detect(Request::create('/admin/dashboard/operating-system', 'GET')));
        $this->assertFalse(AdminWorkspace::isMorePage(Request::create('/admin/dashboard/business-system', 'GET')));
        $this->assertNull(AdminWorkspace::detect(Request::create('/admin/catalog', 'GET')));
        $this->assertTrue(AdminWorkspace::isMorePage(Request::create('/admin/service/list', 'GET')));
    }

    public function test_marketing_settings_and_accounts_keep_their_workspace(): void
    {
        $this->assertSame(AdminWorkspace::MARKETING, AdminWorkspace::detect(Request::create('/admin/marketing', 'GET')));
        $this->assertSame(AdminWorkspace::MARKETING, AdminWorkspace::detect(Request::create('/admin/advertisements/ads-list', 'GET')));
        $this->assertSame(AdminWorkspace::MARKETING, AdminWorkspace::detect(Request::create('/admin/campaign/list', 'GET')));
        $this->assertSame(AdminWorkspace::MARKETING, AdminWorkspace::detect(Request::create('/admin/social-inbox/whatsapp/marketing/campaigns', 'GET')));
        $this->assertFalse(AdminWorkspace::isMorePage(Request::create('/admin/social-inbox/whatsapp/marketing/campaigns', 'GET')));

        $this->assertSame(AdminWorkspace::SETTINGS, AdminWorkspace::detect(Request::create('/admin/settings/business', 'GET')));
        $this->assertSame(AdminWorkspace::SETTINGS, AdminWorkspace::detect(Request::create('/admin/business-settings/get-business-information', 'GET')));
        $this->assertSame(AdminWorkspace::SETTINGS, AdminWorkspace::detect(Request::create('/admin/lead/configuration', 'GET')));
        $this->assertSame(AdminWorkspace::SETTINGS, AdminWorkspace::detect(Request::create('/admin/booking/configuration', 'GET')));
        $this->assertSame(AdminWorkspace::SETTINGS, AdminWorkspace::detect(Request::create('/admin/service-overview/defaults', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/lead', 'GET')));
        $this->assertSame(AdminWorkspace::OPERATIONS, AdminWorkspace::detect(Request::create('/admin/booking/list', 'GET')));

        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/accounts/payroll', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/accounts/attendance', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/accounts/salary', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/dashboard/finance', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/ledger', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/transaction/list', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/customer/wallet/report', 'GET')));
        $this->assertSame(AdminWorkspace::ACCOUNTS, AdminWorkspace::detect(Request::create('/admin/withdraw/request/list', 'GET')));
        $this->assertSame(AdminWorkspace::SETTINGS, AdminWorkspace::detect(Request::create('/admin/withdraw/method/list', 'GET')));
    }

    public function test_search_results_stay_inside_the_open_workspace(): void
    {
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/lead/list'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/lead/list'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::TRAINING, 'admin/booking/details/12'));

        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/employee/list'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/role/index'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::SETTINGS, 'admin/employee/list'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::ACCOUNTS, 'admin/accounts/payroll'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/people/leave'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/hr'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/my-progress'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/my-progress'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::TRAINING, 'admin/my-progress'));

        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/dashboard/people'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/dashboard/hr'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/dashboard/hr'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::TRAINING, 'admin/dashboard/training'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/dashboard/training'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::TRAINING, 'admin/process-guides'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::TRAINING, 'admin/dashboard/business-system'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/dashboard/business-system'));

        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::SETTINGS, 'admin/settings/business'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/settings/business'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::HR, 'admin/reports/booking'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::MARKETING, 'admin/marketing'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::TRAINING, 'admin/marketing'));
        $this->assertTrue(AdminWorkspace::uriBelongsTo(AdminWorkspace::ACCOUNTS, 'admin/ledger'));
        $this->assertFalse(AdminWorkspace::uriBelongsTo(AdminWorkspace::OPERATIONS, 'admin/ledger'));

        $filtered = AdminWorkspace::filterSearchGroups([
            'pages' => [
                ['uri' => 'admin/lead/list', 'page_title' => 'Leads'],
                ['uri' => 'admin/process-guides', 'page_title' => 'Process Guides'],
                ['uri' => 'admin/hr', 'page_title' => 'People'],
            ],
        ], AdminWorkspace::TRAINING);

        $this->assertSame(['admin/process-guides'], array_column($filtered['pages'], 'uri'));
    }
}
