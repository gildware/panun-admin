<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminHeaderChatCounts;
use App\Support\EmployeeSearchAccessFilter;
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
use Illuminate\Support\Facades\Cache;
use Modules\WhatsAppModule\Support\WhatsAppAdminUnread;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\Factory;
use Modules\UserManagement\Entities\User;
use Modules\BookingModule\Entities\Booking;
use Illuminate\Contracts\Support\Renderable;
use Modules\AdminModule\Services\AdminDashboardCache;
use Modules\AdminModule\Services\EmployeeDashboardService;
use Modules\AdminModule\Services\OperationsDashboardService;
use Modules\AdminModule\Services\AdvanceSearch;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\Account;
use Illuminate\Contracts\Foundation\Application;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\CustomerPerformanceService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Modules\TransactionModule\Entities\Transaction;
use Modules\AdminModule\Entities\RouteSearchHistory;
use Modules\AdminModule\Services\StaffPresenceService;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\AdminModule\Entities\UserNotification;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
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
    public function dashboard(Request $request): View|Factory|Application|RedirectResponse
    {
        $employeeData = app(EmployeeDashboardService::class)->build(auth()->user());
        $showEmployeeProgress = is_admin_employee() || ! empty($employeeData['progress_scopes']);

        return view('adminmodule::dashboard-employee', compact('employeeData', 'showEmployeeProgress'));
    }

    public function rankMarksChart(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', Carbon::now()->format('Y-m'));
        $employeeId = $request->query('employee_id');

        try {
            $payload = app(EmployeeDashboardService::class)->buildRankMarksChartForUser(
                auth()->user(),
                $month,
                $employeeId !== null ? (string) $employeeId : null,
            );

            return response()->json($payload);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Admin finance overview (revenue, ledger snippets, earning charts).
     *
     * @throws AuthorizationException
     */
    public function financeDashboard(Request $request): View|Factory|Application
    {
        if (is_admin_employee()) {
            abort(403);
        }

        ['data' => $data, 'chart_data' => $chart_data] = $this->buildFinanceDashboardPayload($request);

        return view('adminmodule::dashboard', compact('data', 'chart_data'));
    }

    /**
     * Admin operations overview (customers, providers, app activity).
     *
     * @throws AuthorizationException
     */
    public function operationsDashboard(Request $request): View|Factory|Application
    {
        if (is_admin_employee()) {
            abort(403);
        }

        $data = app(OperationsDashboardService::class)->build();

        return view('adminmodule::dashboard-operations', compact('data'));
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, chart_data: array<string, mixed>}
     */
    private function buildFinanceDashboardPayload(Request $request): array
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

        $revenueTotals = AdminDashboardCache::rememberMetrics('revenue_totals:v1', fn () => $this->buildRevenueTotalsPayload());
        $total_revenue = (float) ($revenueTotals['total_revenue'] ?? 0);
        $spare_parts_total = (float) ($revenueTotals['spare_parts_total'] ?? 0);
        $service_charges_total = (float) ($revenueTotals['service_charges_total'] ?? 0);
        $repeatLineTotalByParentId = is_array($revenueTotals['repeatLineTotalByParentId'] ?? null)
            ? $revenueTotals['repeatLineTotalByParentId']
            : [];

        $financialSummary = admin_dashboard_financial_summary_metrics();

        $data = [];
        $data[] = ['top_cards' => [
            'total_revenue' => round($total_revenue, 2),
            'service_charges_total' => $service_charges_total,
            'spare_parts_total' => $spare_parts_total,
            'our_earning' => round($our_earning ?? 0, 2),
            'payable_to_providers' => $financialSummary['payable_to_providers'],
            'unsettled_withdraws_total' => $financialSummary['unsettled_withdraws_total'],
            'unsettled_withdraws_pending' => $financialSummary['unsettled_withdraws_pending'],
            'unsettled_withdraws_approved' => $financialSummary['unsettled_withdraws_approved'],
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

        $walletTrxTypes = array_values(WALLET_TRX_TYPE);

        $recent_transactions = $this->transaction
            ->with(['booking:id,readable_id'])
            ->whereNotIn('trx_type', $walletTrxTypes)
            ->latest()
            ->take(5)
            ->get();

        $this_month_transactions_count = $this->transaction
            ->whereNotIn('trx_type', $walletTrxTypes)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $recent_wallet_transactions = $this->transaction
            ->with([
                'booking:id,readable_id',
                'to_user:id,first_name,last_name',
            ])
            ->whereIn('trx_type', $walletTrxTypes)
            ->where(function ($query) {
                $query->where('trx_type', '!=', WALLET_TRX_TYPE['booking_compensation'])
                    ->orWhere('credit', '>', 0);
            })
            ->latest()
            ->take(5)
            ->get();

        $this_month_wallet_transactions_count = $this->transaction
            ->whereIn('trx_type', $walletTrxTypes)
            ->where(function ($query) {
                $query->where('trx_type', '!=', WALLET_TRX_TYPE['booking_compensation'])
                    ->orWhere('credit', '>', 0);
            })
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $recent_completed_bookings = $this->booking
            ->forRevenueReporting()
            ->with([
                'provider:id,company_name,logo',
                'details_amounts',
            ])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function (Booking $booking) {
                $booking->our_earning = $this->computeBookingOurEarning($booking);

                return $booking;
            });

        $data[] = [
            'recent_transactions' => $recent_transactions,
            'this_month_trx_count' => $this_month_transactions_count,
            'recent_wallet_transactions' => $recent_wallet_transactions,
            'this_month_wallet_trx_count' => $this_month_wallet_transactions_count,
            'recent_completed_bookings' => $recent_completed_bookings,
        ];

        $year = (int) (session('dashboard_earning_graph_year') ?: date('Y'));
        $month = session()->has('dashboard_earning_graph_month') ? (int) session('dashboard_earning_graph_month') : null;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        $chart_data = $this->buildDashboardEarningChartData($year, $month, $repeatLineTotalByParentId);

        return [
            'data' => $data,
            'chart_data' => $chart_data,
        ];
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
        $year = (int) $request->input('year', date('Y'));
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        $repeatLineTotalByParentId = $this->cachedRepeatLineTotalByParentId();
        $chart_data = $this->buildDashboardEarningChartData($year, $month, $repeatLineTotalByParentId);

        session()->put('dashboard_earning_graph_year', $year);
        if ($month !== null) {
            session()->put('dashboard_earning_graph_month', $month);
        } else {
            session()->forget('dashboard_earning_graph_month');
        }

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
    public function getUpdatedData(Request $request, StaffPresenceService $staffPresenceService, AdminInboxNotificationService $inboxNotificationService): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        // Cache scalar unread counts only — never the rendered notification HTML.
        // Caching the template made this endpoint fail when disk cache writes failed
        // (full disk), which left all header badges stuck at CSS display:none.
        try {
            $counts = Cache::remember("admin_header_counts:{$userId}", 30, function () use ($userId, $user, $inboxNotificationService) {
                $staffUnreadMessages = AdminHeaderChatCounts::staffUnreadMessages($user);

                return [
                    'message' => $staffUnreadMessages,
                    'staff_message' => $staffUnreadMessages,
                    'staff_unread_messages' => $staffUnreadMessages,
                    'customer_provider_unread_messages' => AdminHeaderChatCounts::supportUnreadMessages($user),
                    'notification_unread_count' => $inboxNotificationService->unreadCount((string) $userId),
                    'notification_external_unread_count' => $inboxNotificationService->unreadCount((string) $userId, UserNotification::CATEGORY_EXTERNAL),
                    'notification_internal_unread_count' => $inboxNotificationService->unreadCount((string) $userId, UserNotification::CATEGORY_INTERNAL),
                    'notification_read_count' => $inboxNotificationService->readCount((string) $userId),
                    'notification_external_read_count' => $inboxNotificationService->readCount((string) $userId, UserNotification::CATEGORY_EXTERNAL),
                    'notification_internal_read_count' => $inboxNotificationService->readCount((string) $userId, UserNotification::CATEGORY_INTERNAL),
                ];
            });
        } catch (\Throwable $e) {
            report($e);
            $staffUnreadMessages = AdminHeaderChatCounts::staffUnreadMessages($user);
            $counts = [
                'message' => $staffUnreadMessages,
                'staff_message' => $staffUnreadMessages,
                'staff_unread_messages' => $staffUnreadMessages,
                'customer_provider_unread_messages' => AdminHeaderChatCounts::supportUnreadMessages($user),
                'notification_unread_count' => $inboxNotificationService->unreadCount((string) $userId),
                'notification_external_unread_count' => $inboxNotificationService->unreadCount((string) $userId, UserNotification::CATEGORY_EXTERNAL),
                'notification_internal_unread_count' => $inboxNotificationService->unreadCount((string) $userId, UserNotification::CATEGORY_INTERNAL),
                'notification_read_count' => $inboxNotificationService->readCount((string) $userId),
                'notification_external_read_count' => $inboxNotificationService->readCount((string) $userId, UserNotification::CATEGORY_EXTERNAL),
                'notification_internal_read_count' => $inboxNotificationService->readCount((string) $userId, UserNotification::CATEGORY_INTERNAL),
            ];
        }

        $externalUnreadCount = (int) ($counts['notification_external_unread_count'] ?? 0);
        $internalUnreadCount = (int) ($counts['notification_internal_unread_count'] ?? 0);
        $externalReadCount = (int) ($counts['notification_external_read_count'] ?? 0);
        $internalReadCount = (int) ($counts['notification_internal_read_count'] ?? 0);

        $notificationExternalTemplate = view('adminmodule::admin.partials._notifications', [
            'category' => UserNotification::CATEGORY_EXTERNAL,
            'notifications' => $inboxNotificationService->recent((string) $userId, 10, UserNotification::CATEGORY_EXTERNAL),
            'unreadCount' => $externalUnreadCount,
            'readCount' => $externalReadCount,
            'compact' => true,
        ])->render();

        $notificationInternalTemplate = view('adminmodule::admin.partials._notifications', [
            'category' => UserNotification::CATEGORY_INTERNAL,
            'notifications' => $inboxNotificationService->recent((string) $userId, 10, UserNotification::CATEGORY_INTERNAL),
            'unreadCount' => $internalUnreadCount,
            'readCount' => $internalReadCount,
            'compact' => true,
        ])->render();

        $whatsappUnreadChats = 0;
        $whatsappUnreadMessages = 0;
        if ($request->user()->can('whatsapp_chat_view')) {
            [$whatsappUnreadChats, $whatsappUnreadMessages] = WhatsAppAdminUnread::counts();
        }

        $newNotificationAlerts = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->latest()
            ->take(5)
            ->get(['id', 'type', 'title', 'body', 'action_url'])
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'category' => $n->category,
                'title' => $n->title,
                'body' => $n->body,
                'action_url' => $n->action_url,
                'action_label' => $n->actionButtonLabel(),
            ])
            ->values()
            ->all();

        $presenceStatus = $staffPresenceService->resolveDisplayStatus($request->user());
        $presenceLabel = $staffPresenceService->statusLabel($presenceStatus);

        return response()->json([
            'status' => 1,
            'data' => array_merge($counts, [
                'notification_external_template' => $notificationExternalTemplate,
                'notification_internal_template' => $notificationInternalTemplate,
                'whatsapp_unread_chats' => $whatsappUnreadChats,
                'whatsapp_unread_messages' => $whatsappUnreadMessages,
                'new_notification_alerts' => $newNotificationAlerts,
                'presence_status' => $presenceStatus,
                'presence_label' => $presenceLabel,
            ]),
        ]);
    }

    public function markNotificationRead(Request $request, string $id, AdminInboxNotificationService $inboxNotificationService): JsonResponse
    {
        $inboxNotificationService->markAsRead($id, (string) $request->user()->id);

        return response()->json([
            'status' => 1,
            'message' => translate(DEFAULT_UPDATE_200['message']),
        ]);
    }

    public function markAllNotificationsRead(Request $request, AdminInboxNotificationService $inboxNotificationService): JsonResponse
    {
        $category = $request->input('category');
        if (! in_array($category, [UserNotification::CATEGORY_EXTERNAL, UserNotification::CATEGORY_INTERNAL, null], true)) {
            $category = null;
        }

        $inboxNotificationService->markAllAsRead((string) $request->user()->id, $category);

        return response()->json([
            'status' => 1,
            'message' => translate(DEFAULT_UPDATE_200['message']),
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
            $fullURL = url($uri) . '?filter=all';
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
        $allRoutes = app(EmployeeSearchAccessFilter::class)->filterGroupedResults($allRoutes);

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
        $searchAccessFilter = app(EmployeeSearchAccessFilter::class);
        if ($searchAccessFilter->applies() && ! $searchAccessFilter->isAllowed((string) $request->input('uri', ''))) {
            Toastr::error(translate(ACCESS_DENIED['message']));

            return back();
        }

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
        $result = app(EmployeeSearchAccessFilter::class)->filterGroupedResults($result);

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
     * @return array<string, float|array<string, float>>
     */
    private function buildRevenueTotalsPayload(): array
    {
        $allCompletedRepeats = BookingRepeat::ofBookingStatus('completed')->with('booking.extra_services')->get();
        $repeatLineTotalByParentId = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($allCompletedRepeats);

        $total_revenue = 0.0;
        $spare_parts_total = 0.0;
        foreach (Booking::query()->forRevenueReporting()->with('extra_services')->get() as $b) {
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

        return [
            'total_revenue' => round($total_revenue, 2),
            'spare_parts_total' => round($spare_parts_total, 2),
            'service_charges_total' => round($total_revenue - $spare_parts_total, 2),
            'repeatLineTotalByParentId' => $repeatLineTotalByParentId,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function cachedRepeatLineTotalByParentId(): array
    {
        $revenueTotals = AdminDashboardCache::rememberMetrics('revenue_totals:v1', fn () => $this->buildRevenueTotalsPayload());

        return is_array($revenueTotals['repeatLineTotalByParentId'] ?? null)
            ? $revenueTotals['repeatLineTotalByParentId']
            : [];
    }

    /**
     * @param  array<string, float>  $repeatLineTotalByParentId
     * @return array{total_earning: array<int, string|float>, commission_earning: array<int, string|float>, categories: array<int, string>, granularity: string}
     */
    private function buildDashboardEarningChartData(int $year, ?int $month, array $repeatLineTotalByParentId): array
    {
        if ($month !== null) {
            return $this->buildDashboardEarningChartDataForMonth($year, $month, $repeatLineTotalByParentId);
        }

        return $this->buildDashboardEarningChartDataForYear($year, $repeatLineTotalByParentId);
    }

    /**
     * @param  array<string, float>  $repeatLineTotalByParentId
     * @return array{total_earning: array<int, string|float>, commission_earning: array<int, string|float>, categories: array<int, string>, granularity: string}
     */
    private function buildDashboardEarningChartDataForYear(int $year, array $repeatLineTotalByParentId): array
    {
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
            $bucketMonth = (int) ($item['month'] ?? 0);
            if ($bucketMonth < 1 || $bucketMonth > 12) {
                continue;
            }

            $adminCommission = (float) ($item['admin_commission'] ?? 0);
            $discountByAdmin = (float) ($item['discount_by_admin'] ?? 0);
            $couponDiscountByAdmin = (float) ($item['coupon_discount_by_admin'] ?? 0);
            $campaignDiscountByAdmin = (float) ($item['campaign_discount_by_admin'] ?? 0);

            $adminEarningByMonth[$bucketMonth] = $adminCommission - $discountByAdmin - $couponDiscountByAdmin - $campaignDiscountByAdmin;
        }

        $scaledCommissionAdjYear = admin_dashboard_scaled_admin_commission_adjustments($year);
        foreach (range(1, 12) as $m) {
            $adminEarningByMonth[$m] = ($adminEarningByMonth[$m] ?? 0) + (float) (($scaledCommissionAdjYear['by_month'] ?? [])[$m] ?? 0);
        }

        $revenueByMonth = $this->dashboardReportedRevenueByMonth($year, $repeatLineTotalByParentId);

        $chart_data = [
            'total_earning' => [],
            'commission_earning' => [],
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'granularity' => 'year',
        ];

        foreach (range(1, 12) as $bucketMonth) {
            $chart_data['total_earning'][] = with_decimal_point((float) ($revenueByMonth[$bucketMonth] ?? 0));
            $chart_data['commission_earning'][] = with_decimal_point((float) ($adminEarningByMonth[$bucketMonth] ?? 0));
        }

        return $chart_data;
    }

    /**
     * @param  array<string, float>  $repeatLineTotalByParentId
     * @return array{total_earning: array<int, string|float>, commission_earning: array<int, string|float>, categories: array<int, string>, granularity: string}
     */
    private function buildDashboardEarningChartDataForMonth(int $year, int $month, array $repeatLineTotalByParentId): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $amounts = $this->booking_details_amount
            ->whereYear('created_at', '=', $year)
            ->whereMonth('created_at', '=', $month)
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
                DB::raw('DAY(created_at) day')
            )
            ->groupby('day')
            ->get()
            ->toArray();

        $adminEarningByDay = array_fill(1, $daysInMonth, 0.0);
        foreach ($amounts as $item) {
            $day = (int) ($item['day'] ?? 0);
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            $adminCommission = (float) ($item['admin_commission'] ?? 0);
            $discountByAdmin = (float) ($item['discount_by_admin'] ?? 0);
            $couponDiscountByAdmin = (float) ($item['coupon_discount_by_admin'] ?? 0);
            $campaignDiscountByAdmin = (float) ($item['campaign_discount_by_admin'] ?? 0);

            $adminEarningByDay[$day] = $adminCommission - $discountByAdmin - $couponDiscountByAdmin - $campaignDiscountByAdmin;
        }

        $scaledCommissionByDay = $this->scaledAdminCommissionAdjustmentsByDay($year, $month, $daysInMonth);
        foreach (range(1, $daysInMonth) as $day) {
            $adminEarningByDay[$day] += (float) ($scaledCommissionByDay[$day] ?? 0);
        }

        $revenueByDay = $this->computeDashboardReportedRevenueByDay($year, $month, $daysInMonth, $repeatLineTotalByParentId);

        $chart_data = [
            'total_earning' => [],
            'commission_earning' => [],
            'categories' => [],
            'granularity' => 'month',
        ];

        foreach (range(1, $daysInMonth) as $day) {
            $chart_data['categories'][] = (string) $day;
            $chart_data['total_earning'][] = with_decimal_point((float) ($revenueByDay[$day] ?? 0));
            $chart_data['commission_earning'][] = with_decimal_point((float) ($adminEarningByDay[$day] ?? 0));
        }

        return $chart_data;
    }

    /**
     * @return array<int, float>
     */
    private function scaledAdminCommissionAdjustmentsByDay(int $year, int $month, int $daysInMonth): array
    {
        $byDay = array_fill(1, $daysInMonth, 0.0);

        $query = Booking::query()
            ->forRevenueReporting()
            ->where('settlement_outcome', BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        foreach ($query->cursor() as $main) {
            if (! $main instanceof Booking) {
                continue;
            }

            $delta = booking_scaled_admin_commission_delta_for_main($main);
            if (abs($delta) < 0.00001) {
                continue;
            }

            $day = (int) $main->created_at->format('j');
            if ($day >= 1 && $day <= $daysInMonth) {
                $byDay[$day] += $delta;
            }
        }

        foreach ($byDay as $day => $value) {
            $byDay[$day] = round((float) $value, 2);
        }

        return $byDay;
    }

    /**
     * @param  array<string, float>  $repeatLineTotalByParentId
     * @return array<int, float>
     */
    private function computeDashboardReportedRevenueByDay(int $year, int $month, int $daysInMonth, array $repeatLineTotalByParentId): array
    {
        $byDay = array_fill(1, $daysInMonth, 0.0);

        $oneTimeInMonth = $this->booking->newQuery()
            ->forRevenueReporting()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with('extra_services')
            ->get();

        foreach ($oneTimeInMonth as $b) {
            $day = (int) $b->created_at->format('j');
            $slice = get_admin_dashboard_reporting_total_and_spare_for_booking($b);
            $byDay[$day] += $slice['reported_total'];
        }

        $repeatsInMonth = BookingRepeat::query()
            ->ofBookingStatus('completed')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with('booking.extra_services')
            ->get();

        foreach ($repeatsInMonth as $r) {
            $day = (int) $r->created_at->format('j');
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatLineTotalByParentId[$parentKey] ?? get_booking_total_amount($r));
            $slice = get_admin_dashboard_reporting_total_and_spare_for_repeat($r, $den);
            $byDay[$day] += $slice['reported_total'];
        }

        foreach ($byDay as $day => $value) {
            $byDay[$day] = round((float) $value, 2);
        }

        return $byDay;
    }

    /**
     * Per-month reported revenue for the admin earning chart (matches top-card basis: special settlements, after-visit cancels via forRevenueReporting).
     *
     * @param  array<string, float>  $repeatLineTotalByParentId  Sum of completed repeat line totals per parent booking_id (global, for correct non-standard weights).
     * @return array<int, float> month => revenue
     */
    private function dashboardReportedRevenueByMonth(int $year, array $repeatLineTotalByParentId): array
    {
        return AdminDashboardCache::rememberMetrics("revenue_by_month:{$year}", function () use ($year, $repeatLineTotalByParentId) {
            return $this->computeDashboardReportedRevenueByMonth($year, $repeatLineTotalByParentId);
        });
    }

    /**
     * @param  array<string, float>  $repeatLineTotalByParentId
     * @return array<int, float>
     */
    private function computeDashboardReportedRevenueByMonth(int $year, array $repeatLineTotalByParentId): array
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

    private function computeBookingOurEarning(Booking $booking): float
    {
        $amounts = $booking->details_amounts;
        $adminCommission = (float) $amounts->sum('admin_commission');
        $ourEarning = $adminCommission
            - (float) $amounts->sum('discount_by_admin')
            - (float) $amounts->sum('coupon_discount_by_admin')
            - (float) $amounts->sum('campaign_discount_by_admin');

        if ($booking->settlement_outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $ourEarning += booking_scaled_admin_commission_delta_for_main($booking);
        }

        return round($ourEarning, 2);
    }
}
