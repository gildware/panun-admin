<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Facades\DB;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\CustomerServiceResponseEnricher;

class CustomerProviderDetailsService
{
    public function __construct(
        private readonly Provider $provider,
        private readonly Category $category,
        private readonly SubscribedService $subscribedService,
        private readonly FavoriteProvider $favoriteProvider,
        private readonly Review $review,
        private readonly Service $service,
    ) {
    }

    public function findProvider(string $providerId): ?Provider
    {
        return $this->provider
            ->with('owner')
            ->withCount([
                'bookings as total_service_served' => function ($query) {
                    $query->where('booking_status', 'completed');
                },
                'subscribed_services',
            ])
            ->find($providerId);
    }

    public function enrichProvider(Provider $provider, string|int|null $customerUserId): Provider
    {
        $provider['is_favorite'] = $this->favoriteProvider
            ->where('customer_user_id', $customerUserId)
            ->where('provider_id', $provider->id)
            ->exists() ? 1 : 0;

        $timeSchedule = provider_config('time_schedule', 'service_schedule', $provider['id'])?->live_values;
        $weekEnds = provider_config('weekends', 'service_schedule', $provider['id'])?->live_values ?? '';
        $provider['time_schedule'] = json_decode($timeSchedule) ?? null;
        $provider['weekends'] = json_decode($weekEnds) ?? [];

        $eligibility = app(ProviderPackageEligibilityResolver::class)->preload([(string) $provider->id]);
        $provider['nextBookingEligibility'] = $eligibility->canAcceptNextBooking((string) $provider->id);
        $provider['scheduleBookingEligibility'] = $eligibility->canScheduleBooking((string) $provider->id);

        $limitStatus = provider_warning_amount_calculate(
            $provider?->owner?->account->account_payable,
            $provider?->owner?->account->account_receivable
        );
        $provider['cash_limit_status'] = $limitStatus == false ? 'available' : $limitStatus;

        return $provider;
    }

    public function getRatingInfo(string $providerId): array
    {
        $ratingGroupCount = DB::table('reviews')
            ->where('provider_id', $providerId)
            ->where('is_active', 1)
            ->select(
                'review_rating',
                DB::raw('count(review_comment) as total_comment'),
                DB::raw('count(*) as total')
            )
            ->groupBy('review_rating')
            ->get();

        $totalRating = 0;
        $ratingCount = 0;
        $reviewCount = 0;

        foreach ($ratingGroupCount as $count) {
            $totalRating += round($count->review_rating * $count->total, 2);
            $ratingCount += $count->total;
            $reviewCount += $count->total_comment;
        }

        return [
            'rating_count' => $ratingCount,
            'review_count' => $reviewCount,
            'average_rating' => round(divnum($totalRating, $ratingCount), 2),
            'rating_group_count' => $ratingGroupCount,
        ];
    }

    public function getSubscribedSubCategoryIds(string $providerId): array
    {
        return $this->subscribedService
            ->ofStatus(1)
            ->where('provider_id', $providerId)
            ->pluck('sub_category_id')
            ->toArray();
    }

    public function getSubCategoriesWithServices(string $providerId, string|int|null $customerUserId): array
    {
        $subscribedSubCategoryIds = $this->getSubscribedSubCategoryIds($providerId);

        $subCategories = $this->category->withoutGlobalScopes()
            ->select([
                'id',
                'parent_id',
                'name',
                'slug',
                'image',
                'position',
                'description',
                'is_active',
                'is_featured',
                'created_at',
                'updated_at',
            ])
            ->whereHas('services', function ($query) {
                $query->ofStatus(1);
            })
            ->whereIn('id', $subscribedSubCategoryIds)
            ->get();

        if ($subscribedSubCategoryIds === []) {
            return $subCategories->all();
        }

        $services = $this->service
            ->with(['variations', 'service_discount', 'category.category_discount'])
            ->whereIn('sub_category_id', $subscribedSubCategoryIds)
            ->where(function ($query) {
                $query->whereDoesntHave('service_discount')
                    ->orWhereHas('service_discount');
            })
            ->where(function ($query) {
                $query->whereDoesntHave('category.category_discount')
                    ->orWhereHas('category.category_discount');
            })
            ->ofStatus(1)
            ->get();

        CustomerServiceResponseEnricher::enrich($services, $customerUserId);
        $servicesBySubCategory = $services->groupBy('sub_category_id');

        foreach ($subCategories as $subCategory) {
            $subCategory->setAttribute(
                'services',
                $servicesBySubCategory->get($subCategory->id, collect())->values()
            );
        }

        return $subCategories->all();
    }

    public function getReviews(string $providerId, int $limit, int $offset)
    {
        return $this->review
            ->with('customer', 'reviewReply')
            ->where('provider_id', $providerId)
            ->where('review_comment', '!=', null)
            ->ofStatus(1)
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset)
            ->withPath('');
    }

    public function getShowcaseItems(string $providerId)
    {
        return ProviderShowcaseItem::where('provider_id', $providerId)
            ->with('storage')
            ->where('is_active', 1)
            ->where('is_approved', ProviderShowcaseItem::STATUS_APPROVED)
            ->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }
}
