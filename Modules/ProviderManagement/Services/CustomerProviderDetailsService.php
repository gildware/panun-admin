<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\CategoryManagement\Entities\Category;
use Modules\CustomerModule\Services\CustomerServicePayloadSlimmer;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderSetting;
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

    public function providerExists(string $providerId): bool
    {
        return $this->provider->where('id', $providerId)->exists();
    }

    public function findProviderForSummary(string $providerId): ?Provider
    {
        return $this->provider
            ->withCount([
                'bookings as total_service_served' => function ($query) {
                    $query->where('booking_status', 'completed');
                },
                'subscribed_services',
            ])
            ->find($providerId);
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

    public function enrichProviderForSummary(Provider $provider, string|int|null $customerUserId): Provider
    {
        $provider['is_favorite'] = $this->favoriteProvider
            ->where('customer_user_id', $customerUserId)
            ->where('provider_id', $provider->id)
            ->exists() ? 1 : 0;

        $schedule = $this->loadProviderScheduleSettings((string) $provider->id);
        $provider['time_schedule'] = $schedule['time_schedule'];
        $provider['weekends'] = $schedule['weekends'];

        $eligibility = app(ProviderPackageEligibilityResolver::class)->preload([(string) $provider->id]);
        $provider['nextBookingEligibility'] = $eligibility->canAcceptNextBooking((string) $provider->id);
        $provider['scheduleBookingEligibility'] = $eligibility->canScheduleBooking((string) $provider->id);

        return $provider;
    }

    public function enrichProvider(Provider $provider, string|int|null $customerUserId): Provider
    {
        $provider['is_favorite'] = $this->favoriteProvider
            ->where('customer_user_id', $customerUserId)
            ->where('provider_id', $provider->id)
            ->exists() ? 1 : 0;

        $schedule = $this->loadProviderScheduleSettings((string) $provider->id);
        $provider['time_schedule'] = $schedule['time_schedule'];
        $provider['weekends'] = $schedule['weekends'];

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

    /**
     * @return array{time_schedule: mixed, weekends: array<int, mixed>}
     */
    private function loadProviderScheduleSettings(string $providerId): array
    {
        $settings = ProviderSetting::query()
            ->where('provider_id', $providerId)
            ->where('settings_type', 'service_schedule')
            ->whereIn('key_name', ['time_schedule', 'weekends'])
            ->get()
            ->keyBy('key_name');

        $timeRaw = $settings->get('time_schedule')?->live_values;
        $weekendsRaw = $settings->get('weekends')?->live_values ?? [];

        return [
            'time_schedule' => is_string($timeRaw) ? (json_decode($timeRaw) ?? null) : $timeRaw,
            'weekends' => is_string($weekendsRaw) ? (json_decode($weekendsRaw) ?? []) : ($weekendsRaw ?? []),
        ];
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

    public function getSubCategoriesWithServices(
        string $providerId,
        string|int|null $customerUserId,
        ?int $limitPerCategory = null
    ): array {
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
            ->whereIn('id', $subscribedSubCategoryIds)
            ->get();

        if ($subscribedSubCategoryIds === []) {
            return $subCategories->all();
        }

        $servicesQuery = $this->service
            ->withoutGlobalScope('zone_wise_data')
            ->with(CustomerServicePayloadSlimmer::listEagerRelations())
            ->whereIn('sub_category_id', $subscribedSubCategoryIds)
            ->where('is_active', 1)
            ->whereHas('subCategory', function ($query) {
                $query->withoutGlobalScopes()->where('is_active', 1);
            });

        $zoneId = Config::get('zone_id');
        if (is_string($zoneId) && $zoneId !== '') {
            $servicesQuery->where(function ($query) use ($zoneId) {
                $query
                    ->whereHas('category.zones', function ($zoneQuery) use ($zoneId) {
                        $zoneQuery->where('zone_id', $zoneId);
                    })
                    ->orWhereHas('subCategory.parent.zones', function ($zoneQuery) use ($zoneId) {
                        $zoneQuery->where('zone_id', $zoneId);
                    });
            });
        }

        $services = $servicesQuery->latest()->get();

        CustomerServiceResponseEnricher::enrich($services, $customerUserId, includeTax: false);
        $servicesBySubCategory = $services->groupBy(fn ($service) => (string) $service->sub_category_id);

        foreach ($subCategories as $subCategory) {
            $categoryServices = $servicesBySubCategory->get((string) $subCategory->id, collect())->values();
            if ($limitPerCategory !== null && $limitPerCategory > 0) {
                $categoryServices = $categoryServices->take($limitPerCategory);
            }

            $subCategory->setRelation('services', $categoryServices);
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
        return ProviderShowcaseItem::query()
            ->select([
                'id',
                'provider_id',
                'title',
                'description',
                'media_type',
                'file_name',
                'sort_order',
            ])
            ->where('provider_id', $providerId)
            ->where('is_active', 1)
            ->where('is_approved', ProviderShowcaseItem::STATUS_APPROVED)
            ->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }
}
