<?php

namespace Modules\BookingModule\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\BookingModule\Entities\Booking;

class AdminBookingListQueryService
{
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
     * @param  array<int, string>  $assigneeIds
     * @return array<string, int>
     */
    public function statusTabCounts(
        Request $request,
        array $assigneeIds,
        bool $repeatOnly,
        mixed $maxBookingAmount
    ): array {
        $tabOptions = ['max_booking_amount' => $maxBookingAmount];
        $scoped = function () use ($request, $assigneeIds, $repeatOnly) {
            return $this->applySharedFilters(Booking::query(), $request, $assigneeIds, $repeatOnly);
        };

        return [
            'all' => $scoped()->count(),
            'pending' => $scoped()->applyBookingListStatusTab('pending', $tabOptions)->count(),
            'accepted' => $scoped()->applyBookingListStatusTab('accepted', $tabOptions)->count(),
            'ongoing' => $scoped()->applyBookingListStatusTab('ongoing', $tabOptions)->count(),
            'completed' => $scoped()->applyBookingListStatusTab('completed', $tabOptions)->count(),
            'reopened' => $scoped()->applyBookingListStatusTab('reopened', $tabOptions)->count(),
            'on_hold' => $scoped()->applyBookingListStatusTab('on_hold', $tabOptions)->count(),
            'canceled' => $scoped()->applyBookingListStatusTab('canceled', $tabOptions)->count(),
            'hold_after_visit' => $scoped()->applyBookingListStatusTab('hold_after_visit', $tabOptions)->count(),
            'resolved' => $scoped()->applyBookingListStatusTab('resolved', $tabOptions)->count(),
            'disputed_cancelled' => $scoped()->applyBookingListStatusTab('disputed_cancelled', $tabOptions)->count(),
            'disputed_completed' => $scoped()->applyBookingListStatusTab('disputed_completed', $tabOptions)->count(),
            'completed_no_or_little' => $scoped()->applyBookingListStatusTab('completed_no_or_little', $tabOptions)->count(),
            'cancelled_after_visit' => $scoped()->applyBookingListStatusTab('cancelled_after_visit', $tabOptions)->count(),
            'loss_making_pending' => $scoped()->applyBookingListStatusTab('loss_making_pending', $tabOptions)->count(),
            'loss_recovered' => $scoped()->applyBookingListStatusTab('loss_recovered', $tabOptions)->count(),
            'loss_settled' => $scoped()->applyBookingListStatusTab('loss_settled', $tabOptions)->count(),
        ];
    }
}
