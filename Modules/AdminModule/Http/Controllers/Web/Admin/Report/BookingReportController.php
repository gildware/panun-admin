<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\BookingModule\Entities\BookingCancellationReason;
use Modules\BookingModule\Entities\BookingHoldReopenReason;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use function pagination_limit;
use function view;
use function with_currency_symbol;

class BookingReportController extends Controller
{
    protected Zone $zone;
    protected Provider $provider;
    protected Category $categories;
    protected Booking $booking;
    protected Account $account;
    protected Service $service;
    protected User $user;
    protected Transaction $transaction;
    protected BookingDetailsAmount $bookingDetailsAmount;
    use AuthorizesRequests;

    public function __construct(Zone $zone, Provider $provider, Category $categories, Service $service, Booking $booking, Account $account, User $user, Transaction $transaction, BookingDetailsAmount $bookingDetailsAmount)
    {
        $this->zone = $zone;
        $this->provider = $provider;
        $this->categories = $categories;
        $this->booking = $booking;

        $this->service = $service;
        $this->account = $account;
        $this->user = $user;
        $this->transaction = $transaction;
        $this->bookingDetailsAmount = $bookingDetailsAmount;
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     * @throws AuthorizationException
     */
    public function getBookingReport(Request $request): Renderable
    {
        $this->authorize('report_view');
        Validator::make($request->all(), [
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
            'provider_ids' => 'array',
            'provider_ids.*' => 'uuid',
            'category_ids' => 'array',
            'category_ids.*' => 'uuid',
            'sub_category_ids' => 'array',
            'sub_category_ids.*' => 'uuid',
            'service_ids' => 'array',
            'service_ids.*' => 'uuid',
            'staff_ids' => 'array',
            'staff_ids.*' => 'string',
            'date_range' => 'in:all_time, this_week, last_week, this_month, last_month, last_15_days, this_year, last_year, last_6_month, this_year_1st_quarter, this_year_2nd_quarter, this_year_3rd_quarter, this_year_4th_quarter, custom_date',
            'from' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'to' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
        ]);


        $zones = $this->zone->ofStatus(1)->select('id', 'name')->get();
        $providers = $this->provider->ofApproval(1)->select('id', 'company_name', 'company_phone')->get();
        $categories = $this->categories->ofType('main')->select('id', 'name')->get();
        $assignees = $this->user->ofType(ADMIN_USER_TYPES)->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')->get();
        $cancellationReasons = BookingCancellationReason::active()->select('id', 'name', 'responsible')->orderBy('name')->get();
        $holdReasons = BookingHoldReopenReason::active()->where('kind', BookingHoldReopenReason::KIND_HOLD)->select('id', 'name', 'responsible')->orderBy('name')->get();

        $categoryIds = array_values(array_filter((array) $request->input('category_ids', [])));
        $subCategoryIds = array_values(array_filter((array) $request->input('sub_category_ids', [])));

        $subCategories = count($categoryIds) > 0
            ? $this->categories->ofType('sub')->whereIn('parent_id', $categoryIds)->select('id', 'name', 'parent_id')->get()
            : collect();

        $services = count($subCategoryIds) > 0
            ? $this->service->whereIn('sub_category_id', $subCategoryIds)->select('id', 'name', 'sub_category_id')->get()
            : collect();

        $allSubCategoriesForJs = $this->categories->ofType('sub')->select('id', 'name', 'parent_id')->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'parent_id' => $c->parent_id,
            ])->values()->all();

