<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;

/**
 * Counts completed services a provider has delivered (one-time bookings + repeat sessions).
 */
class ProviderCompletedServicesCounter
{
    public function countForProvider(string $providerId): int
    {
        $counts = $this->countsForProviders([$providerId]);

        return $counts[$providerId] ?? 0;
    }

    /**
     * @param  list<string>  $providerIds
     * @return array<string, int>
     */
    public function countsForProviders(array $providerIds): array
    {
        $providerIds = array_values(array_unique(array_filter(array_map('strval', $providerIds))));
        if ($providerIds === []) {
            return [];
        }

        $repeatParentIds = DB::table('booking_repeats')
            ->whereNotNull('booking_id')
            ->distinct()
            ->pluck('booking_id');

        $oneTimeCounts = Booking::query()
            ->select('provider_id', DB::raw('COUNT(*) as total'))
            ->whereIn('provider_id', $providerIds)
            ->where('booking_status', 'completed')
            ->when($repeatParentIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $repeatParentIds))
            ->groupBy('provider_id')
            ->pluck('total', 'provider_id')
            ->mapWithKeys(fn ($count, $id) => [(string) $id => (int) $count])
            ->all();

        $repeatCounts = DB::table('booking_repeats')
            ->select(DB::raw('COALESCE(booking_repeats.provider_id, bookings.provider_id) as provider_id'), DB::raw('COUNT(DISTINCT booking_repeats.id) as total'))
            ->join('bookings', 'bookings.id', '=', 'booking_repeats.booking_id')
            ->where('booking_repeats.booking_status', 'completed')
            ->where(function ($query) use ($providerIds) {
                $query->whereIn('bookings.provider_id', $providerIds)
                    ->orWhereIn('booking_repeats.provider_id', $providerIds);
            })
            ->groupBy(DB::raw('COALESCE(booking_repeats.provider_id, bookings.provider_id)'))
            ->pluck('total', 'provider_id')
            ->mapWithKeys(fn ($count, $id) => [(string) $id => (int) $count])
            ->all();

        $result = array_fill_keys($providerIds, 0);

        foreach ($oneTimeCounts as $providerId => $count) {
            $result[$providerId] = ($result[$providerId] ?? 0) + $count;
        }

        foreach ($repeatCounts as $providerId => $count) {
            if (! in_array($providerId, $providerIds, true)) {
                continue;
            }
            $result[$providerId] = ($result[$providerId] ?? 0) + $count;
        }

        return $result;
    }

    /**
     * @param  iterable<int, \Modules\ProviderManagement\Entities\Provider>  $providers
     */
    public function attachToProviders(iterable $providers): void
    {
        $providerIds = [];
        foreach ($providers as $provider) {
            $providerIds[] = (string) $provider->id;
        }

        $counts = $this->countsForProviders($providerIds);

        foreach ($providers as $provider) {
            $provider->total_service_served = $counts[(string) $provider->id] ?? 0;
        }
    }
}
