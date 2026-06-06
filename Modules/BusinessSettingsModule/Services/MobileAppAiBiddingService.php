<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Config;
use Modules\BidModule\Entities\Post;
use Modules\BidModule\Entities\PostBid;
use Modules\BidModule\Http\Controllers\APi\V1\Customer\PostBidController;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;

class MobileAppAiBiddingService
{
    public function __construct(
        protected MobileAppAiCatalogSearchService $catalogSearch,
    ) {}

    public static function looksLikeBiddingIntent(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(bid(?:ding)?s?|custom(?:ized)?\s+booking|post\s+a\s+bid|my\s+bids?|provider\s+bids?|accept\s+bid|decline\s+bid|deny\s+bid|create\s+(?:a\s+)?post)\b/iu',
            $t
        );
    }

    public static function looksLikeAcceptBid(string $text): bool
    {
        return (bool) preg_match('/\b(accept|approve)\s+(?:the\s+)?bid\b/iu', $text);
    }

    public static function looksLikeDenyBid(string $text): bool
    {
        return (bool) preg_match('/\b(deny|decline|reject)\s+(?:the\s+)?bid\b/iu', $text);
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function listPosts(User $user): array
    {
        $posts = Post::query()
            ->with(['service:id,name', 'bids' => fn ($q) => $q->where('status', 'pending')])
            ->where('customer_user_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        if ($posts->isEmpty()) {
            return [
                'ok' => true,
                'customer_message' => 'You have no bidding posts yet. Say **create bidding post for AC repair tomorrow** to request custom quotes from providers.',
                'ui' => $this->biddingMenuUi(),
            ];
        }

        $lines = ["Here are your recent **bidding posts**:\n"];
        foreach ($posts as $post) {
            $svc = (string) ($post->service?->name ?? 'Service');
            $status = (int) $post->is_booked === 1 ? 'booked' : 'open';
            $pending = $post->bids->count();
            $lines[] = '• **'.$svc.'** — '.$status.', '.$pending.' pending bid(s), schedule: '
                .($post->booking_schedule ? date('M j, g:i A', strtotime((string) $post->booking_schedule)) : 'not set');
        }
        $lines[] = "\nSay **show bids** for the latest post, or **accept bid from PROVIDER** / **decline bid**.";

        return [
            'ok' => true,
            'customer_message' => implode("\n", $lines),
            'ui' => $this->biddingMenuUi(),
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function listBidsForLatestPost(User $user): array
    {
        $post = $this->latestOpenPost($user);
        if (! $post) {
            return ['ok' => false, 'customer_message' => 'No open bidding post found. Create one first or check **My biddings** in the app.'];
        }

        $bids = PostBid::query()
            ->with(['provider:id,company_name'])
            ->where('post_id', $post->id)
            ->where('status', 'pending')
            ->orderBy('offered_price')
            ->limit(10)
            ->get();

        if ($bids->isEmpty()) {
            return [
                'ok' => true,
                'customer_message' => 'No pending bids yet on your latest post for **'.($post->service?->name ?? 'service').'**. Providers will notify you when they respond.',
                'ui' => $this->biddingMenuUi(),
            ];
        }

        $lines = ['**Pending bids** on your latest post:'];
        foreach ($bids as $bid) {
            $name = (string) ($bid->provider?->company_name ?? 'Provider');
            $price = with_currency_symbol((float) ($bid->offered_price ?? 0));
            $lines[] = '• **'.$name.'** — '.$price.' (say: accept bid from '.$name.')';
        }

        return [
            'ok' => true,
            'customer_message' => implode("\n", $lines)."\n\nAccepting creates your booking — you complete payment in the app.",
            'ui' => $this->biddingMenuUi(),
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>, pending?: array<string, mixed>}
     */
    public function buildAcceptConfirm(User $user, string $text): array
    {
        $resolved = $this->resolveBidFromText($user, $text);
        if ($resolved === null) {
            return ['ok' => false, 'customer_message' => 'Which bid should I accept? Say **show bids** first, then **accept bid from Provider Name**.'];
        }

        ['bid' => $bid, 'post' => $post] = $resolved;
        $providerName = (string) ($bid->provider?->company_name ?? 'provider');
        $price = with_currency_symbol((float) ($bid->offered_price ?? 0));

        return [
            'ok' => true,
            'customer_message' => 'Accept **'.$providerName.'**\'s bid of **'.$price.'** for **'.($post->service?->name ?? 'service').'**? This will place your booking (pay in the app).',
            'ui' => MobileAppAiConfirmUi::confirmCancel('bid_confirm', 'bid', $text),
            'pending' => [
                'op' => 'accept_bid',
                'post_id' => (string) $post->id,
                'provider_id' => (string) $bid->provider_id,
                'post_bid_id' => (string) $bid->id,
            ],
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>, pending?: array<string, mixed>}
     */
    public function buildDenyConfirm(User $user, string $text): array
    {
        $resolved = $this->resolveBidFromText($user, $text);
        if ($resolved === null) {
            return ['ok' => false, 'customer_message' => 'Which bid should I decline? Say **show bids** then **decline bid from Provider Name**.'];
        }

        ['bid' => $bid] = $resolved;
        $providerName = (string) ($bid->provider?->company_name ?? 'provider');

        return [
            'ok' => true,
            'customer_message' => 'Decline the bid from **'.$providerName.'**?',
            'ui' => MobileAppAiConfirmUi::confirmCancel('bid_confirm', 'bid', $text),
            'pending' => [
                'op' => 'deny_bid',
                'post_id' => (string) $bid->post_id,
                'provider_id' => (string) $bid->provider_id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $pending
     * @return array{ok: bool, customer_message: string}
     */
    public function executePending(User $user, array $pending): array
    {
        $op = (string) ($pending['op'] ?? '');
        if ($op === 'deny_bid') {
            return $this->denyBid($user, $pending);
        }
        if ($op === 'accept_bid') {
            return $this->acceptBid($user, $pending);
        }

        return ['ok' => false, 'customer_message' => 'Unknown bidding action.'];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function createPostFromDescription(User $user, string $text): array
    {
        $serviceQuery = self::extractServiceQuery($text);
        if ($serviceQuery === '') {
            return ['ok' => false, 'customer_message' => 'What service is the bid for? Example: **create bidding post for plumbing leak tomorrow**.'];
        }

        $results = $this->catalogSearch->searchServices($serviceQuery, 3, null, null, $user);
        $items = $results['items'] ?? [];
        if ($items === []) {
            return ['ok' => false, 'customer_message' => 'I could not match **'.$serviceQuery.'** to a catalog service for a bidding post.'];
        }

        $service = $items[0];
        $schedule = MobileAppAiSchedulePhraseParser::parse($text);
        if (! ($schedule['ok'] ?? false)) {
            return ['ok' => false, 'customer_message' => 'When do you need the service? Add a time like **tomorrow 10am** to your request.'];
        }

        $addr = UserAddress::query()
            ->where('user_id', (string) $user->id)
            ->orderByDesc('id')
            ->first();
        if (! $addr) {
            return ['ok' => false, 'customer_message' => 'Save a service address in the app first, then I can create a bidding post.'];
        }

        $zoneId = (string) ($addr->zone_id ?? $this->catalogSearch->resolveCustomerZoneId($user) ?? '');
        if ($zoneId !== '') {
            Config::set('zone_id', $zoneId);
        }

        $post = new Post;
        $post->service_description = trim($serviceQuery).' — '.trim((string) ($schedule['label'] ?? ''));
        $post->booking_schedule = (string) ($schedule['schedule'] ?? now()->addDay()->toDateTimeString());
        $post->customer_user_id = (string) $user->id;
        $post->service_id = (string) ($service['id'] ?? '');
        $post->category_id = (string) ($service['category_id'] ?? '');
        $post->sub_category_id = (string) ($service['sub_category_id'] ?? '');
        $post->service_address_id = (int) $addr->id;
        $post->zone_id = $zoneId;
        $post->save();

        return [
            'ok' => true,
            'customer_message' => 'Created a **bidding post** for **'.($service['name'] ?? 'service').'** on **'.($schedule['label'] ?? 'your schedule').'**. Providers can bid now — say **show bids** to check offers.',
            'ui' => $this->biddingMenuUi(),
        ];
    }

    /**
     * @param  array<string, mixed>  $pending
     * @return array{ok: bool, customer_message: string}
     */
    private function denyBid(User $user, array $pending): array
    {
        $bid = PostBid::query()
            ->where('post_id', (string) ($pending['post_id'] ?? ''))
            ->where('provider_id', (string) ($pending['provider_id'] ?? ''))
            ->where('status', 'pending')
            ->whereHas('post', fn ($q) => $q->where('customer_user_id', (string) $user->id))
            ->first();

        if (! $bid) {
            return ['ok' => false, 'customer_message' => 'Bid not found or already handled.'];
        }

        $bid->status = 'denied';
        $bid->save();

        return ['ok' => true, 'customer_message' => 'Bid declined. Say **show bids** to see remaining offers.'];
    }

    /**
     * @param  array<string, mixed>  $pending
     * @return array{ok: bool, customer_message: string}
     */
    private function acceptBid(User $user, array $pending): array
    {
        $postId = (string) ($pending['post_id'] ?? '');
        $providerId = (string) ($pending['provider_id'] ?? '');

        $bid = PostBid::query()
            ->with(['post.service', 'provider'])
            ->where('post_id', $postId)
            ->where('provider_id', $providerId)
            ->where('status', 'pending')
            ->whereHas('post', fn ($q) => $q->where('customer_user_id', (string) $user->id)->where('is_booked', '!=', 1))
            ->first();

        if (! $bid) {
            return ['ok' => false, 'customer_message' => 'That bid is no longer available.'];
        }

        $addrId = (int) ($bid->post->service_address_id ?? 0);
        $zoneId = (string) ($bid->post->zone_id ?? $this->catalogSearch->resolveCustomerZoneId($user) ?? '');
        if ($zoneId !== '') {
            Config::set('zone_id', $zoneId);
        }

        $request = request();
        $request->merge([
            'post_id' => $postId,
            'provider_id' => $providerId,
            'status' => 'accept',
            'service_address_id' => $addrId > 0 ? $addrId : null,
            'booking_schedule' => $bid->post->booking_schedule,
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = app(PostBidController::class);
        $response = $controller->update($request);
        $data = $response->getData(true);
        $flag = (string) ($data['response_code'] ?? '');

        if (str_contains($flag, '200') || str_contains($flag, 'success')) {
            return [
                'ok' => true,
                'customer_message' => 'Bid accepted — your booking is created. Open **My bookings** or **Biddings** in the app to pay or track status.',
            ];
        }

        return ['ok' => false, 'customer_message' => 'Could not accept the bid right now. Try from **Biddings** in the app.'];
    }

    /**
     * @return array{bid: PostBid, post: Post}|null
     */
    private function resolveBidFromText(User $user, string $text): ?array
    {
        $post = $this->latestOpenPost($user);
        if (! $post) {
            return null;
        }

        $providerHint = '';
        if (preg_match('/\b(?:from|by)\s+(.+)$/iu', $text, $m)) {
            $providerHint = trim((string) ($m[1] ?? ''));
        }

        $query = PostBid::query()
            ->with(['provider:id,company_name', 'post'])
            ->where('post_id', $post->id)
            ->where('status', 'pending');

        if ($providerHint !== '') {
            $query->whereHas('provider', function ($q) use ($providerHint): void {
                $q->where('company_name', 'like', '%'.$providerHint.'%');
            });
        }

        $bid = $query->orderBy('offered_price')->first();
        if (! $bid) {
            return null;
        }

        return ['bid' => $bid, 'post' => $post];
    }

    private function latestOpenPost(User $user): ?Post
    {
        return Post::query()
            ->with('service:id,name')
            ->where('customer_user_id', (string) $user->id)
            ->where('is_booked', '!=', 1)
            ->orderByDesc('created_at')
            ->first();
    }

    private static function extractServiceQuery(string $text): string
    {
        if (preg_match('/\b(?:for|about)\s+(.+?)(?:\s+(?:tomorrow|today|kal|monday|tuesday|wednesday|thursday|friday|saturday|sunday|\d))/iu', $text, $m)) {
            return trim((string) ($m[1] ?? ''));
        }
        if (preg_match('/\bpost\s+(?:for\s+)?(.+)$/iu', $text, $m)) {
            return trim((string) preg_replace('/\b(tomorrow|today|create|bidding|bid)\b/iu', '', (string) ($m[1] ?? '')));
        }

        return trim((string) preg_replace('/\b(create|bidding|bid|post|custom)\b/iu', '', $text));
    }

    /**
     * @return array<string, mixed>
     */
    private function biddingMenuUi(): array
    {
        return [
            'type' => 'bidding_actions',
            'layout' => 'actions',
            'actions' => [
                ['action' => 'open_biddings', 'label' => 'Open biddings', 'style' => 'primary', 'icon' => 'gavel'],
                ['action' => 'open_bookings', 'label' => 'My bookings', 'style' => 'outline', 'icon' => 'event'],
            ],
        ];
    }
}
