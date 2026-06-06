<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiCartRequestParser;
use Modules\BusinessSettingsModule\Services\MobileAppAiReplyStyle;
use Tests\TestCase;

class MobileAppAiCartParserTest extends TestCase
{
    public function test_past_date_remove_parses_visit_before_now_filter(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('jo past date ki services hai unko remove karo');

        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);
        $this->assertSame('visit_before_now', $parsed['cart_filter']);
    }

    public function test_sanitize_strips_tool_names(): void
    {
        $raw = "Okay. I am calling the `manage_customer_cart` tool to remove the item. Done — removed.";
        $clean = MobileAppAiReplyStyle::sanitizeCustomerFacing($raw);

        $this->assertStringNotContainsString('manage_customer_cart', $clean);
        $this->assertStringContainsString('Done', $clean);
    }

    public function test_hinglish_detection_for_roman_urdu(): void
    {
        $this->assertTrue(MobileAppAiReplyStyle::prefersHinglish('jo past date ki services hai unko remove karo'));
        $this->assertFalse(MobileAppAiReplyStyle::prefersHinglish('Please remove the June 4th service'));
    }

    public function test_keep_one_delete_rest_hinglish(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('koi b ek rakho baki delete karo');

        $this->assertNotNull($parsed);
        $this->assertSame('keep_one', $parsed['op']);
        $this->assertSame('', $parsed['target']);
    }

    public function test_ac_keep_one_scoped_hinglish(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('AC ke sab delte karo ek hi rakho cart mein');

        $this->assertNotNull($parsed);
        $this->assertSame('keep_one', $parsed['op']);
        $this->assertSame('ac', $parsed['target']);
    }

    public function test_delete_karo_is_not_a_cart_item_name(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('baki delete karo');

        $this->assertNotNull($parsed);
        $this->assertNotSame('karo', $parsed['target'] ?? 'karo');
    }

    public function test_ac_ki_ek_hi_service_rakho_baki_delete(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('AC ki ek hi service rakho baki delete karo');

        $this->assertNotNull($parsed);
        $this->assertSame('keep_one', $parsed['op']);
        $this->assertSame('ac', $parsed['target']);
    }

    public function test_cart_mein_kya_hai_is_view_cart(): void
    {
        $this->assertTrue(MobileAppAiCartRequestParser::looksLikeViewCart('cart mein kya hai'));
        $this->assertTrue(MobileAppAiCartRequestParser::looksLikeViewCart('mera cart dikhao'));
        $this->assertFalse(MobileAppAiCartRequestParser::looksLikeViewCart('cart se AC hatao'));
    }

    public function test_ek_hi_ac_repair_rakho_baki_remove(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('ek hi AC repair rakho cart mein rakho baki remove karo');

        $this->assertNotNull($parsed);
        $this->assertSame('keep_one', $parsed['op']);
        $this->assertSame('ac repair', $parsed['target']);
    }

    public function test_inverter_ko_hatao_hinglish_remove_target(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('inverter installation ko hatao wahan se');

        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);
        $this->assertSame('inverter installation', $parsed['target']);
    }

    public function test_inverter_wali_ko_delete_karo(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('inverter wali ko delete karo');

        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);
        $this->assertSame('inverter', $parsed['target']);
    }

    public function test_inverter_wali_delete_karo_without_ko(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('inverter wali delete karo');

        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);
        $this->assertSame('inverter', $parsed['target']);
    }

    public function test_ac_wale_ko_remove_karo(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('AC wale ko remove karo');

        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);
        $this->assertSame('AC', $parsed['target']);
    }

    public function test_ac_wali_serviceko_remove_karo(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('AC wali serviceko remove karo');

        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);
        $this->assertSame('AC', $parsed['target']);
    }
}
