<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Services\MobileAppAiIntentCatalog;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentClassifier;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentDispatcher;
use Modules\BusinessSettingsModule\Services\MobileAppAiIntentRoutingPolicy;
use Modules\BusinessSettingsModule\Services\MobileAppAiTurnPlan;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

/**
 * Regression suite — natural language must route to correct intents and handlers.
 */
class CustomerAiIntentRegressionTest extends TestCase
{
    /** @return array<string, string> */
    private function cases(): array
    {
        return [
            "what's in my cart" => MobileAppAiIntentCatalog::CART_SUMMARY,
            'how many booking I have' => MobileAppAiIntentCatalog::BOOKING_SUMMARY,
            'my bookings' => MobileAppAiIntentCatalog::BOOKING_SUMMARY,
            'need to book service' => MobileAppAiIntentCatalog::BOOKING_START,
            'book service' => MobileAppAiIntentCatalog::BOOKING_START,
            'schedule date of each service' => MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            'mera cart dikhao' => MobileAppAiIntentCatalog::CART_SUMMARY,
            'cart dikhao' => MobileAppAiIntentCatalog::CART_SUMMARY,
            'mera cart' => MobileAppAiIntentCatalog::CART_SUMMARY,
            'tap leak ho raha hai' => MobileAppAiIntentCatalog::SERVICE_TRIAGE,
            'AC cooling nahi kar raha' => MobileAppAiIntentCatalog::SERVICE_TRIAGE,
            'want technician' => MobileAppAiIntentCatalog::BOOKING_START,
            'cancel my booking' => MobileAppAiIntentCatalog::BOOKING_CANCEL,
            'kal wala next week kar do' => MobileAppAiIntentCatalog::CART_RESCHEDULE,
            'AC hata do' => MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            'unka kya schedule date hai' => MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            'visit date kya hai' => MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            'nahi service hi chahiye mujhay' => MobileAppAiIntentCatalog::BOOKING_START,
            'bola na service chahiye' => MobileAppAiIntentCatalog::BOOKING_START,
            'AC kharab huwa hai' => MobileAppAiIntentCatalog::SERVICE_TRIAGE,
            'service karni hai' => MobileAppAiIntentCatalog::BOOKING_START,
        ];
    }

    public function test_regression_intents_not_unknown(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $policy = app(MobileAppAiIntentRoutingPolicy::class);
        $failures = [];

        foreach ($this->cases() as $message => $expected) {
            $plan = $policy->buildTurnPlan($user, $message, ['step' => 'idle']);
            $intent = $plan->primary->intent;
            $acceptable = [$expected];
            if ($expected === MobileAppAiIntentCatalog::CART_SUMMARY) {
                $acceptable[] = MobileAppAiIntentCatalog::VIEW_CART;
            }
            if ($expected === MobileAppAiIntentCatalog::BOOKING_SUMMARY) {
                $acceptable[] = MobileAppAiIntentCatalog::BOOKING_STATUS;
            }

            if (! in_array($intent, $acceptable, true) || $intent === MobileAppAiIntentCatalog::UNKNOWN) {
                $failures[] = $message.' → '.$intent.' (expected '.$expected.') mode='.$plan->routingMode;
            }
            if ($plan->routingMode === MobileAppAiTurnPlan::ROUTE_CLARIFY
                && in_array($expected, MobileAppAiIntentCatalog::summaryIntents(), true)) {
                $failures[] = $message.' should execute summary, not clarify';
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_remove_ac_and_keep_not_unknown(): void
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
        $this->assertNotSame(MobileAppAiIntentCatalog::UNKNOWN, $c->intent);
    }

    public function test_need_to_book_service_dispatches_wizard(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first()
            ?? MobileAppAiConversation::query()->create(['user_id' => $user->id, 'booking_draft' => ['step' => 'idle']]);

        $c = app(MobileAppAiIntentClassifier::class)->classify($user, 'need to book service', ['step' => 'idle']);
        $this->assertSame(MobileAppAiIntentCatalog::BOOKING_START, $c->intent);

        $result = app(MobileAppAiIntentDispatcher::class)->dispatch(
            $user,
            $conversation,
            'need to book service',
            $c
        );

        $this->assertNotNull($result, 'booking_start must dispatch to wizard');
        $this->assertNotSame('', trim((string) ($result['reply'] ?? '')));
    }

    public function test_ac_triage_then_service_karni_hai_proceeds_booking(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first()
            ?? MobileAppAiConversation::query()->create(['user_id' => $user->id, 'booking_draft' => ['step' => 'idle']]);

        $draft = [
            'step' => 'service_triage',
            'choices' => [
                'service_query' => 'AC repair',
                'service_name' => 'AC repair',
            ],
            'conversation_state' => [
                'active_service' => 'AC repair',
                'pending_question' => 'triage_issue_detail',
            ],
        ];
        $conversation->update(['booking_draft' => $draft]);

        $policy = app(MobileAppAiIntentRoutingPolicy::class);
        $plan = $policy->buildTurnPlan($user, 'service karni hai', $draft, $conversation);
        $acceptable = [
            MobileAppAiIntentCatalog::BOOKING_START,
            MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
        ];
        $this->assertContains($plan->primary->intent, $acceptable);
        $this->assertSame(MobileAppAiTurnPlan::ROUTE_EXECUTE, $plan->routingMode);

        $payload = \Modules\BusinessSettingsModule\Services\MobileAppAiBookingMessageDetector::resolveWizardPayload(
            'service karni hai',
            $draft
        );
        $this->assertSame('proceed_booking', $payload['action'] ?? null);

        $classification = app(MobileAppAiIntentClassifier::class)->classify($user, 'service karni hai', $draft, $conversation);
        $this->assertSame('ai', $classification->source);

        $orchestrator = app(\Modules\BusinessSettingsModule\Services\MobileAppAiOrchestrator::class);
        $result = $orchestrator->handleUserMessage($user, $conversation->fresh(), 'service karni hai');
        $this->assertStringContainsString('AC', (string) ($result['reply'] ?? ''));
        $this->assertStringNotContainsString('service karni hai', mb_strtolower((string) ($result['reply'] ?? '')));
    }

    public function test_service_chahiye_then_ac_ki_starts_ac_flow(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first()
            ?? MobileAppAiConversation::query()->create(['user_id' => $user->id, 'booking_draft' => ['step' => 'idle']]);
        $conversation->update(['booking_draft' => null]);

        $orchestrator = app(\Modules\BusinessSettingsModule\Services\MobileAppAiOrchestrator::class);
        $first = $orchestrator->handleUserMessage($user, $conversation->fresh(), 'service chahiye');
        $firstReply = mb_strtolower((string) ($first['reply'] ?? ''));
        $this->assertTrue(
            str_contains($firstReply, 'service') || str_contains($firstReply, 'chahiye') || str_contains($firstReply, 'kaun'),
            'Agent should ask what service they need: '.$firstReply
        );

        $second = $orchestrator->handleUserMessage($user, $conversation->fresh(), 'AC ki');
        $reply = mb_strtolower((string) ($second['reply'] ?? ''));
        $this->assertStringContainsString('ac', $reply);
        $this->assertStringNotContainsString('could you tell me a little more', $reply);
    }
}
