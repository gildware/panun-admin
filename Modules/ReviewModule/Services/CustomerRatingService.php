<?php

namespace Modules\ReviewModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Entities\User;

class CustomerRatingService
{
    public function syncReceivedRatings(string $customerId): void
    {
        $ratingGroupCount = DB::table('provider_customer_reviews')
            ->where('customer_id', $customerId)
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

        User::query()->where('id', $customerId)->update([
            'received_rating_count' => $ratingCount,
            'received_avg_rating' => $ratingCount > 0 ? round($totalRating / $ratingCount, 2) : 0,
        ]);
    }
}
