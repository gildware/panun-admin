<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BidModule\Entities\Post;
use Modules\BidModule\Entities\PostBid;
use Modules\UserManagement\Entities\User;

/**
 * Live account summaries — never guess counts from model memory.
 */
class MobileAppAiAccountSummaryService
{
    public function __construct(
        protected MobileAppAiCartService $cartService,
        protected MobileAppAiCustomerBookingService $bookings,
        protected MobileAppAiBiddingService $bidding,
        protected MobileAppAiCatalogSearchService $catalogSearch,
        protected MobileAppAiPricingReply $pricingReply,
    ) {}

    /**
     * @return array{ok: bool, customer_message: string, ui?: mixed}
     */
    public function cartSummary(User $user, string $mode = 'items'): array
    {
        if ($mode === 'total') {
            $priced = $this->pricingReply->build($user);

            return [
                'ok' => true,
                'customer_message' => (string) ($priced['customer_message'] ?? ''),
                'ui' => $priced['ui'] ?? null,
            ];
        }

        $cart = $this->cartService->cartSummaryForUser($user);
        $count = (int) ($cart['item_count'] ?? 0);

        if ($mode === 'count') {
            if ($count === 0) {
                return [
                    'ok' => true,
                    'customer_message' => 'Your cart is empty right now.',
                    'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
                ];
            }

            return [
                'ok' => true,
                'customer_message' => 'You have **'.$count.'** item'.($count === 1 ? '' : 's').' in your cart.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $priced = $this->pricingReply->build($user);

        return [
            'ok' => true,
            'customer_message' => (string) ($priced['customer_message'] ?? ''),
            'ui' => $priced['ui'] ?? null,
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: mixed}
     */
    public function bookingSummary(User $user, string $mode = 'list'): array
    {
        if ($mode === 'count') {
            return $this->bookings->countSummaryForUser($user);
        }

        if ($mode === 'latest') {
            return $this->bookings->latestSummaryForUser($user);
        }

        return $this->bookings->listForUser($user);
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: mixed}
     */
    public function biddingSummary(User $user, string $mode = 'list'): array
    {
        if ($mode === 'count') {
            $posts = (int) Post::query()->where('customer_user_id', (string) $user->id)->count();
            $pending = (int) PostBid::query()
                ->whereHas('post', fn ($q) => $q->where('customer_user_id', (string) $user->id))
                ->where('status', 'pending')
                ->count();

            return [
                'ok' => true,
                'customer_message' => 'You have **'.$posts.'** bidding post'.($posts === 1 ? '' : 's')
                    .($pending > 0 ? ' with **'.$pending.'** pending bid'.($pending === 1 ? '' : 's') : '').'.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        return $this->bidding->listPosts($user);
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: mixed}
     */
    public function addressSummary(User $user, string $mode = 'list'): array
    {
        $result = $this->catalogSearch->listCustomerAddresses($user);
        $count = (int) ($result['count'] ?? 0);

        if ($mode === 'count') {
            return [
                'ok' => true,
                'customer_message' => $count === 0
                    ? 'You have no saved addresses yet. Add one in the app under **Home → location**.'
                    : 'You have **'.$count.'** saved address'.($count === 1 ? '' : 'es').'.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        if ($count === 0) {
            return [
                'ok' => true,
                'customer_message' => 'You have no saved addresses yet. In the app: **Home → tap the location bar → Add new address**.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $lines = [];
        foreach ($result['selectable_options'] ?? [] as $opt) {
            if (is_array($opt)) {
                $lines[] = '• '.(string) ($opt['display'] ?? '');
            }
        }

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply(
                "Your saved addresses:\n\n".implode("\n", array_filter($lines))
            ),
            'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
        ];
    }
}
