<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiCartRequestParser;
use Modules\BusinessSettingsModule\Services\MobileAppAiConversationalResponder;
use Modules\BusinessSettingsModule\Services\MobileAppAiCouponService;
use Modules\BusinessSettingsModule\Services\MobileAppAiFastRouteDetector;
use Modules\BusinessSettingsModule\Services\MobileAppAiPricingReply;
use Tests\TestCase;

/**
 * Routing detectors used by the orchestrator server-first pipeline.
 */
class MobileAppAiOrchestratorRoutingTest extends TestCase
{
    public function test_greeting_fast_route(): void
    {
        $c = MobileAppAiFastRouteDetector::detect('hello');
        $this->assertNotNull($c);
        $this->assertStringContainsString('Panun Kaergar', MobileAppAiConversationalResponder::greetingMessage());
    }

    public function test_cart_view_phrases_route_before_gemini(): void
    {
        $phrases = ['cart mein kya hai', 'mera cart dikhao', "what's in my cart"];
        foreach ($phrases as $p) {
            $this->assertTrue(
                MobileAppAiCartRequestParser::looksLikeViewCart($p),
                "Expected view cart: {$p}"
            );
        }
    }

    public function test_cart_mutation_phrases_parse(): void
    {
        $cases = [
            ['ek hi AC repair rakho baki remove karo', 'keep_one'],
            ['jo past date ki services hai unko remove karo', 'remove'],
            ['clear my cart', 'clear_all'],
        ];
        foreach ($cases as [$text, $op]) {
            $parsed = MobileAppAiCartRequestParser::parse($text);
            $this->assertNotNull($parsed, $text);
            $this->assertSame($op, $parsed['op'], $text);
        }
    }

    public function test_coupon_intent_detected(): void
    {
        $this->assertTrue(MobileAppAiCouponService::looksLikeCouponIntent('apply coupon TESTCODE'));
    }

    public function test_pricing_query_detected(): void
    {
        $this->assertTrue(MobileAppAiPricingReply::looksLikePricingQuery('what will be total charges'));
    }
}
