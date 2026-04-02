<?php

namespace Tests\Unit;

use Modules\WhatsAppModule\Services\WhatsAppTemplateButtonValidator;
use PHPUnit\Framework\TestCase;

class WhatsAppTemplateButtonValidatorTest extends TestCase
{
    public function test_url_static_https_valid(): void
    {
        $this->assertTrue(WhatsAppTemplateButtonValidator::isValidTemplateButtonUrl('https://example.com/path'));
    }

    public function test_url_with_trailing_dynamic_placeholder_valid(): void
    {
        $this->assertTrue(WhatsAppTemplateButtonValidator::isValidTemplateButtonUrl('https://example.com/promo/{{1}}'));
    }

    public function test_url_http_rejected(): void
    {
        $this->assertFalse(WhatsAppTemplateButtonValidator::isValidTemplateButtonUrl('http://example.com'));
    }

    public function test_url_wrong_placeholder_rejected(): void
    {
        $this->assertFalse(WhatsAppTemplateButtonValidator::isValidTemplateButtonUrl('https://example.com/{{2}}'));
    }

    public function test_url_placeholder_not_at_end_rejected(): void
    {
        $this->assertFalse(WhatsAppTemplateButtonValidator::isValidTemplateButtonUrl('https://example.com/{{1}}/more'));
    }

    public function test_url_two_placeholders_rejected(): void
    {
        $this->assertFalse(WhatsAppTemplateButtonValidator::isValidTemplateButtonUrl('https://example.com/{{1}}/{{1}}'));
    }

    public function test_build_enforces_max_two_url_buttons(): void
    {
        $rows = [
            ['kind' => 'URL', 'text' => 'A', 'url' => 'https://a.com'],
            ['kind' => 'URL', 'text' => 'B', 'url' => 'https://b.com'],
            ['kind' => 'URL', 'text' => 'C', 'url' => 'https://c.com'],
        ];
        $out = WhatsAppTemplateButtonValidator::buildButtonsComponent($rows);
        $this->assertSame('Template_buttons_max_url', $out['error']);
        $this->assertNull($out['component']);
    }

    public function test_build_enforces_max_one_phone_button(): void
    {
        $rows = [
            ['kind' => 'PHONE_NUMBER', 'text' => 'Call', 'phone' => '+15551234567'],
            ['kind' => 'PHONE_NUMBER', 'text' => 'Call2', 'phone' => '+15559876543'],
        ];
        $out = WhatsAppTemplateButtonValidator::buildButtonsComponent($rows);
        $this->assertSame('Template_buttons_max_phone', $out['error']);
        $this->assertNull($out['component']);
    }

    public function test_build_rejects_unknown_button_kind(): void
    {
        $rows = [
            ['kind' => 'COPY_CODE', 'text' => 'Code'],
        ];
        $out = WhatsAppTemplateButtonValidator::buildButtonsComponent($rows);
        $this->assertSame('Template_button_kind_invalid', $out['error']);
        $this->assertNull($out['component']);
    }

    public function test_build_valid_mix(): void
    {
        $rows = [
            ['kind' => 'QUICK_REPLY', 'text' => 'Yes'],
            ['kind' => 'URL', 'text' => 'Web', 'url' => 'https://x.com/{{1}}'],
            ['kind' => 'PHONE_NUMBER', 'text' => 'Call', 'phone' => '+923001112233'],
        ];
        $out = WhatsAppTemplateButtonValidator::buildButtonsComponent($rows);
        $this->assertNull($out['error']);
        $this->assertIsArray($out['component']);
        $this->assertSame('BUTTONS', $out['component']['type']);
        $this->assertCount(3, $out['component']['buttons']);
    }
}
