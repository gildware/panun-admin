<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiToolExecutor;

/**
 * Rule-based replies when the customer asks about price / charges (especially after cart add).
 */
final class MobileAppAiPricingReply
{
    public function __construct(
        protected MobileAppAiCartService $cartService,
        protected WhatsAppAiToolExecutor $whatsappTools,
    ) {}

    public static function looksLikePricingQuery(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) < 4) {
            return false;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery($text)) {
            return false;
        }

        return (bool) preg_match(
            '/\b(price|prices|pricing|cost|costs|charge|charges|charged|fee|fees|total|amount|bill|'
            .'kitna|kitne|rate|rates|how\s+much|paisa|rupee|₹|rs\.?|'
            .'visiting\s+charge|consultation\s+fee|payment\s+amount|what\s+will\s+(?:be\s+)?(?:the\s+)?(?:total|charge))\b/iu',
            $t
        ) || (bool) preg_match('/\b(added|add)\b.*\b(charge|charges|cost|price|total)\b/iu', $t);
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function build(User $user, string $userText = ''): array
    {
        $cart = $this->cartService->cartSummaryForUser($user);
        $visitNote = $this->visitingChargeNote();
        $summary = MobileAppAiCartUiPresenter::buildSummaryResponse($cart, $userText);

        if ((int) ($cart['item_count'] ?? 0) === 0) {
            $msg = (string) ($summary['customer_message'] ?? '');
            if ($visitNote !== '') {
                $msg .= "\n\n".$visitNote;
            }

            return [
                'ok' => true,
                'customer_message' => MobileAppAiReplyStyle::clampReply($msg),
                'ui' => $summary['ui'] ?? MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $msg = (string) ($summary['customer_message'] ?? '');
        if ($visitNote !== '') {
            $msg .= "\n\n".$visitNote;
        }
        $msg .= MobileAppAiReplyStyle::prefersHinglish($userText)
            ? "\n\nFinal cost technician ke baad change ho sakti hai."
            : "\n\nFinal job cost can change after the technician inspects on site.";

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply($msg),
            'ui' => $summary['ui'] ?? null,
        ];
    }

    private function visitingChargeNote(): string
    {
        try {
            $result = $this->whatsappTools->execute('get_public_business_info', [], '');
            $data = is_array($result['data'] ?? null) ? $result['data'] : [];

            return trim((string) ($data['visiting_charge_note'] ?? ''));
        } catch (\Throwable) {
            return trim((string) config('whatsapp_ai_support.default_visiting_charge_note', ''));
        }
    }
}
