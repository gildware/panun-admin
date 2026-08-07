<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDisputeReason;
use Modules\BookingModule\Entities\BookingHoldReopenReason;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Services\BookingFinancialSettlementService;

class EmployeeBookingStatusAnalyticsService
{
    /**
     * @param  list<string>  $employeeIds
     * @param  list<array<string, mixed>>  $dailyRows
     * @return array{
     *     series: list<array{key: string, name: string, color: string, data: list<int>}>,
     *     totals: array<string, int>,
     *     widgets: list<array<string, mixed>>,
     *     reason_reports: list<array<string, mixed>>
     * }
     */
    public function build(
        array $employeeIds,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $dailyRows,
        int $bookingsCreated,
        int $completedBookings,
        int $cancelledBookings,
        float $completedAmount,
        float $completionRate,
        int $activePipeline,
    ): array {
        $definitions = $this->statusDefinitions();
        $dayCount = max(1, $dailyRows === [] ? 1 : count($dailyRows));
        $zeros = array_fill(0, $dayCount, 0);
        $emptyTotals = array_fill_keys(array_column($definitions, 'key'), 0);

        if ($employeeIds === []) {
            return [
                'series' => $this->coreEmptySeries($definitions, $zeros),
                'totals' => $emptyTotals,
                'widgets' => $this->buildWidgets($bookingsCreated, $completedBookings, $cancelledBookings, $completedAmount, $completionRate, $activePipeline, $emptyTotals, $definitions),
                'reason_reports' => $this->emptyReasonReports(),
            ];
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $bookings = Booking::query()
            ->whereIn('assignee_id', $employeeIds)
            ->whereNotNull('assignee_id')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->get([
                'id',
                'created_at',
                'booking_status',
                'after_visit_cancel',
                'settlement_outcome',
                'settlement_snapshot',
                'settlement_config',
                'settlement_remarks',
                'reopen_disputed_snapshot',
            ]);

        $holdAfterVisitSet = $this->holdAfterVisitIdSet(
            $bookings->filter(fn ($b) => strtolower((string) $b->booking_status) === 'on_hold')->pluck('id')->all()
        );

        $counts = [];
        $totals = $emptyTotals;
        $idsByBucket = [];
        foreach ($bookings as $booking) {
            $day = Carbon::parse($booking->created_at)->toDateString();
            $key = $this->classifyBooking($booking, $holdAfterVisitSet);
            $counts[$day][$key] = ($counts[$day][$key] ?? 0) + 1;
            $totals[$key] = ($totals[$key] ?? 0) + 1;
            $idsByBucket[$key][] = (string) $booking->id;
        }

        $dayKeys = $dailyRows === []
            ? [$periodStart->toDateString()]
            : array_map(fn (array $row) => (string) ($row['date'] ?? ''), $dailyRows);

        $series = [];
        foreach ($definitions as $def) {
            $data = [];
            foreach ($dayKeys as $day) {
                $data[] = (int) ($counts[$day][$def['key']] ?? 0);
            }
            if (array_sum($data) <= 0) {
                continue;
            }
            $series[] = [
                'key' => $def['key'],
                'name' => $def['name'],
                'color' => $def['color'],
                'data' => $data,
            ];
        }

        if ($series === []) {
            $series = $this->coreEmptySeries($definitions, $zeros);
        }

        return [
            'series' => $series,
            'totals' => $totals,
            'widgets' => $this->buildWidgets($bookingsCreated, $completedBookings, $cancelledBookings, $completedAmount, $completionRate, $activePipeline, $totals, $definitions),
            'reason_reports' => $this->buildReasonReports($bookings, $idsByBucket, $holdAfterVisitSet),
        ];
    }

    /**
     * @param  list<array{key: string, name: string, color: string, tone?: string, icon?: string}>  $definitions
     * @return list<array{key: string, name: string, color: string, data: list<int>}>
     */
    private function coreEmptySeries(array $definitions, array $zeros): array
    {
        $core = ['pending', 'on_hold', 'hold_after_visit', 'completed', 'canceled', 'disputed', 'loss_making'];

        return array_values(array_map(fn (array $def) => [
            'key' => $def['key'],
            'name' => $def['name'],
            'color' => $def['color'],
            'data' => $zeros,
        ], array_filter($definitions, fn (array $def) => in_array($def['key'], $core, true))));
    }

    /**
     * @param  list<string|int>  $onHoldIds
     * @return array<string, bool>
     */
    private function holdAfterVisitIdSet(array $onHoldIds): array
    {
        if ($onHoldIds === []) {
            return [];
        }

        return array_fill_keys(
            Booking::query()->whereIn('id', $onHoldIds)->holdAfterVisit()->pluck('id')->map(fn ($id) => (string) $id)->all(),
            true
        );
    }

    /**
     * @param  array<string, bool>  $holdAfterVisitSet
     */
    private function classifyBooking(Booking $booking, array $holdAfterVisitSet): string
    {
        $id = (string) $booking->id;
        $status = strtolower(trim((string) $booking->booking_status));
        $outcome = (string) ($booking->settlement_outcome ?? '');
        $snapshot = $booking->reopen_disputed_snapshot;
        $isDisputed = false;
        if (is_array($snapshot) && $snapshot !== []) {
            $isDisputed = (($snapshot['type'] ?? '') === 'reopen_disputed_refund') || array_key_exists('booking_dispute_reason_id', $snapshot);
        } elseif (is_string($snapshot) && $snapshot !== '') {
            $isDisputed = str_contains($snapshot, 'reopen_disputed_refund');
        }

        if ($isDisputed) {
            return in_array($status, ['canceled', 'cancelled', 'refunded'], true)
                ? 'disputed_cancelled'
                : ($status === 'completed' ? 'disputed_completed' : 'disputed');
        }

        if ($outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return $this->classifyLossSubtype($booking);
        }

        if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            if ($booking->after_visit_cancel || $outcome === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL) {
                return 'cancelled_after_visit';
            }

            return 'canceled';
        }

        if ($status === 'completed' && $outcome === BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT) {
            return 'completed_no_or_little';
        }

        if ($status === 'on_hold') {
            return isset($holdAfterVisitSet[$id]) ? 'hold_after_visit' : 'on_hold';
        }

        return match ($status) {
            'pending' => 'pending',
            'accepted' => 'accepted',
            'ongoing' => 'ongoing',
            'pending_cancellation' => 'pending_cancellation',
            'completed' => 'completed',
            default => 'other',
        };
    }

