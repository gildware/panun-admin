<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\UserManagement\Entities\User;

/**
 * Cart line cards + summary copy for in-chat UI.
 */
final class MobileAppAiCartUiPresenter
{
    /**
     * @param  array<string, mixed>  $cart  cartSummaryForUser shape
     * @return array{ok: bool, customer_message: string, ui: array<string, mixed>}
     */
    public static function buildSummaryResponse(array $cart, string $userText = ''): array
    {
        $count = (int) ($cart['item_count'] ?? 0);
        if ($count === 0) {
            return [
                'ok' => true,
                'customer_message' => MobileAppAiReplyStyle::localize(
                    'Your cart is empty. Tell me what service you need and I can help you book it.',
                    'Aapka cart khali hai. Bataiye kaunsi service chahiye — main book karwa deta hoon.',
                    $userText
                ),
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $lines = self::messageLines($cart);
        $total = number_format((float) ($cart['cart_total'] ?? 0), 2);

        $message = MobileAppAiReplyStyle::prefersHinglish($userText)
            ? "Aapke cart mein yeh services hain:\n\n".implode("\n", $lines)
                ."\n\n**Total: ₹{$total}**\n\nNeeche lines dekhein ya **Open cart** tap karein checkout ke liye."
            : "Here is your cart:\n\n".implode("\n", $lines)
                ."\n\n**Estimated total: ₹{$total}**\n\nTap a line below or **Open cart** on Home to checkout.";

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply($message),
            'ui' => self::cartLineCardsUi($cart, $userText),
        ];
    }

    /**
     * @param  array<string, mixed>  $cart
     * @return list<string>
     */
    public static function messageLines(array $cart): array
    {
        $lines = [];
        foreach ($cart['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $extra = trim((string) ($item['schedule_label'] ?? '').(
                ($item['address_short'] ?? '') !== '' ? ' · '.$item['address_short'] : ''
            ));
            $lines[] = '• '.(string) ($item['service_name'] ?? 'Service')
                .' — ₹'.number_format((float) ($item['line_total'] ?? 0), 2)
                .($extra !== '' ? ' ('.$extra.')' : '');
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $cart
     * @return array<string, mixed>
     */
    public static function cartLineCardsUi(array $cart, string $userText = ''): array
    {
        $hinglish = MobileAppAiReplyStyle::prefersHinglish($userText);
        $cards = [];
        foreach (array_slice($cart['items'] ?? [], 0, 8) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = (string) ($item['cart_line_id'] ?? '');
            if ($id === '') {
                continue;
            }
            $when = trim((string) ($item['schedule_label'] ?? ''));
            $price = '₹'.number_format((float) ($item['line_total'] ?? 0), 2);
            $cards[] = [
                'choice' => $id,
                'title' => (string) ($item['service_name'] ?? 'Service'),
                'subtitle' => trim($price.($when !== '' ? ' · '.$when : '')),
                'icon' => 'home_repair_service',
            ];
        }

        return [
            'type' => 'cart_summary',
            'layout' => 'cards',
            'compact' => true,
            'title' => $hinglish ? 'Aapka cart' : 'Your cart',
            'cards' => $cards,
            'footer_actions' => [
                [
                    'action' => 'open_cart',
                    'label' => $hinglish ? 'Cart kholen' : 'Open cart & pay',
                    'style' => 'primary',
                    'icon' => 'shopping_cart',
                ],
                [
                    'action' => 'start_booking',
                    'label' => $hinglish ? 'Aur service book karein' : 'Book another service',
                    'style' => 'outline',
                    'icon' => 'home_repair_service',
                ],
            ],
        ];
    }
}
