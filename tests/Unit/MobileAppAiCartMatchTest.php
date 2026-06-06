<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiCartManageService;
use Modules\BusinessSettingsModule\Services\MobileAppAiCartRequestParser;
use ReflectionClass;
use Tests\TestCase;

/**
 * Cart name matching against fixture line labels — catches empty-cart false positives.
 */
class MobileAppAiCartMatchTest extends TestCase
{
    /** @var list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}> */
    private const LINES = [
        [
            'cart_line_id' => 'line-ac-1',
            'service_name' => 'AC Repair',
            'variant_key' => 'Book-at-Home-Consultation',
            'line_total' => 50.0,
            'service_schedule' => '2026-06-05 18:00:00',
        ],
        [
            'cart_line_id' => 'line-ac-2',
            'service_name' => 'AC Repair',
            'variant_key' => 'Book-at-Home-Consultation',
            'line_total' => 50.0,
            'service_schedule' => '2026-06-05 15:37:00',
        ],
        [
            'cart_line_id' => 'line-inv-1',
            'service_name' => 'Inverter Installation',
            'variant_key' => 'Inverter-Installation',
            'line_total' => 1200.0,
            'service_schedule' => '2026-06-15 15:00:00',
        ],
    ];

    public function test_inverter_wali_delete_karo_without_ko_matches(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('inverter wali delete karo');
        $this->assertNotNull($parsed);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame(['line-inv-1'], $match['ids']);
    }

    public function test_inverter_wali_matches_inverter_installation(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('inverter wali ko delete karo');
        $this->assertNotNull($parsed);
        $this->assertSame('remove', $parsed['op']);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame(['line-inv-1'], $match['ids']);
        $this->assertStringContainsString('Inverter Installation', implode(' ', $match['labels']));
    }

    public function test_inverter_installation_ko_hatao_matches(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('inverter installation ko hatao');
        $this->assertNotNull($parsed);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame(['line-inv-1'], $match['ids']);
    }

    public function test_ac_wala_matches_ac_repair(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('ac wala hata do');
        $this->assertNotNull($parsed);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame(['line-ac-1', 'line-ac-2'], $match['ids']);
    }

    public function test_ac_wale_ko_remove_matches_both_ac_lines(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('AC wale ko remove karo');
        $this->assertNotNull($parsed);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame(['line-ac-1', 'line-ac-2'], $match['ids']);
    }

    public function test_ac_wali_serviceko_matches_both_ac_lines(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('AC wali serviceko remove karo');
        $this->assertNotNull($parsed);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame(['line-ac-1', 'line-ac-2'], $match['ids']);
    }

    public function test_unknown_item_returns_error(): void
    {
        $parsed = MobileAppAiCartRequestParser::parse('geyser wala hatao');
        $this->assertNotNull($parsed);

        $match = $this->resolveAgainstFixture($parsed);
        $this->assertSame([], $match['ids']);
        $this->assertNotEmpty($match['error'] ?? '');
    }

    /**
     * @param  array{op: string, target: string, schedule_text: string, cart_line_ids?: list<string>, cart_filter?: string}  $parsed
     * @return array{ids: list<string>, labels: list<string>, error?: string}
     */
    private function resolveAgainstFixture(array $parsed): array
    {
        $service = app(MobileAppAiCartManageService::class);
        $method = (new ReflectionClass(MobileAppAiCartManageService::class))->getMethod('resolveTargetLines');
        $method->setAccessible(true);

        /** @var array{ids: list<string>, labels: list<string>, error?: string} $match */
        $match = $method->invoke($service, self::LINES, $parsed);

        return $match;
    }
}
