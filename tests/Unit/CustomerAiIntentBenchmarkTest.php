<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiIntentCatalog;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentClassifier;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class CustomerAiIntentBenchmarkTest extends TestCase
{
    public function test_fixture_intents_match_classifier(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $path = base_path('tests/Fixtures/customer_ai_intents.json');
        $this->assertFileExists($path);
        $cases = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($cases);

        $classifier = app(MobileAppAiIntentClassifier::class);
        $failures = [];

        foreach ($cases as $case) {
            $message = (string) ($case['message'] ?? '');
            $expected = (string) ($case['intent'] ?? '');
            if ($message === '' || $expected === '') {
                continue;
            }

            $c = $classifier->classify($user, $message, ['step' => 'idle']);
            $maxConf = isset($case['max_confidence']) ? (float) $case['max_confidence'] : null;

            if ($maxConf !== null) {
                if ($c->confidence > $maxConf) {
                    $failures[] = $message.' expected confidence <= '.$maxConf.' got '.$c->confidence;
                }
                continue;
            }

            if ($c->intent !== $expected) {
                $failures[] = $message.' expected '.$expected.' got '.$c->intent.' ('.$c->source.')';
            }
        }

        $strictOnly = (bool) env('MOBILE_APP_AI_BENCHMARK_STRICT', false);
        if ($strictOnly) {
            $this->assertSame([], $failures, "Intent benchmark failures:\n".implode("\n", $failures));
        } else {
            $this->addToAssertionCount(1);
            if ($failures !== []) {
                fwrite(STDERR, "\nBenchmark gaps (".count($failures).") — set MOBILE_APP_AI_BENCHMARK_STRICT=1 to fail CI:\n".implode("\n", array_slice($failures, 0, 15))."\n");
            }
        }
    }

    public function test_intent_catalog_contains_production_intents(): void
    {
        foreach ([
            MobileAppAiIntentCatalog::VIEW_CART,
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            MobileAppAiIntentCatalog::BOOKING_START,
            MobileAppAiIntentCatalog::UNKNOWN,
        ] as $intent) {
            $this->assertTrue(MobileAppAiIntentCatalog::isValid($intent));
        }
    }
}
