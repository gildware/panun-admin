<?php

namespace Modules\ReviewModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;

class ProviderReviewRatingService
{
    public function syncForReviewTargets(?string $serviceId, ?string $providerId): void
    {
        foreach (['service_id' => $serviceId, 'provider_id' => $providerId] as $key => $value) {
            if (!$value) {
                continue;
            }

            $ratingGroupCount = DB::table('reviews')
                ->where($key, $value)
                ->where('is_active', 1)
                ->select('review_rating', DB::raw('count(*) as total'))
                ->groupBy('review_rating')
                ->get();

            $totalRating = 0;
            $ratingCount = 0;

            foreach ($ratingGroupCount as $count) {
                $totalRating += round($count->review_rating * $count->total, 2);
                $ratingCount += $count->total;
            }

            $avgRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 2) : 0;

            if ($key === 'service_id') {
                Service::query()->where('id', $value)->update([
                    'rating_count' => $ratingCount,
                    'avg_rating' => $avgRating,
                ]);
            } elseif ($key === 'provider_id') {
                Provider::query()->where('id', $value)->update([
                    'rating_count' => $ratingCount,
                    'avg_rating' => $avgRating,
                ]);
            }
        }
    }
}
