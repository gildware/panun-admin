<?php

namespace Modules\BookingModule\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\BookingModule\Entities\Booking;

class AdminBookingListQueryService
{
    /**
     * How long tab badge counts may be slightly stale (seconds).
     */
    private const TAB_COUNTS_CACHE_TTL = 45;

    /**
     * Filters shared by the booking table and the status-tab counts.
     * Status itself is applied separately so each tab can keep its own total.
     *
     * @param  array<int, string>  $assigneeIds
     */
    public function applySharedFilters(
        Builder $query,
        Request $request,
        array $assigneeIds,
        bool $repeatOnly,
        bool $scopeRepeatFlag = true
    ): Builder {
        $query
            ->adminListSearch($request->input('search'))
            ->when($request->input('provider_assigned') === 'assigned', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNotNull('provider_id')
                        ->orWhereHas('repeat', function ($q) {
                            $q->whereNotNull('provider_id');
                        });
                });
            })
            ->when($request->input('provider_assigned') === 'unassigned', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('provider_id');
                });
            })
            ->when($scopeRepeatFlag, function ($query) use ($repeatOnly) {
                $query->where('is_repeated', $repeatOnly ? 1 : 0);
            })
            ->filterByZoneIds($request->input('zone_ids'))
            ->filterBySubcategoryIds($request->input('sub_category_ids'))
            ->filterByCategoryIds($request->input('category_ids'))
            ->filterByDateRange($request->input('start_date'), $request->input('end_date'))
            ->filterByScheduleDateRange($request->input('schedule_start_date'), $request->input('schedule_end_date'))
            ->filterByAssigneeIds($assigneeIds);

        return $query;
    }

    /**
     * Status tab totals for the current list filters (not the unfiltered database).
     *
     * Simple status badges are computed in one conditional aggregate query.
     * Complex tabs (reopen / loss / hold-after-visit / etc.) still need scoped counts,
     * and the full result is cached briefly to avoid repeating that work on every refresh.
     *
     * @param  array<int, string>  $assigneeIds
     * @return array<string, int>
     */
    public function statusTabCounts(
        Request $request,
        array $assigneeIds,
        bool $repeatOnly,
        mixed $maxBookingAmount
    ): array {
        $cacheKey = 'admin_booking_tab_counts:'.md5((string) json_encode([
            'search' => (string) $request->input('search', ''),
            'provider_assigned' => (string) $request->input('provider_assigned', ''),
            'zone_ids' => array_values((array) $request->input('zone_ids', [])),
            'sub_category_ids' => array_values((array) $request->input('sub_category_ids', [])),
            'category_ids' => array_values((array) $request->input('category_ids', [])),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date' => (string) $request->input('end_date', ''),
            'schedule_start_date' => (string) $request->input('schedule_start_date', ''),
            'schedule_end_date' => (string) $request->input('schedule_end_date', ''),
            'assignee_ids' => array_values($assigneeIds),
            'repeat_only' => $repeatOnly,
            'max_booking_amount' => $maxBookingAmount,
        ]));

        return Cache::remember($cacheKey, self::TAB_COUNTS_CACHE_TTL, function () use ($request, $assigneeIds, $repeatOnly, $maxBookingAmount) {
            return $this->computeStatusTabCounts($request, $assigneeIds, $repeatOnly, $maxBookingAmount);
        });
    }

    /**
     * @param  array<int, string>  $assigneeIds
     * @return array<string, int>
     */
    private function computeStatusTabCounts(
        Request $request,
        array $assigneeIds,
        bool $repeatOnly,
        mixed $maxBookingAmount
    ): array {
        $tabOptions = ['max_booking_amount' => $maxBookingAmount];
        $scoped = function () use ($request, $assigneeIds, $repeatOnly) {
            return $this->applySharedFilters(Booking::query(), $request, $assigneeIds, $repeatOnly);
        };

        $max = is_numeric($maxBookingAmount) ? (float) $maxBookingAmount : null;
        $simple = $this->simpleStatusTabCounts($scoped(), $max);

        $complexTabs = [
            'reopened',
            'hold_after_visit',
            'resolved',
            'disputed_cancelled',
            'disputed_completed',
            'completed_no_or_little',
            'cancelled_after_visit',
            'loss_making_pending',
            'loss_recovered',
            'loss_settled',
        ];

        $counts = $simple;
        foreach ($complexTabs as $tab) {
            $counts[$tab] = (int) $scoped()->applyBookingListStatusTab($tab, $tabOptions)->count();
        }

        return $counts;
    }

    /**
     * One query for the common status tabs (plus "all").
     *
     * @return array<string, int>
     */
    private function simpleStatusTabCounts(Builder $query, ?float $maxBookingAmount): array
    {
        $table = $query->getModel()->getTable();
        $pendingSql = $this->adminPendingCaseSql($table, $maxBookingAmount);
        $acceptedSql = $this->adminAcceptedCaseSql($table, $maxBookingAmount);

        $bindings = [];
        if ($maxBookingAmount !== null) {
            $bindings[] = $maxBookingAmount;
            $bindings[] = $maxBookingAmount;
        }

        $row = $query
            ->reorder()
            ->toBase()
            ->selectRaw(
                "COUNT(*) as `all_count`,
                SUM(CASE WHEN {$pendingSql} THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN {$acceptedSql} THEN 1 ELSE 0 END) as accepted_count,
                SUM(CASE WHEN {$table}.booking_status = 'ongoing' THEN 1 ELSE 0 END) as ongoing_count,
                SUM(CASE WHEN {$table}.booking_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN {$table}.booking_status = 'on_hold' THEN 1 ELSE 0 END) as on_hold_count,
                SUM(CASE WHEN {$table}.booking_status IN ('canceled', 'refunded') THEN 1 ELSE 0 END) as canceled_count",
                $bindings
            )
            ->first();

        return [
            'all' => (int) ($row->all_count ?? 0),
            'pending' => (int) ($row->pending_count ?? 0),
            'accepted' => (int) ($row->accepted_count ?? 0),
            'ongoing' => (int) ($row->ongoing_count ?? 0),
            'completed' => (int) ($row->completed_count ?? 0),
            'on_hold' => (int) ($row->on_hold_count ?? 0),
            'canceled' => (int) ($row->canceled_count ?? 0),
        ];
    }

    private function adminPendingCaseSql(string $table, ?float $maxBookingAmount): string
    {
        if ($maxBookingAmount === null) {
            return "{$table}.booking_status = 'pending'";
        }

        // Mirrors scopeAdminPendingBookings (cash_after_service amount / verified gate).
        return "{$table}.booking_status = 'pending' AND (
            {$table}.payment_method != 'cash_after_service'
            OR (
                {$table}.payment_method = 'cash_after_service'
                AND ({$table}.total_booking_amount <= ? OR {$table}.is_verified = 1)
            )
        )";
    }

    private function adminAcceptedCaseSql(string $table, ?float $maxBookingAmount): string
    {
        if ($maxBookingAmount === null) {
            return "{$table}.booking_status = 'accepted'";
        }

        return "{$table}.booking_status = 'accepted' AND (
            {$table}.payment_method != 'cash_after_service'
            OR (
                {$table}.payment_method = 'cash_after_service'
                AND ({$table}.total_booking_amount <= ? OR {$table}.is_verified = 1)
            )
        )";
    }
}
