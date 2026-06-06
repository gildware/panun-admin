<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\CategoryManagement\Entities\Category;
use Modules\ZoneManagement\Entities\Zone;

class AdminBusinessAiBookingQueueInsightService
{
    public function __construct(
        protected AdminBusinessAiBookingInsightService $bookingInsights,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function query(array $args): array
    {
        $queue = strtolower(trim((string) ($args['queue'] ?? 'overdue_followups')));

        return match ($queue) {
            'verify_requests' => $this->verifyRequests($args),
            'offline_payments' => $this->offlinePayments($args),
            'special_scenarios' => $this->specialScenarios($args),
            'overdue_followups' => $this->overdueFollowups($args),
            default => [
                'ok' => false,
                'error' => 'unknown_queue',
                'allowed' => ['verify_requests', 'offline_payments', 'special_scenarios', 'overdue_followups'],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))?->live_values ?? 0;
        $tabs = BookingFinancialSettlementService::specialScenarioListTabOutcomes();
        $scenarioCounts = [];
        $baseCount = Booking::query()
            ->where('is_repeated', 0)
            ->whereNotNull('settlement_outcome')
            ->where('settlement_outcome', '!=', '');
        foreach ($tabs as $tabKey => $out) {
            $scenarioCounts[$tabKey] = $out === null
                ? (clone $baseCount)->count()
                : (clone $baseCount)->where('settlement_outcome', $out)->count();
        }

        return [
            'ok' => true,
            'verify_pending' => $this->verifyCount($maxBookingAmount, 'pending'),
            'verify_denied' => $this->verifyCount($maxBookingAmount, 'denied'),
            'offline_payment_pending' => $this->offlinePaymentBaseQuery()->count(),
            'special_scenario_counts' => $scenarioCounts,
            'overdue_booking_followups' => BookingFollowup::query()
                ->where('status', 'scheduled')
                ->whereDate('date', '<=', Carbon::today())
                ->whereHas('booking', fn ($bq) => $bq->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS))
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function verifyRequests(array $args): array
    {
        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))?->live_values ?? 0;
        $type = strtolower(trim((string) ($args['verify_type'] ?? 'pending')));
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $q = Booking::query()
            ->with(['customer:id,first_name,last_name,phone', 'provider:id,company_name', 'assignee:id,first_name,last_name', 'zone:id,name'])
            ->where('payment_method', 'cash_after_service')
            ->where('total_booking_amount', '>', $maxBookingAmount)
            ->whereIn('booking_status', ['pending', 'accepted']);

        if ($type === 'denied') {
            $q->where('is_verified', 2);
        } else {
            $q->where('is_verified', 0);
        }

        $this->applyCommonBookingFilters($q, $args);

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($limit)->get();

        return [
            'ok' => true,
            'queue' => 'verify_requests',
            'verify_type' => $type,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'bookings' => $this->bookingInsights->enrichSummaries($rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function offlinePayments(array $args): array
    {
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));
        $q = $this->offlinePaymentBaseQuery()
            ->with([
                'customer:id,first_name,last_name,phone',
                'provider:id,company_name',
                'assignee:id,first_name,last_name',
                'zone:id,name',
                'booking_partial_payments',
            ]);

        $this->applyCommonBookingFilters($q, $args);

        if (! empty($args['search'])) {
            $search = trim((string) $args['search']);
            $q->where(function ($query) use ($search) {
                $query->where('readable_id', 'like', '%'.$search.'%')
                    ->orWhereHas('booking_partial_payments', function ($pq) use ($search) {
                        $pq->where('paid_with', 'offline')->where('transaction_id', 'like', '%'.$search.'%');
                    });
            });
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($limit)->get();

        return [
            'ok' => true,
            'queue' => 'offline_payments',
            'total_matching' => $total,
            'returned' => $rows->count(),
            'bookings' => $rows->map(function (Booking $b) {
                $summary = $this->bookingInsights->enrichSummaries(collect([$b]))[0] ?? [];
                $offlinePartial = $b->booking_partial_payments
                    ->where('paid_with', 'offline')
                    ->first();

                return array_merge($summary, [
                    'offline_transaction_id' => $offlinePartial?->transaction_id,
                    'offline_paid_amount' => $offlinePartial ? (float) ($offlinePartial->paid_amount ?? 0) : null,
                ]);
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function specialScenarios(array $args): array
    {
        $tabs = BookingFinancialSettlementService::specialScenarioListTabOutcomes();
        $scenario = strtolower(trim((string) ($args['scenario'] ?? 'all')));
        if ($scenario === 'cancel_after_visit') {
            $scenario = 'cancelled_after_visit';
        }
        $outcomeFilter = $tabs[$scenario] ?? null;
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $q = Booking::query()
            ->with(['customer:id,first_name,last_name,phone', 'provider:id,company_name', 'assignee:id,first_name,last_name', 'zone:id,name'])
            ->where('is_repeated', 0)
            ->whereNotNull('settlement_outcome')
            ->where('settlement_outcome', '!=', '')
            ->when($outcomeFilter !== null, fn ($bq) => $bq->where('settlement_outcome', $outcomeFilter));

        $this->applyCommonBookingFilters($q, $args);

        $counts = [];
        $baseCount = Booking::query()
            ->where('is_repeated', 0)
            ->whereNotNull('settlement_outcome')
            ->where('settlement_outcome', '!=', '');
        foreach ($tabs as $tabKey => $out) {
            $counts[$tabKey] = $out === null
                ? (clone $baseCount)->count()
                : (clone $baseCount)->where('settlement_outcome', $out)->count();
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($limit)->get();

        return [
            'ok' => true,
            'queue' => 'special_scenarios',
            'scenario' => $scenario,
            'scenario_counts' => $counts,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'bookings' => $rows->map(function (Booking $b) {
                $summary = $this->bookingInsights->enrichSummaries(collect([$b]))[0] ?? [];

                return array_merge($summary, [
                    'settlement_outcome' => $b->settlement_outcome,
                    'settlement_remarks' => $b->settlement_remarks,
                    'after_visit_cancel' => (bool) $b->after_visit_cancel,
                ]);
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function overdueFollowups(array $args): array
    {
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));
        $asOf = ! empty($args['as_of_date'])
            ? Carbon::parse((string) $args['as_of_date'])->endOfDay()
            : Carbon::today()->endOfDay();

        $followupQ = BookingFollowup::query()
            ->with(['booking.customer:id,first_name,last_name,phone', 'booking.provider:id,company_name', 'booking.assignee:id,first_name,last_name', 'booking.zone:id,name'])
            ->where('status', 'scheduled')
            ->where('date', '<=', $asOf)
            ->whereHas('booking', function ($bq) use ($args) {
                $bq->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
                $this->applyCommonBookingFilters($bq, $args);
            });

        $total = (clone $followupQ)->count();
        $rows = $followupQ->orderBy('date')->limit($limit)->get();

        return [
            'ok' => true,
            'queue' => 'overdue_followups',
            'as_of' => $asOf->toDateString(),
            'total_matching' => $total,
            'returned' => $rows->count(),
            'followups' => $rows->map(fn (BookingFollowup $f) => [
                'id' => $f->id,
                'date' => $f->date?->toIso8601String(),
                'for' => $f->for,
                'reason' => $f->reason,
                'remarks' => $f->remarks,
                'booking' => $f->booking ? array_merge(
                    $this->bookingInsights->enrichSummaries(collect([$f->booking]))[0] ?? [],
                    ['status' => $f->booking->booking_status]
                ) : null,
            ])->values()->all(),
        ];
    }

    /**
     * @return Builder<Booking>
     */
    private function offlinePaymentBaseQuery(): Builder
    {
        return Booking::query()
            ->whereIn('booking_status', ['pending', 'accepted'])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('payment_method', 'offline_payment')->where('is_paid', 0);
                })->orWhereHas('booking_partial_payments', function ($q) {
                    $q->where('paid_with', 'offline');
                });
            });
    }

    private function verifyCount(mixed $maxBookingAmount, string $type): int
    {
        $q = Booking::query()
            ->where('payment_method', 'cash_after_service')
            ->where('total_booking_amount', '>', $maxBookingAmount)
            ->whereIn('booking_status', ['pending', 'accepted']);

        return $type === 'denied'
            ? (clone $q)->where('is_verified', 2)->count()
            : (clone $q)->where('is_verified', 0)->count();
    }

    /**
     * @param  Builder<Booking>  $q
     * @param  array<string, mixed>  $args
     */
    private function applyCommonBookingFilters(Builder $q, array $args): void
    {
        if (! empty($args['readable_id'])) {
            $q->where('readable_id', 'like', '%'.trim((string) $args['readable_id']).'%');
        }
        if (! empty($args['booking_status']) && (string) $args['booking_status'] !== 'all') {
            $q->where('booking_status', strtolower(trim((string) $args['booking_status'])));
        }
        if (! empty($args['zone'])) {
            $zoneName = trim((string) $args['zone']);
            $zoneId = Zone::query()->where('name', 'like', '%'.$zoneName.'%')->value('id');
            if ($zoneId) {
                $q->where('zone_id', $zoneId);
            } else {
                $q->whereRaw('1 = 0');
            }
        }
        if (! empty($args['category'])) {
            $catName = trim((string) $args['category']);
            $catId = Category::query()->where('name', 'like', '%'.$catName.'%')->value('id');
            if ($catId) {
                $q->where(function ($cq) use ($catId) {
                    $cq->where('category_id', $catId)->orWhere('sub_category_id', $catId);
                });
            } else {
                $q->whereRaw('1 = 0');
            }
        }
        if (! empty($args['assignee_id'])) {
            $q->where('assignee_id', (string) $args['assignee_id']);
        } elseif (! empty($args['assignee_search'])) {
            $s = '%'.trim((string) $args['assignee_search']).'%';
            $q->whereHas('assignee', function ($aq) use ($s) {
                $aq->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }
        if (! empty($args['lead_id'])) {
            $q->where('lead_id', (int) $args['lead_id']);
        }
        if (! empty($args['date_from'])) {
            $q->where('created_at', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('created_at', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }
    }
}
