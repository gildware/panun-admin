<?php

namespace Modules\ProviderManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\SubscriptionSubscriberBooking;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\ProviderManagement\Entities\Provider;

class ProviderPackageEligibilityResolver
{
    /** @var Collection<string, PackageSubscriber> */
    private Collection $subscribers;

    /** @var Collection<string, Provider> */
    private Collection $providers;

    /** @var array<string, int> */
    private array $completedBookingCounts = [];

    /** @var array<string, int> */
    private array $subscriptionBookingCounts = [];

    /** @var array<string, bool> */
    private array $bookingEligibilityCache = [];

    /** @var array<string, bool> */
    private array $advertisementEligibilityCache = [];

    private bool $globalAdsEnabled;

    private int $minimumBookingsForAds;

    public function __construct()
    {
        $this->subscribers = collect();
        $this->providers = collect();
        $this->globalAdsEnabled = (int) (business_config('advertisement_status', 'provider_config')?->live_values ?? 0) === 1;
        $this->minimumBookingsForAds = (int) (business_config('advertisement_minimum_bookings', 'provider_config')?->live_values ?? 0);
    }

    /**
     * @param  list<string>  $providerIds
     */
    public function preload(array $providerIds): self
    {
        $providerIds = array_values(array_unique(array_filter(array_map('strval', $providerIds))));
        if ($providerIds === []) {
            return $this;
        }

        $this->subscribers = PackageSubscriber::query()
            ->with(['limits', 'feature', 'payment'])
            ->whereIn('provider_id', $providerIds)
            ->get()
            ->keyBy(fn (PackageSubscriber $subscriber) => (string) $subscriber->provider_id);

        $this->providers = Provider::query()
            ->whereIn('id', $providerIds)
            ->get()
            ->keyBy(fn (Provider $provider) => (string) $provider->id);

        if ($this->globalAdsEnabled) {
            $needsBookingCount = $this->providers
                ->filter(fn (Provider $provider) => $provider->allow_advertisement === null)
                ->keys()
                ->all();

            if ($needsBookingCount !== [] && $this->minimumBookingsForAds > 0) {
                $this->completedBookingCounts = Booking::query()
                    ->select('provider_id', DB::raw('COUNT(*) as total'))
                    ->whereIn('provider_id', $needsBookingCount)
                    ->where('booking_status', 'completed')
                    ->groupBy('provider_id')
                    ->pluck('total', 'provider_id')
                    ->mapWithKeys(fn ($count, $id) => [(string) $id => (int) $count])
                    ->all();
            }
        }

        $this->batchLoadSubscriptionBookingCounts($providerIds);

        foreach ($providerIds as $providerId) {
            $this->bookingEligibilityCache[$providerId] = $this->resolveCanAcceptNextBooking($providerId);
            $this->advertisementEligibilityCache[$providerId] = $this->resolveCanShowAdvertisement($providerId);
        }

        return $this;
    }

    /**
     * @param  list<string>  $providerIds
     */
    private function batchLoadSubscriptionBookingCounts(array $providerIds): void
    {
        $limitedMeta = [];

        foreach ($providerIds as $providerId) {
            $subscriber = $this->subscribers->get($providerId);
            if (! $subscriber || ! $subscriber->payment_id || ! $subscriber->payment?->is_paid) {
                continue;
            }

            $bookingLimit = $subscriber->limits
                ->where('provider_id', $providerId)
                ->firstWhere('key', 'booking');

            if (! $bookingLimit || ! $bookingLimit->is_limited) {
                continue;
            }

            $startDate = $subscriber->package_start_date;
            $endDate = $subscriber->package_end_date;
            if (! $startDate || ! $endDate) {
                continue;
            }

            $limitedMeta[$providerId] = [
                'log_id' => (string) $subscriber->package_subscriber_log_id,
                'start' => date('Y-m-d', strtotime($startDate)),
                'end' => date('Y-m-d', strtotime($endDate)),
            ];
        }

        if ($limitedMeta === []) {
            return;
        }

        $providerIdsForQuery = array_keys($limitedMeta);
        $logIds = array_values(array_unique(array_column($limitedMeta, 'log_id')));

        $rows = SubscriptionSubscriberBooking::query()
            ->select('provider_id', 'package_subscriber_log_id', DB::raw('DATE(updated_at) as usage_date'))
            ->whereIn('provider_id', $providerIdsForQuery)
            ->whereIn('package_subscriber_log_id', $logIds)
            ->get();

        foreach ($limitedMeta as $providerId => $meta) {
            $this->subscriptionBookingCounts[$providerId] = $rows->filter(function ($row) use ($providerId, $meta) {
                return (string) $row->provider_id === $providerId
                    && (string) $row->package_subscriber_log_id === $meta['log_id']
                    && (string) $row->usage_date >= $meta['start']
                    && (string) $row->usage_date <= $meta['end'];
            })->count();
        }
    }

