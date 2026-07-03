<?php

namespace Modules\AdminModule\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class AdminDashboardCache
{
    public const METRICS_TTL = 120;

    public static function rememberMetrics(string $key, Closure $callback): mixed
    {
        return Cache::remember("admin_dashboard_metrics:{$key}", self::METRICS_TTL, $callback);
    }

    public static function forgetMetrics(): void
    {
        $keys = [
            'financial_summary:v3',
            'revenue_totals:v1',
            'top_providers:v1',
            'top_customers:v1',
        ];

        foreach ($keys as $key) {
            Cache::forget("admin_dashboard_metrics:{$key}");
        }

        $year = (int) date('Y');
        for ($y = $year - 2; $y <= $year + 1; $y++) {
            Cache::forget("admin_dashboard_metrics:revenue_by_month:{$y}");
            Cache::forget("admin_dashboard_metrics:commission_adj:{$y}");
        }
    }
}
