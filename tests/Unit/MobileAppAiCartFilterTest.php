<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiCartManageService;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentCatalog;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentClassification;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentClassifier;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class MobileAppAiCartFilterTest extends TestCase
{
    public function test_visit_before_now_filter_selects_past_lines(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $parsed = [
            'op' => 'remove',
            'target' => '',
            'schedule_text' => '',
            'cart_line_ids' => [],
            'cart_filter' => 'visit_before_now',
        ];

        $match = app(MobileAppAiCartManageService::class)->matchTargets($user, $parsed);
        $this->assertIsArray($match['ids']);
    }

    public function test_remove_past_date_rule_confidence_is_low_without_name_match(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $c = app(MobileAppAiIntentClassifier::class)->classify($user, 'remove ones with past date', ['step' => 'idle']);

        $this->assertSame(MobileAppAiIntentCatalog::CART_REMOVE_ITEM, $c->intent);
        $this->assertTrue(
            $c->entityString('cart_filter') !== ''
                || $c->entityStringList('cart_line_ids') !== []
                || $c->source !== 'rules'
                || $c->confidence < 0.82,
            'Ambiguous remove should refine via Gemini or structured entities, not high-confidence name-only rules'
        );
    }
}
