<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Traits\UploadSizeHelperTrait;
use Carbon\Carbon;
use Modules\AdminModule\Traits\AdminMenuWithRoutes;
use function auth;
use function view;
use function bcrypt;
use function response;
use function file_uploader;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use function response_formatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\WhatsAppModule\Support\WhatsAppAdminUnread;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Modules\UserManagement\Entities\User;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Illuminate\Contracts\Support\Renderable;
use Modules\AdminModule\Services\AdvanceSearch;
use Modules\ServiceManagement\Entities\Service;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\TransactionModule\Entities\Account;
use Illuminate\Contracts\Foundation\Application;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\CustomerPerformanceService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Modules\TransactionModule\Entities\Transaction;
use Modules\AdminModule\Entities\RouteSearchHistory;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingCompensation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class AdminController extends Controller
{
    use AdminMenuWithRoutes;
    protected Provider $provider;
    protected Account $account;
    protected Booking $booking;
    protected Service $service;
    protected User $user;
    protected Transaction $transaction;
    protected ChannelList $channelList;
    protected BookingDetailsAmount $booking_details_amount;
    protected $advanceSearchService;
    use AuthorizesRequests;
    use UploadSizeHelperTrait;
    public function __construct(ChannelList $channelList, Provider $provider, Service $service, Account $account, Booking $booking, User $user, Transaction $transaction, BookingDetailsAmount $booking_details_amount, AdvanceSearch $advanceSearchService)
    {
        $this->provider = $provider;
        $this->service = $service;
        $this->account = $account;
        $this->booking = $booking;
        $this->user = $user;
        $this->transaction = $transaction;
        $this->channelList = $channelList;
        $this->booking_details_amount = $booking_details_amount;
        $this->advanceSearchService = $advanceSearchService;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param Transaction $transaction
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function dashboard(Request $request, Transaction $transaction): View|Factory|Application
    {
        $baseQuery = BookingDetailsAmount::whereHas('booking', function ($query) use ($request) {
            $query->forRevenueReporting();
        })->orWhereHas('repeat', function ($subQuery) {
            $subQuery->ofBookingStatus('completed');
        });
        $scaledCommissionAdjAll = admin_dashboard_scaled_admin_commission_adjustments(null);
        $admin_commission = (float) $baseQuery->sum('admin_commission') + (float) ($scaledCommissionAdjAll['total'] ?? 0);
        $discount_by_admin = $baseQuery->sum('discount_by_admin');
        $coupon_discount_by_admin = $baseQuery->sum('coupon_discount_by_admin');
        $campaign_discount_by_admin = $baseQuery->sum('campaign_discount_by_admin');

        $our_earning = $admin_commission - $discount_by_admin - $coupon_discount_by_admin - $campaign_discount_by_admin;

        $allCompletedRepeats = BookingRepeat::ofBookingStatus('completed')->with('booking.extra_services')->get();
        $repeatLineTotalByParentId = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($allCompletedRepeats);

        $total_revenue = 0.0;
        $spare_parts_total = 0.0;
        foreach ($this->booking->forRevenueReporting()->with('extra_services')->get() as $b) {
            $slice = get_admin_dashboard_reporting_total_and_spare_for_booking($b);
            $total_revenue += $slice['reported_total'];
            $spare_parts_total += $slice['spare_parts'];
        }
        foreach ($allCompletedRepeats as $r) {
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatLineTotalByParentId[$parentKey] ?? get_booking_total_amount($r));
            $slice = get_admin_dashboard_reporting_total_and_spare_for_repeat($r, $den);
            $total_revenue += $slice['reported_total'];
            $spare_parts_total += $slice['spare_parts'];
        }
        $total_revenue = round($total_revenue, 2);
        $spare_parts_total = round($spare_parts_total, 2);
        $service_charges_total = round($total_revenue - $spare_parts_total, 2);

        $financialSummary = admin_dashboard_financial_summary_metrics();

        $data = [];
        $data[] = ['top_cards' => [
            'total_revenue' => round($total_revenue, 2),
            'service_charges_total' => $service_charges_total,
            'spare_parts_total' => $spare_parts_total,
            'our_earning' => round($our_earning ?? 0, 2),
            'payable_to_providers' => $financialSummary['payable_to_providers'],
            'payable_to_customers' => $financialSummary['payable_to_customers'],
            'balance_with_providers' => $financialSummary['balance_with_providers'],
            'total_amount_received_by_company' => $financialSummary['total_amount_received_by_company'],
            'total_loss_in_all_bookings' => $financialSummary['total_loss_in_all_bookings'],
            'total_bad_debt_with_customers' => $financialSummary['total_bad_debt_with_customers'],
            'total_write_off_company' => $financialSummary['total_write_off_company'] ?? 0,
            'total_write_off_provider' => $financialSummary['total_write_off_provider'] ?? 0,
            'total_customer' => $this->user->where(['user_type' => 'customer'])->count(),
            'total_provider' => $this->provider->where(['is_approved' => 1])->count(),
            'total_services' => $this->service->count()
        ]];

        $recent_ledger_transactions = LedgerTransaction::query()
            ->whereCompanyCounterpartyOnly()
            ->with([
                'booking:id,readable_id',
                'creator:id,first_name,last_name,email',
            ])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $this_month_ledger_transactions_count = LedgerTransaction::query()
            ->whereCompanyCounterpartyOnly()
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->count();
        $data[] = [
            'recent_ledger_transactions' => $recent_ledger_transactions,
            'this_month_ledger_trx_count' => $this_month_ledger_transactions_count
        ];

        $companyCompensatedToCustomers = (float) BookingCompensation::query()
            ->where('from_party', BookingCompensation::PARTY_COMPANY)
            ->where('to_party', BookingCompensation::PARTY_CUSTOMER)
            ->sum('amount');
        $companyCompensatedToProviders = (float) BookingCompensation::query()
            ->where('from_party', BookingCompensation::PARTY_COMPANY)
            ->where('to_party', BookingCompensation::PARTY_PROVIDER)
            ->sum('amount');
        $data[] = [
            'compensation_totals' => [
                'company_to_customers' => round($companyCompensatedToCustomers, 2),
                'company_to_providers' => round($companyCompensatedToProviders, 2),
            ],
        ];

        $bookings = $this->booking->with(['detail.service' => function ($query) {
            $query->select('id', 'name', 'thumbnail');
        }])
            ->where('booking_status', 'pending')
            ->latest()
            ->take(5)
            ->get();
        $data[] = ['bookings' => $bookings];

        $data[] = ['top_providers' => $this->topProvidersByPerformanceScore(5)];

        $data[] = ['top_customers' => $this->topCustomersByPerformanceScore(5)];

        $todaysPendingFollowupsBase = BookingFollowup::query()
            ->where('status', 'scheduled')
            // Include missed follow-ups from previous days up to and including today.
            ->whereDate('date', '<=', Carbon::today())
            ->whereHas('booking', function ($bookingQuery) {
                $bookingQuery->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            });
        $todaysPendingFollowupsTotal = (clone $todaysPendingFollowupsBase)->count();

        $todays_pending_followups = (clone $todaysPendingFollowupsBase)
            ->with(['booking.assignee', 'booking.customer', 'booking.provider'])
            // Sort from previous to current.
            ->orderBy('date')
            ->take(5)
            ->get();
        $data[] = [
            'todays_pending_followups' => $todays_pending_followups,
            'todays_pending_followups_total' => $todaysPendingFollowupsTotal,
        ];

        $todaysPendingLeadFollowupsBase = Lead::query()
            ->whereNotNull('next_followup_at')
            // Include missed follow-ups from previous days up to and including today.
            ->whereDate('next_followup_at', '<=', Carbon::today());
        app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($todaysPendingLeadFollowupsBase);
        $todaysPendingLeadFollowupsTotal = (clone $todaysPendingLeadFollowupsBase)->count();

        $todays_pending_lead_followups = (clone $todaysPendingLeadFollowupsBase)
            // Sort from previous to current.
            ->orderBy('next_followup_at')
            ->take(5)
            ->get();

        $handledByIds = $todays_pending_lead_followups->pluck('handled_by')->filter()->unique()->values()->all();
        $handledByUsers = $handledByIds !== []
            ? $this->user->whereIn('id', $handledByIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy(fn ($u) => (string) $u->id)
            : collect();

        foreach ($todays_pending_lead_followups as $lead) {
            $user = $lead->handled_by ? $handledByUsers->get((string) $lead->handled_by) : null;
            $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
            $lead->handled_by_name = $fullName ?: ($user->email ?? null);
        }

        $data[] = [
            'todays_pending_lead_followups' => $todays_pending_lead_followups,
            'todays_pending_lead_followups_total' => $todaysPendingLeadFollowupsTotal,
        ];

        $year = session()->has('dashboard_earning_graph_year') ? session('dashboard_earning_graph_year') : date('Y');
        $amounts = $this->booking_details_amount
            ->whereYear('created_at', '=', $year)
            ->where(function ($q) {
                $q->whereHas('booking', function ($query) {
                    $query->forRevenueReporting();
                })->orWhereHas('repeat', function ($subQuery) {
                    $subQuery->ofBookingStatus('completed');
                });
            })
            ->select(
                DB::raw('sum(admin_commission) as admin_commission'),
                DB::raw('sum(discount_by_admin) as discount_by_admin'),
                DB::raw('sum(coupon_discount_by_admin) as coupon_discount_by_admin'),
                DB::raw('sum(campaign_discount_by_admin) as campaign_discount_by_admin'),
                DB::raw('MONTH(created_at) month')
            )
            ->groupby('month')
            ->get()
            ->toArray();

        // Admin commission per month (net of discounts), matching dashboard tile logic.
        $adminEarningByMonth = [];
        foreach ($amounts as $item) {
            $month = (int) ($item['month'] ?? 0);
            if ($month < 1 || $month > 12) {
                continue;
            }

            $adminCommission = (float) ($item['admin_commission'] ?? 0);
            $discountByAdmin = (float) ($item['discount_by_admin'] ?? 0);
            $couponDiscountByAdmin = (float) ($item['coupon_discount_by_admin'] ?? 0);
            $campaignDiscountByAdmin = (float) ($item['campaign_discount_by_admin'] ?? 0);

            $adminEarningByMonth[$month] = $adminCommission - $discountByAdmin - $couponDiscountByAdmin - $campaignDiscountByAdmin;
        }

        $scaledCommissionAdjYear = admin_dashboard_scaled_admin_commission_adjustments((int) $year);
        foreach (range(1, 12) as $m) {
            $adminEarningByMonth[$m] = ($adminEarningByMonth[$m] ?? 0) + (float) (($scaledCommissionAdjYear['by_month'] ?? [])[$m] ?? 0);
        }

        // Total revenue per month: same basis as top cards (special settlements use overridden preview amounts).
        $revenueByMonth = $this->dashboardReportedRevenueByMonth((int) $year, $repeatLineTotalByParentId);

        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        foreach ($months as $month) {
            $monthTotalRevenue = (float) ($revenueByMonth[$month] ?? 0);
            $monthAdminEarning = (float) ($adminEarningByMonth[$month] ?? 0);

            $chart_data['total_earning'][] = with_decimal_point($monthTotalRevenue);
            $chart_data['commission_earning'][] = with_decimal_point($monthAdminEarning);
        }

        return view('adminmodule::dashboard', compact('data', 'chart_data'));
    }


    public function component()
    {
        return view("adminmodule::component");
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDashboardEarningGraph(Request $request): JsonResponse
    {
        $year = $request['year'];
        $amounts = $this->booking_details_amount
            ->whereYear('created_at', '=', $year)
            ->where(function ($q) {
                $q->whereHas('booking', function ($query) {
                    $query->forRevenueReporting();
                })->orWhereHas('repeat', function ($subQuery) {
                    $subQuery->ofBookingStatus('completed');
                });
            })
            ->select(
                DB::raw('sum(admin_commission) as admin_commission'),
                DB::raw('sum(discount_by_admin) as discount_by_admin'),
                DB::raw('sum(coupon_discount_by_admin) as coupon_discount_by_admin'),
                DB::raw('sum(campaign_discount_by_admin) as campaign_discount_by_admin'),
                DB::raw('MONTH(created_at) month')
            )
            ->groupby('month')
            ->get()
            ->toArray();

        $adminEarningByMonth = [];
        foreach ($amounts as $item) {
            $month = (int) ($item['month'] ?? 0);
            if ($month < 1 || $month > 12) {
                continue;
            }

            $adminCommission = (float) ($item['admin_commission'] ?? 0);
            $discountByAdmin = (float) ($item['discount_by_admin'] ?? 0);
            $couponDiscountByAdmin = (float) ($item['coupon_discount_by_admin'] ?? 0);
            $campaignDiscountByAdmin = (float) ($item['campaign_discount_by_admin'] ?? 0);

            $adminEarningByMonth[$month] = $adminCommission - $discountByAdmin - $couponDiscountByAdmin - $campaignDiscountByAdmin;
        }

        $scaledCommissionAdjYear = admin_dashboard_scaled_admin_commission_adjustments((int) $year);
        foreach (range(1, 12) as $m) {
            $adminEarningByMonth[$m] = ($adminEarningByMonth[$m] ?? 0) + (float) (($scaledCommissionAdjYear['by_month'] ?? [])[$m] ?? 0);
        }

        $repeatLineTotalByParentId = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id(
            BookingRepeat::ofBookingStatus('completed')->with('booking.extra_services')->get()
        );
        $revenueByMonth = $this->dashboardReportedRevenueByMonth((int) $year, $repeatLineTotalByParentId);

        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        foreach ($months as $month) {
            $monthTotalRevenue = (float) ($revenueByMonth[$month] ?? 0);
            $monthAdminEarning = (float) ($adminEarningByMonth[$month] ?? 0);

            $chart_data['total_earning'][] = with_decimal_point($monthTotalRevenue);
            $chart_data['commission_earning'][] = with_decimal_point($monthAdminEarning);
        }

        session()->put('dashboard_earning_graph_year', $request['year']);

        return response()->json($chart_data);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (in_array($request->user()->user_type, ADMIN_USER_TYPES)) {
            $user = $this->user->where(['id' => auth('api')->id()])->with(['roles'])->first();
            return response()->json(response_formatter(DEFAULT_200, $user), 200);
        }
        return response()->json(response_formatter(DEFAULT_403), 401);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function edit(Request $request): JsonResponse
    {
        if (in_array($request->user()->user_type, ADMIN_USER_TYPES)) {
            return response()->json(response_formatter(DEFAULT_200, auth('api')->user()), 200);
        }
        return response()->json(response_formatter(DEFAULT_403), 401);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function profileInfo(Request $request): Renderable
    {
        return view('adminmodule::admin.profile-update');
    }

    /**
     * Modify provider information
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $check = $this->validateUploadedFile($request, ['profile_image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'profile_image' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'password' => '',
            'confirm_password' => !is_null($request->password) ? 'required|same:password' : '',
        ]);

        $user = $this->user->find($request->user()->id);
        $user->first_name = $request->first_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->last_name = $request->last_name;
        if ($request->has('profile_image')) {
            $user->profile_image = file_uploader('user/profile_image/', APPLICATION_IMAGE_FORMAT, $request->profile_image, $user->profile_image);
        }
        if (!is_null($request->password)) {
            $user->password = bcrypt($request->confirm_password);
        }
        $user->save();

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function getUpdatedData(Request $request): JsonResponse
    {
        $message = $this->channelList->wherehas('channelUsers', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)->where('is_read', 0);
        })->count();

        $whatsappUnreadChats = 0;
        $whatsappUnreadMessages = 0;
        if ($request->user()->can('whatsapp_chat_view')) {
            [$whatsappUnreadChats, $whatsappUnreadMessages] = WhatsAppAdminUnread::counts();
        }

        return response()->json([
            'status' => 1,
            'data' => [
                'message' => $message,
                'whatsapp_unread_chats' => $whatsappUnreadChats,
                'whatsapp_unread_messages' => $whatsappUnreadMessages,
            ]
        ]);
    }

    private function routeFullUrl($uri)
    {
        $fullURL = url($uri);
        if ($uri == 'admin/booking/list/verification') {
            $fullURL = url($uri) . '?booking_status=pending&type=pending';
        }
        if ($uri == 'admin/booking/list') {
            $fullURL = url($uri) . '?booking_status=all&service_type=all';
        }
        if ($uri == 'admin/configuration/get-notification-setting') {
            $fullURL = url($uri) . '?type=customers';
        }
        if ($uri == 'admin/customer/settings') {
            $fullURL = url($uri) . '?web_page=loyalty_point';
        }
        if ($uri == 'admin/chat/index') {
            $fullURL = url($uri) . '?user_type=customer';
        }
        return $fullURL;
    }

    public function searchRouting(Request $request)
    {
        $searchKeyword = $request->input('search');
       $formattedRoutes = $this->advanceSearchService->pageSearchList($searchKeyword,"admin");
        $menuSearchResults = [];
        if (!empty($searchKeyword)) {
            $menuSearchResults = $this->advanceSearchService->searchMenuList($searchKeyword,"admin");
        }
        $modelSearchResults = $this->advanceSearchService->searchModelList($searchKeyword,"admin");
        $allRoutes = $this->advanceSearchService->sortByPriority($formattedRoutes, $modelSearchResults, $menuSearchResults, $searchKeyword);
        return response()->json([
            'keyword' => $searchKeyword,
            'result' => $allRoutes,
            'htmlView' => view('adminmodule::admin._advance_search', [
                'result' => $allRoutes,
                'keyword' => $searchKeyword,
                'recent' => false,
            ])->render()
        ]);
    }

    /**
     * @return array{routeName: string, URI: array|string|string[], fullRoute: string}
     */
    private function filterRoute($model, $route, $type = null, $name = null, $prefix = null): array
    {
        $uri = $route->uri();
        $routeName = $route->getName();
        $formattedRouteName = ucwords(str_replace(['.', '_'], ' ', Str::afterLast($routeName, '.')));
        $uriWithParameter = str_replace('{id}', $model->id, $uri);
        $fullURL = url('/') . '/' . $uriWithParameter;
        if ($type == 'booking') {
            $fullURL = url('/') . '/' . $uriWithParameter . '?web_page=details';
        }
        if ($type == 'customer') {
            $fullURL = $formattedRouteName == 'Detail' ? $fullURL . '?web_page=overview' : $fullURL;
        }
        if ($type == 'provider') {
            $fullURL = $formattedRouteName == 'Details' ? $fullURL . '?web_page=overview' : $fullURL;
        }

        $routeName = $prefix ? $prefix . ' ' . $formattedRouteName : $formattedRouteName;
        $routeName = $name ? $routeName . ' - (' . $name . ')' : $routeName;

        return [
            'page_title' => $routeName ?? '',
            'page_title_value' => $routeName ?? '',
            'key' => base64_encode($uri),
            'uri' => $uriWithParameter ?? '',
            'full_route' => $fullURL ?? '',
            'type' => $model->getTable(),
            'priority' => 3,
        ];

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function storeClickedRoute(Request $request): RedirectResponse
    {
        $userId = auth()->id();
        $userType = auth()->user()->user_type;
        $response = $request['response'];

        if (is_string($response)) {
            $response = json_decode($response, true);
        }
        $clickedRoute = RouteSearchHistory::updateOrCreate(
            [
                'user_id' => $userId,
                'user_type' => $userType,
                'route_uri' => $request['uri'],
            ],
            [
                'route_name' => $request['page_title_value'],
                'route_uri' =>  $request['uri'],
                'route_full_url' => $request['route_full_url'],
                'keyword' =>  $request['keyword'],
                'response' => $response,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $clickedRoute->touch();

        $userClickCount = RouteSearchHistory::where('user_id', $userId)->where('user_type', $userType)->count();

        if ($userClickCount >= 15) {
            RouteSearchHistory::where('user_id', $userId)->where('user_type', $userType)->orderBy('created_at', 'asc')->first()->delete();
        }

        $redirectUrl = $request['route_full_url'];
        $separator = (parse_url($redirectUrl, PHP_URL_QUERY) ? '&' : '?');
        $redirectUrl .= $separator . 'keyword=' . urlencode($request['keyword']);
        return redirect($redirectUrl);

    }



    public function recentSearch(): JsonResponse
    {
        $userId = auth()->id();
        $userType = auth()->user()->user_type;
        $recentSearches = RouteSearchHistory::where('user_id', $userId)
            ->where('user_type', $userType)
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get();
        $formattedResult = collect($recentSearches)->map(function ($item) {
            return [
                'page_title_value' => $item['route_name'],
                'page_title' => $item['route_name'],
                'uri' => $item['route_uri'],
                'full_route' => $item['route_full_url'],
                'keyword' => $item['keyword'],
                'response' => $item['response'],

            ];
        });
        $result = $this->advanceSearchService->getSortRecentSearchByType($formattedResult);

        return response()->json([
            'keyword' => '',
            'result' => $result,
            'htmlView' => view('adminmodule::admin._advance_search', [
                'result' => $result,
                'recent' => count($result) > 0 ? true : false,
                'keyword' => '',
            ])->render()
        ]);
    }

    public function refreshSetupGuideUI(): JsonResponse
    {
        $setup = getSetupGuideSteps('admin_panel', auth()->user());

        return response()->json([
            'percentage' => $setup['percentage'],
            'unchecked_keys' => collect($setup['steps'])
                ->where('checked', false)
                ->pluck('key')
                ->values(),
            'unchecked_count' => collect($setup['steps'])
                ->where('checked', false)
                ->count(),
            'steps' => $setup['steps'],
            'all_completed' => collect($setup['steps'])
                ->every(fn ($step) => $step['checked']),
        ]);

    }

    public function acknowledgeSetupGuideWelcome(): JsonResponse
    {
        acknowledgeAdminSetupGuideWelcome(auth()->id());

        return response()->json(['ok' => true]);
    }

    /**
     * Per-month reported revenue for the admin earning chart (matches top-card basis: special settlements, after-visit cancels via forRevenueReporting).
     *
     * @param  array<string, float>  $repeatLineTotalByParentId  Sum of completed repeat line totals per parent booking_id (global, for correct non-standard weights).
     * @return array<int, float> month => revenue
     */
    private function dashboardReportedRevenueByMonth(int $year, array $repeatLineTotalByParentId): array
    {
        $byMonth = array_fill(1, 12, 0.0);

        $oneTimeInYear = $this->booking->newQuery()
            ->forRevenueReporting()
            ->whereYear('created_at', $year)
            ->with('extra_services')
            ->get();

        foreach ($oneTimeInYear as $b) {
            $month = (int) $b->created_at->format('n');
            $slice = get_admin_dashboard_reporting_total_and_spare_for_booking($b);
            $byMonth[$month] += $slice['reported_total'];
        }

        $repeatsInYear = BookingRepeat::query()
            ->ofBookingStatus('completed')
            ->whereYear('created_at', $year)
            ->with('booking.extra_services')
            ->get();

        foreach ($repeatsInYear as $r) {
            $month = (int) $r->created_at->format('n');
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatLineTotalByParentId[$parentKey] ?? get_booking_total_amount($r));
            $slice = get_admin_dashboard_reporting_total_and_spare_for_repeat($r, $den);
            $byMonth[$month] += $slice['reported_total'];
        }

        foreach ($byMonth as $m => $v) {
            $byMonth[$m] = round((float) $v, 2);
        }

        return $byMonth;
    }

    /**
     * Approved providers with at least one revenue-reporting completed booking, ordered by performance score (highest first).
     *
     * @return \Illuminate\Support\Collection<int, Provider>
     */
    private function topProvidersByPerformanceScore(int $limit)
    {
        $providers = $this->provider
            ->with(['owner', 'subscribed_services.category'])
            ->ofApproval(1)
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->forRevenueReporting();
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        if ($providers->isEmpty()) {
            return collect();
        }

        $metrics = app(ProviderPerformanceService::class)->getAggregatedProviderPerformanceMetrics(
            $providers->pluck('id')->all()
        );

        return $providers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->values()
            ->take($limit)
            ->map(function ($provider) use ($metrics) {
                $provider->performance_score = (int) ($metrics->get($provider->id)->performance_score ?? 0);

                return $provider;
            });
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function topCustomersByPerformanceScore(int $limit)
    {
        $customers = $this->user
            ->inCustomerDirectory()
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->ofBookingStatus('completed');
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        if ($customers->isEmpty()) {
            return collect();
        }

        $metrics = app(CustomerPerformanceService::class)->getAggregatedCustomerPerformanceMetrics(
            $customers->pluck('id')->all()
        );

        return $customers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->values()
            ->take($limit)
            ->map(function ($customer) use ($metrics) {
                $customer->performance_score = (int) ($metrics->get($customer->id)->performance_score ?? 0);

                return $customer;
            });
    }
}