        $allServicesForJs = $this->service->select('id', 'name', 'sub_category_id')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'sub_category_id' => $s->sub_category_id,
            ])->values()->all();

        $queryParams = $request->only('search', 'zone_ids', 'provider_ids', 'category_ids', 'sub_category_ids', 'service_ids', 'staff_ids', 'date_range');
        if ($request->date_range === 'custom_date') {
            $queryParams['from'] = $request->from;
            $queryParams['to'] = $request->to;
        }

        $filtered_bookings = self::filterQuery($this->booking, $request)
            ->with(['customer', 'provider.owner'])
            ->when($request->has('booking_status') && $request['booking_status'] != 'all', function ($query) use ($request) {
                $query->where('booking_status', $request['booking_status']);
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->latest()->paginate(pagination_limit())
            ->appends($queryParams);


        $bookingStatusKeys = array_column(BOOKING_STATUSES, 'key');
        $bookings_for_amount = self::filterQuery($this->booking, $request)
            ->with(['customer', 'provider.owner'])
            ->whereIn('booking_status', $bookingStatusKeys)
            ->get();

        $bookings_count = [];
        $bookings_count['total_bookings'] = $bookings_for_amount->count();
        foreach ($bookingStatusKeys as $statusKey) {
            $bookings_count[$statusKey] = $bookings_for_amount->where('booking_status', $statusKey)->count();
        }

        $booking_amount = [];
        $booking_amount['total_booking_amount'] = $bookings_for_amount->sum('total_booking_amount');
        $booking_amount['total_paid_booking_amount'] = $bookings_for_amount->where('payment_method', '!=', 'cash_after_service')->where('booking_status', 'completed')->sum('total_booking_amount');
        $booking_amount['total_unpaid_booking_amount'] = $bookings_for_amount->where('payment_method', '!=', 'cash_after_service')->where('booking_status', '!=', 'completed')->sum('total_booking_amount');


        $date_range = $request['date_range'];
        if (is_null($date_range) || $date_range == 'all_time') {
            $deterministic = 'year';
        } elseif ($date_range == 'this_week' || $date_range == 'last_week') {
            $deterministic = 'week';
        } elseif ($date_range == 'this_month' || $date_range == 'last_month' || $date_range == 'last_15_days') {
            $deterministic = 'day';
        } elseif ($date_range == 'this_year' || $date_range == 'last_year' || $date_range == 'last_6_month' || $date_range == 'this_year_1st_quarter' || $date_range == 'this_year_2nd_quarter' || $date_range == 'this_year_3rd_quarter' || $date_range == 'this_year_4th_quarter') {
            $deterministic = 'month';
        } elseif ($date_range == 'custom_date') {
            $from = Carbon::parse($request['from'])->startOfDay();
            $to = Carbon::parse($request['to'])->endOfDay();
            $diff = Carbon::parse($from)->diffInDays($to);

            if ($diff <= 7) {
                $deterministic = 'week';
            } elseif ($diff <= 30) {
                $deterministic = 'day';
            } elseif ($diff <= 365) {
                $deterministic = 'month';
            } else {
                $deterministic = 'year';
            }
        }
        $group_by_deterministic = $deterministic == 'week' ? 'day' : $deterministic;

        $amounts = $this->bookingDetailsAmount
            ->whereHas('booking', function ($query) use ($request) {
                self::filterQuery($query, $request)->whereIn('booking_status', ['accepted', 'ongoing', 'completed', 'canceled']);
            })
            ->when(isset($group_by_deterministic), function ($query) use ($group_by_deterministic) {
                $query->select(
                    DB::raw('sum(admin_commission) as admin_commission'),

                    DB::raw($group_by_deterministic . '(created_at) ' . $group_by_deterministic)
                );
            })
            ->groupby($group_by_deterministic)
            ->get()->toArray();

        $bookings = self::filterQuery($this->booking, $request)
            ->whereIn('booking_status', ['accepted', 'ongoing', 'completed', 'canceled'])
            ->when(isset($group_by_deterministic), function ($query) use ($group_by_deterministic) {
                $query->select(
                    DB::raw('sum(total_booking_amount) as total_booking_amount'),
                    DB::raw('sum(total_tax_amount) as total_tax_amount'),

                    DB::raw($group_by_deterministic . '(created_at) ' . $group_by_deterministic)
                );
            })
            ->groupby($group_by_deterministic)
            ->get()->toArray();

        $chart_data = ['booking_amount' => array(), 'tax_amount' => array(), 'admin_commission' => array(), 'timeline' => array()];
        if ($deterministic == 'month') {
            $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            foreach ($months as $month) {
                $found = 0;
                $chart_data['timeline'][] = $month;
                foreach ($bookings as $key => $item) {
                    if ($item['month'] == $month) {
                        $chart_data['booking_amount'][] = $item['total_booking_amount'];
                        $chart_data['tax_amount'][] = $item['total_tax_amount'];

                        $chart_data['admin_commission'][] = $amounts[$key]['admin_commission'] ?? 0;
                        $found = 1;
                    }
                }
                if (!$found) {
                    $chart_data['booking_amount'][] = 0;
                    $chart_data['tax_amount'][] = 0;
                    $chart_data['admin_commission'][] = 0;
                }
            }

        } elseif ($deterministic == 'year') {
            foreach ($bookings as $key => $item) {
                $chart_data['booking_amount'][] = $item['total_booking_amount'];
                $chart_data['tax_amount'][] = $item['total_tax_amount'];
                $chart_data['timeline'][] = $item[$deterministic];

                $chart_data['admin_commission'][] = $amounts[$key]['admin_commission'] ?? 0;
            }
        } elseif ($deterministic == 'day') {
            if ($date_range == 'this_month') {
                $to = Carbon::now()->lastOfMonth();
            } elseif ($date_range == 'last_month') {
                $to = Carbon::now()->subMonth()->endOfMonth();
            } elseif ($date_range == 'last_15_days') {
                $to = Carbon::now();
            }

            $number = date('d', strtotime($to));

            for ($i = 1; $i <= $number; $i++) {
                $found = 0;
                $chart_data['timeline'][] = $i;
                foreach ($bookings as $key => $item) {
                    if ($item['day'] == $i) {
                        $chart_data['booking_amount'][] = $item['total_booking_amount'];
                        $chart_data['tax_amount'][] = $item['total_tax_amount'];

                        $chart_data['admin_commission'][] = $amounts[$key]['admin_commission'] ?? 0;
                        $found = 1;
                    }
                }
                if (!$found) {
                    $chart_data['booking_amount'][] = 0;
                    $chart_data['tax_amount'][] = 0;
                    $chart_data['admin_commission'][] = 0;
                }
            }
        } elseif ($deterministic == 'week') {
            if ($date_range == 'this_week') {
                $from = Carbon::now()->startOfWeek();
                $to = Carbon::now()->endOfWeek();
            } elseif ($date_range == 'last_week') {
                $from = Carbon::now()->subWeek()->startOfWeek();
                $to = Carbon::now()->subWeek()->endOfWeek();
            }

            for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                $found = 0;
                $chart_data['timeline'][] = $i;
                foreach ($bookings as $key => $item) {
                    if ($item['day'] == $i) {
                        $chart_data['booking_amount'][] = $item['total_booking_amount'];
                        $chart_data['tax_amount'][] = $item['total_tax_amount'];

                        $chart_data['admin_commission'][] = $amounts[$key]['admin_commission'] ?? 0;
                        $found = 1;
                    }
                }
                if (!$found) {
                    $chart_data['booking_amount'][] = 0;
                    $chart_data['tax_amount'][] = 0;
                    $chart_data['admin_commission'][] = 0;
                }
            }
        }

        $statusBreakdownRows = self::filterQuery($this->booking->newQuery(), $request)
            ->selectRaw("
                CASE
                    WHEN reopen_disputed_snapshot IS NOT NULL
                         AND JSON_EXTRACT(reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'
                         AND booking_status IN ('canceled','cancelled','refunded')
                    THEN 'disputed_cancelled'
                    WHEN reopen_disputed_snapshot IS NOT NULL
                         AND JSON_EXTRACT(reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'
                         AND booking_status = 'completed'
                    THEN 'disputed_completed'
                    ELSE booking_status
                END as status_bucket,
                COUNT(*) as cnt,
                COALESCE(SUM(total_booking_amount), 0) as total_amount
            ")
            ->groupBy('status_bucket')
            ->get()
            ->keyBy('status_bucket');

        $statusBucketMeta = [
            ['key' => 'pending', 'label' => translate('Booking_status_tpl_pending')],
            ['key' => 'accepted', 'label' => translate('Booking_status_tpl_accepted')],
            ['key' => 'ongoing', 'label' => translate('Booking_status_tpl_ongoing')],
            ['key' => 'on_hold', 'label' => translate('Booking_status_tpl_on_hold')],
            ['key' => 'completed', 'label' => translate('Booking_status_tpl_completed')],
            ['key' => 'canceled', 'label' => translate('Booking_status_tpl_canceled')],
            ['key' => 'refunded', 'label' => translate('Booking_status_tpl_refunded')],
            ['key' => 'disputed_cancelled', 'label' => 'Disputed & Cancelled'],
            ['key' => 'disputed_completed', 'label' => 'Disputed & Completed'],
        ];

        $report_status_table = [];
        $report_status_chart = ['labels' => [], 'counts' => [], 'amounts' => []];
        foreach ($statusBucketMeta as $meta) {
            $key = $meta['key'];
            $row = $statusBreakdownRows->get($key);
            $cnt = $row ? (int) $row->cnt : 0;
            $amt = $row ? (float) $row->total_amount : 0.0;
            $label = $meta['label'];
            $report_status_table[] = [
                'key' => $key,
                'label' => $label,
                'count' => $cnt,
                'amount' => $amt,
            ];
            if ($cnt > 0) {
                $report_status_chart['labels'][] = $label;
                $report_status_chart['counts'][] = $cnt;
                $report_status_chart['amounts'][] = round($amt, 2);
            }
        }
        if (count($report_status_chart['labels']) === 0) {
            $report_status_chart['labels'] = [translate('Total_Bookings')];
            $report_status_chart['counts'] = [0];
            $report_status_chart['amounts'] = [0];
        }

        $visitRetained = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $visitFeeSplit = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;

        $baseBookingIdsQuery = self::filterQuery($this->booking->newQuery(), $request)->select('id');

        $refund_total_amount = (float) LedgerTransaction::query()
            ->whereIn('booking_id', $baseBookingIdsQuery)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where('reason', LedgerTransaction::REASON_REFUND)
            ->sum('amount');
        $refund_total_amount = round($refund_total_amount, 2);

        $earnedCompletedBookingIds = self::filterQuery($this->booking->newQuery(), $request)
            ->where('booking_status', 'completed')
            ->where(function ($q) {
                $q->whereNull('reopen_disputed_snapshot')
                    ->orWhereRaw("JSON_EXTRACT(reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund'");
            })
            ->select('id');

        $earnedDisputedCompletedBookingIds = self::filterQuery($this->booking->newQuery(), $request)
            ->where('booking_status', 'completed')
            ->whereRaw("reopen_disputed_snapshot IS NOT NULL AND JSON_EXTRACT(reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'")
            ->select('id');

        $earnedSpecialCancelBookingIds = self::filterQuery($this->booking->newQuery(), $request)
            ->whereIn('booking_status', ['canceled', 'cancelled', 'refunded'])
            ->where(function ($q) use ($visitRetained) {
                $q->where('after_visit_cancel', true)
                    ->orWhere('settlement_outcome', $visitRetained);
            })
            ->where(function ($q) {
                $q->whereNull('reopen_disputed_snapshot')
                    ->orWhereRaw("JSON_EXTRACT(reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund'");
            })
            ->select('id');

        $earningBuckets = [
            [
                'label' => translate('Booking_status_tpl_completed'),
                'booking_ids' => $earnedCompletedBookingIds,
            ],
            [
                'label' => 'Disputed & Completed',
                'booking_ids' => $earnedDisputedCompletedBookingIds,
            ],
            [
                'label' => translate('Bfs_label_cancel_keep_visit'),
                'booking_ids' => $earnedSpecialCancelBookingIds,
            ],
        ];

        $earning_chart = ['labels_short' => [], 'labels_full' => [], 'customer_paid' => [], 'company_commission' => []];
        foreach ($earningBuckets as $idx => $b) {
            $fullLabel = (string) $b['label'];
            $shortLabel = match ($idx) {
                0 => 'Completed',
                1 => 'Disp+Comp',
                2 => 'After-visit',
                default => $fullLabel,
            };
            $earning_chart['labels_full'][] = $fullLabel;
            $earning_chart['labels_short'][] = $shortLabel;

            $paid = (float) DB::table('booking_partial_payments')
                ->whereIn('booking_id', $b['booking_ids'])
                ->sum('paid_amount');
            $earning_chart['customer_paid'][] = round($paid, 2);

            $commission = (float) DB::table('booking_details_amounts')
                ->whereIn('booking_id', $b['booking_ids'])
                ->sum('admin_commission');
            $earning_chart['company_commission'][] = round($commission, 2);
        }

        $latestCancelHistoryIds = DB::table('booking_status_histories')
            ->selectRaw('MAX(id) as id')
            ->whereIn('booking_id', $baseBookingIdsQuery)
            ->where('booking_status', 'canceled')
            ->groupBy('booking_id');

        $visitRetainedOutcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;

        $cancel_bucket_rows = DB::table('bookings as b')
            ->whereIn('b.id', $baseBookingIdsQuery)
            ->selectRaw("
                SUM(CASE
                    WHEN b.reopen_disputed_snapshot IS NOT NULL
                         AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'
                         AND b.booking_status IN ('canceled','cancelled','refunded')
                    THEN 1 ELSE 0 END
                ) as disputed_cancelled_cnt,
                COALESCE(SUM(CASE
                    WHEN b.reopen_disputed_snapshot IS NOT NULL
                         AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'
                         AND b.booking_status IN ('canceled','cancelled','refunded')
                    THEN b.total_booking_amount ELSE 0 END
                ), 0) as disputed_cancelled_amt,
                SUM(CASE
                    WHEN b.reopen_disputed_snapshot IS NOT NULL
                         AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'
                         AND b.booking_status = 'completed'
                    THEN 1 ELSE 0 END
                ) as disputed_completed_cnt,
                COALESCE(SUM(CASE
                    WHEN b.reopen_disputed_snapshot IS NOT NULL
                         AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'
                         AND b.booking_status = 'completed'
                    THEN b.total_booking_amount ELSE 0 END
                ), 0) as disputed_completed_amt,
                SUM(CASE
                    WHEN (b.booking_status IN ('canceled','cancelled','refunded'))
                         AND (b.after_visit_cancel = 1 OR b.settlement_outcome = ?)
                         AND NOT (b.reopen_disputed_snapshot IS NOT NULL AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund')
                    THEN 1 ELSE 0 END
                ) as special_cnt,
                COALESCE(SUM(CASE
                    WHEN (b.booking_status IN ('canceled','cancelled','refunded'))
                         AND (b.after_visit_cancel = 1 OR b.settlement_outcome = ?)
                         AND NOT (b.reopen_disputed_snapshot IS NOT NULL AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund')
                    THEN b.total_booking_amount ELSE 0 END
                ), 0) as special_amt,
                SUM(CASE
                    WHEN b.booking_status IN ('canceled','cancelled')
                         AND (b.after_visit_cancel = 0 OR b.after_visit_cancel IS NULL)
                         AND (b.settlement_outcome IS NULL OR b.settlement_outcome <> ?)
                         AND (b.reopen_disputed_snapshot IS NULL OR JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund')
                    THEN 1 ELSE 0 END
                ) as normal_cnt,
                COALESCE(SUM(CASE
                    WHEN b.booking_status IN ('canceled','cancelled')
                         AND (b.after_visit_cancel = 0 OR b.after_visit_cancel IS NULL)
                         AND (b.settlement_outcome IS NULL OR b.settlement_outcome <> ?)
                         AND (b.reopen_disputed_snapshot IS NULL OR JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund')
                    THEN b.total_booking_amount ELSE 0 END
                ), 0) as normal_amt
            ", [$visitRetainedOutcome, $visitRetainedOutcome, $visitRetainedOutcome, $visitRetainedOutcome])
            ->first();

        $cancel_bucket_chart = [
            'labels' => [
                'Canceled (before visit)',
                'Canceled (special settlement / after visit)',
                'Disputed & Cancelled',
                'Disputed & Completed',
            ],
            'counts' => [
                (int) ($cancel_bucket_rows->normal_cnt ?? 0),
                (int) ($cancel_bucket_rows->special_cnt ?? 0),
                (int) ($cancel_bucket_rows->disputed_cancelled_cnt ?? 0),
                (int) ($cancel_bucket_rows->disputed_completed_cnt ?? 0),
            ],
            'amounts' => [
                round((float) ($cancel_bucket_rows->normal_amt ?? 0), 2),
                round((float) ($cancel_bucket_rows->special_amt ?? 0), 2),
                round((float) ($cancel_bucket_rows->disputed_cancelled_amt ?? 0), 2),
                round((float) ($cancel_bucket_rows->disputed_completed_amt ?? 0), 2),
            ],
        ];

        $cancel_total_pie_chart = [
            'labels' => [
                'Cancelled',
                'Cancelled after visit',
                'Disputed & Cancelled',
            ],
            'counts' => [
                (int) ($cancel_bucket_rows->normal_cnt ?? 0),
                (int) ($cancel_bucket_rows->special_cnt ?? 0),
                (int) ($cancel_bucket_rows->disputed_cancelled_cnt ?? 0),
            ],
        ];

        $cancel_reason_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestCancelHistoryIds, 'lh', fn ($j) => $j->on('h.id', '=', 'lh.id'))
            ->leftJoin('booking_cancellation_reasons as r', 'r.id', '=', 'h.booking_cancellation_reason_id')
            ->join('bookings as b', 'b.id', '=', 'h.booking_id')
            ->whereIn('b.booking_status', ['canceled', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('b.after_visit_cancel')->orWhere('b.after_visit_cancel', false);
            })
            ->where(function ($q) use ($visitRetainedOutcome) {
                $q->whereNull('b.settlement_outcome')->orWhere('b.settlement_outcome', '!=', $visitRetainedOutcome);
            })
            ->where(function ($q) {
                $q->whereNull('b.reopen_disputed_snapshot')
                    ->orWhereRaw("JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund'");
            })
            ->selectRaw('COALESCE(r.id, 0) as reason_id, COALESCE(r.name, ?) as reason_name, COALESCE(r.responsible, ?) as responsible, COUNT(DISTINCT b.id) as booking_count, COALESCE(SUM(b.total_booking_amount), 0) as total_amount', [
                translate('Unknown'),
                BookingCancellationReason::RESPONSIBLE_NO_ONE,
            ])
            ->groupBy('reason_id', 'reason_name', 'responsible')
            ->orderByDesc('booking_count')
            ->get()
            ->map(fn ($row) => [
                'reason_id' => (int) $row->reason_id,
                'reason_name' => (string) $row->reason_name,
                'responsible' => (string) $row->responsible,
                'booking_count' => (int) $row->booking_count,
                'total_amount' => (float) $row->total_amount,
            ])
            ->values()
            ->all();

        $cancel_reason_chart = [
            'labels' => array_map(fn ($r) => $r['reason_name'], $cancel_reason_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $cancel_reason_rows),
        ];
        if (count($cancel_reason_chart['labels']) === 0) {
            $cancel_reason_chart = ['labels' => [translate('Canceled')], 'counts' => [0]];
        }

        // Cancelled after visit / special settlement cancellation reasons
        $cancel_after_visit_reason_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestCancelHistoryIds, 'lh_av', fn ($j) => $j->on('h.id', '=', 'lh_av.id'))
            ->leftJoin('booking_cancellation_reasons as r', 'r.id', '=', 'h.booking_cancellation_reason_id')
            ->join('bookings as b', 'b.id', '=', 'h.booking_id')
            ->whereIn('b.booking_status', ['canceled', 'cancelled', 'refunded'])
            ->where(function ($q) use ($visitRetainedOutcome) {
                $q->where('b.after_visit_cancel', true)->orWhere('b.settlement_outcome', $visitRetainedOutcome);
            })
            ->where(function ($q) {
                $q->whereNull('b.reopen_disputed_snapshot')
                    ->orWhereRaw("JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund'");
            })
            ->selectRaw('COALESCE(r.id, 0) as reason_id, COALESCE(r.name, ?) as reason_name, COUNT(DISTINCT b.id) as booking_count', [
                translate('Unknown'),
            ])
            ->groupBy('reason_id', 'reason_name')
            ->orderByDesc('booking_count')
            ->get()
            ->map(fn ($row) => [
                'reason_id' => (int) $row->reason_id,
                'reason_name' => (string) $row->reason_name,
                'booking_count' => (int) $row->booking_count,
            ])
            ->values()
            ->all();

        $cancel_after_visit_reason_chart = [
            'labels' => array_map(fn ($r) => $r['reason_name'], $cancel_after_visit_reason_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $cancel_after_visit_reason_rows),
        ];
        if (count($cancel_after_visit_reason_chart['labels']) === 0) {
            $cancel_after_visit_reason_chart = ['labels' => [translate('Canceled')], 'counts' => [0]];
        }

        // Disputed & cancelled cancellation reasons
        $disputed_cancel_reason_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestCancelHistoryIds, 'lh_dc', fn ($j) => $j->on('h.id', '=', 'lh_dc.id'))
            ->leftJoin('booking_cancellation_reasons as r', 'r.id', '=', 'h.booking_cancellation_reason_id')
            ->join('bookings as b', 'b.id', '=', 'h.booking_id')
            ->whereIn('b.booking_status', ['canceled', 'cancelled', 'refunded'])
            ->whereRaw("b.reopen_disputed_snapshot IS NOT NULL AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'")
            ->selectRaw('COALESCE(r.id, 0) as reason_id, COALESCE(r.name, ?) as reason_name, COUNT(DISTINCT b.id) as booking_count', [
                translate('Unknown'),
            ])
            ->groupBy('reason_id', 'reason_name')
            ->orderByDesc('booking_count')
            ->get()
            ->map(fn ($row) => [
                'reason_id' => (int) $row->reason_id,
                'reason_name' => (string) $row->reason_name,
                'booking_count' => (int) $row->booking_count,
            ])
            ->values()
            ->all();

        $disputed_cancel_reason_chart = [
            'labels' => array_map(fn ($r) => $r['reason_name'], $disputed_cancel_reason_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $disputed_cancel_reason_rows),
        ];
        if (count($disputed_cancel_reason_chart['labels']) === 0) {
            $disputed_cancel_reason_chart = ['labels' => [translate('Unknown')], 'counts' => [0]];
        }

        $cancel_service_rows = DB::table('booking_details as d')
            ->join('bookings as b', 'b.id', '=', 'd.booking_id')
            ->leftJoin('services as s', 's.id', '=', 'd.service_id')
            ->whereIn('b.id', $baseBookingIdsQuery)
            ->whereIn('b.booking_status', ['canceled', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('b.after_visit_cancel')->orWhere('b.after_visit_cancel', false);
            })
            ->where(function ($q) use ($visitRetainedOutcome) {
                $q->whereNull('b.settlement_outcome')->orWhere('b.settlement_outcome', '!=', $visitRetainedOutcome);
            })
            ->where(function ($q) {
                $q->whereNull('b.reopen_disputed_snapshot')
                    ->orWhereRaw("JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund'");
            })
            ->selectRaw('COALESCE(s.id, "") as service_id, COALESCE(s.name, ?) as service_name, COUNT(DISTINCT b.id) as booking_count, COALESCE(SUM(d.total_cost), 0) as service_total', [
                translate('Unknown'),
            ])
            ->groupBy('service_id', 'service_name')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'service_id' => (string) $row->service_id,
                'service_name' => (string) $row->service_name,
                'booking_count' => (int) $row->booking_count,
                'service_total' => (float) $row->service_total,
            ])
            ->values()
            ->all();

        $cancel_service_chart = [
            'labels' => array_map(fn ($r) => $r['service_name'], $cancel_service_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $cancel_service_rows),
        ];
        if (count($cancel_service_chart['labels']) === 0) {
            $cancel_service_chart = ['labels' => [translate('service')], 'counts' => [0]];
        }

        $cancel_special_service_rows = DB::table('booking_details as d')
            ->join('bookings as b', 'b.id', '=', 'd.booking_id')
            ->leftJoin('services as s', 's.id', '=', 'd.service_id')
            ->whereIn('b.id', $baseBookingIdsQuery)
            ->whereIn('b.booking_status', ['canceled', 'cancelled', 'refunded'])
            ->where(function ($q) use ($visitRetainedOutcome) {
                $q->where('b.after_visit_cancel', true)->orWhere('b.settlement_outcome', $visitRetainedOutcome);
            })
            ->where(function ($q) {
                $q->whereNull('b.reopen_disputed_snapshot')
                    ->orWhereRaw("JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') <> 'reopen_disputed_refund'");
            })
            ->selectRaw('COALESCE(s.id, "") as service_id, COALESCE(s.name, ?) as service_name, COUNT(DISTINCT b.id) as booking_count, COALESCE(SUM(d.total_cost), 0) as service_total', [
                translate('Unknown'),
            ])
            ->groupBy('service_id', 'service_name')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'service_id' => (string) $row->service_id,
                'service_name' => (string) $row->service_name,
                'booking_count' => (int) $row->booking_count,
                'service_total' => (float) $row->service_total,
            ])
            ->values()
            ->all();

        $cancel_special_service_chart = [
            'labels' => array_map(fn ($r) => $r['service_name'], $cancel_special_service_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $cancel_special_service_rows),
        ];
        if (count($cancel_special_service_chart['labels']) === 0) {
            $cancel_special_service_chart = ['labels' => [translate('service')], 'counts' => [0]];
        }

        $disputed_service_rows = DB::table('booking_details as d')
            ->join('bookings as b', 'b.id', '=', 'd.booking_id')
            ->leftJoin('services as s', 's.id', '=', 'd.service_id')
            ->whereIn('b.id', $baseBookingIdsQuery)
            ->whereRaw("b.reopen_disputed_snapshot IS NOT NULL AND JSON_EXTRACT(b.reopen_disputed_snapshot, '$.type') = 'reopen_disputed_refund'")
            ->selectRaw('COALESCE(s.id, "") as service_id, COALESCE(s.name, ?) as service_name, COUNT(DISTINCT b.id) as booking_count, COALESCE(SUM(d.total_cost), 0) as service_total', [
                translate('Unknown'),
            ])
            ->groupBy('service_id', 'service_name')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'service_id' => (string) $row->service_id,
                'service_name' => (string) $row->service_name,
                'booking_count' => (int) $row->booking_count,
                'service_total' => (float) $row->service_total,
            ])
            ->values()
            ->all();

        $disputed_service_chart = [
            'labels' => array_map(fn ($r) => $r['service_name'], $disputed_service_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $disputed_service_rows),
        ];
        if (count($disputed_service_chart['labels']) === 0) {
            $disputed_service_chart = ['labels' => [translate('service')], 'counts' => [0]];
        }

        $cancel_remarks_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestCancelHistoryIds, 'lh2', fn ($j) => $j->on('h.id', '=', 'lh2.id'))
            ->selectRaw('COALESCE(NULLIF(TRIM(h.status_change_remarks), ""), ?) as remarks, COUNT(*) as cnt', [translate('N/A')])
            ->groupBy('remarks')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['remarks' => (string) $row->remarks, 'count' => (int) $row->cnt])
            ->values()
            ->all();

        $latestHoldHistoryIds = DB::table('booking_status_histories')
            ->selectRaw('MAX(id) as id')
            ->whereIn('booking_id', $baseBookingIdsQuery)
            ->where('booking_status', 'on_hold')
            ->groupBy('booking_id');

        $hold_reason_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestHoldHistoryIds, 'lh3', fn ($j) => $j->on('h.id', '=', 'lh3.id'))
            ->leftJoin('booking_hold_reopen_reasons as r', function ($j) {
                $j->on('r.id', '=', 'h.booking_hold_reopen_reason_id')
                    ->where('r.kind', '=', BookingHoldReopenReason::KIND_HOLD);
            })
            ->join('bookings as b', 'b.id', '=', 'h.booking_id')
            ->selectRaw('COALESCE(r.id, 0) as reason_id, COALESCE(r.name, ?) as reason_name, COALESCE(r.responsible, ?) as responsible, COUNT(DISTINCT b.id) as booking_count, COALESCE(SUM(b.total_booking_amount), 0) as total_amount', [
                translate('Unknown'),
                BookingHoldReopenReason::RESPONSIBLE_NO_ONE,
            ])
            ->groupBy('reason_id', 'reason_name', 'responsible')
            ->orderByDesc('booking_count')
            ->get()
            ->map(fn ($row) => [
                'reason_id' => (int) $row->reason_id,
                'reason_name' => (string) $row->reason_name,
                'responsible' => (string) $row->responsible,
                'booking_count' => (int) $row->booking_count,
                'total_amount' => (float) $row->total_amount,
            ])
            ->values()
            ->all();

        $hold_reason_chart = [
            'labels' => array_map(fn ($r) => $r['reason_name'], $hold_reason_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $hold_reason_rows),
        ];
        if (count($hold_reason_chart['labels']) === 0) {
            $hold_reason_chart = ['labels' => [translate('Booking_status_tpl_on_hold')], 'counts' => [0]];
        }

        $hold_service_rows = DB::table('booking_details as d')
            ->join('bookings as b', 'b.id', '=', 'd.booking_id')
            ->leftJoin('services as s', 's.id', '=', 'd.service_id')
            ->whereIn('b.id', $baseBookingIdsQuery)
            ->where('b.booking_status', 'on_hold')
            ->selectRaw('COALESCE(s.id, "") as service_id, COALESCE(s.name, ?) as service_name, COUNT(DISTINCT b.id) as booking_count, COALESCE(SUM(d.total_cost), 0) as service_total', [
                translate('Unknown'),
            ])
            ->groupBy('service_id', 'service_name')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'service_id' => (string) $row->service_id,
                'service_name' => (string) $row->service_name,
                'booking_count' => (int) $row->booking_count,
                'service_total' => (float) $row->service_total,
            ])
            ->values()
            ->all();

        $hold_service_chart = [
            'labels' => array_map(fn ($r) => $r['service_name'], $hold_service_rows),
            'counts' => array_map(fn ($r) => $r['booking_count'], $hold_service_rows),
        ];
        if (count($hold_service_chart['labels']) === 0) {
            $hold_service_chart = ['labels' => [translate('service')], 'counts' => [0]];
        }

        $hold_remarks_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestHoldHistoryIds, 'lh4', fn ($j) => $j->on('h.id', '=', 'lh4.id'))
            ->selectRaw('COALESCE(NULLIF(TRIM(h.status_change_remarks), ""), ?) as remarks, COUNT(*) as cnt', [translate('N/A')])
            ->groupBy('remarks')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['remarks' => (string) $row->remarks, 'count' => (int) $row->cnt])
            ->values()
            ->all();

        return view('adminmodule::admin.report.booking', compact(
            'zones',
            'providers',
            'categories',
            'assignees',
            'cancellationReasons',
            'holdReasons',
            'subCategories',
            'services',
            'allSubCategoriesForJs',
            'allServicesForJs',
            'filtered_bookings',
            'bookings_count',
            'booking_amount',
            'refund_total_amount',
            'earning_chart',
            'chart_data',
            'queryParams',
            'report_status_table',
            'report_status_chart',
            'cancel_reason_rows',
            'cancel_reason_chart',
            'cancel_service_rows',
            'cancel_service_chart',
            'cancel_bucket_chart',
            'cancel_total_pie_chart',
            'cancel_after_visit_reason_rows',
            'cancel_after_visit_reason_chart',
            'disputed_cancel_reason_rows',
            'disputed_cancel_reason_chart',
            'cancel_special_service_rows',
            'cancel_special_service_chart',
            'disputed_service_rows',
            'disputed_service_chart',
            'cancel_remarks_rows',
            'hold_reason_rows',
            'hold_reason_chart',
            'hold_service_rows',
            'hold_service_chart',
            'hold_remarks_rows'
        ));
    }


    /**
     * @param Request $request
     * @return string|StreamedResponse
     * @throws \OpenSpout\Common\Exception\IOException
     * @throws \OpenSpout\Common\Exception\InvalidArgumentException
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     * @throws \OpenSpout\Writer\Exception\WriterNotOpenedException
     */
    public function getBookingReportDownload(Request $request): string|StreamedResponse
    {
        $this->authorize('report_export');
        Validator::make($request->all(), [
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
            'provider_ids' => 'array',
            'provider_ids.*' => 'uuid',
            'category_ids' => 'array',
            'category_ids.*' => 'uuid',
            'sub_category_ids' => 'array',
            'sub_category_ids.*' => 'uuid',
            'service_ids' => 'array',
            'service_ids.*' => 'uuid',
            'date_range' => 'in:all_time, this_week, last_week, this_month, last_month, last_15_days, this_year, last_year, last_6_month, this_year_1st_quarter, this_year_2nd_quarter, this_year_3rd_quarter, this_year_4th_quarter, custom_date',
            'from' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'to' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
        ]);

        $filtered_bookings = self::filterQuery($this->booking, $request)
            ->with(['customer', 'provider.owner',])
            ->ofBookingStatus('completed')
            ->when($request->has('booking_status'), function ($query) use ($request) {
                $query->whereIn('booking_status', [$request['booking_status']]);
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->latest()->get();

        return (new FastExcel($filtered_bookings))->download(time() . '-booking-report.xlsx', function ($booking) {
            return [
                'Booking ID' => $booking->readable_id,
                'Customer Name' => isset($booking->customer) ? ($booking->customer->first_name . ' ' . $booking->customer->last_name) : '',
                'Customer Phone' => isset($booking->customer) ? ($booking->customer->phone ?? '') : '',
                'Customer Email' => isset($booking->customer) ? ($booking->customer->email ?? '') : '',
                'Provider Name' => isset($booking->provider) && isset($booking->provider->owner) ? ($booking->provider->owner->first_name . ' ' . $booking->provider->owner->last_name) : '',
                'Provider Phone' => isset($booking->provider) && isset($booking->provider->owner) ? ($booking->provider->owner->phone ?? '') : '',
                'Provider Email' => isset($booking->provider) && isset($booking->provider->owner) ? ($booking->provider->owner->email ?? '') : '',

                'Booking Amount' => with_currency_symbol($booking['total_booking_amount']),
                'Service Discount' => with_currency_symbol($booking['total_discount_amount']),
                'Coupon Discount' => with_currency_symbol($booking['total_coupon_amount']),
                'VAT / Tax' => with_currency_symbol($booking['total_tax_amount']),
            ];
        });
    }

    /**
     * @param $instance
     * @param $request
     * @return mixed
     */
    function filterQuery($instance, $request): mixed
    {
        return $instance
            ->when($request->has('zone_ids'), function ($query) use ($request) {
                $query->whereIn('zone_id', $request['zone_ids']);
            })
            ->when($request->has('provider_ids'), function ($query) use ($request) {
                $query->whereIn('provider_id', $request['provider_ids']);
            })
            ->when($request->has('staff_ids'), function ($query) use ($request) {
                $query->filterByAssigneeIds($request->input('staff_ids'));
            })
            ->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })
            ->when($request->has('sub_category_ids'), function ($query) use ($request) {
                $query->whereIn('sub_category_id', $request['sub_category_ids']);
            })
            ->when(
                collect($request->input('service_ids', []))->filter(fn ($id) => is_string($id) && $id !== '')->isNotEmpty(),
                function ($query) use ($request) {
                    $serviceIds = collect($request->input('service_ids', []))->filter(fn ($id) => is_string($id) && $id !== '')->values()->all();
                    $query->whereHas('detail', function ($q) use ($serviceIds) {
                        $q->whereIn('service_id', $serviceIds);
                    });
                }
            )
            ->when(
                $request->has('date_range') && $request['date_range'] == 'custom_date',
                function ($query) use ($request) {
                $query->whereBetween('created_at', [Carbon::parse($request['from'])->startOfDay(), Carbon::parse($request['to'])->endOfDay()]);
            })
            ->when($request->has('date_range') && $request['date_range'] != 'custom_date', function ($query) use ($request) {
                if ($request['date_range'] == 'this_week') {
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);

                } elseif ($request['date_range'] == 'last_week') {
                    $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);

                } elseif ($request['date_range'] == 'this_month') {
                    $query->whereYear('created_at', Carbon::now()->year)
                        ->whereMonth('created_at', Carbon::now()->month);

                } elseif ($request['date_range'] == 'last_month') {
                    $lastMonth = Carbon::now()->subMonth();
                    $query->whereYear('created_at', $lastMonth->year)
                        ->whereMonth('created_at', $lastMonth->month);

                } elseif ($request['date_range'] == 'last_15_days') {
                    $query->whereBetween('created_at', [Carbon::now()->subDay(15), Carbon::now()]);

                } elseif ($request['date_range'] == 'this_year') {
                    $query->whereYear('created_at', Carbon::now()->year);

                } elseif ($request['date_range'] == 'last_year') {
                    $query->whereYear('created_at', Carbon::now()->subYear()->year);

                } elseif ($request['date_range'] == 'last_6_month') {
                    $query->whereBetween('created_at', [Carbon::now()->subMonth(6), Carbon::now()]);

                } elseif ($request['date_range'] == 'this_year_1st_quarter') {
                    $query->whereBetween('created_at', [Carbon::now()->month(1)->startOfQuarter(), Carbon::now()->month(1)->endOfQuarter()]);

                } elseif ($request['date_range'] == 'this_year_2nd_quarter') {
                    $query->whereBetween('created_at', [Carbon::now()->month(4)->startOfQuarter(), Carbon::now()->month(4)->endOfQuarter()]);

                } elseif ($request['date_range'] == 'this_year_3rd_quarter') {
                    $query->whereBetween('created_at', [Carbon::now()->month(7)->startOfQuarter(), Carbon::now()->month(7)->endOfQuarter()]);

                } elseif ($request['date_range'] == 'this_year_4th_quarter') {
                    $query->whereBetween('created_at', [Carbon::now()->month(10)->startOfQuarter(), Carbon::now()->month(10)->endOfQuarter()]);
                }
            });
    }


}
