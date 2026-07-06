<?php

namespace Modules\PromotionManagement\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\PromotionManagement\Entities\Campaign;

class CustomerCampaignApiQuery
{
    /**
     * Customer/mobile surfaces only need campaign visuals + discount metadata for navigation.
     */
    public static function withCustomerRelations(Builder $query): Builder
    {
        return $query->with([
            'discount' => fn ($discountQuery) => $discountQuery->select([
                'id',
                'discount_title',
                'discount_type',
                'discount_amount',
                'discount_amount_type',
                'min_purchase',
                'max_discount_amount',
                'limit_per_user',
                'promotion_type',
                'is_active',
                'start_date',
                'end_date',
                'created_at',
                'updated_at',
            ]),
        ]);
    }

    public static function query(): Builder
    {
        return self::withCustomerRelations(Campaign::query());
    }
}
