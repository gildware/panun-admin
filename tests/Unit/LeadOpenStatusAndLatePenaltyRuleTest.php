<?php

namespace Tests\Unit;

use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Tests\TestCase;

/**
 * Scoring skips late lead follow-up penalties when the lead is not open.
 * These cases lock the open/closed rule that EmployeeProgressScoreService uses.
 */
class LeadOpenStatusAndLatePenaltyRuleTest extends TestCase
{
    private LeadOpenStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeadOpenStatusService;
    }

    public function test_booked_customer_lead_is_closed_so_late_penalty_should_not_apply(): void
    {
        $lead = new Lead([
            'id' => 3339,
            'lead_type' => Lead::TYPE_CUSTOMER,
            'non_responsive_at' => null,
        ]);
        $lead->syncOriginal();

        $status = new CustomerLeadStatus([
            'id' => 3,
            'name' => 'Booked',
            'base_type' => 'completed',
        ]);
        $status->id = 3;

        $history = new LeadTypeHistory([
            'lead_id' => 3339,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => ['customer_lead_status_id' => 3],
        ]);

        $isOpen = $this->service->isLeadOpenByTypeHistory(
            $lead,
            $history,
            collect([3 => $status])
        );

        $this->assertFalse($isOpen, 'Booked/closed leads must be excluded from late follow-up penalties');
    }

    public function test_pending_customer_lead_is_open_so_late_penalty_can_apply(): void
    {
        $lead = new Lead([
            'id' => 100,
            'lead_type' => Lead::TYPE_CUSTOMER,
            'non_responsive_at' => null,
        ]);
        $lead->syncOriginal();

        $status = new CustomerLeadStatus([
            'id' => 1,
            'name' => 'Pending',
            'base_type' => 'pending',
        ]);
        $status->id = 1;

        $history = new LeadTypeHistory([
            'lead_id' => 100,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => ['customer_lead_status_id' => 1],
        ]);

        $isOpen = $this->service->isLeadOpenByTypeHistory(
            $lead,
            $history,
            collect([1 => $status])
        );

        $this->assertTrue($isOpen);
    }

    public function test_cancelled_customer_lead_is_closed(): void
    {
        $lead = new Lead([
            'id' => 101,
            'lead_type' => Lead::TYPE_CUSTOMER,
            'non_responsive_at' => null,
        ]);
        $lead->syncOriginal();

        $status = new CustomerLeadStatus([
            'id' => 2,
            'name' => 'Cancelled',
            'base_type' => 'cancel',
        ]);
        $status->id = 2;

        $history = new LeadTypeHistory([
            'lead_id' => 101,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => ['customer_lead_status_id' => 2],
        ]);

        $this->assertFalse($this->service->isLeadOpenByTypeHistory(
            $lead,
            $history,
            collect([2 => $status])
        ));
    }

    public function test_only_open_leads_can_receive_the_next_call(): void
    {
        $this->assertTrue(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_CUSTOMER, false, 'pending'));
        $this->assertTrue(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_UNKNOWN, false, null));
        $this->assertFalse(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_CUSTOMER, false, 'completed'));
        $this->assertFalse(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_CUSTOMER, false, 'booked'));
        $this->assertFalse(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_CUSTOMER, false, 'cancel'));
        $this->assertFalse(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_INVALID, false, null));
        $this->assertFalse(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_FUTURE_CUSTOMER, false, null));
        $this->assertFalse(LeadOpenStatusService::canReuseLeadForCall(Lead::TYPE_UNKNOWN, true, null));
    }
}
