<?php

namespace Tests\Unit;

use Tests\TestCase;

class SupportInboxHelpersTest extends TestCase
{
    public function test_support_inbox_peer_prefers_customer_over_other_admins(): void
    {
        $admin = (object) ['user_id' => 'admin-1', 'user' => (object) ['user_type' => 'super-admin']];
        $employee = (object) ['user_id' => 'emp-1', 'user' => (object) ['user_type' => 'admin-employee']];
        $customer = (object) ['user_id' => 'cust-1', 'user' => (object) ['user_type' => 'customer']];

        $peer = support_inbox_peer_channel_user([$admin, $employee, $customer], 'emp-1');

        $this->assertSame('cust-1', $peer->user_id);
    }

    public function test_support_inbox_peer_prefers_provider(): void
    {
        $admin = (object) ['user_id' => 'admin-1', 'user' => (object) ['user_type' => 'super-admin']];
        $provider = (object) ['user_id' => 'prov-1', 'user' => (object) ['user_type' => 'provider-admin']];

        $peer = support_inbox_peer_channel_user([$admin, $provider], 'admin-1');

        $this->assertSame('prov-1', $peer->user_id);
    }

    public function test_staff_direct_channel_rejects_support_threads_with_other_admins(): void
    {
        $admin = (object) ['user_id' => 'admin-1', 'user' => (object) ['user_type' => 'super-admin']];
        $employee = (object) ['user_id' => 'emp-1', 'user' => (object) ['user_type' => 'admin-employee']];
        $customer = (object) ['user_id' => 'cust-1', 'user' => (object) ['user_type' => 'customer']];

        $this->assertFalse(is_staff_direct_channel('support_customer', [$admin, $employee, $customer]));
        $this->assertFalse(is_staff_direct_channel('staff_group', [$admin, $employee]));
        $this->assertTrue(is_staff_direct_channel(null, [$admin, $employee]));
    }
}
