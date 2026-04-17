<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Provider\Report;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\BookingModule\Entities\BookingCancellationReason;
use Modules\BookingModule\Entities\BookingHoldReopenReason;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function __construct(Zone $zone, Provider $provider, Category $categories, Service $service, Booking $booking, Account $account, User $user, Transaction $transaction, BookingDetailsAmount $booking_details_amount)
    {
        $this->zone = $zone;
        $this->provider = $provider;
        $this->categories = $categories;
        $this->booking = $booking;

        $this->service = $service;
        $this->account = $account;
        $this->user = $user;
        $this->transaction = $transaction;
        $this->booking_details_amount = $booking_details_amount;
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function getBookingReport(Request $request): Renderable
    {
        Validator::make($request->all(), [
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
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

        //Dropdown data
        $zones = $this->zone->ofStatus(1)->select('id', 'name')->get();
        $categories = $this->categories->ofType('main')->select('id', 'name')->get();

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

        //params
        $queryParams = ['booking_status' => $request->input('booking_status', 'pending')];
        $queryParams += $request->only(['search', 'booking_status', 'zone_ids', 'category_ids', 'sub_category_ids', 'service_ids', 'date_range']);
        if ($request['date_range'] === 'custom_date') {
            $queryParams['from'] = $request['from'];
            $queryParams['to'] = $request['to'];
        }

        //** Table Data **
        $filteredBookings = self::filterQuery($this->booking, $request)
            ->with(['provider.owner', 'customer' => function ($query) {
                $query->withTrashed();
            }])
            ->when($request->has('booking_status') && $request['booking_status'] != 'all' , function ($query) use($request) {
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

        //** Card Data **
        $bookingStatusKeys = array_column(BOOKING_STATUSES, 'key');
        $bookingsForAmount = self::filterQuery($this->booking, $request)
            ->with(['customer', 'provider.owner'])
            ->whereIn('booking_status', $bookingStatusKeys)
            ->get();

        $bookingsCount = [];
        $bookingsCount['total_bookings'] = $bookingsForAmount->count();
        foreach ($bookingStatusKeys as $statusKey) {
            $bookingsCount[$statusKey] = $bookingsForAmount->where('booking_status', $statusKey)->count();
        }

        $bookingAmount = [];
        $bookingAmount['total_booking_amount'] = $bookingsForAmount->sum('total_booking_amount');
        $bookingAmount['total_paid_booking_amount'] = $bookingsForAmount->where('payment_method', '!=', 'cash_after_service')->where('booking_status', 'completed')->sum('total_booking_amount');
        $bookingAmount['total_unpaid_booking_amount'] = $bookingsForAmount->where('payment_method', '!=', 'cash_after_service')->where('booking_status', '!=', 'completed')->sum('total_booking_amount');

        //** Chart Data **

        //deterministic
        $dateRange = $request['date_range'];
        if(is_null($dateRange) || $dateRange == 'all_time') {
            $deterministic = 'year';
        } elseif ($dateRange == 'this_week' || $dateRange == 'last_week') {
            $deterministic = 'week';
        } elseif ($dateRange == 'this_month' || $dateRange == 'last_month' || $dateRange == 'last_15_days') {
            $deterministic = 'day';
        } elseif ($dateRange == 'this_year' || $dateRange == 'last_year' || $dateRange == 'last_6_month' || $dateRange == 'this_year_1st_quarter' || $dateRange == 'this_year_2nd_quarter' || $dateRange == 'this_year_3rd_quarter' || $dateRange == 'this_year_4th_quarter') {
            $deterministic = 'month';
        } elseif($dateRange == 'custom_date') {
            $from = Carbon::parse($request['from'])->startOfDay();
            $to = Carbon::parse($request['to'])->endOfDay();
            $diff = Carbon::parse($from)->diffInDays($to);

            if($diff <= 7) {
                $deterministic = 'week';
            } elseif ($diff <= 30) {
                $deterministic = 'day';
            } elseif ($diff <= 365) {
                $deterministic = 'month';
            } else {
                $deterministic = 'year';
            }
        }
        $groupByDeterministic = $deterministic=='week'?'day':$deterministic;

        $amounts = $this->booking_details_amount
            ->whereHas('booking', function ($query) use ($request) {
                self::filterQuery($query, $request)->whereIn('booking_status', ['accepted', 'ongoing', 'completed', 'canceled']);
            })
            ->when(isset($groupByDeterministic), function ($query) use ($groupByDeterministic) {
                $query->select(
                    DB::raw('sum(admin_commission) as admin_commission'),

                    DB::raw($groupByDeterministic.'(created_at) '.$groupByDeterministic)
                );
            })
            ->groupby($groupByDeterministic)
            ->get()->toArray();

        $bookings = self::filterQuery($this->booking, $request)
            ->whereIn('booking_status', ['accepted', 'ongoing', 'completed', 'canceled'])
            ->when(isset($groupByDeterministic), function ($query) use ($groupByDeterministic) {
                $query->select(
                    DB::raw('sum(total_booking_amount) as total_booking_amount'),
                    DB::raw('sum(total_tax_amount) as total_tax_amount'),

                    DB::raw($groupByDeterministic.'(created_at) '.$groupByDeterministic)
                );
            })
            ->groupby($groupByDeterministic)
            ->get()->toArray();

        $chartData = ['booking_amount'=>array(), 'tax_amount'=>array(), 'admin_commission'=>array(), 'timeline'=>array()];
        //data filter for deterministic
        if($deterministic == 'month') {
            $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            foreach ($months as $month) {
                $found=0;
                $chartData['timeline'][] = $month;
                foreach ($bookings as $key=>$item) {
                    if ($item['month'] == $month) {
                        $chartData['booking_amount'][] = $item['total_booking_amount'];
                        $chartData['tax_amount'][] = $item['total_tax_amount'];

                        $chartData['admin_commission'][] = $amounts[$key]['admin_commission']??0;
                        $found=1;
                    }
                }
                if(!$found){
                    $chartData['booking_amount'][] = 0;
                    $chartData['tax_amount'][] = 0;
                    $chartData['admin_commission'][] = 0;
                }
            }

        }
        elseif ($deterministic == 'year') {
            foreach ($bookings as $key=>$item) {
                $chartData['booking_amount'][] = $item['total_booking_amount'];
                $chartData['tax_amount'][] = $item['total_tax_amount'];
                $chartData['timeline'][] = $item[$deterministic];

                $chartData['admin_commission'][] = $amounts[$key]['admin_commission']??0;
            }
        }
        elseif ($deterministic == 'day') {
            if ($dateRange == 'this_month') {
                $to = Carbon::now()->lastOfMonth();
            } elseif ($dateRange == 'last_month') {
                $to = Carbon::now()->subMonth()->endOfMonth();
            } elseif ($dateRange == 'last_15_days') {
                $to = Carbon::now();
            }

            $number = date('d',strtotime($to));

            for ($i = 1; $i <= $number; $i++) {
                $found=0;
                $chartData['timeline'][] = $i;
                foreach ($bookings as $key=>$item) {
                    if ($item['day'] == $i) {
                        $chartData['booking_amount'][] = $item['total_booking_amount'];
                        $chartData['tax_amount'][] = $item['total_tax_amount'];

                        $chartData['admin_commission'][] = $amounts[$key]['admin_commission']??0;
                        $found=1;
                    }
                }
                if(!$found){
                    $chartData['booking_amount'][] = 0;
                    $chartData['tax_amount'][] = 0;
                    $chartData['admin_commission'][] = 0;
                }
            }
        }
        elseif ($deterministic == 'week') {
            if ($dateRange == 'this_week') {
                $from = Carbon::now()->startOfWeek();
                $to = Carbon::now()->endOfWeek();
            } elseif ($dateRange == 'last_week') {
                $from = Carbon::now()->subWeek()->startOfWeek();
                $to = Carbon::now()->subWeek()->endOfWeek();
            }

            for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                $found=0;
                $chartData['timeline'][] = $i;
                foreach ($bookings as $key=>$item) {
                    if ($item['day'] == $i) {
                        $chartData['booking_amount'][] = $item['total_booking_amount'];
                        $chartData['tax_amount'][] = $item['total_tax_amount'];

                        $chartData['admin_commission'][] = $amounts[$key]['admin_commission']??0;
                        $found=1;
                    }
                }
                if(!$found) {
                    $chartData['booking_amount'][] = 0;
                    $chartData['tax_amount'][] = 0;
                    $chartData['admin_commission'][] = 0;
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

        $report_tag_labels = [
            translate('Booking_tag_disputed'),
            translate('Booking_tag_compensated'),
            translate('Booking_tag_cancel_after_visit'),
            translate('Booking_tag_complete_no_service'),
            translate('Reopened'),
        ];
        $report_tag_counts = [
            (int) self::filterQuery($this->booking->newQuery(), $request)
                ->where('reopen_disputed_snapshot->type', 'reopen_disputed_refund')
                ->count(),
            (int) self::filterQuery($this->booking->newQuery(), $request)->whereHas('compensations')->count(),
            (int) self::filterQuery($this->booking->newQuery(), $request)
                ->whereIn('booking_status', ['canceled', 'refunded', 'cancelled'])
                ->where(function ($q) use ($visitRetained) {
                    $q->where('after_visit_cancel', true)
                        ->orWhere('settlement_outcome', $visitRetained);
                })
                ->count(),
            (int) self::filterQuery($this->booking->newQuery(), $request)
                ->where('booking_status', 'completed')
                ->where('settlement_outcome', $visitFeeSplit)
                ->count(),
            (int) self::filterQuery($this->booking->newQuery(), $request)
                ->where(function ($q) {
                    $q->whereNotNull('originated_from_booking_id')
                        ->orWhereNotNull('last_reopen_event_at');
                })
                ->count(),
        ];

        $baseBookingIdsQuery = self::filterQuery($this->booking->newQuery(), $request)->select('id');

        $latestCancelHistoryIds = DB::table('booking_status_histories')
            ->selectRaw('MAX(id) as id')
            ->whereIn('booking_id', $baseBookingIdsQuery)
            ->where('booking_status', 'canceled')
            ->groupBy('booking_id');

        $cancel_reason_rows = DB::table('booking_status_histories as h')
            ->joinSub($latestCancelHistoryIds, 'lh', fn ($j) => $j->on('h.id', '=', 'lh.id'))
            ->leftJoin('booking_cancellation_reasons as r', 'r.id', '=', 'h.booking_cancellation_reason_id')
            ->join('bookings as b', 'b.id', '=', 'h.booking_id')
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

        $cancel_service_rows = DB::table('booking_details as d')
            ->join('bookings as b', 'b.id', '=', 'd.booking_id')
            ->leftJoin('services as s', 's.id', '=', 'd.service_id')
            ->whereIn('b.id', $baseBookingIdsQuery)
            ->where('b.booking_status', 'canceled')
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

        return view('providermanagement::provider.report.booking', compact(
            'zones',
            'categories',
            'subCategories',
            'services',
            'allSubCategoriesForJs',
            'allServicesForJs',
            'filteredBookings',
            'bookingsCount',
            'bookingAmount',
            'chartData',
            'queryParams',
            'report_status_table',
            'report_status_chart',
            'report_tag_labels',
            'report_tag_counts',
            'cancel_reason_rows',
            'cancel_reason_chart',
            'cancel_service_rows',
            'cancel_service_chart',
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
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function getBookingReportDownload(Request $request): string|StreamedResponse
    {
        Validator::make($request->all(), [
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
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


        $filteredBookings = self::filterQuery($this->booking, $request)
            ->with(['customer', 'provider.owner', ])
            ->when($request->has('booking_status') && $request['booking_status'] != 'all', function ($query) use($request) {
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
            ->latest()->get();

        return (new FastExcel($filteredBookings))->download(time().'-booking-report.xlsx', function ($booking) {
            return [
                'Booking ID' => $booking->readable_id,
                'Customer Name' => isset($booking->customer) ? ($booking->customer->first_name . ' ' . $booking->customer->last_name) : '',
                'Customer Phone' => isset($booking->customer) ? ($booking->customer->phone??'') : '',
                'Customer Email' => isset($booking->customer) ? ($booking->customer->email??'') : '',
                'Provider Name' => isset($booking->provider) && isset($booking->provider->owner) ? ($booking->provider->owner->first_name . ' ' . $booking->provider->owner->last_name) : '',
                'Provider Phone' => isset($booking->provider) && isset($booking->provider->owner) ? ($booking->provider->owner->phone??'') : '',
                'Provider Email' => isset($booking->provider) && isset($booking->provider->owner) ? ($booking->provider->owner->email??'') : '',

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
            ->where('provider_id', $request->user()->provider->id)
            ->when($request->has('zone_ids'), function ($query) use($request) {
                $query->whereIn('zone_id', $request['zone_ids']);
            })
            ->when($request->has('category_ids'), function ($query) use($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })
            ->when($request->has('sub_category_ids'), function ($query) use($request) {
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
            ->when($request->has('date_range') && $request['date_range'] == 'custom_date', function ($query) use($request) {
                $query->whereBetween('created_at', [Carbon::parse($request['from'])->startOfDay(), Carbon::parse($request['to'])->endOfDay()]);
            })
            ->when($request->has('date_range') && $request['date_range'] != 'custom_date', function ($query) use($request) {
                //DATE RANGE
                if($request['date_range'] == 'this_week') {
                    //this week
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);

                } elseif ($request['date_range'] == 'last_week') {
                    //last week
                    $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);

                } elseif ($request['date_range'] == 'this_month') {
                    //this month
                    $query->whereMonth('created_at', Carbon::now()->month);

                } elseif ($request['date_range'] == 'last_month') {
                    //last month
                    $query->whereMonth('created_at', Carbon::now()->subMonth()->month);

                } elseif ($request['date_range'] == 'last_15_days') {
                    //last 15 days
                    $query->whereBetween('created_at', [Carbon::now()->subDay(15), Carbon::now()]);

                } elseif ($request['date_range'] == 'this_year') {
                    //this year
                    $query->whereYear('created_at', Carbon::now()->year);

                } elseif ($request['date_range'] == 'last_year') {
                    //last year
                    $query->whereYear('created_at', Carbon::now()->subYear()->year);

                } elseif ($request['date_range'] == 'last_6_month') {
                    //last 6month
                    $query->whereBetween('created_at', [Carbon::now()->subMonth(6), Carbon::now()]);

                } elseif ($request['date_range'] == 'this_year_1st_quarter') {
                    //this year 1st quarter
                    $query->whereBetween('created_at', [Carbon::now()->month(1)->startOfQuarter(), Carbon::now()->month(1)->endOfQuarter()]);

                } elseif ($request['date_range'] == 'this_year_2nd_quarter') {
                    //this year 2nd quarter
                    $query->whereBetween('created_at', [Carbon::now()->month(4)->startOfQuarter(), Carbon::now()->month(4)->endOfQuarter()]);

                } elseif ($request['date_range'] == 'this_year_3rd_quarter') {
                    //this year 3rd quarter
                    $query->whereBetween('created_at', [Carbon::now()->month(7)->startOfQuarter(), Carbon::now()->month(7)->endOfQuarter()]);

                } elseif ($request['date_range'] == 'this_year_4th_quarter') {
                    //this year 4th quarter
                    $query->whereBetween('created_at', [Carbon::now()->month(10)->startOfQuarter(), Carbon::now()->month(10)->endOfQuarter()]);
                }
            });
    }


}
