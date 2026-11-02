<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeadOtherPhoneLinkTest extends TestCase
{
    public function test_modal_markup_matches_standard_lead_form_classes(): void
    {
        $path = base_path('Modules/LeadManagement/Resources/views/admin/leads/partials/_other-phone-link-modal.blade.php');
        $html = file_get_contents($path);
        $this->assertStringContainsString('modal-header border-0', $html);
        $this->assertStringContainsString('modal-body pt-0', $html);
        $this->assertStringContainsString('form-check-input', $html);
        $this->assertStringContainsString('form-check-label', $html);
        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('btn btn--secondary', $html);
        $this->assertStringContainsString('btn btn--primary', $html);
        $this->assertStringContainsString('id="other-phone-owner-type"', $html);
        $this->assertStringContainsString('other_phone_search_type', $html);
        $this->assertStringContainsString('exclude_lead_id', $html);
        $this->assertStringContainsString('Other_phone_search_includes_leads_hint', $html);
        $this->assertStringContainsString('Search_by_name_phone_or_lead_id', $html);
        $this->assertStringContainsString('customer_lead', $html);
        $this->assertStringNotContainsString('btn-check', $html);
        $this->assertStringNotContainsString('other-phone-step', $html);
        $this->assertStringNotContainsString('form-select-lg', $html);
    }

    public function test_search_and_link_support_customer_leads_by_id(): void
    {
        $controller = file_get_contents(base_path('Modules/LeadManagement/Http/Controllers/Web/Admin/LeadOtherPhoneLinkController.php'));
        $this->assertStringContainsString('OWNER_CUSTOMER_LEAD', $controller);
        $this->assertStringContainsString('leadIdTerm', $controller);
        $this->assertStringContainsString("orWhere('id', \$leadIdTerm)", $controller);
        $this->assertStringContainsString('customer,provider,provider_lead,customer_lead', $controller);
        $this->assertStringContainsString('finishMergeIntoLinkedLead', $controller);

        $entity = file_get_contents(base_path('Modules/LeadManagement/Entities/PartyAlternatePhone.php'));
        $this->assertStringContainsString("OWNER_CUSTOMER_LEAD = 'customer_lead'", $entity);
        $this->assertStringContainsString('function isLeadOwnerType', $entity);

        $service = file_get_contents(base_path('Modules/LeadManagement/Services/LeadOtherPhoneLinkService.php'));
        $this->assertStringContainsString('OWNER_CUSTOMER_LEAD', $service);
        $this->assertStringContainsString('finishMergeIntoLinkedLead', $service);
        $this->assertStringContainsString('isLeadOwnerType', $service);
    }

    public function test_merge_moves_followups_and_type_history(): void
    {
        $service = file_get_contents(base_path('Modules/LeadManagement/Services/LeadOtherPhoneLinkService.php'));
        $this->assertStringContainsString('LeadFollowup::query()', $service);
        $this->assertStringContainsString('mergeTypeHistories', $service);
        $this->assertStringContainsString('mergeProviderChecklist', $service);
        $this->assertStringContainsString('finishMergeIntoLinkedLead', $service);
        $this->assertStringContainsString('function unlink(', $service);
        $this->assertStringContainsString('removeAlternateByDigits', file_get_contents(base_path('Modules/LeadManagement/Services/PartyPhoneDirectory.php')));

        $sidebar = file_get_contents(base_path('Modules/LeadManagement/Resources/views/admin/leads/partials/_show-redesign-sidebar.blade.php'));
        $this->assertStringContainsString('other-phone.finish-merge', $sidebar);
        $this->assertStringContainsString('Move_all_data_into_linked_lead', $sidebar);
        $this->assertStringContainsString('other-phone.unlink', $sidebar);
        $this->assertStringContainsString('Delink_other_phone', $sidebar);
        $this->assertStringContainsString("['provider_lead', 'customer_lead']", $sidebar);
    }
}
