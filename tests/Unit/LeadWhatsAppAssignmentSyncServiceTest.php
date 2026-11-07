<?php

namespace Tests\Unit;

use Modules\LeadManagement\Entities\Lead;
use Modules\WhatsAppModule\Services\LeadWhatsAppAssignmentSyncService;
use Tests\TestCase;

class LeadWhatsAppAssignmentSyncServiceTest extends TestCase
{
    public function test_chat_handler_for_human_lead_assignee(): void
    {
        $this->assertSame(
            'emp-uuid-1',
            LeadWhatsAppAssignmentSyncService::chatHandlerForLead('emp-uuid-1')
        );
    }

    public function test_chat_handler_for_ai_or_unassigned_lead(): void
    {
        $this->assertSame(Lead::HANDLED_BY_AI, LeadWhatsAppAssignmentSyncService::chatHandlerForLead(null));
        $this->assertSame(Lead::HANDLED_BY_AI, LeadWhatsAppAssignmentSyncService::chatHandlerForLead(''));
        $this->assertSame(Lead::HANDLED_BY_AI, LeadWhatsAppAssignmentSyncService::chatHandlerForLead(Lead::HANDLED_BY_AI));
    }

    public function test_assignee_is_human_rejects_int_cast_zero(): void
    {
        $this->assertFalse(Lead::assigneeIsHuman(null));
        $this->assertFalse(Lead::assigneeIsHuman(''));
        $this->assertFalse(Lead::assigneeIsHuman('0'));
        $this->assertFalse(Lead::assigneeIsHuman(Lead::HANDLED_BY_AI));
        $this->assertTrue(Lead::assigneeIsHuman('emp-uuid-1'));
    }
}
