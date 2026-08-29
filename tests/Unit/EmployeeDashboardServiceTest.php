<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Gate;
use Modules\AdminModule\Services\EmployeeDashboardService;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class EmployeeDashboardServiceTest extends TestCase
{
    public function test_demo_employee_can_access_dashboard_gate(): void
    {
        try {
            $user = User::query()->where('email', 'employee.demo@panunkaergar.com')->with('roles')->first();
        } catch (\Illuminate\Database\QueryException) {
            $this->markTestSkipped('Requires application database with seeded demo employee.');
        }

        if (! $user) {
            $this->markTestSkipped('Demo employee user not seeded.');
        }

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('dashboard'));
        $this->assertTrue(Gate::allows('booking_view'));
        $this->assertFalse(Gate::allows('role_view'));
        $this->assertFalse(Gate::allows('transaction_view'));
        $this->assertTrue(Gate::allows('whatsapp_chat_view'));
        $this->assertTrue(Gate::allows('whatsapp_chat_reply'));
        $this->assertTrue(Gate::allows('whatsapp_chat_assign'));
    }

    public function test_employee_dashboard_service_returns_expected_structure(): void
    {
        try {
            $user = User::query()->where('email', 'employee.demo@panunkaergar.com')->first();
        } catch (\Illuminate\Database\QueryException) {
            $this->markTestSkipped('Requires application database with seeded demo employee.');
        }

        if (! $user) {
            $this->markTestSkipped('Demo employee user not seeded.');
        }

        $data = app(EmployeeDashboardService::class)->build($user);

        $this->assertArrayHasKey('greeting', $data);
        $this->assertArrayHasKey('focus_line', $data);
        $this->assertArrayHasKey('focus_all_clear', $data);
        $this->assertIsBool($data['focus_all_clear']);
        $this->assertArrayHasKey('work_queue', $data);
        $this->assertArrayHasKey('pending', $data['work_queue']);
        $this->assertArrayHasKey('pickup', $data['work_queue']);
        $this->assertCount(3, $data['work_queue']['pending']['widgets']);
        $this->assertCount(4, $data['work_queue']['pickup']['widgets']);
        $this->assertSame('pending_tasks', $data['work_queue']['pending']['widgets'][2]['key']);
        $this->assertSame('whatsapp_unassigned', $data['work_queue']['pickup']['widgets'][2]['key']);
        $this->assertSame('whatsapp_assigned_unread', $data['work_queue']['pickup']['widgets'][3]['key']);
        $this->assertCount(3, $data['work_queue']['pending']['boxes']);
        $this->assertCount(4, $data['work_queue']['pickup']['boxes']);
        $this->assertSame('whatsapp_unassigned', $data['work_queue']['pickup']['boxes'][2]['key']);
        $this->assertSame('whatsapp_assigned_unread', $data['work_queue']['pickup']['boxes'][3]['key']);
        $this->assertSame('pending_tasks', $data['work_queue']['pending']['boxes'][2]['key']);
        $this->assertArrayHasKey('tabs', $data['work_queue']['pending']['boxes'][0]);
        $this->assertArrayHasKey('yours', $data['work_queue']['pending']['boxes'][0]['tabs']);
        $this->assertArrayHasKey('all', $data['work_queue']['pending']['boxes'][0]['tabs']);
        $this->assertArrayHasKey('all', $data['work_queue']['pending']['boxes'][1]['tabs']);
        $this->assertArrayHasKey('columns', $data['work_queue']['pending']['boxes'][0]);
        $this->assertArrayHasKey('layout_slot', $data['work_queue']['pending']['boxes'][2]);
        $this->assertSame('side', $data['work_queue']['pending']['boxes'][2]['layout_slot']);
        $this->assertSame('cards', $data['work_queue']['pending']['boxes'][2]['list_display']);
        $this->assertArrayHasKey('today_done', $data);
        $this->assertArrayHasKey('items', $data['today_done']);
        $this->assertArrayHasKey('total', $data['today_done']);
        $this->assertArrayHasKey('monthly', $data);
        $this->assertArrayHasKey('stats', $data['monthly']);
        $todayKeys = array_column($data['today_done']['items'], 'key');
        $monthKeys = array_column($data['monthly']['stats'], 'key');
        $this->assertSame($todayKeys, $monthKeys);
        $this->assertCount(11, $todayKeys);
        $this->assertArrayHasKey('contribution_vs_all', $data);
        $this->assertIsArray($data['contribution_vs_all']);
        $this->assertArrayHasKey('today', $data['contribution_vs_all']);
        $this->assertArrayHasKey('monthly', $data['contribution_vs_all']);
        $this->assertIsArray($data['contribution_vs_all']['today']);
        $this->assertIsArray($data['contribution_vs_all']['monthly']);
        $this->assertArrayHasKey('leaderboard', $data);
        $this->assertArrayHasKey('rank_marks_chart', $data);
        $this->assertSame([], $data['rank_marks_chart']['series'] ?? null);

        $this->assertArrayNotHasKey('pulse', $data);
        $this->assertArrayNotHasKey('progress_scopes', $data);
        $this->assertArrayNotHasKey('attention_widgets', $data);
        $this->assertArrayNotHasKey('priority_followup_boxes', $data);
        $this->assertArrayNotHasKey('lead_followups', $data);
        $this->assertArrayNotHasKey('tasks', $data);
    }

    public function test_admin_work_dashboard_only_builds_team_progress_scope(): void
    {
        try {
            $user = User::query()->where('user_type', 'super-admin')->first();
        } catch (\Illuminate\Database\QueryException) {
            $this->markTestSkipped('Requires application database with a super-admin user.');
        }

        if (! $user) {
            $this->markTestSkipped('Super-admin user not seeded.');
        }

        $data = app(EmployeeDashboardService::class)->build($user);

        $this->assertArrayHasKey('progress_scopes', $data);
        $this->assertSame(['__all__'], array_keys($data['progress_scopes']));
        $this->assertArrayHasKey('work_queue', $data);
        $this->assertSame([], $data['progress_scopes']['__all__']['rank_marks_chart']['series'] ?? null);
    }
}
