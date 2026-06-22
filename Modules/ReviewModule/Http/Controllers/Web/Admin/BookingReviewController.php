<?php

namespace Modules\ReviewModule\Http\Controllers\Web\Admin;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\ReviewModule\Entities\Review;
use Modules\UserManagement\Entities\User;

class BookingReviewController extends Controller
{
    public function __construct(
        private readonly Review $review,
        private readonly ProviderCustomerReview $providerCustomerReview,
    ) {
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $reviews = $this->buildPendingReviewsCollection($search);
        $paginatedReviews = $this->paginateCollection($reviews, $request);

        return view('reviewmodule::admin.booking-reviews.list', [
            'reviews' => $paginatedReviews,
            'search' => $search,
            'pendingCount' => $reviews->count(),
        ]);
    }

    private function buildPendingReviewsCollection(?string $search): Collection
    {
        $customerReviews = $this->review
            ->where('is_active', 0)
            ->with(['booking', 'customer', 'provider', 'service'])
            ->latest()
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'type' => 'customer_to_provider',
                'review_type_label' => translate('Customer_to_Provider'),
                'given_by' => $this->formatParty(
                    $this->formatUserName($review->customer),
                    'customer',
                    $review->customer_id
                        ? route('admin.customer.detail', [$review->customer_id, 'web_page' => 'overview'])
                        : null
                ),
                'given_to' => $this->formatParty(
                    $review->provider?->company_name ?? translate('Provider_not_found'),
                    'provider',
                    $review->provider_id
                        ? route('admin.provider.details', [$review->provider_id, 'web_page' => 'overview'])
                        : null
                ),
                'booking_id' => $review->booking?->readable_id ?? 'N/A',
                'booking_uuid' => $review->booking_id,
                'rating' => $review->review_rating,
                'description' => $review->review_comment,
                'created_at' => $review->created_at,
                'is_active' => (bool) $review->is_active,
                'approve_route' => route('admin.service.review-approve', $review->id),
                'delete_route' => route('admin.service.review-delete', $review->id),
            ]);

        $providerReviews = $this->providerCustomerReview
            ->where('is_active', 0)
            ->with(['booking', 'customer', 'provider'])
            ->latest()
            ->get()
            ->map(fn (ProviderCustomerReview $review) => [
                'id' => $review->id,
                'type' => 'provider_to_customer',
                'review_type_label' => translate('Provider_to_Customer'),
                'given_by' => $this->formatParty(
                    $review->provider?->company_name ?? translate('Provider_not_found'),
                    'provider',
                    $review->provider_id
                        ? route('admin.provider.details', [$review->provider_id, 'web_page' => 'overview'])
                        : null
                ),
                'given_to' => $this->formatParty(
                    $this->formatUserName($review->customer),
                    'customer',
                    $review->customer_id
                        ? route('admin.customer.detail', [$review->customer_id, 'web_page' => 'overview'])
                        : null
                ),
                'booking_id' => $review->booking?->readable_id ?? 'N/A',
                'booking_uuid' => $review->booking_id,
                'rating' => $review->review_rating,
                'description' => $review->review_comment,
                'created_at' => $review->created_at,
                'is_active' => (bool) $review->is_active,
                'approve_route' => route('admin.customer.customer-review-approve', $review->id),
                'delete_route' => route('admin.customer.customer-review-delete', $review->id),
            ]);

        $merged = $customerReviews
            ->concat($providerReviews)
            ->sortByDesc('created_at')
            ->values();

        if (!$search) {
            return $merged;
        }

        $needle = mb_strtolower(trim($search));

        return $merged->filter(function (array $review) use ($needle) {
            $haystack = mb_strtolower(implode(' ', [
                $review['given_by']['name'],
                $review['given_by']['role_label'],
                $review['given_to']['name'],
                $review['given_to']['role_label'],
                $review['booking_id'],
                (string) $review['rating'],
                (string) ($review['description'] ?? ''),
            ]));

            return str_contains($haystack, $needle);
        })->values();
    }

    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = pagination_limit();
        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function formatUserName(?User $user): string
    {
        if (!$user) {
            return translate('N/A');
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->email ?? translate('N/A'));
    }

    private function formatParty(string $name, string $role, ?string $profileUrl): array
    {
        return [
            'name' => $name,
            'role' => $role,
            'role_label' => $role === 'provider' ? translate('Provider') : translate('Customer'),
            'profile_url' => $profileUrl,
        ];
    }
}
