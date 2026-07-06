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

        if ($this->globalAdsEnabled) {
            $this->providers = Provider::query()
                ->whereIn('id', $providerIds)
                ->get()
                ->keyBy(fn (Provider $provider) => (string) $provider->id);

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

        foreach ($providerIds as $providerId) {
            $subscriber = $this->subscribers->get($providerId);
            if (! $subscriber || ! $subscriber->payment_id) {
                continue;
            }

            $isPaid = (bool) $subscriber->payment?->is_paid;
            if (! $isPaid) {
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

            $this->subscriptionBookingCounts[$providerId] = SubscriptionSubscriberBooking::query()
                ->where('provider_id', $providerId)
                ->where('package_subscriber_log_id', $subscriber->package_subscriber_log_id)
                ->whereBetween(DB::raw('DATE(updated_at)'), [
                    date('Y-m-d', strtotime($startDate)),
                    date('Y-m-d', strtotime($endDate)),
                ])
                ->count();
        }

        return $this;
    }

    public function canAcceptNextBooking(string $providerId): bool
    {
        $providerId = (string) $providerId;
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
        if (! $this->providerCanUseAdvertisement($providerId)) {
            return false;
        }

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

        return $subscriber->feature->contains(fn ($value) => $value->feature === 'advertisement');
    }

    public function providerCanUseAdvertisement(string $providerId): bool
    {
        if (! $this->globalAdsEnabled) {
            return false;
        }

        $providerId = (string) $providerId;
        $provider = $this->providers->get($providerId) ?? Provider::query()->find($providerId);

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

        $completedBookings = $this->completedBookingCounts[$providerId] ?? null;
        if ($completedBookings === null) {
            $completedBookings = Booking::query()
                ->where('provider_id', $providerId)
                ->where('booking_status', 'completed')
                ->count();
        }

        return $completedBookings >= $this->minimumBookingsForAds;
    }
}
