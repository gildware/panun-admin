<?php

namespace Modules\ProviderManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard earning cards for the provider app — sums provider_earning from completed
 * revenue-reporting jobs (same basis as business / earning reports), not wallet credits.
 */
class ProviderDashboardEarningStatsService
{
    /**
     * @return array{this_week: array{total: float, change: float}, this_month: array{total: float, change: float}, this_year: array{total: float, change: float}}
     */
    public function forProvider(string $providerId): array
    {
        $now = Carbon::now();

        $thisWeekStart = $now->copy()->startOfWeek();
        $thisWeekEnd = $now->copy()->endOfWeek();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();

        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $thisYearStart = $now->copy()->startOfYear();
        $thisYearEnd = $now->copy()->endOfYear();
        $lastYearStart = $now->copy()->subYear()->startOfYear();
        $lastYearEnd = $now->copy()->subYear()->endOfYear();

        $thisWeek = $this->sumProviderEarning($providerId, $thisWeekStart, $thisWeekEnd);
        $lastWeek = $this->sumProviderEarning($providerId, $lastWeekStart, $lastWeekEnd);

        $thisMonth = $this->sumProviderEarning($providerId, $thisMonthStart, $thisMonthEnd);
        $lastMonth = $this->sumProviderEarning($providerId, $lastMonthStart, $lastMonthEnd);

        $thisYear = $this->sumProviderEarning($providerId, $thisYearStart, $thisYearEnd);
        $lastYear = $this->sumProviderEarning($providerId, $lastYearStart, $lastYearEnd);

        return [
            'this_week' => [
                'total' => round($thisWeek, 2),
                'change' => $this->percentChange($thisWeek, $lastWeek),
            ],
            'this_month' => [
                'total' => round($thisMonth, 2),
                'change' => $this->percentChange($thisMonth, $lastMonth),
            ],
            'this_year' => [
                'total' => round($thisYear, 2),
                'change' => $this->percentChange($thisYear, $lastYear),
            ],
        ];
    }

    private function sumProviderEarning(string $providerId, Carbon $start, Carbon $end): float
    {
        $start = $start->copy()->startOfSecond();
        $end = $end->copy()->endOfSecond();

        $oneTime = (float) DB::table('booking_details_amounts as bda')
            ->join('bookings as b', 'b.id', '=', 'bda.booking_id')
            ->where('b.provider_id', $providerId)
            ->whereNull('bda.booking_repeat_id')
            ->where(function ($query) {
                $query->where('b.booking_status', 'completed')
                    ->orWhere(function ($nested) {
                        $nested->where('b.booking_status', 'canceled')
                            ->where('b.after_visit_cancel', 1);
                    });
            })
            ->whereBetween('bda.created_at', [$start, $end])
            ->sum('bda.provider_earning');

        $repeats = (float) DB::table('booking_details_amounts as bda')
            ->join('booking_repeats as br', 'br.id', '=', 'bda.booking_repeat_id')
            ->join('bookings as b', 'b.id', '=', 'br.booking_id')
            ->where('b.provider_id', $providerId)
            ->where('br.booking_status', 'completed')
            ->whereBetween('bda.created_at', [$start, $end])
            ->sum('bda.provider_earning');

        return $oneTime + $repeats;
    }

    private function percentChange(float $current, float $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }

        return $current > 0 ? 100.0 : 0.0;
    }
}
