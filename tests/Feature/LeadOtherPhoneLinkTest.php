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
        $this->assertStringContainsString('Provider_search_includes_open_leads_hint', $html);
        $this->assertStringNotContainsString('btn-check', $html);
        $this->assertStringNotContainsString('other-phone-step', $html);
        $this->assertStringNotContainsString('form-select-lg', $html);
    }
}
