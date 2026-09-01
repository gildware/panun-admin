<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Services\WhatsAppAiBookingIntentDetector;
use PHPUnit\Framework\TestCase;

class WhatsAppAiBookingIntentDetectorTest extends TestCase
{
    public function test_detects_rufaida_style_electrician_and_fan_install(): void
    {
        $blob = "Asalam u alaikum\nDo u provide electrician\nInstallation of ceiling fan\nMill stop\nWhite house opp. Govt. Boys higher secondary near hope medicate\nWill tell u later the proper date";

        $this->assertTrue(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed($blob));
        $this->assertTrue(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed('Do u provide electrician'));
        $this->assertTrue(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed('Installation of ceiling fan'));
    }

    public function test_greeting_only_is_not_customer_service_need(): void
    {
        $this->assertFalse(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed('Asalam u alaikum'));
        $this->assertFalse(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed('Hi'));
        $this->assertFalse(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed(''));
    }

    public function test_catalog_browse_is_not_customer_service_need(): void
    {
        $this->assertTrue(WhatsAppAiBookingIntentDetector::looksLikeCatalogBrowseOnly('What services do you offer?'));
        $this->assertFalse(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed('What services do you offer?'));
    }

    public function test_provider_onboarding_is_not_customer_service_need(): void
    {
        $this->assertTrue(WhatsAppAiBookingIntentDetector::looksLikeProviderOnboarding('I want to join as provider, I am electrician'));
        $this->assertFalse(WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed('I want to join as provider, I am electrician'));
    }
}
