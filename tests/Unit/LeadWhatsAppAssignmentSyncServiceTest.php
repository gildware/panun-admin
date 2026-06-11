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
}
