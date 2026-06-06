<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiBookingMessageDetector;
use Modules\BusinessSettingsModule\Services\MobileAppAiCatalogServiceMatcher;
use Modules\BusinessSettingsModule\Services\MobileAppAiServiceIntentResolver;
use Modules\BusinessSettingsModule\Services\MobileAppAiCartRequestParser;
use Modules\BusinessSettingsModule\Services\MobileAppAiPricingReply;
use Modules\BusinessSettingsModule\Services\MobileAppAiCouponService;
use Modules\BusinessSettingsModule\Services\MobileAppAiBiddingService;
use Modules\BusinessSettingsModule\Services\MobileAppAiBookingManageService;
use Modules\BusinessSettingsModule\Services\MobileAppAiServiceDetailsService;
use Modules\BusinessSettingsModule\Services\MobileAppAiCartScheduleReply;
use Modules\BusinessSettingsModule\Services\MobileAppAiServiceQueryNormalizer;
use Tests\TestCase;

class MobileAppAiRoutingTest extends TestCase
{
    public function test_generic_book_a_service_is_not_service_request(): void
    {
        $this->assertTrue(MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase('Book a service'));
        $this->assertFalse(MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest('Book a service'));
    }

    public function test_tap_leak_maps_to_plumbing(): void
    {
        $intent = MobileAppAiServiceIntentResolver::resolve('Tap Leaking Water');
        $this->assertSame('plumbing', $intent['trade_id']);
        $this->assertTrue(MobileAppAiServiceQueryNormalizer::looksLikeProblemOrService('Tap Leaking Water'));
    }

    public function test_laptop_is_unsupported_not_troubleshoot(): void
    {
        $intent = MobileAppAiServiceIntentResolver::resolve('Laptop Not Working');
        $this->assertNotNull($intent['unsupported']);
        $this->assertFalse(MobileAppAiBookingMessageDetector::looksLikeAppTroubleshoot('Laptop Not Working'));
    }

    public function test_booking_status_not_booking_intent(): void
    {
        $this->assertTrue(MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery('my booking status'));
        $this->assertFalse(MobileAppAiBookingMessageDetector::hasBookingIntent('my booking status'));
    }

    public function test_booking_count_query_detected(): void
    {
        $this->assertTrue(MobileAppAiBookingMessageDetector::looksLikeBookingCountQuery('how many booking I have'));
        $this->assertTrue(MobileAppAiBookingMessageDetector::looksLikeBookingCountQuery('how many booking I have in my'));
        $this->assertTrue(MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery('kitne booking hain'));
    }

    public function test_clear_cart_phrase_is_detected(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('can you clear my all cart');
        $this->assertNotNull($parsed);
        $this->assertSame('clear_all', $parsed['op'] ?? null);
    }

    public function test_remove_specific_cart_item_phrase(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('remove AC repair from my cart');
        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op'] ?? null);
        $this->assertStringContainsString('ac repair', mb_strtolower((string) ($parsed['target'] ?? '')));
    }

    public function test_remove_and_keep_only_parses_ac_not_full_sentence(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse(
            'can you remove AC repair services and keep only inverter one'
        );
        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op'] ?? null);
        $target = mb_strtolower((string) ($parsed['target'] ?? ''));
        $this->assertStringContainsString('ac repair', $target);
        $this->assertStringNotContainsString('keep only', $target);
        $this->assertStringNotContainsString('inverter', $target);
    }

    public function test_keep_only_cart_phrase(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('keep only inverter installation');
        $this->assertNotNull($parsed);
        $this->assertSame('keep_only', $parsed['op'] ?? null);
        $this->assertStringContainsString('inverter', mb_strtolower((string) ($parsed['target'] ?? '')));
    }

    public function test_pricing_query_detected_after_cart(): void
    {
        $this->assertTrue(MobileAppAiPricingReply::looksLikePricingQuery('what will be total charges'));
        $this->assertTrue(MobileAppAiPricingReply::looksLikePricingQuery('you added service what will be charges'));
        $this->assertFalse(MobileAppAiPricingReply::looksLikePricingQuery('my booking status'));
    }

    public function test_matcher_prefers_water_leakage_over_geyser(): void
    {
        $options = [
            ['name' => 'Geyser Installation', 'pick' => 1],
            ['name' => 'Water Leakage Repair', 'pick' => 2],
        ];
        $intent = MobileAppAiServiceIntentResolver::resolve('Tap Leaking Water');
        $best = MobileAppAiCatalogServiceMatcher::pickBest($options, 'Tap Leaking Water', $intent);
        $this->assertSame('Water Leakage Repair', $best['name'] ?? null);
    }

    public function test_coupon_intent_detected(): void
    {
        $this->assertTrue(MobileAppAiCouponService::looksLikeCouponIntent('apply coupon SAVE10'));
        $this->assertSame('SAVE10', MobileAppAiCouponService::extractCouponCode('use coupon save10'));
    }

    public function test_bidding_intent_detected(): void
    {
        $this->assertTrue(MobileAppAiBiddingService::looksLikeBiddingIntent('show my bids'));
        $this->assertTrue(MobileAppAiBiddingService::looksLikeAcceptBid('accept the bid from Ali'));
    }

    public function test_booking_cancel_and_rebook_intents(): void
    {
        $this->assertTrue(MobileAppAiBookingManageService::looksLikeCancelBooking('cancel my booking'));
        $this->assertTrue(MobileAppAiBookingManageService::looksLikeRebook('rebook last order'));
    }

    public function test_service_details_intent(): void
    {
        $this->assertTrue(MobileAppAiServiceDetailsService::looksLikeServiceDetailsIntent('tell me about AC repair'));
    }

    public function test_whats_in_my_cart_with_curly_apostrophe(): void
    {
        $curly = "what\u{2019}s in my cart";
        $this->assertTrue(MobileAppAiCartRequestParser::looksLikeViewCart($curly));
        $parsed = MobileAppAiCartRequestParser::parse($curly);
        $this->assertNotNull($parsed);
        $this->assertSame('view', $parsed['op'] ?? null);
    }

    public function test_whats_in_my_cart_plain_phrase(): void
    {
        $this->assertTrue(MobileAppAiCartRequestParser::looksLikeViewCart("what's in my cart"));
        $this->assertSame('view', MobileAppAiCartRequestParser::parse("what's in my cart")['op'] ?? null);
    }

    public function test_each_service_does_not_trigger_ac_trade(): void
    {
        $intent = MobileAppAiServiceIntentResolver::resolve('what is schedule date of each service');
        $this->assertNotSame('ac', $intent['trade_id'] ?? null);
        $this->assertFalse(MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest('what is schedule date of each service'));
    }

    public function test_cart_schedule_query_detected(): void
    {
        $this->assertTrue(MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery('what is schedule date of each service'));
        $this->assertTrue(MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery('what is schedule dat of my services in my cart'));
    }
}
