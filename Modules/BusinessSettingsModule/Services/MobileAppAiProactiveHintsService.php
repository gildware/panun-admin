<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Optional hints — only when they match what the customer just asked about.
 */
class MobileAppAiProactiveHintsService
{
    public function __construct(
        protected MobileAppAiCustomerStateService $customerState,
    ) {}

    public function appendIfRelevant(
        User $user,
        string $reply,
        ?MobileAppAiConversation $conversation = null,
        ?MobileAppAiIntentClassification $classification = null,
    ): string {
        if (! config('mobile_app_ai_production.proactive.enabled', true)) {
            return $reply;
        }

        if (! $this->shouldOfferHint($classification, $conversation, $reply)) {
            return $reply;
        }

        $hint = $this->buildHint($user, $conversation, $classification);
        if ($hint === '') {
            return $reply;
        }

        return MobileAppAiReplyStyle::clampReply($reply."\n\n".$hint);
    }

    private function shouldOfferHint(
        ?MobileAppAiIntentClassification $classification,
        ?MobileAppAiConversation $conversation,
        string $reply,
    ): bool {
        if ($classification === null) {
            return false;
        }

        $neverAppend = [
            MobileAppAiIntentCatalog::SERVICE_TRIAGE,
            MobileAppAiIntentCatalog::BOOKING_START,
            MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
            MobileAppAiIntentCatalog::SERVICE_DETAILS,
            MobileAppAiIntentCatalog::APP_TROUBLESHOOT,
            MobileAppAiIntentCatalog::HUMAN_SUPPORT,
            MobileAppAiIntentCatalog::THANKS,
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            MobileAppAiIntentCatalog::CART_RESCHEDULE,
            MobileAppAiIntentCatalog::BOOKING_CANCEL,
        ];

        if (in_array($classification->intent, $neverAppend, true)) {
            return false;
        }

        $draft = $conversation && is_array($conversation->booking_draft)
            ? $conversation->booking_draft
            : [];
        $step = (string) ($draft['step'] ?? 'idle');
        if ($step !== '' && $step !== 'idle') {
            return false;
        }

        $lower = mb_strtolower($reply);
        if (str_contains($lower, 'what service do you need')
            || str_contains($lower, 'troubleshoot')
            || str_contains($lower, 'try this')
            || str_contains($lower, 'visit schedule')) {
            return false;
        }

        $cartRelated = [
            MobileAppAiIntentCatalog::VIEW_CART,
            MobileAppAiIntentCatalog::CART_SUMMARY,
            MobileAppAiIntentCatalog::PRICING_QUERY,
            MobileAppAiIntentCatalog::GREETING,
        ];

        return in_array($classification->intent, $cartRelated, true);
    }

    private function buildHint(
        User $user,
        ?MobileAppAiConversation $conversation,
        ?MobileAppAiIntentClassification $classification,
    ): string {
        $state = $this->customerState->build($user, $conversation);
        $cartCount = (int) ($state['cart']['item_count'] ?? 0);

        if ($cartCount < 3 || $classification === null) {
            return '';
        }

        if ($classification->intent === MobileAppAiIntentCatalog::GREETING) {
            return '';
        }

        if (in_array($classification->intent, [
            MobileAppAiIntentCatalog::VIEW_CART,
            MobileAppAiIntentCatalog::CART_SUMMARY,
        ], true)) {
            $schedules = [];
            foreach ((array) ($state['cart']['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $label = (string) ($item['schedule_label'] ?? '');
                if ($label !== '') {
                    $schedules[$label] = true;
                }
            }
            if (count($schedules) >= 3) {
                return 'Your items have visits on different days — ask **when are my visits** and I will list each date.';
            }
        }

        return '';
    }
}
