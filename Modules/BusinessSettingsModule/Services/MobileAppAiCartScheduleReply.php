<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\UserManagement\Entities\User;

/**
 * Answers when the customer asks for visit date/time on cart lines (read-only).
 */
final class MobileAppAiCartScheduleReply
{
    public function __construct(
        protected MobileAppAiCartService $cartService,
    ) {}

    public static function looksLikeCartScheduleQuery(string $text): bool
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        if (MobileAppAiCartRequestParser::looksLikeRescheduleChangeIntent($t)) {
            return false;
        }

        if (MobileAppAiCartRequestParser::detectCartFilter($text) !== ''
            && preg_match('/\b(remove|delete|drop|cancel|hatao?|hata\s+do|nikal)\b/iu', $t)) {
            return false;
        }

        if (! preg_match('/\b(schedule|scheduled|date|dates|time|times|slot|visit|appointment|when)\b/iu', $t)) {
            return false;
        }

        if (preg_match('/\b(cart|basket)\b/iu', $t)) {
            return true;
        }

        if (preg_match('/\bvisit\s+date\b/iu', $t)) {
            return true;
        }

        // English
        if (preg_match(
            '/\b(?:what|which|when|tell|show|check)\b.*\b(?:schedule|date|time|visit)\b/iu',
            $t
        ) || preg_match(
            '/\b(?:schedule|date|time)\b.*\b(?:each|every|my|the)\b.*\b(?:service|item|line)\b/iu',
            $t
        )) {
            return true;
        }

        // Roman Urdu / Hinglish (kya=kya hai, kab=when, unka=their)
        return (bool) preg_match(
            '/\b(?:kya|kab|kitne|konsa|kon|kaun)\b.*\b(?:schedule|date|time|visit|tarikh|din|samay|slot)\b/iu',
            $t
        ) || (bool) preg_match(
            '/\b(?:schedule|date|time|visit|tarikh)\b.*\b(?:kya|kab|hai|hain|konsa|kaun)\b/iu',
            $t
        ) || (bool) preg_match(
            '/\b(?:unka|inki|unki|inka|inke|uska|uski|iske|is|ye|ye)\b.*\b(?:schedule|date|time|visit|kab)\b/iu',
            $t
        );
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function build(User $user): array
    {
        $cart = $this->cartService->cartSummaryForUser($user);
        $items = $cart['items'] ?? [];

        if ((int) ($cart['item_count'] ?? 0) === 0) {
            return [
                'ok' => true,
                'customer_message' => 'Your cart is empty — there are no visit times set yet. Tell me what service you need and I can add it with a date.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $lines = ['Here is the **visit schedule** for each item in your cart:'];
        $hasAny = false;
        $missing = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = (string) ($item['service_name'] ?? 'Service');
            $qty = (int) ($item['quantity'] ?? 1);
            $qtyPrefix = $qty > 1 ? $qty.' × ' : '';
            $label = trim((string) ($item['schedule_label'] ?? ''));
            $raw = (string) ($item['service_schedule'] ?? '');

            if ($label !== '') {
                $hasAny = true;
                $lines[] = '• '.$qtyPrefix.'**'.$name.'** — '.$label;
            } elseif ($raw !== '') {
                $hasAny = true;
                try {
                    $lines[] = '• '.$qtyPrefix.'**'.$name.'** — '.Carbon::parse($raw)->format('j M, g:i A');
                } catch (\Throwable) {
                    $lines[] = '• '.$qtyPrefix.'**'.$name.'** — '.$raw;
                }
            } else {
                $missing[] = $name;
            }
        }

        $info = is_array($cart['cart_service_info'] ?? null) ? $cart['cart_service_info'] : null;
        if ($info !== null && ! empty($info['service_schedule'])) {
            try {
                $shared = Carbon::parse((string) $info['service_schedule'])->format('j M, g:i A');
                $lines[] = "\n**Cart default visit time:** ".$shared;
            } catch (\Throwable) {
                // ignore
            }
        }

        if ($missing !== []) {
            $unique = array_values(array_unique($missing));
            $lines[] = "\nNo date set yet for: **".implode('**, **', $unique)
                .'**. Say **change visit to tomorrow 5pm** or set the time in **Cart** on Home.';
        } elseif (! $hasAny) {
            $lines[] = "\nNo visit date is set on your cart items yet. Open **Cart** on Home to pick a time, or tell me e.g. **tomorrow 10am**.";
        } else {
            $lines[] = "\nTo change a time, say **reschedule AC repair to tomorrow 5pm** or update it in **Cart** on Home.";
        }

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply(implode("\n", $lines)),
            'ui' => [
                'type' => 'assistant_actions',
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'open_cart', 'label' => 'Open cart', 'style' => 'primary', 'icon' => 'shopping_cart'],
                    ['action' => 'open_bookings', 'label' => 'My bookings', 'style' => 'outline', 'icon' => 'event'],
                ],
            ],
        ];
    }
}
