<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Resume abandoned booking wizard with Continue / Start Over.
 */
class MobileAppAiBookingResumeService
{
    /**
     * @return array{reply: string, ui: array<string, mixed>}|null
     */
    public function buildResumeOffer(User $user, MobileAppAiConversation $conversation): ?array
    {
        if (! config('mobile_app_ai_production.resume_booking.enabled', true)) {
            return null;
        }

        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $step = (string) ($draft['step'] ?? 'idle');
        if ($step === '' || $step === 'idle' || str_contains($step, 'confirm')) {
            return null;
        }

        $updated = $conversation->updated_at;
        if ($updated instanceof Carbon) {
            $hours = (int) config('mobile_app_ai_production.resume_booking.stale_hours', 24);
            if ($updated->diffInHours(now()) > $hours) {
                return null;
            }
        }

        $service = (string) ($draft['choices']['service_name'] ?? $draft['choices']['query'] ?? '');
        if ($service === '') {
            return null;
        }

        return [
            'reply' => MobileAppAiReplyStyle::clampReply(
                'You were booking **'.$service.'** earlier. Would you like to **continue** where you left off, or **start over**?'
            ),
            'ui' => [
                'type' => 'assistant_actions',
                'actions' => [
                    ['label' => 'Continue booking', 'action' => 'booking_resume_continue'],
                    ['label' => 'Start over', 'action' => 'booking_resume_restart'],
                ],
            ],
        ];
    }

    public function clearWizard(MobileAppAiConversation $conversation): void
    {
        $conversation->booking_draft = ['step' => 'idle', 'choices' => []];
        $conversation->save();
    }
}