    private function classifyLossSubtype(Booking $booking): string
    {
        $snap = is_array($booking->settlement_snapshot) ? $booking->settlement_snapshot : [];
        $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
        $loss = (float) ($snap['scaled_loss_amount'] ?? 0);
        $writeoff = (float) ($snap['scaled_loss_writeoff_amount'] ?? $cfg['scaled_loss_writeoff_amount'] ?? 0);

        if ($writeoff > 0.009) {
            return 'loss_settled';
        }
        if ($loss <= 0.009) {
            return 'loss_recovered';
        }

        return 'loss_making';
    }

    /**
     * @return list<array{key: string, name: string, color: string, tone: string, icon: string}>
     */
    private function statusDefinitions(): array
    {
        return [
            ['key' => 'pending', 'name' => translate('Pending'), 'color' => '#d97706', 'tone' => 'warning', 'icon' => 'hourglass_top'],
            ['key' => 'accepted', 'name' => translate('Accepted'), 'color' => '#2563eb', 'tone' => 'brand', 'icon' => 'thumb_up'],
            ['key' => 'ongoing', 'name' => translate('Ongoing'), 'color' => '#5c6194', 'tone' => 'brand', 'icon' => 'play_circle'],
            ['key' => 'on_hold', 'name' => translate('On_hold') ?? 'On hold', 'color' => '#64748b', 'tone' => 'warning', 'icon' => 'pause_circle'],
            ['key' => 'hold_after_visit', 'name' => translate('Hold_after_visit') ?? 'Hold after visit', 'color' => '#7c3aed', 'tone' => 'warning', 'icon' => 'home_work'],
            ['key' => 'pending_cancellation', 'name' => translate('Pending_cancellation') ?? 'Pending cancellation', 'color' => '#ea580c', 'tone' => 'danger', 'icon' => 'hourglass_bottom'],
            ['key' => 'completed', 'name' => translate('Bookings_completed') ?? translate('completed'), 'color' => '#059669', 'tone' => 'good', 'icon' => 'check_circle'],
            ['key' => 'completed_no_or_little', 'name' => translate('Bfs_list_badge_completed_no_or_little') ?? 'Completed (visit fee)', 'color' => '#0d9488', 'tone' => 'good', 'icon' => 'volunteer_activism'],
            ['key' => 'canceled', 'name' => translate('Cancelled'), 'color' => '#dc2626', 'tone' => 'danger', 'icon' => 'cancel'],
            ['key' => 'cancelled_after_visit', 'name' => translate('Bfs_list_badge_cancelled_after_visit') ?? 'Cancelled after visit', 'color' => '#b91c1c', 'tone' => 'danger', 'icon' => 'event_busy'],
            ['key' => 'disputed', 'name' => translate('Disputed') ?? 'Disputed', 'color' => '#9333ea', 'tone' => 'danger', 'icon' => 'gavel'],
            ['key' => 'disputed_cancelled', 'name' => translate('Disputed_cancelled') ?? (translate('Disputed').' · '.translate('Cancelled')), 'color' => '#7e22ce', 'tone' => 'danger', 'icon' => 'gavel'],
            ['key' => 'disputed_completed', 'name' => translate('Disputed_completed') ?? (translate('Disputed').' · '.translate('completed')), 'color' => '#6b21a8', 'tone' => 'danger', 'icon' => 'gavel'],
            ['key' => 'loss_making', 'name' => translate('Loss_making') ?? 'Loss making', 'color' => '#c2410c', 'tone' => 'danger', 'icon' => 'trending_down'],
            ['key' => 'loss_recovered', 'name' => translate('Loss_recovered') ?? 'Loss recovered', 'color' => '#ea580c', 'tone' => 'warning', 'icon' => 'replay'],
            ['key' => 'loss_settled', 'name' => translate('Loss_settled') ?? 'Loss settled', 'color' => '#9a3412', 'tone' => 'warning', 'icon' => 'request_quote'],
            ['key' => 'other', 'name' => translate('Other') ?? 'Other', 'color' => '#94a3b8', 'tone' => 'brand', 'icon' => 'more_horiz'],
        ];
    }