    public function canAcceptNextBooking(string $providerId): bool
    {
        $providerId = (string) $providerId;

        return $this->bookingEligibilityCache[$providerId]
            ?? ($this->bookingEligibilityCache[$providerId] = $this->resolveCanAcceptNextBooking($providerId));
    }

    public function canScheduleBooking(string $providerId): bool
    {
        $providerId = (string) $providerId;
        $subscriber = $this->subscribers->get($providerId);

        if (! $subscriber) {
            return true;
        }

        if (! $subscriber->payment_id) {
            return false;
        }

        if ($subscriber->is_canceled) {
            return false;
        }

        $now = Carbon::now();
        $startDate = $subscriber->package_start_date;
        $endDate = $subscriber->package_end_date;

        if (! $startDate || ! $endDate) {
            return false;
        }

        if ($now > $endDate) {
            return false;
        }

        return $subscriber->feature->contains(fn ($value) => $value->feature === 'schedule_service');
    }

    public function canShowAdvertisement(string $providerId): bool
    {
        $providerId = (string) $providerId;

        return $this->advertisementEligibilityCache[$providerId]
            ?? ($this->advertisementEligibilityCache[$providerId] = $this->resolveCanShowAdvertisement($providerId));
    }

    private function resolveCanAcceptNextBooking(string $providerId): bool
    {
        $subscriber = $this->subscribers->get($providerId);

        if (! $subscriber || $subscriber->payment_id === null) {
            return true;
        }

        $isPaid = (bool) $subscriber->payment?->is_paid;
        if (! $isPaid) {
            return false;
        }

        if ($subscriber->is_canceled) {
            return false;
        }

        $now = Carbon::now()->subDay();
        $bookingLimit = $subscriber->limits
            ->where('provider_id', $providerId)
            ->firstWhere('key', 'booking');

        if (! $bookingLimit) {
            return false;
        }

        if (! $bookingLimit->is_limited) {
            return true;
        }

        $startDate = $subscriber->package_start_date;
        $endDate = $subscriber->package_end_date;
        if (! $startDate || ! $endDate) {
            return false;
        }

        if ($now > $endDate) {
            return false;
        }

        $bookingCount = $this->subscriptionBookingCounts[$providerId] ?? 0;
        $leftBookingCount = (int) $bookingLimit->limit_count - $bookingCount;

        return $leftBookingCount > 0;
    }

    private function resolveCanShowAdvertisement(string $providerId): bool
    {
        if (! $this->providerCanUseAdvertisement($providerId)) {
            return false;
        }

        $subscriber = $this->subscribers->get($providerId);

        if (! $subscriber) {
            return true;
        }

        if (! $subscriber->payment_id) {
            return false;
        }

        if ($subscriber->is_canceled) {
            return false;
        }

        $now = Carbon::now();
        $startDate = $subscriber->package_start_date;
        $endDate = $subscriber->package_end_date;

        if (! $startDate || ! $endDate) {
            return false;
        }

        if ($now > $endDate) {
            return false;
        }

        return $subscriber->feature->contains(fn ($value) => $value->feature === 'advertisement');
    }

    public function providerCanUseAdvertisement(string $providerId): bool
    {
        if (! $this->globalAdsEnabled) {
            return false;
        }

        $providerId = (string) $providerId;
        $provider = $this->providers->get($providerId);

        if (! $provider) {
            return false;
        }

        if ($provider->allow_advertisement !== null && (int) $provider->allow_advertisement === 0) {
            return false;
        }

        if ((int) $provider->allow_advertisement === 1) {
            return true;
        }

        if ($this->minimumBookingsForAds <= 0) {
            return true;
        }

        $completedBookings = $this->completedBookingCounts[$providerId] ?? 0;

        return $completedBookings >= $this->minimumBookingsForAds;
    }

    /**
     * @return list<string>
     */
    public function filterBookingEligible(array $providerIds): array
    {
        return array_values(array_filter(
            array_map('strval', $providerIds),
            fn (string $id) => $this->canAcceptNextBooking($id)
        ));
    }

    /**
     * @return list<string>
     */
    public function filterAdvertisementEligible(array $providerIds): array
    {
        return array_values(array_filter(
            array_map('strval', $providerIds),
            fn (string $id) => $this->canShowAdvertisement($id)
        ));
    }
}
