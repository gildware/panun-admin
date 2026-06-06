<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiIntentCatalog;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentClassifier;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class MobileAppAiIntentClassifierTest extends TestCase
{
    public function test_whats_in_my_cart_classifies_as_view_cart(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $c = app(MobileAppAiIntentClassifier::class)->classify($user, "what\u{2019}s in my cart", ['step' => 'idle']);

        $this->assertContains($c->intent, [MobileAppAiIntentCatalog::VIEW_CART, MobileAppAiIntentCatalog::CART_SUMMARY]);
        $this->assertGreaterThan(0.7, $c->confidence);
    }

    public function test_schedule_date_not_booking_start(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $c = app(MobileAppAiIntentClassifier::class)->classify($user, 'what is schedule date of each service', ['step' => 'idle']);

        $this->assertSame(MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY, $c->intent);
        $this->assertNotSame(MobileAppAiIntentCatalog::BOOKING_START, $c->intent);
    }

    public function test_remove_and_keep_parses_entities(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $c = app(MobileAppAiIntentClassifier::class)->classify(
            $user,
            'remove AC repair services and keep only inverter one',
            ['step' => 'idle']
        );

        $this->assertSame(MobileAppAiIntentCatalog::CART_REMOVE_ITEM, $c->intent);
        $this->assertStringContainsString('ac repair', mb_strtolower($c->entityString('remove_target')));
    }
}