    /**
     * @param  array<string, int>  $totals
     * @param  list<array{key: string, name: string, color: string, tone: string, icon: string}>  $definitions
     * @return list<array<string, mixed>>
     */
    private function buildWidgets(
        int $handled,
        int $completed,
        int $cancelled,
        float $completedAmount,
        float $completionRate,
        int $activePipeline,
        array $totals,
        array $definitions,
    ): array {
        $pct = fn (int $count): float => $handled > 0 ? round(($count / $handled) * 100, 1) : 0.0;
        $always = ['pending', 'on_hold', 'hold_after_visit', 'completed', 'canceled', 'disputed_cancelled', 'disputed_completed', 'loss_making', 'loss_recovered', 'loss_settled', 'cancelled_after_visit'];

        $rows = [
            [
                'key' => 'handled',
                'label' => translate('Bookings_created'),
                'sublabel' => translate('Progress_bookings_handled_sub'),
                'count' => $handled,
                'total' => $handled,
                'pct' => $handled > 0 ? 100.0 : 0.0,
                'tone' => 'brand',
                'icon' => 'event',
            ],
            [
                'key' => 'completed_amount',
                'label' => translate('Completed_amount'),
                'sublabel' => translate('Completed_amount'),
                'count' => (int) round($completedAmount),
                'value' => with_currency_symbol($completedAmount),
                'pct' => null,
                'tone' => 'good',
                'icon' => 'payments',
            ],
            [
                'key' => 'completion_rate',
                'label' => translate('completion_rate'),
                'sublabel' => translate('Bookings_completed'),
                'count' => (int) round($completionRate),
                'value' => $completionRate.'%',
                'pct' => $completionRate,
                'tone' => 'brand',
                'icon' => 'trending_up',
            ],
        ];

        foreach ($definitions as $def) {
            $count = (int) ($totals[$def['key']] ?? 0);
            if ($count <= 0 && ! in_array($def['key'], $always, true)) {
                continue;
            }
            $rows[] = [
                'key' => $def['key'],
                'label' => $def['name'],
                'sublabel' => translate('Progress_of_created_bookings') ?? translate('Bookings_created'),
                'count' => $count,
                'total' => $handled,
                'pct' => $pct($count),
                'tone' => $def['tone'],
                'icon' => $def['icon'],
            ];
        }

        if ($activePipeline > 0) {
            $rows[] = [
                'key' => 'active_pipeline',
                'label' => translate('Progress_active_bookings'),
                'sublabel' => translate('Progress_open_pipeline_sub'),
                'count' => $activePipeline,
                'total' => $handled,
                'pct' => null,
                'tone' => 'brand',
                'icon' => 'pending_actions',
                'is_pipeline' => true,
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @param  array<string, list<string>>  $idsByBucket
     * @param  array<string, bool>  $holdAfterVisitSet
     * @return list<array<string, mixed>>
     */
    private function buildReasonReports(Collection $bookings, array $idsByBucket, array $holdAfterVisitSet): array
    {
        $byId = $bookings->keyBy(fn ($b) => (string) $b->id);

        $regularHoldIds = [];
        $havIds = [];
        foreach ($bookings as $booking) {
            if (strtolower((string) $booking->booking_status) !== 'on_hold') {
                continue;
            }
            $id = (string) $booking->id;
            if (isset($holdAfterVisitSet[$id])) {
                $havIds[] = $id;
            } else {
                $regularHoldIds[] = $id;
            }
        }

        $cancelledIds = array_values(array_unique(array_merge(
            $idsByBucket['canceled'] ?? [],
            $idsByBucket['cancelled_after_visit'] ?? [],
        )));

        $disputedIds = array_values(array_unique(array_merge(
            $idsByBucket['disputed'] ?? [],
            $idsByBucket['disputed_cancelled'] ?? [],
            $idsByBucket['disputed_completed'] ?? [],
        )));

        $lossIds = array_values(array_unique(array_merge(
            $idsByBucket['loss_making'] ?? [],
            $idsByBucket['loss_recovered'] ?? [],
            $idsByBucket['loss_settled'] ?? [],
        )));

        return [
            [
                'key' => 'on_hold',
                'label' => translate('On_hold') ?? 'On hold',
                'help_key' => 'booking_reason_on_hold',
                'rows' => $this->holdReasonRows($regularHoldIds),
            ],
            [
                'key' => 'hold_after_visit',
                'label' => translate('Hold_after_visit') ?? 'Hold after visit',
                'help_key' => 'booking_reason_hold_after_visit',
                'rows' => $this->holdReasonRows($havIds),
            ],
            [
                'key' => 'canceled',
                'label' => translate('Cancelled'),
                'help_key' => 'booking_reason_cancelled',
                'rows' => $this->cancelReasonRows($cancelledIds),
            ],
            [
                'key' => 'disputed',
                'label' => translate('Disputed') ?? 'Disputed',
                'help_key' => 'booking_reason_disputed',
                'rows' => $this->disputeReasonRows($disputedIds, $byId),
            ],
            [
                'key' => 'loss',
                'label' => translate('Loss_making') ?? 'Loss making',
                'help_key' => 'booking_reason_loss',
                'rows' => $this->lossReasonRows($lossIds, $byId, $idsByBucket),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function emptyReasonReports(): array
    {
        return [
            ['key' => 'on_hold', 'label' => translate('On_hold') ?? 'On hold', 'help_key' => 'booking_reason_on_hold', 'rows' => []],
            ['key' => 'hold_after_visit', 'label' => translate('Hold_after_visit') ?? 'Hold after visit', 'help_key' => 'booking_reason_hold_after_visit', 'rows' => []],
            ['key' => 'canceled', 'label' => translate('Cancelled'), 'help_key' => 'booking_reason_cancelled', 'rows' => []],
            ['key' => 'disputed', 'label' => translate('Disputed') ?? 'Disputed', 'help_key' => 'booking_reason_disputed', 'rows' => []],
            ['key' => 'loss', 'label' => translate('Loss_making') ?? 'Loss making', 'help_key' => 'booking_reason_loss', 'rows' => []],
        ];
    }

    /**
     * @param  list<string>  $bookingIds
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function holdReasonRows(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $latestIds = BookingStatusHistory::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('booking_id', $bookingIds)
            ->whereNull('booking_repeat_id')
            ->where('booking_status', 'on_hold')
            ->groupBy('booking_id');

        $rows = DB::table('booking_status_histories as h')
            ->joinSub($latestIds, 'lh', fn ($j) => $j->on('h.id', '=', 'lh.id'))
            ->leftJoin('booking_hold_reopen_reasons as r', function ($j) {
                $j->on('r.id', '=', 'h.booking_hold_reopen_reason_id')
                    ->where('r.kind', '=', BookingHoldReopenReason::KIND_HOLD);
            })
            ->selectRaw('COALESCE(r.name, ?) as reason_name, COUNT(*) as total', [translate('Not_Specified') ?? 'Not specified'])
            ->groupBy('reason_name')
            ->orderByDesc('total')
            ->get();

        return $this->mapReasonCounts($rows);
    }

    /**
     * @param  list<string>  $bookingIds
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function cancelReasonRows(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $latestIds = BookingStatusHistory::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('booking_id', $bookingIds)
            ->whereNull('booking_repeat_id')
            ->whereIn('booking_status', ['canceled', 'cancelled', 'refunded'])
            ->groupBy('booking_id');

        $histories = DB::table('booking_status_histories as h')
            ->joinSub($latestIds, 'lh', fn ($j) => $j->on('h.id', '=', 'lh.id'))
            ->leftJoin('booking_cancellation_reasons as ar', 'ar.id', '=', 'h.booking_cancellation_reason_id')
            ->leftJoin('booking_customer_cancellation_reasons as cr', 'cr.id', '=', 'h.booking_customer_cancellation_reason_id')
            ->leftJoin('booking_provider_cancellation_reasons as pr', 'pr.id', '=', 'h.booking_provider_cancellation_reason_id')
            ->select([
                'h.booking_cancellation_reason_id',
                'h.booking_customer_cancellation_reason_id',
                'h.booking_provider_cancellation_reason_id',
                'ar.name as admin_reason',
                'cr.name as customer_reason',
                'pr.name as provider_reason',
            ])
            ->get();

        $counts = [];
        foreach ($histories as $row) {
            $label = $row->admin_reason
                ?? $row->customer_reason
                ?? $row->provider_reason
                ?? (translate('Not_Specified') ?? 'Not specified');
            $prefix = '';
            if ($row->admin_reason) {
                $prefix = (translate('Admin') ?? 'Admin').': ';
            } elseif ($row->customer_reason) {
                $prefix = (translate('Customer') ?? 'Customer').': ';
            } elseif ($row->provider_reason) {
                $prefix = (translate('Provider') ?? 'Provider').': ';
            }
            $key = $prefix.$label;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $this->mapCountArray($counts);
    }

    /**
     * @param  list<string>  $bookingIds
     * @param  Collection<string, Booking>  $byId
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function disputeReasonRows(array $bookingIds, Collection $byId): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $counts = [];
        $reasonIds = [];
        foreach ($bookingIds as $id) {
            $booking = $byId->get($id);
            $snap = $booking?->reopen_disputed_snapshot;
            if (is_string($snap) && $snap !== '') {
                $decoded = json_decode($snap, true);
                $snap = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($snap)) {
                $snap = [];
            }
            $name = (string) ($snap['booking_dispute_reason_name'] ?? '');
            $rid = $snap['booking_dispute_reason_id'] ?? null;
            if ($name !== '') {
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            } elseif ($rid) {
                $reasonIds[(string) $rid] = ($reasonIds[(string) $rid] ?? 0) + 1;
            } else {
                $label = translate('Not_Specified') ?? 'Not specified';
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }

        if ($reasonIds !== []) {
            $names = BookingDisputeReason::query()->whereIn('id', array_keys($reasonIds))->pluck('name', 'id');
            foreach ($reasonIds as $rid => $count) {
                $label = (string) ($names[$rid] ?? (translate('Not_Specified') ?? 'Not specified'));
                $counts[$label] = ($counts[$label] ?? 0) + $count;
            }
        }

        // Fallback: latest dispute history FK
        if ($counts === []) {
            $latestIds = BookingStatusHistory::query()
                ->selectRaw('MAX(id) as id')
                ->whereIn('booking_id', $bookingIds)
                ->whereNull('booking_repeat_id')
                ->whereNotNull('booking_dispute_reason_id')
                ->groupBy('booking_id');

            $rows = DB::table('booking_status_histories as h')
                ->joinSub($latestIds, 'lh', fn ($j) => $j->on('h.id', '=', 'lh.id'))
                ->leftJoin('booking_dispute_reasons as r', 'r.id', '=', 'h.booking_dispute_reason_id')
                ->selectRaw('COALESCE(r.name, ?) as reason_name, COUNT(*) as total', [translate('Not_Specified') ?? 'Not specified'])
                ->groupBy('reason_name')
                ->orderByDesc('total')
                ->get();

            return $this->mapReasonCounts($rows);
        }

        return $this->mapCountArray($counts);
    }

    /**
     * @param  list<string>  $bookingIds
     * @param  Collection<string, Booking>  $byId
     * @param  array<string, list<string>>  $idsByBucket
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function lossReasonRows(array $bookingIds, Collection $byId, array $idsByBucket): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $counts = [];
        foreach ($bookingIds as $id) {
            $booking = $byId->get($id);
            $remarks = trim((string) ($booking?->settlement_remarks ?? ''));
            if ($remarks !== '') {
                $counts[$remarks] = ($counts[$remarks] ?? 0) + 1;
                continue;
            }

            if (in_array($id, $idsByBucket['loss_settled'] ?? [], true)) {
                $label = translate('Loss_settled') ?? 'Loss settled';
            } elseif (in_array($id, $idsByBucket['loss_recovered'] ?? [], true)) {
                $label = translate('Loss_recovered') ?? 'Loss recovered';
            } else {
                $label = translate('Loss_making') ?? 'Loss making (pending)';
            }
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        return $this->mapCountArray($counts);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function mapReasonCounts(Collection $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row->reason_name] = (int) $row->total;
        }

        return $this->mapCountArray($counts);
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function mapCountArray(array $counts): array
    {
        arsort($counts);
        $total = array_sum($counts);
        if ($total <= 0) {
            return [];
        }

        return collect($counts)->map(fn (int $count, string $label) => [
            'label' => $label,
            'count' => $count,
            'total' => $total,
            'pct' => round(($count / $total) * 100, 1),
        ])->values()->all();
    }
}
