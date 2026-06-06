<?php

namespace Tests\Unit;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Services\MobileAppAiBookingSessionService;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class MobileAppAiBookingToolTest extends TestCase
{
    public function test_service_karwani_hai_at_triage_proceeds_to_booking(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $conv = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
        $conv->update([
            'booking_draft' => [
                'step' => 'service_triage',
                'choices' => ['service_query' => 'AC repair', 'service_name' => 'AC repair'],
            ],
        ]);

        $session = app(MobileAppAiBookingSessionService::class);
        $result = $session->handle($user, ['action' => 'search', 'message' => 'service karwani hai']);

        $this->assertStringContainsString('AC', (string) ($result['customer_message'] ?? ''));
        $this->assertNotSame('service_triage', $result['wizard_step'] ?? 'service_triage');
    }

    public function test_kya_at_service_confirm_clarifies_not_triage(): void
    {
        $user = User::query()->where('user_type', 'customer')->first();
        if (! $user) {
            $this->markTestSkipped('No customer user');
        }

        $conv = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
        $conv->update([
            'booking_draft' => [
                'step' => 'service_confirm',
                'choices' => ['pending_service_name' => 'AC Repair', 'confirm_pick' => 'AC Repair'],
            ],
        ]);

        $session = app(MobileAppAiBookingSessionService::class);
        $result = $session->handle($user, ['action' => 'search', 'message' => 'kya']);

        $this->assertSame('service_confirm', $result['wizard_step'] ?? '');
        $this->assertStringContainsString('AC Repair', (string) ($result['customer_message'] ?? ''));
    }
}
