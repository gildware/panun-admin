<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\BusinessSettingsModule\Entities\SubscriptionPackage;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\Coupon;
use Modules\PromotionManagement\Entities\Discount;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\Service;

class AdminBusinessAiCatalogInsightService
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryServices(array $args): array
    {
        $q = Service::query()->with(['category:id,name', 'subCategory:id,name']);
        $this->applyServiceFilters($q, $args);

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('order_count')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'services' => $rows->map(fn (Service $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category?->name,
                'sub_category' => $s->subCategory?->name,
                'is_active' => (bool) $s->is_active,
                'order_count' => (int) ($s->order_count ?? 0),
                'avg_rating' => (float) ($s->avg_rating ?? 0),
                'rating_count' => (int) ($s->rating_count ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyzeServices(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'catalog_overview')));

        return match ($analysis) {
            'top_by_orders' => $this->servicesTopByOrders($args),
            'by_category' => $this->servicesByCategory($args),
            'low_rated' => $this->servicesLowRated($args),
            'inactive_overview' => $this->servicesInactiveOverview(),
            default => $this->servicesCatalogOverview(),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryCategories(array $args): array
    {
        $q = Category::query()->with(['zones:id,name']);
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where('name', 'like', $s);
        }
        if (! empty($args['category_type'])) {
            $type = strtolower(trim((string) $args['category_type']));
            if ($type === 'main') {
                $q->where('position', 1);
            } elseif ($type === 'sub') {
                $q->where('position', 2);
            }
        }
        if (isset($args['is_active'])) {
            $q->where('is_active', ! empty($args['is_active']) ? 1 : 0);
        }
        if (! empty($args['zone'])) {
            $zoneName = trim((string) $args['zone']);
            $q->whereHas('zones', fn ($zq) => $zq->where('name', 'like', '%'.$zoneName.'%'));
        }

        $total = (clone $q)->count();
        $rows = $q->orderBy('name')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'categories' => $rows->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => (int) ($c->position ?? 0) === 1 ? 'main' : 'sub',
                'is_active' => (bool) $c->is_active,
                'is_featured' => (bool) ($c->is_featured ?? false),
                'zones' => $c->zones?->pluck('name')->all() ?? [],
                'service_count' => Service::query()
                    ->where(function ($sq) use ($c) {
                        $sq->where('category_id', $c->id)->orWhere('sub_category_id', $c->id);
                    })->count(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyzeCategoryCatalog(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'catalog_overview')));

        return match ($analysis) {
            'by_zone' => $this->categoriesByZone(),
            'inactive_overview' => $this->categoriesInactiveOverview(),
            default => $this->categoriesCatalogOverview(),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyzeReviews(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'overview')));
        $from = ! empty($args['date_from']) ? Carbon::parse((string) $args['date_from'])->startOfDay() : null;
        $to = ! empty($args['date_to']) ? Carbon::parse((string) $args['date_to'])->endOfDay() : null;

        $base = Review::query()->where('is_active', 1);
        if ($from) {
            $base->where('created_at', '>=', $from);
        }
        if ($to) {
            $base->where('created_at', '<=', $to);
        }

        return match ($analysis) {
            'by_rating' => $this->reviewsByRating($base),
            'top_rated_services' => $this->reviewsTopRatedServices($base, $args),
            'low_rated_services' => $this->reviewsLowRatedServices($base, $args),
            'top_rated_providers' => $this->reviewsTopRatedProviders($base, $args),
            'recent_negative' => $this->reviewsRecentNegative($base, $args),
            default => $this->reviewsOverview($base),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryPromotions(array $args): array
    {
        $promotionType = strtolower(trim((string) ($args['promotion_type'] ?? 'all')));
        $q = Discount::query()->with(['translations', 'coupons:id,discount_id,coupon_code,is_active']);

        if ($promotionType !== '' && $promotionType !== 'all') {
            $q->where('promotion_type', $promotionType);
        }
        if (isset($args['is_active'])) {
            $q->where('is_active', ! empty($args['is_active']) ? 1 : 0);
        }
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where(function ($w) use ($s) {
                $w->where('id', 'like', $s)
                    ->orWhereHas('coupons', fn ($cq) => $cq->where('coupon_code', 'like', $s));
            });
        }
        if (! empty($args['active_now'])) {
            $q->where('is_active', 1)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now());
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'promotions' => $rows->map(fn (Discount $d) => [
                'id' => $d->id,
                'title' => $d->title ?? null,
                'promotion_type' => $d->promotion_type,
                'discount_type' => $d->discount_type,
                'discount_amount' => (float) ($d->discount_amount ?? 0),
                'discount_amount_type' => $d->discount_amount_type,
                'is_active' => (bool) $d->is_active,
                'start_date' => $d->start_date,
                'end_date' => $d->end_date,
                'coupon_codes' => $d->coupons?->pluck('coupon_code')->filter()->values()->all() ?? [],
                'coupon_count' => $d->coupons?->count() ?? 0,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyzePromotions(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'promotion_overview')));

        return match ($analysis) {
            'active_coupons' => $this->promotionsActiveByType('coupon'),
            'active_discounts' => $this->promotionsActiveByType('discount'),
            'active_campaigns' => $this->promotionsActiveByType('campaign'),
            'by_type' => $this->promotionsByType(),
            default => $this->promotionsOverview(),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function querySubscriptions(array $args): array
    {
        $q = PackageSubscriber::query()->with(['provider:id,company_name,company_phone', 'package:id,name,price,duration']);
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where(function ($w) use ($s) {
                $w->where('package_name', 'like', $s)
                    ->orWhereHas('provider', fn ($pq) => $pq->where('company_name', 'like', $s)->orWhere('company_phone', 'like', $s));
            });
        }
        if (! empty($args['package_id'])) {
            $q->where('subscription_package_id', (string) $args['package_id']);
        }
        if (! empty($args['status'])) {
            $status = strtolower(trim((string) $args['status']));
            if ($status === 'active') {
                $q->where('package_end_date', '>=', now());
            } elseif ($status === 'expired') {
                $q->where('package_end_date', '<', now());
            } elseif ($status === 'expiring_soon') {
                $q->whereBetween('package_end_date', [now(), now()->addDays(14)]);
            }
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('package_end_date')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'subscribers' => $rows->map(fn (PackageSubscriber $sub) => [
                'id' => $sub->id,
                'provider' => $sub->provider?->company_name,
                'provider_phone' => $sub->provider?->company_phone,
                'package_name' => $sub->package_name ?? $sub->package?->name,
                'package_price' => (float) ($sub->package_price ?? 0),
                'start_date' => $sub->package_start_date?->toIso8601String(),
                'end_date' => $sub->package_end_date?->toIso8601String(),
                'is_active' => $sub->package_end_date ? $sub->package_end_date->isFuture() : null,
                'payment_method' => $sub->payment_method,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyzeSubscriptions(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'subscription_overview')));

        return match ($analysis) {
            'by_package' => $this->subscriptionsByPackage(),
            'expiring_soon' => $this->subscriptionsExpiringSoon($args),
            default => $this->subscriptionsOverview(),
        };
    }

    /**
     * @param  Builder<Service>  $q
     * @param  array<string, mixed>  $args
     */
    private function applyServiceFilters(Builder $q, array $args): void
    {
        if (! empty($args['search'])) {
            $s = '%'.trim((string) $args['search']).'%';
            $q->where('name', 'like', $s);
        }
        if (! empty($args['category'])) {
            $cat = trim((string) $args['category']);
            $q->where(function ($cq) use ($cat) {
                $cq->whereHas('category', fn ($catQ) => $catQ->where('name', 'like', '%'.$cat.'%'))
                    ->orWhereHas('subCategory', fn ($subQ) => $subQ->where('name', 'like', '%'.$cat.'%'));
            });
        }
        if (isset($args['is_active'])) {
            $q->where('is_active', ! empty($args['is_active']) ? 1 : 0);
        }
        if (! empty($args['min_rating'])) {
            $q->where('avg_rating', '>=', (float) $args['min_rating']);
        }
        if (! empty($args['max_rating'])) {
            $q->where('avg_rating', '<=', (float) $args['max_rating']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function servicesCatalogOverview(): array
    {
        $total = Service::query()->count();
        $active = Service::query()->where('is_active', 1)->count();
        $inactive = $total - $active;
        $withOrders = Service::query()->where('order_count', '>', 0)->count();
        $avgRating = (float) (Service::query()->where('rating_count', '>', 0)->avg('avg_rating') ?? 0);

        $top = Service::query()
            ->with(['category:id,name'])
            ->orderByDesc('order_count')
            ->limit(10)
            ->get(['id', 'name', 'category_id', 'order_count', 'avg_rating', 'is_active']);

        return [
            'ok' => true,
            'analysis' => 'catalog_overview',
            'total_services' => $total,
            'active_services' => $active,
            'inactive_services' => $inactive,
            'services_with_orders' => $withOrders,
            'average_rating' => round($avgRating, 2),
            'top_by_orders' => $top->map(fn (Service $s) => [
                'name' => $s->name,
                'category' => $s->category?->name,
                'order_count' => (int) ($s->order_count ?? 0),
                'avg_rating' => (float) ($s->avg_rating ?? 0),
                'is_active' => (bool) $s->is_active,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function servicesTopByOrders(array $args): array
    {
        $q = Service::query()->with(['category:id,name', 'subCategory:id,name']);
        $this->applyServiceFilters($q, $args);
        $rows = $q->orderByDesc('order_count')->limit($this->limit($args))->get();

        return [
            'ok' => true,
            'analysis' => 'top_by_orders',
            'returned' => $rows->count(),
            'services' => $rows->map(fn (Service $s) => [
                'name' => $s->name,
                'category' => $s->category?->name,
                'sub_category' => $s->subCategory?->name,
                'order_count' => (int) ($s->order_count ?? 0),
                'avg_rating' => (float) ($s->avg_rating ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function servicesByCategory(array $args): array
    {
        $rows = DB::table('services')
            ->join('categories', 'services.category_id', '=', 'categories.id')
            ->select('categories.name as category', DB::raw('count(*) as service_count'), DB::raw('sum(services.order_count) as total_orders'))
            ->whereNull('services.deleted_at')
            ->groupBy('categories.name')
            ->orderByDesc('total_orders')
            ->limit(15)
            ->get();

        return [
            'ok' => true,
            'analysis' => 'by_category',
            'by_category' => $rows->map(fn ($r) => [
                'category' => $r->category,
                'service_count' => (int) $r->service_count,
                'total_orders' => (int) ($r->total_orders ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function servicesLowRated(array $args): array
    {
        $minReviews = max(1, (int) ($args['min_reviews'] ?? 3));
        $rows = Service::query()
            ->with(['category:id,name'])
            ->where('rating_count', '>=', $minReviews)
            ->where('avg_rating', '<', (float) ($args['max_rating'] ?? 3.5))
            ->orderBy('avg_rating')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'analysis' => 'low_rated',
            'returned' => $rows->count(),
            'services' => $rows->map(fn (Service $s) => [
                'name' => $s->name,
                'category' => $s->category?->name,
                'avg_rating' => (float) ($s->avg_rating ?? 0),
                'rating_count' => (int) ($s->rating_count ?? 0),
                'order_count' => (int) ($s->order_count ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function servicesInactiveOverview(): array
    {
        $inactive = Service::query()->where('is_active', 0)->count();
        $activeNoOrders = Service::query()->where('is_active', 1)->where('order_count', 0)->count();

        return [
            'ok' => true,
            'analysis' => 'inactive_overview',
            'inactive_services' => $inactive,
            'active_with_zero_orders' => $activeNoOrders,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoriesCatalogOverview(): array
    {
        $main = Category::query()->where('position', 1)->count();
        $sub = Category::query()->where('position', 2)->count();
        $activeMain = Category::query()->where('position', 1)->where('is_active', 1)->count();
        $activeSub = Category::query()->where('position', 2)->where('is_active', 1)->count();

        return [
            'ok' => true,
            'analysis' => 'catalog_overview',
            'main_categories' => $main,
            'sub_categories' => $sub,
            'active_main_categories' => $activeMain,
            'active_sub_categories' => $activeSub,
            'inactive_main_categories' => $main - $activeMain,
            'inactive_sub_categories' => $sub - $activeSub,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoriesByZone(): array
    {
        $rows = DB::table('category_zone')
            ->join('zones', 'category_zone.zone_id', '=', 'zones.id')
            ->join('categories', 'category_zone.category_id', '=', 'categories.id')
            ->select('zones.name as zone', DB::raw('count(distinct categories.id) as category_count'))
            ->groupBy('zones.name')
            ->orderByDesc('category_count')
            ->limit(15)
            ->get();

        return [
            'ok' => true,
            'analysis' => 'by_zone',
            'by_zone' => $rows->map(fn ($r) => [
                'zone' => $r->zone,
                'category_count' => (int) $r->category_count,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoriesInactiveOverview(): array
    {
        return [
            'ok' => true,
            'analysis' => 'inactive_overview',
            'inactive_main_categories' => Category::query()->where('position', 1)->where('is_active', 0)->count(),
            'inactive_sub_categories' => Category::query()->where('position', 2)->where('is_active', 0)->count(),
        ];
    }

    /**
     * @param  Builder<Review>  $base
     * @return array<string, mixed>
     */
    private function reviewsOverview(Builder $base): array
    {
        $q = clone $base;
        $total = (clone $q)->count();
        $avg = (float) ((clone $q)->avg('review_rating') ?? 0);

        return [
            'ok' => true,
            'analysis' => 'overview',
            'total_reviews' => $total,
            'average_rating' => round($avg, 2),
            'by_rating' => (clone $q)->select('review_rating', DB::raw('count(*) as total'))
                ->groupBy('review_rating')
                ->orderByDesc('review_rating')
                ->pluck('total', 'review_rating')
                ->all(),
        ];
    }

    /**
     * @param  Builder<Review>  $base
     * @return array<string, mixed>
     */
    private function reviewsByRating(Builder $base): array
    {
        $rows = (clone $base)->select('review_rating', DB::raw('count(*) as total'))
            ->groupBy('review_rating')
            ->orderByDesc('review_rating')
            ->get();

        return [
            'ok' => true,
            'analysis' => 'by_rating',
            'by_rating' => $rows->map(fn ($r) => [
                'stars' => (int) $r->review_rating,
                'count' => (int) $r->total,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Builder<Review>  $base
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function reviewsTopRatedServices(Builder $base, array $args): array
    {
        $rows = (clone $base)
            ->join('services', 'reviews.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('avg(reviews.review_rating) as avg_rating'), DB::raw('count(*) as review_count'))
            ->groupBy('services.id', 'services.name')
            ->having('review_count', '>=', max(1, (int) ($args['min_reviews'] ?? 2)))
            ->orderByDesc('avg_rating')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'analysis' => 'top_rated_services',
            'services' => $rows->map(fn ($r) => [
                'name' => $r->name,
                'avg_rating' => round((float) $r->avg_rating, 2),
                'review_count' => (int) $r->review_count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Builder<Review>  $base
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function reviewsLowRatedServices(Builder $base, array $args): array
    {
        $rows = (clone $base)
            ->join('services', 'reviews.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('avg(reviews.review_rating) as avg_rating'), DB::raw('count(*) as review_count'))
            ->groupBy('services.id', 'services.name')
            ->having('review_count', '>=', max(1, (int) ($args['min_reviews'] ?? 2)))
            ->orderBy('avg_rating')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'analysis' => 'low_rated_services',
            'services' => $rows->map(fn ($r) => [
                'name' => $r->name,
                'avg_rating' => round((float) $r->avg_rating, 2),
                'review_count' => (int) $r->review_count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Builder<Review>  $base
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function reviewsTopRatedProviders(Builder $base, array $args): array
    {
        $rows = (clone $base)
            ->join('providers', 'reviews.provider_id', '=', 'providers.id')
            ->select('providers.company_name', DB::raw('avg(reviews.review_rating) as avg_rating'), DB::raw('count(*) as review_count'))
            ->groupBy('providers.id', 'providers.company_name')
            ->having('review_count', '>=', max(1, (int) ($args['min_reviews'] ?? 2)))
            ->orderByDesc('avg_rating')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'analysis' => 'top_rated_providers',
            'providers' => $rows->map(fn ($r) => [
                'company' => $r->company_name,
                'avg_rating' => round((float) $r->avg_rating, 2),
                'review_count' => (int) $r->review_count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Builder<Review>  $base
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function reviewsRecentNegative(Builder $base, array $args): array
    {
        $maxStars = (int) ($args['max_stars'] ?? 2);
        $rows = (clone $base)
            ->with(['service:id,name', 'provider:id,company_name', 'customer:id,first_name,last_name'])
            ->where('review_rating', '<=', $maxStars)
            ->orderByDesc('created_at')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'analysis' => 'recent_negative',
            'returned' => $rows->count(),
            'reviews' => $rows->map(fn (Review $r) => [
                'rating' => (int) $r->review_rating,
                'service' => $r->service?->name,
                'provider' => $r->provider?->company_name,
                'customer' => $r->customer ? trim($r->customer->first_name.' '.$r->customer->last_name) : null,
                'comment' => $r->review_comment ?? null,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionsOverview(): array
    {
        $total = Discount::query()->count();
        $activeNow = Discount::query()
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();
        $byType = Discount::query()
            ->select('promotion_type', DB::raw('count(*) as total'))
            ->groupBy('promotion_type')
            ->pluck('total', 'promotion_type');

        return [
            'ok' => true,
            'analysis' => 'promotion_overview',
            'total_promotions' => $total,
            'active_now' => $activeNow,
            'by_promotion_type' => $byType,
            'total_coupon_codes' => Coupon::query()->count(),
            'active_coupon_codes' => Coupon::query()->where('is_active', 1)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionsByType(): array
    {
        $rows = Discount::query()
            ->select('promotion_type', DB::raw('count(*) as total'), DB::raw('sum(case when is_active = 1 then 1 else 0 end) as active_count'))
            ->groupBy('promotion_type')
            ->get();

        return [
            'ok' => true,
            'analysis' => 'by_type',
            'by_type' => $rows->map(fn ($r) => [
                'promotion_type' => $r->promotion_type,
                'total' => (int) $r->total,
                'active' => (int) $r->active_count,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionsActiveByType(string $type): array
    {
        $rows = Discount::query()
            ->with(['translations', 'coupons:id,discount_id,coupon_code,is_active'])
            ->where('promotion_type', $type)
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->orderByDesc('end_date')
            ->limit(20)
            ->get();

        return [
            'ok' => true,
            'analysis' => 'active_'.$type.'s',
            'promotion_type' => $type,
            'count' => $rows->count(),
            'promotions' => $rows->map(fn (Discount $d) => [
                'title' => $d->title ?? null,
                'discount_amount' => (float) ($d->discount_amount ?? 0),
                'discount_amount_type' => $d->discount_amount_type,
                'start_date' => $d->start_date,
                'end_date' => $d->end_date,
                'coupon_codes' => $d->coupons?->pluck('coupon_code')->filter()->values()->all() ?? [],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionsOverview(): array
    {
        $totalPackages = SubscriptionPackage::query()->count();
        $activePackages = SubscriptionPackage::query()->where('is_active', 1)->count();
        $totalSubscribers = PackageSubscriber::query()->count();
        $activeSubscribers = PackageSubscriber::query()->where('package_end_date', '>=', now())->count();
        $expired = $totalSubscribers - $activeSubscribers;
        $expiringSoon = PackageSubscriber::query()
            ->whereBetween('package_end_date', [now(), now()->addDays(14)])
            ->count();

        return [
            'ok' => true,
            'analysis' => 'subscription_overview',
            'total_packages' => $totalPackages,
            'active_packages' => $activePackages,
            'total_subscribers' => $totalSubscribers,
            'active_subscribers' => $activeSubscribers,
            'expired_subscribers' => $expired,
            'expiring_within_14_days' => $expiringSoon,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionsByPackage(): array
    {
        $rows = PackageSubscriber::query()
            ->select('package_name', 'subscription_package_id', DB::raw('count(*) as subscriber_count'))
            ->groupBy('package_name', 'subscription_package_id')
            ->orderByDesc('subscriber_count')
            ->limit(15)
            ->get();

        return [
            'ok' => true,
            'analysis' => 'by_package',
            'by_package' => $rows->map(fn ($r) => [
                'package_name' => $r->package_name,
                'subscriber_count' => (int) $r->subscriber_count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function subscriptionsExpiringSoon(array $args): array
    {
        $days = max(1, (int) ($args['days'] ?? 14));
        $rows = PackageSubscriber::query()
            ->with(['provider:id,company_name,company_phone'])
            ->whereBetween('package_end_date', [now(), now()->addDays($days)])
            ->orderBy('package_end_date')
            ->limit($this->limit($args))
            ->get();

        return [
            'ok' => true,
            'analysis' => 'expiring_soon',
            'days_window' => $days,
            'count' => $rows->count(),
            'subscribers' => $rows->map(fn (PackageSubscriber $sub) => [
                'provider' => $sub->provider?->company_name,
                'package_name' => $sub->package_name,
                'end_date' => $sub->package_end_date?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function limit(array $args): int
    {
        $max = (int) config('admin_business_ai.max_query_limit', 50);
        $default = (int) config('admin_business_ai.default_query_limit', 25);
        $n = (int) ($args['limit'] ?? $default);

        return max(1, min($max, $n));
    }
}
