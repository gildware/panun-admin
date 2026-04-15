<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Lib\DiscountCostBearer;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingAdditionalInformation;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingRepeatDetails;
use Modules\BookingModule\Entities\BookingRepeatHistory;
use Modules\BookingModule\Entities\BookingScheduleHistory;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Entities\SubscriptionBookingType;
use Modules\BookingModule\Entities\BookingCancellationReason;
use Modules\BookingModule\Entities\BookingHoldReopenReason;
use Modules\BookingModule\Services\AdminCompanyInflowPaymentService;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\BookingModule\Services\AdminBookingDeletionService;
use Modules\BookingModule\Services\BookingReadableIdAllocator;
use Modules\BookingModule\Services\BookingReopenService;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Services\ZoneCoverageNormalizationService;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\Source;
use Modules\PaymentModule\Entities\OfflinePayment;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BookingController extends Controller
{

    private Booking $booking;
    private BookingRepeat $bookingRepeat;
    private BookingStatusHistory $bookingStatusHistory;
    private BookingRepeatHistory $bookingRepeatHistory;
    private BookingScheduleHistory $bookingScheduleHistory;
    private $subscribedSubCategories;
    private Category $category;
    private Zone $zone;
    private Serviceman $serviceman;
    private Provider $provider;
    private UserAddress $userAddress;
    private BookingDetail $bookingDetails;
    private BookingAdditionalInformation $bookingAdditionalInformation;
    private BookingRepeatDetails $bookingRepeatDetail;

    use BookingTrait;
    use AuthorizesRequests;

    public function __construct(Booking $booking, BookingRepeatDetails $bookingRepeatDetail, BookingRepeatHistory $bookingRepeatHistory, BookingRepeat $bookingRepeat, BookingDetail $bookingDetails, BookingStatusHistory $bookingStatusHistory, BookingScheduleHistory $bookingScheduleHistory, SubscribedService $subscribedService, Category $category, Zone $zone, Serviceman $serviceman, Provider $provider, UserAddress $userAddress, BookingAdditionalInformation $bookingAdditionalInformation)
    {
        $this->booking = $booking;
        $this->bookingRepeat = $bookingRepeat;
        $this->bookingRepeatDetail = $bookingRepeatDetail;
        $this->bookingRepeatHistory = $bookingRepeatHistory;
        $this->bookingDetails = $bookingDetails;
        $this->bookingStatusHistory = $bookingStatusHistory;
        $this->bookingScheduleHistory = $bookingScheduleHistory;
        $this->category = $category;
        $this->zone = $zone;
        $this->serviceman = $serviceman;
        $this->provider = $provider;
        $this->userAddress = $userAddress;
        $this->bookingAdditionalInformation = $bookingAdditionalInformation;
        try {
            $this->subscribedSubCategories = $subscribedService->where(['is_subscribed' => 1])->pluck('sub_category_id')->toArray();
        } catch (\Exception $exception) {
            $this->subscribedSubCategories = $subscribedService->pluck('sub_category_id')->toArray();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingConfigurationReasonVariables(): array
    {
        return [
            'bookingCancellationReasons' => BookingCancellationReason::query()->where('is_active', true)->orderBy('name')->get(),
            'bookingHoldReasons' => BookingHoldReopenReason::query()->where('is_active', true)->where('kind', BookingHoldReopenReason::KIND_HOLD)->orderBy('name')->get(),
            'bookingReopenReasons' => BookingHoldReopenReason::query()->where('is_active', true)->where('kind', BookingHoldReopenReason::KIND_REOPEN)->orderBy('name')->get(),
        ];
    }

    /**
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat|null  $booking
     * @return array<string, array<int, mixed>>
     */
    private function adminBookingStatusReasonRules(string $from, string $to, $booking = null): array
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));
        $allowed = $booking !== null
            && ($booking instanceof \Modules\BookingModule\Entities\Booking || $booking instanceof \Modules\BookingModule\Entities\BookingRepeat)
            ? booking_admin_status_transition_allowed_for_booking($booking, $from, $to)
            : booking_admin_status_transition_allowed($from, $to);
        if (! $allowed) {
            return [];
        }
        if ($to === 'canceled') {
            return [
                'reason_responsible' => ['required', Rule::in(BookingCancellationReason::responsibleOptions())],
                'booking_cancellation_reason_id' => [
                    'required',
                    Rule::exists('booking_cancellation_reasons', 'id')->where(function ($query) {
                        $query->where('is_active', 1)
                            ->where('responsible', (string) request()->input('reason_responsible'));
                    }),
                ],
                'status_change_remarks' => 'nullable|string|max:2000',
            ];
        }
        if ($to === 'on_hold') {
            return [
                'reason_responsible' => ['required', Rule::in(BookingHoldReopenReason::responsibleOptions())],
                'booking_hold_reopen_reason_id' => [
                    'required',
                    Rule::exists('booking_hold_reopen_reasons', 'id')->where(function ($query) {
                        $query->where('is_active', 1)
                            ->where('kind', BookingHoldReopenReason::KIND_HOLD)
                            ->where('responsible', (string) request()->input('reason_responsible'));
                    }),
                ],
                'hold_estimated_service_schedule' => ['required', 'date'],
                'status_change_remarks' => 'nullable|string|max:2000',
            ];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: ?int, 1: ?int, 2: ?string}
     */
    private function extractStatusChangeReasonMeta(array $validated, string $from, string $to): array
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));
        $remarks = $validated['status_change_remarks'] ?? null;
        if ($to === 'canceled') {
            return [
                isset($validated['booking_cancellation_reason_id']) ? (int) $validated['booking_cancellation_reason_id'] : null,
                null,
                is_string($remarks) ? $remarks : null,
            ];
        }
        if ($to === 'on_hold') {
            return [
                null,
                isset($validated['booking_hold_reopen_reason_id']) ? (int) $validated['booking_hold_reopen_reason_id'] : null,
                is_string($remarks) ? $remarks : null,
            ];
        }

        return [null, null, null];
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('booking_view');
        $allowedBookingStatuses = array_merge(array_column(BOOKING_STATUSES, 'key'), ['all', 'reopened']);
        $request->validate([
            'booking_status' => 'nullable|in:' . implode(',', $allowedBookingStatuses),
        ]);

        $queryParams = $request->only(['zone_ids', 'category_ids', 'sub_category_ids', 'start_date', 'end_date', 'search']);
        $queryParams['assignee_ids'] = $this->normalizeAdminAssigneeFilterIds((array) $request->input('assignee_ids', []));
        $filterCounter = collect($queryParams)->filter()->count();
        $bookingStatus = $queryParams['booking_status'] = $request->input('booking_status') ?: 'all';
        $queryParams['booking_type'] = $request->input('booking_type', '');
        $queryParams['service_type'] = 'all';
        $queryParams['provider_assigned'] = $request->input('provider_assigned', '');

        if (empty($queryParams['start_date'])) {
            $queryParams['start_date'] = null;
        }
        if (empty($queryParams['end_date'])) {
            $queryParams['end_date'] = null;
        }

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;
        $bookings = $this->booking
            ->with(array_merge(
                ['customer', 'assignee', 'followups', 'extra_services'],
                $bookingStatus === 'reopened' ? [
                    'reopenEvents.holdReopenReason',
                    'spawnedFollowupBookings',
                    'originatedFromBooking.reopenEvents.holdReopenReason',
                ] : [],
                $bookingStatus === 'canceled' ? ['latestParentCancellationStatusHistory.cancellationReason'] : [],
                $bookingStatus === 'on_hold' ? ['latestParentHoldStatusHistory.holdReopenReason'] : [],
            ))
            ->search($request['search'], ['readable_id'])
            ->when($bookingStatus != 'all', function ($query) use ($bookingStatus, $maxBookingAmount, $request) {
                if ($bookingStatus === 'reopened') {
                    $query->reopenedChain();
                } else {
                    $query->when($bookingStatus == 'pending', function ($query) use ($maxBookingAmount) {
                        $query->adminPendingBookings($maxBookingAmount);
                    })->when($bookingStatus == 'accepted', function ($query) use ($maxBookingAmount) {
                        $query->adminAcceptedBookings($maxBookingAmount);
                    })->ofBookingStatus($bookingStatus);
                }
            })
            ->when($request['provider_assigned'] == 'assigned', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNotNull('provider_id')
                        ->orWhereHas('repeat', function ($q) {
                            $q->whereNotNull('provider_id');
                        });
                });
            })
            ->when($request['provider_assigned'] == 'unassigned', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('provider_id');
                });
            })
            ->filterByZoneIds($request['zone_ids'])
            ->filterBySubcategoryIds($request['sub_category_ids'])
            ->filterByCategoryIds($request['category_ids'])
            ->filterByDateRange($request['start_date'], $request['end_date'])
            ->filterByAssigneeIds($queryParams['assignee_ids'])
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParams);

        foreach ($bookings as $booking) {
            if ($booking->repeat->isNotEmpty()) {
                $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                    $parts = explode('-', $repeat->readable_id);
                    $suffix = end($parts);
                    return $this->readableIdToNumber($suffix);
                });

                $booking->repeats = $sortedRepeats->values();

                $nextService = $booking->repeats->firstWhere('booking_status', 'ongoing')
                    ?? $booking->repeats->firstWhere('booking_status', 'on_hold')
                    ?? $booking->repeats->firstWhere('booking_status', 'accepted')
                    ?? $booking->repeats->firstWhere('booking_status', 'pending');

                $lastRepeat = $booking->repeats->last();
                $booking['nextServiceId'] = $nextService ? $nextService->id : null;
                $booking['nextService'] = $nextService;
                $booking['lastRepeat'] = $lastRepeat;
            }
        }


        $zones = $this->zone->withoutGlobalScope('translate')->select('id', 'name')->get();
        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();
        $subCategories = $this->category->select('id', 'parent_id', 'name')->where('position', 2)->get();
        $assigneeUsers = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
            ->get();

        $bookingTabCounts = $this->adminBookingListStatusTabCounts();

        return view('bookingmodule::admin.booking.list', compact('bookings', 'zones', 'categories', 'subCategories', 'assigneeUsers', 'queryParams', 'filterCounter', 'bookingTabCounts'));
    }

    /**
     * Bookings that use a non-standard financial settlement (single bookings only), filterable by scenario tab.
     */
    public function specialScenarioBookings(Request $request): Renderable
    {
        $this->authorize('booking_view');
        $tabs = BookingFinancialSettlementService::specialScenarioListTabOutcomes();
        $scenario = $request->query('scenario', 'all');
        if ($scenario === 'cancel_after_visit') {
            $scenario = 'cancelled_after_visit';
        }
        if (! array_key_exists($scenario, $tabs)) {
            $scenario = 'all';
        }
        $outcomeFilter = $tabs[$scenario];

        $queryParams = $request->only(['search']);
        $queryParams['scenario'] = $scenario;

        $bookings = $this->booking
            ->with(['customer', 'provider.owner', 'assignee', 'extra_services', 'service_address'])
            ->where('is_repeated', 0)
            ->whereNotNull('settlement_outcome')
            ->where('settlement_outcome', '!=', '')
            ->when($outcomeFilter !== null, fn ($q) => $q->where('settlement_outcome', $outcomeFilter))
            ->search($request->input('search'), ['readable_id'])
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParams);

        $scenarioCounts = [];
        $baseCountQuery = $this->booking->query()
            ->where('is_repeated', 0)
            ->whereNotNull('settlement_outcome')
            ->where('settlement_outcome', '!=', '');
        foreach ($tabs as $tabKey => $out) {
            $scenarioCounts[$tabKey] = $out === null
                ? (clone $baseCountQuery)->count()
                : (clone $baseCountQuery)->where('settlement_outcome', $out)->count();
        }

        return view('bookingmodule::admin.booking.special-scenario-list', compact(
            'bookings',
            'queryParams',
            'scenario',
            'scenarioCounts'
        ));
    }

    /**
     * Status tab totals for the admin booking list (unfiltered; matches each tab's base query).
     */
    /**
     * @return list<string>
     */
    protected function normalizeAdminAssigneeFilterIds(array $raw): array
    {
        $out = [];
        foreach ($raw as $id) {
            if ($id === null || $id === '') {
                continue;
            }
            $id = is_string($id) ? trim($id) : $id;
            if ($id === '__unassigned__' || $id === 'unassigned') {
                $out[] = '__unassigned__';
                continue;
            }
            if (is_string($id) && Str::isUuid($id)) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    protected function adminBookingListStatusTabCounts(): array
    {
        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;

        return [
            'all' => $this->booking->count(),
            'pending' => $this->booking->newQuery()->adminPendingBookings($maxBookingAmount)->count(),
            'accepted' => $this->booking->newQuery()->adminAcceptedBookings($maxBookingAmount)->count(),
            'ongoing' => $this->booking->newQuery()->where('booking_status', 'ongoing')->count(),
            'completed' => $this->booking->newQuery()->where('booking_status', 'completed')->count(),
            'reopened' => $this->booking->newQuery()->reopenedChain()->count(),
            'on_hold' => $this->booking->newQuery()->where('booking_status', 'on_hold')->count(),
            'canceled' => $this->booking->newQuery()->whereIn('booking_status', ['canceled', 'refunded'])->count(),
        ];
    }

    /**
     * Show the form for creating a new booking from admin panel.
     *
     * @param Request $request
     * @return Factory|View|Application
     * @throws AuthorizationException
     */
    public function create(Request $request): Factory|View|Application|RedirectResponse
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));
            return redirect()->route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);
        }

        $reopenNewBookingDraft = null;
        $reopenPrefill = [];

        if ($request->boolean('from_reopen')) {
            $draft = session('reopen_new_booking_draft');
            if (empty($draft['source_booking_id'])) {
                session()->forget('reopen_new_booking_draft');
                Toastr::warning(translate('Invalid_reopen_follow_up_session'));

                return redirect()->route('admin.booking.create');
            }

            $src = $this->booking->find($draft['source_booking_id']);
            if (!$src || ($src->booking_status ?? '') !== 'completed') {
                session()->forget('reopen_new_booking_draft');
                Toastr::error(translate('Source_booking_must_remain_completed_for_follow_up'));

                return redirect()->route('admin.booking.create');
            }

            if ($src->isLossMakingFinancialSettlement()) {
                session()->forget('reopen_new_booking_draft');
                Toastr::error(translate('Loss_making_completed_booking_cannot_be_reopened'));

                return redirect()->route('admin.booking.details', [$src->id, 'web_page' => 'details']);
            }

            if ($src->blocksAdminReopenDueToDecidedChargesSpecialSettlement()) {
                session()->forget('reopen_new_booking_draft');
                Toastr::error(translate('Bfs_decided_charges_settlement_booking_cannot_be_reopened'));

                return redirect()->route('admin.booking.details', [$src->id, 'web_page' => 'details']);
            }

            $reopenNewBookingDraft = array_merge($draft, [
                'source_readable_id' => $src->readable_id,
            ]);

            $reopenPrefill = [
                'customer_id' => $src->customer_id,
                'zone_id' => $src->zone_id,
                'category_id' => $src->category_id,
                'sub_category_id' => $src->sub_category_id,
                'service_address_id' => $src->service_address_id,
                'service_location' => $src->service_location ?? 'customer',
            ];
        }

        $request->merge(array_merge($reopenPrefill, $request->query(), $request->old()));

        return $this->buildBookingCreateView($request, 'bookingmodule::admin.booking.create', $reopenNewBookingDraft);
    }

    /**
     * Show the form for creating a new booking from a lead (customer lead).
     *
     * @param Request $request
     * @param int $lead
     * @return Factory|View|Application|RedirectResponse
     * @throws AuthorizationException
     */
    public function createFromLead(Request $request, int $lead): Factory|View|Application|RedirectResponse
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));
            return redirect()->route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);
        }

        $leadModel = Lead::with(['source'])->findOrFail($lead);

        if ($leadModel->lead_type !== \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER) {
            Toastr::error(translate('Lead_is_not_a_customer_type'));
            return redirect()->route('admin.lead.show', $leadModel->id);
        }

        // Try to find existing customer by phone; otherwise create one
        $customer = User::query()->inCustomerDirectory()
            ->where('phone', $leadModel->phone_number)
            ->first();

        if (!$customer) {
            $defaultPassword = config('app.default_customer_password', '12345678');
            $customer = new User();
            $customer->first_name = $leadModel->name;
            $customer->last_name = '';
            $customer->phone = $leadModel->phone_number;
            $customer->email = null;
            $customer->profile_image = 'default.png';
            $customer->gender = 'male';
            $customer->password = bcrypt($defaultPassword);
            $customer->user_type = 'customer';
            $customer->customer_app_access = true;
            $customer->is_active = 1;
            $customer->save();
        }

        // Load latest customer-type history data for this lead (service info, estimated date, etc.)
        $typeHistory = \Modules\LeadManagement\Entities\LeadTypeHistory::where('lead_id', $leadModel->id)
            ->where('type', 'customer')
            ->latest()
            ->first();

        $customerData = ($typeHistory && is_array($typeHistory->data)) ? $typeHistory->data : [];

        $schedulePrefill = null;
        if (!empty($customerData['estimated_service_at'])) {
            try {
                $schedulePrefill = Carbon::parse($customerData['estimated_service_at'])->format('Y-m-d\TH:i');
            } catch (Throwable $e) {
                $schedulePrefill = is_string($customerData['estimated_service_at']) ? $customerData['estimated_service_at'] : null;
            }
        }

        // Prefill booking form values from lead data (keys match LeadController customer type payload; allow aliases)
        $prefill = [
            'lead_id' => $leadModel->id,
            'customer_id' => $customer->id,
            'zone_id' => $customerData['zone_id'] ?? null,
            'category_id' => $customerData['service_category'] ?? $customerData['category_id'] ?? null,
            'sub_category_id' => $customerData['service_subcategory'] ?? $customerData['sub_category_id'] ?? null,
            'service_id' => $customerData['service_name'] ?? $customerData['service_id'] ?? null,
            'variant_key' => $customerData['variant_key'] ?? null,
            'service_description' => $customerData['service_description'] ?? null,
            'service_schedule' => $schedulePrefill,
            'booking_source' => $leadModel->source?->name ?? null,
        ];

        $waReadable = $this->resolveWhatsAppBookingReadableIdForCustomerLead($leadModel);
        if ($waReadable !== null) {
            $prefill['whatsapp_reserved_readable_id'] = $waReadable;
        }

        $context = (string) $request->query('context', 'lead');
        $prefill['booking_go_back_url'] = match ($context) {
            'lead_modal' => route('admin.lead.show', ['id' => $leadModel->id, 'in_modal' => 1]),
            'lead' => route('admin.lead.show', $leadModel->id),
            default => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']),
        };

        // Merge prefill data with query params and old input (so user edits win)
        $request->merge(array_merge($prefill, $request->query(), $request->old()));

        return $this->buildBookingCreateView($request, 'bookingmodule::admin.booking.create-from-lead', null);
    }

    /**
     * WhatsApp AI / sync rows store the booking request id before a system booking exists.
     * When staff opens "Add booking" from the CRM lead, reuse that row (same readable_id) if still open.
     */
    protected function resolveWhatsAppBookingReadableIdForCustomerLead(Lead $lead): ?string
    {
        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) ($lead->phone_number ?? '')) ?? '';
        if (strlen($digits) < 10) {
            return null;
        }
        $last10 = substr($digits, -10);

        $base = WhatsAppBooking::query()
            ->whereIn('status', [
                WhatsAppBooking::STATUS_DRAFT,
                WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN,
            ])
            ->where(function ($q) {
                $q->whereNull('system_booking_id')->orWhere('system_booking_id', '');
            });

        $wa = (clone $base)
            ->where('lead_id', $lead->id)
            ->orderByDesc('updated_at')
            ->first();

        if (!$wa) {
            $wa = (clone $base)
                ->where('phone', 'like', '%'.$last10)
                ->orderByDesc('updated_at')
                ->first();
        }

        if (!$wa || !BookingReadableIdAllocator::isAppReadableIdFormat((string) $wa->booking_id)) {
            return null;
        }

        return (string) $wa->booking_id;
    }

    /**
     * Prefill Add New Booking from a WhatsApp-sourced booking row (same readable_id when reserved).
     */
    public function createFromWhatsAppBooking(Request $request, string $booking_id): Factory|View|Application|RedirectResponse
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));

            return redirect()->route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);
        }

        $wa = WhatsAppBooking::query()->where('booking_id', $booking_id)->first();
        if (!$wa) {
            Toastr::error(translate('Not_found'));

            return redirect()->route('admin.whatsapp.conversations.index', ['tab' => 'bookings']);
        }

        $customer = User::findByContactPhone((string) $wa->phone);
        if (!$customer || $customer->user_type !== 'customer') {
            $defaultPassword = config('app.default_customer_password', '12345678');
            $customer = new User();
            $name = trim((string) ($wa->name ?? ''));
            $customer->first_name = $name !== '' ? $name : 'Customer';
            $customer->last_name = '';
            $customer->phone = (string) $wa->phone;
            $customer->email = null;
            $customer->profile_image = 'default.png';
            $customer->gender = 'male';
            $customer->password = bcrypt($defaultPassword);
            $customer->user_type = 'customer';
            $customer->customer_app_access = true;
            $customer->is_active = 1;
            $customer->save();
        }

        $serviceAddressId = null;
        $addrText = trim((string) ($wa->address ?? ''));
        if ($addrText !== '') {
            $existingAddr = $this->userAddress->newQuery()
                ->where('user_id', $customer->id)
                ->where('address', $addrText)
                ->first();
            if ($existingAddr) {
                $serviceAddressId = $existingAddr->id;
            } else {
                $ua = new UserAddress();
                $ua->user_id = $customer->id;
                $ua->address = $addrText;
                $district = trim((string) ($wa->district ?? ''));
                if ($district !== '') {
                    $ua->city = Str::limit($district, 191, '');
                }
                $ua->save();
                $serviceAddressId = $ua->id;
            }
        }

        $zone = null;
        $district = trim((string) ($wa->district ?? ''));
        if ($district !== '') {
            $zone = $this->zone->newQuery()
                ->whereRaw('LOWER(name) = ?', [Str::lower($district)])
                ->first();
        }

        $waSource = Source::query()->active()->whereRaw('LOWER(name) = ?', ['whatsapp'])->first();
        $bookingSourceLabel = $waSource?->name ?? 'whatsapp';

        $desc = trim((string) ($wa->service_description ?? ''));
        if ($desc === '' && trim((string) ($wa->service ?? '')) !== '') {
            $desc = trim((string) $wa->service);
        }

        $prefill = [
            'customer_id' => $customer->id,
            'service_description' => $desc !== '' ? $desc : null,
            'booking_source' => $bookingSourceLabel,
            'whatsapp_reserved_readable_id' => BookingReadableIdAllocator::isAppReadableIdFormat((string) $wa->booking_id)
                ? (string) $wa->booking_id
                : null,
        ];

        if ($serviceAddressId) {
            $prefill['service_address_id'] = $serviceAddressId;
            $prefill['service_location'] = 'customer';
        }

        if ($zone) {
            $prefill['zone_id'] = (string) $zone->id;
        }

        $j = $wa->admin_prefill_json;
        if (is_array($j)) {
            foreach (['zone_id', 'category_id', 'sub_category_id', 'service_id', 'variant_key'] as $k) {
                if (!empty($j[$k])) {
                    $prefill[$k] = (string) $j[$k];
                }
            }
            $waEmail = trim((string) ($j['contact_email'] ?? ''));
            if ($waEmail !== '' && filter_var($waEmail, FILTER_VALIDATE_EMAIL)) {
                if (! trim((string) ($customer->email ?? ''))) {
                    $customer->email = Str::limit($waEmail, 191, '');
                    $customer->save();
                }
            }
        }

        if ($wa->prefered_datetime) {
            try {
                $prefill['service_schedule'] = $wa->prefered_datetime->format('Y-m-d\TH:i');
            } catch (Throwable) {
                $prefill['service_schedule'] = $wa->prefered_datetime->toDateTimeString();
            }
        }

        if ($wa->lead_id) {
            $prefill['lead_id'] = (string) $wa->lead_id;
        }

        $context = (string) $request->query('context');
        $leadIdQ = (int) $request->query('lead_id');
        if ($context === 'lead_modal' && $leadIdQ > 0) {
            $prefill['booking_go_back_url'] = route('admin.lead.show', ['id' => $leadIdQ, 'in_modal' => 1]);
        } elseif ($context === 'lead' && $leadIdQ > 0) {
            $prefill['booking_go_back_url'] = route('admin.lead.show', $leadIdQ);
        } else {
            $prefill['booking_go_back_url'] = route('admin.whatsapp.conversations.index', ['tab' => 'bookings']);
        }

        $prefill = array_filter(
            $prefill,
            static fn ($v) => $v !== null && $v !== ''
        );

        $request->merge(array_merge($prefill, $request->query(), $request->old()));

        return $this->buildBookingCreateView($request, 'bookingmodule::admin.booking.create', null);
    }

    /**
     * Build data and view for booking create form (used by both standard create and create-from-lead flows).
     *
     * @param Request $request
     * @param string $view
     * @param array<string, mixed>|null $reopenNewBookingDraft Session-backed follow-up-from-reopen context (create flow only)
     * @return Factory|View|Application
     */
    protected function buildBookingCreateView(Request $request, string $view, ?array $reopenNewBookingDraft = null): Factory|View|Application
    {
        $zones = $this->zone->withoutGlobalScope('translate')->select('id', 'name', 'parent_id')->get();
        $zoneTreeOptions = Zone::flatTreeOptionsForSelect($zones);
        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();
        $subCategories = $this->category->select('id', 'parent_id', 'name')->where('position', 2)->get();
        $providers = $this->provider->with('owner')->get();
        $servicemen = $this->serviceman->with('user')->get();
        $customers = User::query()->inCustomerDirectory()
            ->orderByDesc('created_at')
            ->select('id', 'first_name', 'last_name', 'phone')
            ->limit(100)
            ->get();

        $prefillCustomerId = $request->input('customer_id');
        if ($prefillCustomerId && !$customers->contains(fn ($u) => (string) $u->id === (string) $prefillCustomerId)) {
            $extraCustomer = User::query()
                ->inCustomerDirectory()
                ->where('id', $prefillCustomerId)
                ->select('id', 'first_name', 'last_name', 'phone')
                ->first();
            if ($extraCustomer) {
                $customers->prepend($extraCustomer);
            }
        }

        // Lead sources for unified "Booking Source" options (same as Add Lead Source)
        $sources = Source::active()->orderBy('name')->get(['id', 'name']);

        // Assignees: super-admins and admin employees
        $assignees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
            ->get();

        $currentAdmin = auth()->user();

        $advancePaymentMethodGroups = $this->getAdminAdvancePaymentMethodGroupsForCreate();

        $bookingGoBackUrl = $this->sanitizeBookingGoBackUrl($request->input('booking_go_back_url'));
        if ($bookingGoBackUrl === null) {
            $bookingGoBackUrl = route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);
        }

        return view($view, compact(
            'zones',
            'zoneTreeOptions',
            'categories',
            'subCategories',
            'providers',
            'servicemen',
            'customers',
            'assignees',
            'currentAdmin',
            'sources',
            'reopenNewBookingDraft',
            'advancePaymentMethodGroups',
            'bookingGoBackUrl'
        ));
    }

    /**
     * Only allow same-host admin URLs as booking form "Go back" targets.
     */
    protected function sanitizeBookingGoBackUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }
        $url = trim($url);
        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) {
            return null;
        }
        $path = $parts['path'];
        if (!str_starts_with($path, '/admin') && $path !== '/admin') {
            return null;
        }
        $appUrl = (string) config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        if (!empty($parts['host']) && $appHost && strcasecmp((string) $parts['host'], (string) $appHost) !== 0) {
            return null;
        }

        return $url;
    }

    /**
     * Grouped choices for how advance / manual receipts were collected (digital gateways + wallet + offline). Excludes cash after service.
     *
     * @return list<array{id: string, label: string, options: list<array{key: string, label: string, kind: string, fields: list<array<string, mixed>>}>}>
     */
    protected function getAdminAdvancePaymentMethodGroupsForCreate(): array
    {
        return AdminCompanyInflowPaymentService::advanceMethodGroups();
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<string>
     */
    protected function collectAdvanceMethodKeysFromGroups(array $groups): array
    {
        return AdminCompanyInflowPaymentService::collectKeysFromGroups($groups);
    }

    /**
     * Ensure offline/customer advance inputs round-trip on preview → confirm (and stay on $data after validate).
     *
     * @param  array<string, mixed>  $data
     */
    protected function mergeAdvanceMethodFieldsFromRequestIntoData(Request $request, array &$data): void
    {
        $raw = $request->input('advance_method_fields');
        if (! is_array($raw)) {
            $data['advance_method_fields'] = [];

            return;
        }
        $out = [];
        foreach ($raw as $k => $v) {
            $key = is_string($k) ? $k : (is_int($k) ? (string) $k : '');
            if ($key === '') {
                continue;
            }
            $out[$key] = is_scalar($v) ? (string) $v : '';
        }
        $data['advance_method_fields'] = $out;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    protected function flattenAdminAdvanceMethodOptionsForPreview(array $groups): array
    {
        $flat = [];
        foreach ($groups as $group) {
            foreach ($group['options'] ?? [] as $opt) {
                $flat[] = ['key' => (string) $opt['key'], 'label' => (string) $opt['label']];
            }
        }

        return $flat;
    }

    protected function classifyAdminAdvanceChoiceKind(string $choice): string
    {
        return AdminCompanyInflowPaymentService::classifyChoiceKind($choice);
    }

    /**
     * Validates transaction reference / offline field requirements after base rules (when advance amount &gt; 0).
     */
    protected function assertAdminAdvancePaymentFollowUpValidation(Request $request): void
    {
        $advance = (float) ($request->input('advance_paid_amount') ?? 0);
        if ($advance <= 0) {
            return;
        }

        $choice = (string) ($request->input('advance_payment_method') ?? '');
        if ($choice === '') {
            return;
        }

        AdminCompanyInflowPaymentService::validateAdvanceFollowUp($request, $choice);
    }

    protected function truncateBookingTransactionIdField(string $s): string
    {
        return AdminCompanyInflowPaymentService::truncateBookingTransactionIdField($s);
    }

    protected function truncateLedgerTransactionIdField(string $s): string
    {
        return AdminCompanyInflowPaymentService::truncateLedgerTransactionIdField($s);
    }

    /**
     * Value for booking.transaction_id, booking_partial_payments.transaction_id, ledger.transaction_id — reference only (no method labels).
     */
    protected function extractAdminAdvanceTransactionIdForStorageOnly(string $advanceChoice, Request $request): string
    {
        return AdminCompanyInflowPaymentService::extractTransactionIdForStorageOnly($advanceChoice, $request);
    }

    /**
     * Full offline context for ledger.reference_note (method name + all filled fields).
     */
    protected function buildAdminAdvanceOfflineReferenceNoteForLedger(string $advanceChoice, Request $request): ?string
    {
        return AdminCompanyInflowPaymentService::buildOfflineReferenceNoteForLedger($advanceChoice, $request);
    }

    protected function mapAdvancePartialPaidWithToLedgerPaymentMethod(string $partialPaidWith): string
    {
        return AdminCompanyInflowPaymentService::mapPartialPaidWithToLedgerPaymentMethod($partialPaidWith);
    }

    /**
     * @return array{payment_method: string, is_paid: int, partial_paid_with: string}
     */
    protected function resolveAdminCreateBookingPaymentFromAdvanceChoice(string $choice, bool $isFullyPaidUpfront): array
    {
        $isOfflineChoice = $choice === 'offline' || str_starts_with($choice, 'offline:');
        $partialPaidWith = match (true) {
            $choice === 'cash_after_service' => 'cash_after_service',
            $isOfflineChoice => 'offline',
            default => $choice,
        };

        if (! $isFullyPaidUpfront) {
            return [
                'payment_method' => 'cash_after_service',
                'is_paid' => 0,
                'partial_paid_with' => $partialPaidWith,
            ];
        }

        $matchKey = match (true) {
            $isOfflineChoice => 'offline',
            $choice === 'cash_after_service' => 'cash_after_service',
            default => $choice,
        };

        $paymentMethod = match ($matchKey) {
            'offline' => 'offline_payment',
            'cash_after_service' => 'cash_after_service',
            'wallet_payment' => 'wallet_payment',
            default => $choice,
        };

        return [
            'payment_method' => $paymentMethod,
            'is_paid' => 1,
            'partial_paid_with' => $partialPaidWith,
        ];
    }

    /**
     * @param  array<string, mixed>  $data  Validated preview/store payload
     */
    protected function buildAdminCreatePaymentPreviewCopy(array $data, float $totalBilling, float $dueBalance): array
    {
        $advance = (float) ($data['advance_paid_amount'] ?? 0);
        if ($advance <= 0) {
            return [
                'method_line' => translate('Cash_After_Service'),
                'footnote' => translate('Final_payment_will_be_collected_upon_service_completion'),
            ];
        }

        $key = (string) ($data['advance_payment_method'] ?? '');
        $label = null;
        foreach ($this->flattenAdminAdvanceMethodOptionsForPreview($this->getAdminAdvancePaymentMethodGroupsForCreate()) as $opt) {
            if ($opt['key'] === $key) {
                $label = $opt['label'];
                break;
            }
        }
        $advanceLabel = $label ?? ($key !== '' ? $key : translate('Cash_After_Service'));

        $kind = $this->classifyAdminAdvanceChoiceKind($key);
        $advanceDisplay = match ($kind) {
            'offline' => translate('offline_payment') . ': ' . $advanceLabel,
            'digital' => translate('Digital_payment') . ': ' . $advanceLabel,
            default => $advanceLabel,
        };

        if ($dueBalance <= 0.009) {
            return [
                'method_line' => $advanceDisplay,
                'footnote' => translate('Booking_is_fully_paid_in_advance'),
            ];
        }

        // Do not append "Cash after service" to the method line — that reads like a second payment method.
        // The footnote already states that any remaining balance follows cash-after-service rules.
        return [
            'method_line' => $advanceDisplay,
            'footnote' => translate('Partial_advance_recorded_remaining_due_follows_CAS'),
        ];
    }

    /**
     * @return list<array{service_id: string, variant_key: string, quantity: int}>
     */
    protected function parseBookingCreateCartLinesFromRequest(Request $request): array
    {
        $lines = [];
        $raw = $request->input('booking_create_cart_json');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $sid = $item['service_id'] ?? null;
                    $vk = $item['variant_key'] ?? null;
                    $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                    if ($sid && $vk && $qty >= 1) {
                        $lineOut = [
                            'service_id' => (string) $sid,
                            'variant_key' => (string) $vk,
                            'quantity' => max(1, $qty),
                            'line_discount' => max(0.0, (float) ($item['line_discount'] ?? 0)),
                        ];
                        if (isset($item['unit_price']) && is_numeric($item['unit_price']) && (float) $item['unit_price'] > 0) {
                            $lineOut['unit_price'] = round((float) $item['unit_price'], 4);
                        }
                        $lineOut['line_discount_cost_bearer'] = DiscountCostBearer::normalize($item['line_discount_cost_bearer'] ?? null);
                        $lines[] = $lineOut;
                    }
                }
            }
        }
        if ($lines === [] && $request->filled('service_id') && $request->filled('variant_key')) {
            $qty = (int) $request->input('service_quantity', 1);
            $lines[] = [
                'service_id' => (string) $request->input('service_id'),
                'variant_key' => (string) $request->input('variant_key'),
                'quantity' => max(1, $qty),
            ];
        }

        return $lines;
    }

    /**
     * @return list<array{title: string, details: ?string, type: string, quantity: int, price: float, discount: float}>
     */
    protected function parseBookingCreateExtraServicesFromRequest(Request $request): array
    {
        $out = [];
        $raw = $request->input('booking_create_extra_services_json');
        if (! is_string($raw) || $raw === '') {
            return $out;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $out;
        }
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $type = $row['type'] ?? BookingExtraService::TYPE_SERVICE;
            $type = $type === BookingExtraService::TYPE_SPARE_PART ? BookingExtraService::TYPE_SPARE_PART : BookingExtraService::TYPE_SERVICE;
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $price = max(0.0, (float) ($row['price'] ?? 0));
            $discount = max(0.0, (float) ($row['discount'] ?? 0));
            $details = isset($row['details']) ? (string) $row['details'] : null;
            if ($details !== null && mb_strlen($details) > 2000) {
                $details = mb_substr($details, 0, 2000);
            }
            if (mb_strlen($title) > 255) {
                $title = mb_substr($title, 0, 255);
            }
            $out[] = [
                'title' => $title,
                'details' => $details,
                'type' => $type,
                'quantity' => $qty,
                'price' => $price,
                'discount' => $discount,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{service_id: string, variant_key: string, quantity: int}>  $lines
     * @param  array<string, float>  $acOverrides
     * @param  list<array{title: string, details: ?string, type: string, quantity: int, price: float, discount: float}>  $extrasInput
     * @return array{
     *   lines: list<array<string, mixed>>,
     *   sum_line_totals: float,
     *   sum_tax: float,
     *   sum_basic_discount: float,
     *   sum_campaign_discount: float,
     *   extra_fee: float,
     *   additional_charge_lines: list<array<string, mixed>>,
     *   extras: list<array<string, mixed>>,
     *   extras_total: float,
     *   grand_total: float
     * }
     */
    protected function buildAdminCreateBookingCartPricing(
        string $zoneId,
        string $providerId,
        array $lines,
        array $acOverrides,
        array $extrasInput
    ): array {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'service_id' => [translate('Select_Service')],
            ]);
        }

        $lineCalcs = [];
        $cartObjects = [];

        foreach ($lines as $line) {
            $serviceId = $line['service_id'];
            $variantKey = $line['variant_key'];
            $quantity = max(1, (int) $line['quantity']);

            $service = Service::active()
                ->with(['category.category_discount', 'category.campaign_discount', 'subCategory', 'service_discount'])
                ->where('id', $serviceId)
                ->first();

            $variation = Variation::firstForBookingZone(
                $serviceId,
                $variantKey,
                $zoneId
            );

            if (! $service || ! $variation) {
                throw ValidationException::withMessages([
                    'booking_create_cart_json' => [translate('Invalid service')],
                ]);
            }

            $isSubscribed = SubscribedService::query()
                ->where('provider_id', $providerId)
                ->where('sub_category_id', $service->sub_category_id)
                ->where('is_subscribed', 1)
                ->exists();

            if (! $isSubscribed) {
                throw ValidationException::withMessages([
                    'booking_create_cart_json' => [translate('Provider is not subscribed to this category')],
                ]);
            }

            $catalogUnit = (float) ($variation->price ?? 0);
            $customUnit = isset($line['unit_price']) && is_numeric($line['unit_price']) && (float) $line['unit_price'] > 0
                ? round((float) $line['unit_price'], 4)
                : null;
            $unitPrice = $customUnit ?? $catalogUnit;
            $manualLineDiscount = max(0.0, (float) ($line['line_discount'] ?? 0));
            $useManualPricing = ($manualLineDiscount > 0.0001)
                || ($customUnit !== null && abs($customUnit - $catalogUnit) > 0.0001);

            if ($unitPrice <= 0) {
                throw ValidationException::withMessages([
                    'booking_create_cart_json' => [translate('Invalid service')],
                ]);
            }

            if ($useManualPricing) {
                $subtotal = round($unitPrice * $quantity, 2);
                $manualDiscApplied = min($manualLineDiscount, $subtotal);
                $basisAfterDisc = round($subtotal - $manualDiscApplied, 2);
                $taxPct = effective_service_tax_percentage($service);
                $tax = round(($basisAfterDisc * $taxPct) / 100, 2);
                $basicDiscount = $manualDiscApplied;
                $campaignDiscount = 0.0;
                $lineTotalBeforeAc = round($basisAfterDisc + $tax, 2);
            } else {
                $variationPrice = $catalogUnit;
                $basicDiscount = basic_discount_calculation($service, $variationPrice * $quantity);
                $campaignDiscount = campaign_discount_calculation($service, $variationPrice * $quantity);
                $subtotal = round($variationPrice * $quantity, 2);
                $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
                $tax = round((($variationPrice * $quantity - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);
                $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
                $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;
                $lineTotalBeforeAc = round($subtotal - $basicDiscount - $campaignDiscount + $tax, 2);
            }

            $lineCalcs[] = [
                'service' => $service,
                'variation' => $variation,
                'service_id' => $service->id,
                'variant_key' => $variantKey,
                'quantity' => $quantity,
                'service_name' => $service->name ?? '',
                'variant_label' => $variation->variant ?? $variation->variant_key,
                'service_cost_unit' => round($unitPrice, 4),
                'basic_discount' => $basicDiscount,
                'campaign_discount' => $campaignDiscount,
                'tax_amount' => $tax,
                'line_total_before_ac' => $lineTotalBeforeAc,
                'input_unit_price' => ($customUnit !== null && abs($customUnit - $catalogUnit) > 0.0001) ? $customUnit : null,
                'input_line_discount' => $manualLineDiscount,
                'line_discount_cost_bearer' => DiscountCostBearer::normalize($line['line_discount_cost_bearer'] ?? null),
            ];

            $o = new \stdClass();
            $o->service_id = $service->id;
            $o->total_cost = $lineTotalBeforeAc;
            $o->tax_amount = $tax;
            $o->service = $service;
            $cartObjects[] = $o;
        }

        $acComputed = compute_additional_charges_for_cart_items(collect($cartObjects));
        $mergedLines = merge_additional_charge_line_amount_overrides($acComputed['lines'], $acOverrides);
        $finalAc = finalize_additional_charge_lines($mergedLines);
        $extraFee = $finalAc['total'];

        $sumLineTotals = round(array_sum(array_column($lineCalcs, 'line_total_before_ac')), 2);
        $sumTax = round(array_sum(array_column($lineCalcs, 'tax_amount')), 2);
        $sumBasic = round(array_sum(array_column($lineCalcs, 'basic_discount')), 2);
        $sumCampaign = round(array_sum(array_column($lineCalcs, 'campaign_discount')), 2);

        $extrasNormalized = [];
        $extrasTotal = 0.0;
        foreach ($extrasInput as $ex) {
            $total = max(0, round(($ex['quantity'] * $ex['price']) - $ex['discount'], 2));
            $extrasNormalized[] = array_merge($ex, ['total' => $total]);
            $extrasTotal = round($extrasTotal + $total, 2);
        }

        $grandTotal = round($sumLineTotals + $extraFee + $extrasTotal, 2);

        return [
            'lines' => $lineCalcs,
            'sum_line_totals' => $sumLineTotals,
            'sum_tax' => $sumTax,
            'sum_basic_discount' => $sumBasic,
            'sum_campaign_discount' => $sumCampaign,
            'extra_fee' => $extraFee,
            'additional_charge_lines' => $finalAc['lines'],
            'extras' => $extrasNormalized,
            'extras_total' => $extrasTotal,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * @param  array<string, mixed>  $cartPricing  Output of buildAdminCreateBookingCartPricing()
     * @return array{company_commission: float, provider_commission: float}
     */
    protected function computeAdminCreateBookingCommissionPreview(array $cartPricing, string $providerId, Service $firstLineService): array
    {
        $grandTotal = round((float) ($cartPricing['grand_total'] ?? 0), 2);
        $sparePartsTotal = 0.0;
        foreach ($cartPricing['extras'] ?? [] as $ex) {
            if (($ex['type'] ?? '') === BookingExtraService::TYPE_SPARE_PART) {
                $sparePartsTotal = round($sparePartsTotal + (float) ($ex['total'] ?? 0), 2);
            }
        }
        $nonCommAc = 0.0;
        foreach ($cartPricing['additional_charge_lines'] ?? [] as $row) {
            $commissionable = $row['commissionable'] ?? true;
            if ($commissionable === false || $commissionable === 0 || $commissionable === '0') {
                $nonCommAc = round($nonCommAc + (float) ($row['amount'] ?? 0), 2);
            }
        }
        $tierSetup = resolve_commission_tier_setup_for_create_preview($providerId, $firstLineService);

        return calculate_commission_for_admin_booking_create_preview(
            $grandTotal,
            $sparePartsTotal,
            $nonCommAc,
            $tierSetup
        );
    }

    /**
     * Preview booking before final submission
     *
     * @param Request $request
     * @return Factory|View|Application|RedirectResponse
     * @throws AuthorizationException
     */
    public function preview(Request $request): Factory|View|Application|RedirectResponse
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));
            return redirect()->back()->withInput();
        }

        try {
            $advanceMethodKeys = $this->collectAdvanceMethodKeysFromGroups($this->getAdminAdvancePaymentMethodGroupsForCreate());
            if ((float) ($request->input('advance_paid_amount') ?? 0) > 0 && $advanceMethodKeys === []) {
                throw ValidationException::withMessages([
                    'advance_paid_amount' => [translate('No_active_payment_methods_for_advance')],
                ]);
            }

            $data = $request->validate([
                'customer_id' => ['required', 'exists:users,id'],
                'provider_id' => ['required', 'exists:providers,id'],
                'zone_id' => ['required', 'uuid'],
                'category_id' => ['required', 'uuid'],
                'sub_category_id' => ['required', 'uuid'],
                'service_id' => ['required', 'uuid'],
                'variant_key' => ['required', 'string'],
                'service_schedule' => ['required', 'date'],
                'service_address_id' => ['nullable', 'integer', 'required_if:service_location,customer'],
                'service_location' => ['required', 'in:customer,provider'],
                'booking_source' => ['required', 'string', 'max:255'],
                'advance_paid_amount' => ['nullable', 'numeric', 'min:0'],
                'advance_payment_method' => [
                    Rule::excludeIf(fn () => (float) ($request->input('advance_paid_amount') ?? 0) <= 0),
                    'required',
                    'string',
                    'max:80',
                    Rule::in($advanceMethodKeys),
                ],
                'advance_transaction_id' => ['nullable', 'string', 'max:191'],
                'advance_method_fields' => ['nullable', 'array'],
                'advance_method_fields.*' => ['nullable', 'string', 'max:2000'],
                'assignee_id' => ['nullable', 'exists:users,id'],
                'service_description' => ['nullable', 'string', 'max:2000'],
                'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
                'in_modal' => ['nullable', 'boolean'],
                'reopen_source_booking_id' => ['nullable', 'uuid', 'exists:bookings,id'],
                'ac_line_amount' => ['nullable', 'array'],
                'ac_line_amount.*' => ['nullable', 'numeric', 'min:0'],
                'booking_create_cart_json' => ['nullable', 'string'],
                'booking_create_extra_services_json' => ['nullable', 'string'],
                'service_quantity' => ['nullable', 'integer', 'min:1'],
                'whatsapp_reserved_readable_id' => ['nullable', 'string', 'max:32'],
                'booking_go_back_url' => ['nullable', 'string', 'max:2048'],
            ], [
                'advance_payment_method.required' => translate('Advance_payment_method_is_required_when_advance_amount_is_set'),
            ]);

            $this->assertAdminAdvancePaymentFollowUpValidation($request);

            $this->mergeAdvanceMethodFieldsFromRequestIntoData($request, $data);

            $this->assertValidWhatsappReservedReadableId($data);

            $data['booking_go_back_url'] = $this->sanitizeBookingGoBackUrl($data['booking_go_back_url'] ?? null)
                ?? route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);

            $reopenPreviewId = $data['reopen_source_booking_id'] ?? null;
            if ($reopenPreviewId) {
                $srcPreview = Booking::query()->find($reopenPreviewId);
                if ($srcPreview && $srcPreview->isLossMakingFinancialSettlement()) {
                    throw ValidationException::withMessages([
                        'reopen_source_booking_id' => [translate('Loss_making_completed_booking_cannot_be_reopened')],
                    ]);
                }
                if ($srcPreview && $srcPreview->blocksAdminReopenDueToDecidedChargesSpecialSettlement()) {
                    throw ValidationException::withMessages([
                        'reopen_source_booking_id' => [translate('Bfs_decided_charges_settlement_booking_cannot_be_reopened')],
                    ]);
                }
            }

            // If service location is provider, clear service_address_id
            if ($data['service_location'] === 'provider') {
                $data['service_address_id'] = null;
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $bookingGoBackUrl = $data['booking_go_back_url'];

        $data['ac_line_amount'] = (array) ($data['ac_line_amount'] ?? []);
        $acOverrides = array_map(
            static fn ($v) => is_numeric($v) ? (float) $v : 0.0,
            $data['ac_line_amount']
        );

        // Keep original booking_source casing for round-trip to create (select options match Source names).

        // Load related data for preview
        $customer = User::find($data['customer_id']);
        $provider = $this->provider->with('owner')->find($data['provider_id']);
        $zone = $this->zone->find($data['zone_id']);
        $address = $data['service_address_id'] ? $this->userAddress->find($data['service_address_id']) : null;
        $assignee = $data['assignee_id'] ? User::find($data['assignee_id']) : null;

        $cartLines = $this->parseBookingCreateCartLinesFromRequest($request);
        $extrasParsed = $this->parseBookingCreateExtraServicesFromRequest($request);

        try {
            $cartPricing = $this->buildAdminCreateBookingCartPricing(
                (string) $data['zone_id'],
                (string) $data['provider_id'],
                $cartLines,
                $acOverrides,
                $extrasParsed
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $firstLine = $cartPricing['lines'][0];
        /** @var Service $firstService */
        $firstService = $firstLine['service'];
        $category = $this->category->find($firstService->category_id);
        $subCategory = $this->category->find($firstService->sub_category_id);
        $service = $firstService;
        $variation = $firstLine['variation'];

        $data['category_id'] = (string) $firstService->category_id;
        $data['sub_category_id'] = (string) $firstService->sub_category_id;
        $data['service_id'] = (string) $firstLine['service_id'];
        $data['variant_key'] = (string) $firstLine['variant_key'];

        $data['booking_create_cart_json'] = json_encode(array_map(static function ($l) {
            $var = $l['variation'] ?? null;
            $cat = round((float) ($var->price ?? 0), 4);
            $svc = $l['service'] ?? null;
            $row = [
                'service_id' => $l['service_id'],
                'variant_key' => $l['variant_key'],
                'quantity' => $l['quantity'],
                'service_name' => $l['service_name'] ?? '',
                'variant_label' => $l['variant_label'] ?? '',
            ];
            if ($svc && ! empty($svc->category_id)) {
                $row['category_id'] = (string) $svc->category_id;
            }
            if ($svc && ! empty($svc->sub_category_id)) {
                $row['sub_category_id'] = (string) $svc->sub_category_id;
            }
            if ($cat > 0) {
                $row['catalog_unit_price'] = $cat;
            }
            if (! empty($l['input_unit_price'])) {
                $row['unit_price'] = $l['input_unit_price'];
            }
            if (($l['input_line_discount'] ?? 0) > 0.0001) {
                $row['line_discount'] = $l['input_line_discount'];
            }
            $row['line_discount_cost_bearer'] = DiscountCostBearer::normalize($l['line_discount_cost_bearer'] ?? null);

            return $row;
        }, $cartPricing['lines']));

        $data['booking_create_extra_services_json'] = $cartPricing['extras'] === []
            ? ''
            : json_encode($cartPricing['extras']);

        $data['extra_fee'] = $cartPricing['extra_fee'];
        $totalBilling = $cartPricing['grand_total'];
        $additionalChargeLines = $cartPricing['additional_charge_lines'];
        $advance = (float) ($data['advance_paid_amount'] ?? 0);
        $paidUpfrontPreview = $advance > 0 ? min($advance, $totalBilling) : 0.0;
        $dueBalance = round(max(0.0, $totalBilling - $paidUpfrontPreview), 2);

        $adminPaymentPreview = $this->buildAdminCreatePaymentPreviewCopy($data, $totalBilling, $dueBalance);

        $createCartPreviewLines = $cartPricing['lines'];
        $createCartPreviewExtras = $cartPricing['extras'];
        $createCartHasTax = $cartPricing['sum_tax'] > 0.0001;

        $commissionPreview = $this->computeAdminCreateBookingCommissionPreview(
            $cartPricing,
            (string) $data['provider_id'],
            $firstService
        );

        $view = !empty($data['lead_id'])
            ? 'bookingmodule::admin.booking.preview-from-lead'
            : 'bookingmodule::admin.booking.preview';

        return view($view,
            compact('data', 'customer', 'provider', 'zone', 'category', 'subCategory', 'service', 'address', 'assignee', 'variation', 'totalBilling', 'dueBalance', 'additionalChargeLines', 'createCartPreviewLines', 'createCartPreviewExtras', 'createCartHasTax', 'commissionPreview', 'adminPaymentPreview', 'bookingGoBackUrl'));
    }

    /**
     * Store a newly created booking from admin panel.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $this->authorize('booking_add');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));
            return redirect()->back()->withInput();
        }

        // Debug logging for admin booking store flow
        \Log::info('ADMIN_BOOKING_STORE_REQUEST', [
            'user_id' => auth()->id(),
            'payload' => $request->all(),
            'url' => $request->fullUrl(),
        ]);

        $advanceMethodKeys = $this->collectAdvanceMethodKeysFromGroups($this->getAdminAdvancePaymentMethodGroupsForCreate());
        if ((float) ($request->input('advance_paid_amount') ?? 0) > 0 && $advanceMethodKeys === []) {
            throw ValidationException::withMessages([
                'advance_paid_amount' => [translate('No_active_payment_methods_for_advance')],
            ]);
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:users,id'],
            'provider_id' => ['required', 'exists:providers,id'],
            'zone_id' => ['required', 'uuid'],
            'category_id' => ['required', 'uuid'],
            'sub_category_id' => ['required', 'uuid'],
            'service_id' => ['required', 'uuid'],
            'variant_key' => ['required', 'string'],
            'service_schedule' => ['required', 'date'],
            'service_address_id' => ['nullable', 'integer', 'required_if:service_location,customer'],
            'service_location' => ['required', 'in:customer,provider'],
            'booking_source' => ['required', 'string', 'max:255'],
            'advance_paid_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_payment_method' => [
                Rule::excludeIf(fn () => (float) ($request->input('advance_paid_amount') ?? 0) <= 0),
                'required',
                'string',
                'max:80',
                Rule::in($advanceMethodKeys),
            ],
            'advance_transaction_id' => ['nullable', 'string', 'max:191'],
            'advance_method_fields' => ['nullable', 'array'],
            'advance_method_fields.*' => ['nullable', 'string', 'max:2000'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'service_description' => ['nullable', 'string', 'max:2000'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'in_modal' => ['nullable', 'boolean'],
            'reopen_source_booking_id' => ['nullable', 'uuid', 'exists:bookings,id'],
            'ac_line_amount' => ['nullable', 'array'],
            'ac_line_amount.*' => ['nullable', 'numeric', 'min:0'],
            'booking_create_cart_json' => ['nullable', 'string'],
            'booking_create_extra_services_json' => ['nullable', 'string'],
            'service_quantity' => ['nullable', 'integer', 'min:1'],
            'whatsapp_reserved_readable_id' => ['nullable', 'string', 'max:32'],
            'booking_go_back_url' => ['nullable', 'string', 'max:2048'],
        ], [
            'advance_payment_method.required' => translate('Advance_payment_method_is_required_when_advance_amount_is_set'),
        ]);

        $this->assertAdminAdvancePaymentFollowUpValidation($request);

        $this->mergeAdvanceMethodFieldsFromRequestIntoData($request, $data);

        $this->assertValidWhatsappReservedReadableId($data);

        if (empty($data['lead_id'])) {
            $reserved = trim((string) ($data['whatsapp_reserved_readable_id'] ?? ''));
            if ($reserved !== '' && BookingReadableIdAllocator::isAppReadableIdFormat($reserved)) {
                $waLeadId = WhatsAppBooking::query()->where('booking_id', $reserved)->value('lead_id');
                if ($waLeadId) {
                    $data['lead_id'] = (int) $waLeadId;
                }
            }
        }

        // If service location is provider, clear service_address_id
        if ($data['service_location'] === 'provider') {
            $data['service_address_id'] = null;
        }

        $acOverrides = array_map(
            static fn ($v) => is_numeric($v) ? (float) $v : 0.0,
            (array) ($data['ac_line_amount'] ?? [])
        );

        // Normalize booking source
        $data['booking_source'] = strtolower($data['booking_source']);

        $cartLines = $this->parseBookingCreateCartLinesFromRequest($request);
        $extrasParsed = $this->parseBookingCreateExtraServicesFromRequest($request);
        $cartPricing = $this->buildAdminCreateBookingCartPricing(
            (string) $data['zone_id'],
            (string) $data['provider_id'],
            $cartLines,
            $acOverrides,
            $extrasParsed
        );

        /** @var Service $firstService */
        $firstService = $cartPricing['lines'][0]['service'];
        $data['category_id'] = (string) $firstService->category_id;
        $data['sub_category_id'] = (string) $firstService->sub_category_id;
        $data['service_id'] = (string) $cartPricing['lines'][0]['service_id'];
        $data['variant_key'] = (string) $cartPricing['lines'][0]['variant_key'];

        $totalCost = $cartPricing['grand_total'];

        $reopenSourceId = $data['reopen_source_booking_id'] ?? null;
        $reopenDraft = $reopenSourceId ? session('reopen_new_booking_draft') : null;
        if ($reopenSourceId) {
            if (!$reopenDraft || (string) ($reopenDraft['source_booking_id'] ?? '') !== (string) $reopenSourceId) {
                throw ValidationException::withMessages([
                    'reopen_source_booking_id' => [translate('Invalid_reopen_follow_up_session')],
                ]);
            }
            $draftReopenReasonId = (int) ($reopenDraft['booking_hold_reopen_reason_id'] ?? 0);
            if ($draftReopenReasonId <= 0) {
                throw ValidationException::withMessages([
                    'reopen_source_booking_id' => [translate('Reopen_reason_required')],
                ]);
            }
            if (! BookingHoldReopenReason::query()
                ->whereKey($draftReopenReasonId)
                ->where('is_active', 1)
                ->where('kind', BookingHoldReopenReason::KIND_REOPEN)
                ->exists()) {
                throw ValidationException::withMessages([
                    'reopen_source_booking_id' => [translate('Invalid_reopen_follow_up_session')],
                ]);
            }

            $sourceForFollowUp = Booking::query()->find($reopenSourceId);
            if ($sourceForFollowUp && $sourceForFollowUp->isLossMakingFinancialSettlement()) {
                throw ValidationException::withMessages([
                    'reopen_source_booking_id' => [translate('Loss_making_completed_booking_cannot_be_reopened')],
                ]);
            }
            if ($sourceForFollowUp && $sourceForFollowUp->blocksAdminReopenDueToDecidedChargesSpecialSettlement()) {
                throw ValidationException::withMessages([
                    'reopen_source_booking_id' => [translate('Bfs_decided_charges_settlement_booking_cannot_be_reopened')],
                ]);
            }
        }

        DB::beginTransaction();

        try {
            $advanceAmount = (float) ($data['advance_paid_amount'] ?? 0);
            if ($advanceAmount > $totalCost) {
                throw ValidationException::withMessages([
                    'advance_paid_amount' => [translate('Advance_amount_cannot_exceed_total_billing_amount')],
                ]);
            }

            $extraFee = $cartPricing['extra_fee'];
            $finalAcLines = $cartPricing['additional_charge_lines'];

            $paidUpfront = $advanceAmount > 0 ? min($advanceAmount, $totalCost) : 0.0;
            $dueAfterAdvance = round(max(0.0, $totalCost - $paidUpfront), 2);
            $isFullyPaidUpfront = $paidUpfront > 0 && $dueAfterAdvance <= 0;

            $advanceChoice = $advanceAmount > 0 ? (string) ($data['advance_payment_method'] ?? '') : '';
            $advanceTxnIdOnly = $advanceAmount > 0
                ? $this->extractAdminAdvanceTransactionIdForStorageOnly($advanceChoice, $request)
                : '';
            $advanceLedgerReferenceNote = $advanceAmount > 0 && str_starts_with($advanceChoice, 'offline:')
                ? $this->buildAdminAdvanceOfflineReferenceNoteForLedger($advanceChoice, $request)
                : null;

            // Create booking
            $booking = new Booking();
            $booking->customer_id = $data['customer_id'];
            $booking->provider_id = $data['provider_id'];
            $booking->zone_id = $data['zone_id'];
            $booking->category_id = $data['category_id'];
            $booking->sub_category_id = $data['sub_category_id'];
            $booking->booking_status = 'accepted';
            if ($advanceAmount <= 0) {
                $booking->payment_method = 'cash_after_service';
                $booking->is_paid = 0;
                $partialPaidWithForAdvance = 'offline';
            } else {
                $resolved = $this->resolveAdminCreateBookingPaymentFromAdvanceChoice($advanceChoice, $isFullyPaidUpfront);
                $booking->payment_method = $resolved['payment_method'];
                $booking->is_paid = $resolved['is_paid'];
                $partialPaidWithForAdvance = $resolved['partial_paid_with'];
                if ($isFullyPaidUpfront && $advanceTxnIdOnly !== '') {
                    $booking->transaction_id = $advanceTxnIdOnly;
                }
            }
            $booking->service_schedule = $data['service_schedule'];
            $booking->service_address_id = $data['service_address_id'] ?? null;
            $booking->service_location = $data['service_location']; // 'customer' or 'provider'
            $booking->booking_source = $data['booking_source'];
            $booking->assignee_id = $data['assignee_id'] ?? null;
            $booking->service_description = $data['service_description'] ?? null;
            $booking->booking_otp = rand(100000, 999999);
            $booking->extra_fee = $extraFee;
            $booking->additional_charges_breakdown = count($finalAcLines) ? $finalAcLines : null;
            $booking->lead_id = $data['lead_id'] ?? null;

            // total_booking_amount = service line totals only; extra services persist separately; get_booking_total_amount adds extra_fee + extras
            $booking->total_booking_amount = round($cartPricing['sum_line_totals'], 2);
            $booking->total_tax_amount = $cartPricing['sum_tax'];
            $booking->total_discount_amount = $cartPricing['sum_basic_discount'];
            $booking->total_campaign_discount_amount = $cartPricing['sum_campaign_discount'];
            $booking->total_coupon_discount_amount = 0;

            $reservedRid = trim((string) ($data['whatsapp_reserved_readable_id'] ?? ''));
            if ($reservedRid !== '') {
                $booking->readable_id = $reservedRid;
            }

            $booking->save();

            // Record advance payment as an offline partial payment if provided (always received by company)
            if (!empty($data['advance_paid_amount']) && $data['advance_paid_amount'] > 0) {
                $paidAmount = min($data['advance_paid_amount'], $totalCost);
                $dueAmount = max($totalCost - $paidAmount, 0);

                $advanceTxnFallback = $this->truncateBookingTransactionIdField(trim((string) ($data['advance_transaction_id'] ?? '')));

                $advancePartial = BookingPartialPayment::create([
                    'booking_id' => $booking->id,
                    'paid_with' => $partialPaidWithForAdvance,
                    'transaction_id' => $advanceTxnIdOnly !== '' ? $advanceTxnIdOnly : ($advanceTxnFallback !== '' ? $advanceTxnFallback : null),
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'received_by' => 'company',
                ]);

                ledger_record_in([
                    'amount' => $paidAmount,
                    'transaction_id' => $this->truncateLedgerTransactionIdField($advanceTxnIdOnly !== '' ? $advanceTxnIdOnly : $advanceTxnFallback),
                    'booking_id' => $booking->id,
                    'payment_method' => $this->mapAdvancePartialPaidWithToLedgerPaymentMethod($partialPaidWithForAdvance),
                    'reference_note' => $advanceLedgerReferenceNote,
                    'date' => now()->toDateString(),
                    'received_by' => LedgerTransaction::RECEIVED_BY_COMPANY,
                    'created_by' => auth()->id(),
                    'booking_partial_payment_id' => $advancePartial->id,
                ]);
            }

            foreach ($cartPricing['lines'] as $calc) {
                /** @var Service $svc */
                $svc = $calc['service'];
                $quantity = (int) $calc['quantity'];
                $unitPrice = (float) $calc['service_cost_unit'];
                $basicDiscount = (float) $calc['basic_discount'];
                $campaignDiscount = (float) $calc['campaign_discount'];
                $tax = (float) $calc['tax_amount'];
                $lineTotalBeforeAc = (float) $calc['line_total_before_ac'];

                $detail = new BookingDetail();
                $detail->booking_id = $booking->id;
                $detail->service_id = $svc->id;
                $detail->service_name = $svc->name ?? 'service-not-found';
                $detail->variant_key = (string) $calc['variant_key'];
                $detail->quantity = $quantity;
                $detail->service_cost = $unitPrice;
                $detail->discount_amount = $basicDiscount;
                $detail->campaign_discount_amount = $campaignDiscount;
                $detail->overall_coupon_discount_amount = 0;
                $detail->tax_amount = $tax;
                $detail->total_cost = $lineTotalBeforeAc;
                $detail->save();

                $bookingDetailsAmount = new BookingDetailsAmount();
                $bookingDetailsAmount->booking_details_id = $detail->id;
                $bookingDetailsAmount->booking_id = $booking->id;
                $bookingDetailsAmount->service_unit_cost = $unitPrice;
                $bookingDetailsAmount->service_quantity = $quantity;
                $bookingDetailsAmount->service_tax = $tax;
                $lineBearer = DiscountCostBearer::normalize($calc['line_discount_cost_bearer'] ?? null);
                $lineSplits = DiscountCostBearer::splitBasicAndCampaign($basicDiscount, $campaignDiscount, $lineBearer);
                $bookingDetailsAmount->discount_by_admin = $lineSplits['discount_by_admin'];
                $bookingDetailsAmount->discount_by_provider = $lineSplits['discount_by_provider'];
                $bookingDetailsAmount->campaign_discount_by_admin = $lineSplits['campaign_discount_by_admin'];
                $bookingDetailsAmount->campaign_discount_by_provider = $lineSplits['campaign_discount_by_provider'];
                $bookingDetailsAmount->coupon_discount_by_admin = 0;
                $bookingDetailsAmount->coupon_discount_by_provider = 0;
                $bookingDetailsAmount->discount_cost_bearer = $lineBearer;
                $bookingDetailsAmount->admin_commission = 0;
                $bookingDetailsAmount->save();
            }

            foreach ($cartPricing['extras'] as $ex) {
                $row = new BookingExtraService([
                    'booking_id' => $booking->id,
                    'title' => $ex['title'],
                    'details' => $ex['details'],
                    'type' => $ex['type'],
                    'quantity' => $ex['quantity'],
                    'price' => $ex['price'],
                    'discount' => $ex['discount'],
                    'total' => $ex['total'],
                ]);
                $row->save();
            }

            // Create schedule history
            $schedule = new BookingScheduleHistory();
            $schedule->booking_id = $booking->id;
            $schedule->changed_by = auth()->id();
            $schedule->schedule = date('Y-m-d H:i:s', strtotime($data['service_schedule'])) ?? now()->addHours(5);
            $schedule->save();

            // Create status history
            $statusHistory = new BookingStatusHistory();
            $statusHistory->changed_by = auth()->id();
            $statusHistory->booking_id = $booking->id;
            $statusHistory->booking_status = 'accepted';
            $statusHistory->save();

            if ($reopenSourceId && $reopenDraft) {
                $sourceBooking = Booking::query()->whereKey($reopenSourceId)->lockForUpdate()->first();
                if (!$sourceBooking || ($sourceBooking->booking_status ?? '') !== 'completed') {
                    throw new \RuntimeException(translate('Source_booking_must_remain_completed_for_follow_up'));
                }

                app(BookingReopenService::class)->linkNewBookingFromReopenedCompleted(
                    $sourceBooking,
                    $booking,
                    $request->user(),
                    (string) ($reopenDraft['complaint_notes'] ?? ''),
                    (int) ($reopenDraft['booking_hold_reopen_reason_id'] ?? 0) ?: null
                );
                session()->forget('reopen_new_booking_draft');
            }

            DB::commit();

            \Log::info('ADMIN_BOOKING_STORE_SUCCESS', [
                'booking_id' => $booking->id,
                'readable_id' => $booking->readable_id,
            ]);

            $this->syncWhatsAppBookingRowAfterAdminCreate(
                trim((string) ($data['whatsapp_reserved_readable_id'] ?? '')),
                (string) $booking->id
            );

            try {
                $fresh = Booking::query()
                    ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
                    ->find($booking->id);
                if ($fresh) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)->sendBookingConfirmation($fresh);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp booking confirmation failed', [
                    'booking_id' => $booking->id,
                    'message' => $e->getMessage(),
                ]);
            }

            // If created from a lead, update lead status & history and go back to lead details with booking info
            $leadId = $data['lead_id'] ?? null;
            if ($leadId) {
                try {
                    $this->syncCustomerLeadHistoryWithSystemBooking((int) $leadId, (string) $booking->id);
                } catch (\Throwable $e) {
                    \Log::error('ADMIN_LEAD_UPDATE_AFTER_BOOKING_EXCEPTION', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'lead_id' => $leadId,
                        'booking_id' => $booking->id,
                    ]);
                }

                $redirectParams = ['id' => $leadId];
                if (!empty($data['in_modal'])) {
                    // Lead details page uses in_modal to switch to modal layout
                    $redirectParams['in_modal'] = 1;
                }

                return redirect()
                    ->route('admin.lead.show', $redirectParams)
                    ->with('created_booking', [
                        'id' => $booking->id,
                        'readable_id' => $booking->readable_id,
                    ]);
            }

            // Otherwise go to success screen with options: add new, view details, dashboard
            return redirect()->route('admin.booking.success', ['id' => $booking->id]);
        } catch (\Throwable $exception) {
            DB::rollBack();

            \Log::error('ADMIN_BOOKING_STORE_EXCEPTION', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            Toastr::error(translate('failed_to_create_booking'));

            return redirect()->back()->withInput();
        }
    }

    /**
     * Show booking success page with options.
     *
     * @param string $id
     * @return Factory|View|Application|RedirectResponse
     */
    public function success(string $id): Factory|View|Application|RedirectResponse
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));
            return redirect()->route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);
        }

        $booking = $this->booking->find($id);
        
        if (!$booking) {
            Toastr::error(translate('Booking_not_found'));
            return redirect()->route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']);
        }

        return view('bookingmodule::admin.booking.success', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function checkBooking(): Renderable
    {
        $this->booking->where('is_checked', 0)->update(['is_checked' => 1]);
    }

    /**
     * @param Request $request
     * @return Factory|View|Application
     * @throws AuthorizationException
     */
    public function bookingVerificationList(Request $request): Factory|View|Application
    {
        $this->authorize('booking_view');
        $request->validate([
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
            'type' => 'in:pending,denied'
        ]);
        $request['booking_status'] = $request['booking_status'] ?? 'pending';

        $queryParams = [];
        $filterCounter = 0;
        $type = $request->type ?? 'pending';

        if ($request->has('zone_ids')) {
            $zoneIds = $request['zone_ids'];
            $queryParams['zone_ids'] = $zoneIds;
            $filterCounter += count($zoneIds);
        }

        if ($request->has('category_ids')) {
            $categoryIds = $request['category_ids'];
            $queryParams['category_ids'] = $categoryIds;
            $filterCounter += count($categoryIds);
        }

        if ($request->has('sub_category_ids')) {
            $subCategoryIds = $request['sub_category_ids'];
            $queryParams['sub_category_ids'] = $subCategoryIds;
            $filterCounter += count($subCategoryIds);
        }

        if ($request->has('start_date')) {
            $startDate = $request['start_date'];
            $queryParams['start_date'] = $startDate;
            if (!is_null($request['start_date'])) $filterCounter++;
        } else {
            $queryParams['start_date'] = null;
        }

        if ($request->has('end_date')) {
            $endDate = $request['end_date'];
            $queryParams['end_date'] = $endDate;
            if (!is_null($request['end_date'])) $filterCounter++;
        } else {
            $queryParams['end_date'] = null;
        }

        if ($request->has('search')) {
            $search = $request['search'];
            $queryParams['search'] = $search;
        }

        $queryParams['type'] = $type;

        if ($request->has('booking_status')) {
            $bookingStatus = $request['booking_status'];
            $queryParams['booking_status'] = $bookingStatus;
        } else {
            $queryParams['booking_status'] = 'pending';
        }

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;

        $bookings = $this->booking->with(['customer', 'assignee', 'followups', 'extra_services'])
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($bookingStatus == 'pending', function ($query) use ($maxBookingAmount, $type) {
                $query->when($type == 'pending', function ($query) {
                    $query->where('is_verified', '0');
                })->when($type == 'denied', function ($query) {
                    $query->where('is_verified', '2');
                })
                    ->where('payment_method', 'cash_after_service')
                    ->Where('total_booking_amount', '>', $maxBookingAmount)
                    ->whereIn('booking_status', ['pending', 'accepted']);
            })
            ->when($request->has('zone_ids'), function ($query) use ($request) {
                $query->whereIn('zone_id', $request['zone_ids']);
            })->when($queryParams['start_date'] != null && $queryParams['end_date'] != null, function ($query) use ($request) {
                if ($request['start_date'] == $request['end_date']) {
                    $query->whereDate('created_at', Carbon::parse($request['start_date'])->startOfDay());
                } else {
                    $query->whereBetween('created_at', [Carbon::parse($request['start_date'])->startOfDay(), Carbon::parse($request['end_date'])->endOfDay()]);
                }
            })->when($request->has('sub_category_ids'), function ($query) use ($request) {
                $query->whereIn('sub_category_id', $request['sub_category_ids']);
            })->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })
            ->latest()->paginate(pagination_limit())->appends($queryParams);

        foreach ($bookings as $booking) {
            if ($booking->repeat->isNotEmpty()) {
                $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                    $parts = explode('-', $repeat->readable_id);
                    $suffix = end($parts);
                    return $this->readableIdToNumber($suffix);
                });
                $booking->repeats = $sortedRepeats->values();

                $nextService = $booking->repeats->firstWhere('booking_status', 'ongoing')
                    ?? $booking->repeats->firstWhere('booking_status', 'on_hold')
                    ?? $booking->repeats->firstWhere('booking_status', 'accepted')
                    ?? $booking->repeats->firstWhere('booking_status', 'pending');

                $booking['nextService'] = $nextService;
            }
        }

        $zones = $this->zone->select('id', 'name')->withoutGlobalScope('translate')->get();
        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();
        $subCategories = $this->category->select('id', 'parent_id', 'name')->where('position', 2)->get();

        return view('bookingmodule::admin.booking.verification-list', compact('bookings', 'zones', 'categories', 'subCategories', 'queryParams', 'filterCounter', 'type'));
    }

    /**
     * @param Request $request
     * @return Factory|View|Application
     * @throws AuthorizationException
     */
    public function bookingOfflinePaymentList(Request $request): Factory|View|Application
    {
        $this->authorize('booking_view');
        $request['booking_status'] = $request['booking_status'] ?? 'pending';

        $queryParams = [];
        $filterCounter = 0;

        if ($request->has('zone_ids')) {
            $zoneIds = $request['zone_ids'];
            $queryParams['zone_ids'] = $zoneIds;
            $filterCounter += count($zoneIds);
        }

        if ($request->has('category_ids')) {
            $categoryIds = $request['category_ids'];
            $queryParams['category_ids'] = $categoryIds;
            $filterCounter += count($categoryIds);
        }

        if ($request->has('sub_category_ids')) {
            $subCategoryIds = $request['sub_category_ids'];
            $queryParams['sub_category_ids'] = $subCategoryIds;
            $filterCounter += count($subCategoryIds);
        }

        if ($request->has('start_date')) {
            $startDate = $request['start_date'];
            $queryParams['start_date'] = $startDate;
            if (!is_null($request['start_date'])) $filterCounter++;
        } else {
            $queryParams['start_date'] = null;
        }

        if ($request->has('end_date')) {
            $endDate = $request['end_date'];
            $queryParams['end_date'] = $endDate;
            if (!is_null($request['end_date'])) $filterCounter++;
        } else {
            $queryParams['end_date'] = null;
        }

        if ($request->has('booking_status')) {
            $bookingStatus = $request['booking_status'];
            $queryParams['booking_status'] = $bookingStatus;
        } else {
            $queryParams['booking_status'] = 'pending';
        }

        $hasSearch = $request->has('search') || $request->has('keyword');
        if ($request->has('search')) {
            $queryParams['search'] = $request['search'];
        } elseif ($request->has('keyword')) {
            $queryParams['search'] = $request['keyword'];
        }

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;

        $bookings = $this->booking->with(['customer', 'booking_partial_payments', 'assignee', 'followups', 'extra_services'])
            ->when($hasSearch, function ($query) use ($request) {
                $search = $request->input('search', $request->input('keyword', ''));
                $query->where(function ($query) use ($search) {
                    $keys = explode(' ', $search);
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                    // Also search by advance/offline partial payment transaction_id
                    $query->orWhereHas('booking_partial_payments', function ($q) use ($search) {
                        $q->where('paid_with', 'offline')
                            ->where('transaction_id', 'LIKE', '%' . $search . '%');
                    });
                });
            })
            ->whereIn('booking_status', ['pending', 'accepted'])
            ->where(function ($query) {
                // Include: full offline_payment bookings OR bookings with advance (offline) partial payment
                $query->where(function ($q) {
                    $q->where('payment_method', 'offline_payment')->where('is_paid', 0);
                })->orWhereHas('booking_partial_payments', function ($q) {
                    $q->where('paid_with', 'offline');
                });
            })
            ->where('is_paid', 0)
            ->when($request->has('zone_ids'), function ($query) use ($request) {
                $query->whereIn('zone_id', $request['zone_ids']);
            })->when($queryParams['start_date'] != null && $queryParams['end_date'] != null, function ($query) use ($request) {
                if ($request['start_date'] == $request['end_date']) {
                    $query->whereDate('created_at', Carbon::parse($request['start_date'])->startOfDay());
                } else {
                    $query->whereBetween('created_at', [Carbon::parse($request['start_date'])->startOfDay(), Carbon::parse($request['end_date'])->endOfDay()]);
                }
            })->when($request->has('sub_category_ids'), function ($query) use ($request) {
                $query->whereIn('sub_category_id', $request['sub_category_ids']);
            })->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })
            ->latest()->paginate(pagination_limit())->appends($queryParams);

        $zones = $this->zone->select('id', 'name')->withoutGlobalScope('translate')->get();
        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();
        $subCategories = $this->category->select('id', 'parent_id', 'name')->where('position', 2)->get();

        return view('bookingmodule::admin.booking.offline-payment-list', compact('bookings', 'zones', 'categories', 'subCategories', 'queryParams', 'filterCounter'));
    }

    /**
     * Today's pending (scheduled) follow-ups - full page.
     *
     * @return Renderable
     * @throws AuthorizationException
     */
    public function todaysFollowups(Request $request): Renderable
    {
        $this->authorize('booking_view');

        $selectedAssigneeId = (string) $request->input('assignee_id', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $effectiveTo = Carbon::today()->toDateString();
        if ($dateTo) {
            try {
                $parsedTo = Carbon::parse($dateTo)->toDateString();
                $effectiveTo = $parsedTo > Carbon::today()->toDateString() ? Carbon::today()->toDateString() : $parsedTo;
            } catch (\Throwable $e) {
                $effectiveTo = Carbon::today()->toDateString();
            }
        }

        $baseQuery = \Modules\BookingModule\Entities\BookingFollowup::query()
            ->where('status', 'scheduled')
            // Include missed follow-ups from previous days up to and including today.
            ->whereDate('date', '<=', $effectiveTo)
            ->whereHas('booking', function ($bookingQuery) {
                $bookingQuery->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('date', '>=', $dateFrom);
            })
            ->when($selectedAssigneeId !== '', function ($q) use ($selectedAssigneeId) {
                $q->whereHas('booking', function ($bookingQuery) use ($selectedAssigneeId) {
                    $bookingQuery->where('assignee_id', $selectedAssigneeId);
                });
            });

        $totalFollowups = (clone $baseQuery)->count();

        $followups = (clone $baseQuery)
            ->with(['booking.assignee', 'booking.customer', 'booking.provider'])
            // Sort from previous to current.
            ->orderBy('date')
            ->paginate(pagination_limit())
            ->appends($request->query());

        $assignees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('bookingmodule::admin.booking.todays-followups', compact(
            'followups',
            'assignees',
            'selectedAssigneeId',
            'dateFrom',
            'dateTo',
            'totalFollowups'
        ));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable|RedirectResponse
     * @throws AuthorizationException
     */
    public function details($id, Request $request): Renderable|RedirectResponse
    {
        $this->authorize('booking_view');
        if ($request->input('web_page') === 'status') {
            return redirect()->route('admin.booking.details', [$id, 'web_page' => 'history']);
        }
        $webPage = $request->input('web_page', 'details');
        if (!in_array($webPage, ['details', 'history', 'followups'], true)) {
            $webPage = 'details';
        }
        $request->merge(['web_page' => $webPage]);

        if ($webPage === 'details') {

            $booking = $this->booking->with([
                'detail.service' => function ($query) {
                    $query->withTrashed();
                },
                'detail.service.variations',
                'detail.service.category',
                'detail.service.subCategory',
                'customer',
                'provider',
                'serviceman',
                'assignee',
                'status_histories' => fn ($q) => $q->with(['user', 'cancellationReason', 'holdReopenReason']),
                'latestParentCancellationStatusHistory.cancellationReason',
                'latestParentHoldStatusHistory.holdReopenReason',
                'latestParentHoldStatusHistory.user',
                'booking_partial_payments.ledgerTransactions',
                'booking_offline_payments',
                'followups',
                'extra_services',
                'reopenEvents.actor',
                'reopenEvents.holdReopenReason',
                'originatedFromBooking',
                'originatedFromBooking.reopenEvents.holdReopenReason',
                'spawnedFollowupBookings',
                'reopenedByUser',
                'reopenCaseResolvedByUser',
                'compensations.creator',
            ])
                ->find($id);

            if (!$booking) {
                return redirect()->route('admin.booking.list')->withErrors(['message' => translate('Booking not found')]);
            }

            // Load variations for each detail with proper constraints (service_id and zone_id)
            if ($booking->detail) {
                foreach ($booking->detail as $detail) {
                    if ($detail->variant_key && $detail->service_id && $booking->zone_id) {
                        $detail->variation = Variation::firstForBookingZone(
                            (string) $detail->service_id,
                            (string) $detail->variant_key,
                            (string) $booking->zone_id,
                            false
                        );
                    }
                }
            }

            $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

            $servicemen = $this->serviceman->with(['user'])
                ->where('provider_id', $booking?->provider_id)
                ->whereHas('user', function ($query) {
                    $query->ofStatus(1);
                })
                ->latest()
                ->get();

            $category = $booking?->detail?->first()?->service?->category;
            $subCategory = $booking?->detail?->first()?->service?->subCategory;

            $subscribedSubCategoryIds = SubscribedService::query()
                ->where('provider_id', $booking->provider_id)
                ->where('is_subscribed', 1)
                ->pluck('sub_category_id')
                ->filter()
                ->unique()
                ->values();

            $leafZoneIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds([(string) $booking->zone_id]);
            $bookingEditCategories = $this->category
                ->withoutGlobalScope('translate')
                ->where('position', 1)
                ->where('is_active', 1)
                ->whereIn('id', SubscribedService::query()
                    ->where('provider_id', $booking->provider_id)
                    ->where('is_subscribed', 1)
                    ->distinct()
                    ->pluck('category_id'))
                ->when($leafZoneIds !== [], function ($q) use ($leafZoneIds) {
                    $q->whereHas('zones', function ($query) use ($leafZoneIds) {
                        $query->whereIn('zones.id', $leafZoneIds);
                    });
                })
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            $services = $subscribedSubCategoryIds->isEmpty()
                ? collect()
                : Service::query()
                    ->select('id', 'name', 'category_id', 'sub_category_id')
                    ->whereIn('sub_category_id', $subscribedSubCategoryIds)
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->get();

            $customerAddress = $this->userAddress->find($booking['service_address_id']);
            $zones = Zone::ofStatus(1)->withoutGlobalScope('translate')->get();

            // Important: the booking details page itself may contain `?search=...` from the booking list filters.
            // That query param is unrelated to provider search, and it caused the reassign-provider modal to
            // sometimes open with an empty provider list. Provider searching is handled via the modal AJAX.
            $allProviders = $this->provider
                ->when($request->filled('provider_search'), function ($query) use ($request) {
                    $keys = explode(' ', (string) $request->input('provider_search'));
                    return $query->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                                ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                                ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                        }
                    });
                })
                ->when(isset($booking->sub_category_id), function ($query) use ($request, $booking) {
                    $query->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                        $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
                    });
                })
                ->coveringLeafZone($booking->zone_id)
                ->withCount('bookings', 'reviews')
                ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                    $query->where('is_suspended', 0);
                })
                ->where('service_availability', 1)
                ->where('is_active_for_jobs', 1)
                ->withCount('reviews')
                ->ofApproval(1)->ofStatus(1)
                ->get();

            $providers = [];

            foreach ($allProviders as $provider) {
                if (provider_accepts_booking_service_location($provider->id, $booking->service_location)) {
                    $providers[] = $provider;
                }
            }

            $sort_by = 'default';
            $zoneCenter = Zone::selectRaw("*,ST_AsText(ST_Centroid(`coordinates`)) as center")->withoutGlobalScope('translate')->find($booking->zone_id);

            $currentZone = [];
            $centerLat = [];
            $centerLng = [];
            $area = [];

            if (isset($zoneCenter)) {
                $currentZone = format_coordinates(json_decode($zoneCenter->coordinates[0]->toJson(), true));
                $centerLat = trim(explode(' ', $zoneCenter->center)[1], 'POINT()');
                $centerLng = trim(explode(' ', $zoneCenter->center)[0], 'POINT()');

                $area = json_decode($zoneCenter->coordinates[0]->toJson(), true);
            }

            $assignees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
                ->orderBy('first_name')->orderBy('last_name')
                ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
                ->get();

            $scheduledNext = ($booking->followups ?? collect())->where('status', 'scheduled')->sortBy('date');
            $nextFollowupCustomer = $scheduledNext->where('for', 'customer')->first();
            $nextFollowupProvider = $scheduledNext->where('for', 'provider')->first();
            $customerName = booking_display_customer_name($booking, $customerAddress);
            $customerPhone = booking_display_customer_phone($booking, $customerAddress);

            $remainingDueForAddPayment = get_booking_admin_add_payment_remaining_amount($booking);
            $maxRefundAmount = (float) (get_booking_refund_display_totals($booking)['refundable_remaining'] ?? 0);
            if ($this->bookingSuppressesAdminCustomerRefundCard($booking)) {
                $maxRefundAmount = 0;
            }

            $additionalChargesDisplayRows = enrich_booking_additional_charges_breakdown_for_display($booking);

            $financialSettlementService = app(BookingFinancialSettlementService::class);
            $financialSettlementOutcomes = BookingFinancialSettlementService::outcomeOptionsForSpecialScenariosModal();
            $defaultVisitFeeCompanyPercent = $financialSettlementService->defaultVisitCompanyPercent();
            $bfsDefaultCustomAdminCommission = $financialSettlementService->defaultTierAdminCommissionForBooking($booking);
            $allowDeleteAdminBookingPartialPayments = $this->bookingAllowsAdminPartialPaymentDeletion($booking);

            try {
                $advancePaymentMethodGroups = $this->getAdminAdvancePaymentMethodGroupsForCreate();

                return view('bookingmodule::admin.booking.details', array_merge(
                    compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'bookingEditCategories', 'providers', 'sort_by', 'assignees', 'nextFollowupCustomer', 'nextFollowupProvider', 'customerName', 'customerPhone', 'remainingDueForAddPayment', 'maxRefundAmount', 'additionalChargesDisplayRows', 'financialSettlementOutcomes', 'defaultVisitFeeCompanyPercent', 'bfsDefaultCustomAdminCommission', 'advancePaymentMethodGroups', 'allowDeleteAdminBookingPartialPayments'),
                    $this->bookingConfigurationReasonVariables()
                ));
            } catch (Throwable $e) {
                Log::error('Booking details view failed: ' . $e->getMessage(), ['exception' => $e, 'booking_id' => $id]);
                Toastr::error(translate('Unable to load booking details. Please try again.'));
                return redirect()->route('admin.booking.list');
            }
        } elseif ($webPage === 'history') {
            $booking = $this->booking->with([
                'change_logs.changedBy',
                'customer',
                'provider',
                'service_address',
                'booking_partial_payments',
                'reopenEvents.actor',
                'originatedFromBooking',
                'spawnedFollowupBookings',
                'reopenedByUser',
                'reopenCaseResolvedByUser',
            ])->find($id);

            if (!$booking) {
                return redirect()->route('admin.booking.list')->withErrors(['message' => translate('Booking not found')]);
            }

            return view('bookingmodule::admin.booking.history', compact('booking', 'webPage'));
        } elseif ($webPage === 'followups') {
            $booking = $this->booking->with(['followups.createdBy', 'customer', 'provider', 'service_address', 'booking_partial_payments', 'reopenEvents.actor', 'originatedFromBooking', 'spawnedFollowupBookings', 'reopenedByUser', 'reopenCaseResolvedByUser'])->find($id);
            $webPage = 'followups';
            $scheduledNext = ($booking->followups ?? collect())->where('status', 'scheduled')->sortBy('date');
            $nextFollowupCustomer = $scheduledNext->where('for', 'customer')->first();
            $nextFollowupProvider = $scheduledNext->where('for', 'provider')->first();
            $customerName = $booking->customer ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? '')) : ($booking->service_address->contact_person_name ?? '');
            $customerPhone = $booking->customer ? ($booking->customer->phone ?? '') : ($booking->service_address->contact_person_number ?? '');
            return view('bookingmodule::admin.booking.followups', compact('booking', 'webPage', 'nextFollowupCustomer', 'nextFollowupProvider', 'customerName', 'customerPhone'));
        }

        Toastr::success(translate(ACCESS_DENIED['message']));
        return back();
    }

    public function storeFollowup(Request $request, $id): RedirectResponse
    {
        $this->authorize('booking_view');
        $booking = $this->booking->findOrFail($id);
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'for' => ['required', 'in:customer,provider'],
        ]);
        $validated['booking_id'] = $booking->id;
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'scheduled';
        $validated['date'] = Carbon::parse($validated['date'])->format('Y-m-d H:i:s');
        \Modules\BookingModule\Entities\BookingFollowup::create($validated);
        Toastr::success(translate('Follow_up_added_successfully'));
        return redirect()->route('admin.booking.details', [$id, 'web_page' => 'followups']);
    }

    public function reopenFromCompleted(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');
        $validated = $request->validate([
            'booking_hold_reopen_reason_id' => [
                'required',
                'integer',
                Rule::exists('booking_hold_reopen_reasons', 'id')->where(fn ($q) => $q->where('is_active', 1)->where('kind', BookingHoldReopenReason::KIND_REOPEN)),
            ],
            'complaint_notes' => ['nullable', 'string', 'max:5000'],
            'target_status' => ['required', Rule::in(['pending', 'accepted'])],
            'service_schedule' => ['required', 'date'],
        ]);
        $reopenReasonId = (int) $validated['booking_hold_reopen_reason_id'];

        $booking = $this->booking->findOrFail($id);
        $service = app(BookingReopenService::class);

        try {
            $targetStatus = (string) $validated['target_status'];
            $result = $service->reopenInPlace(
                $booking,
                $request->user(),
                (string) ($validated['complaint_notes'] ?? ''),
                $targetStatus,
                $reopenReasonId,
                (string) $validated['service_schedule']
            );
            Toastr::success(translate('Booking_reopened_in_place'));

            return redirect()->route('admin.booking.details', [$result['booking']->id, 'web_page' => 'details']);
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());

            return back()->withInput();
        }
    }

    public function resolveReopenTicket(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->with(['reopenEvents', 'booking_partial_payments'])->findOrFail($id);

        if (!$booking->isOpenReopenTicket()) {
            Toastr::error(translate('This_booking_is_not_an_open_reopen_ticket'));

            return back();
        }

        if (($booking->booking_status ?? '') !== 'completed') {
            Toastr::error(translate('Complete_booking_before_mark_reopen_resolved'));

            return back();
        }

        $validated = $request->validate([
            'reopen_resolve_remarks' => ['required', 'string', 'max:5000'],
        ], [
            'reopen_resolve_remarks.required' => translate('Reopen_resolve_remarks_required'),
        ]);

        $remarks = trim((string) $validated['reopen_resolve_remarks']);
        if ($remarks === '') {
            throw ValidationException::withMessages([
                'reopen_resolve_remarks' => [translate('Reopen_resolve_remarks_required')],
            ]);
        }

        try {
            DB::transaction(function () use ($booking, $request, $remarks) {
                $userId = $request->user()->id;
                $booking->reopen_resolved_at = now();
                $booking->reopen_resolved_by = $userId;
                $booking->reopen_resolve_remarks = $remarks;
                $booking->save();
            });

            $booking->refresh();
            try {
                app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)->sendReopenCaseResolved($booking);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WhatsApp reopen resolved notification failed', [
                    'booking_id' => $booking->id,
                    'message' => $e->getMessage(),
                ]);
            }

            Toastr::success(translate('Reopen_case_marked_resolved'));
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());

            return back();
        }

        return back();
    }

    /**
     * Open reopen ticket: mark booking completed and close the reopen case in one step (notes required).
     * Allowed only from statuses that may transition to completed (e.g. ongoing) with payment rules satisfied.
     */
    public function resolveReopenAndComplete(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->with(['booking_partial_payments', 'reopenEvents'])->findOrFail($id);

        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            Toastr::error(translate('Reopen_scenarios_single_booking_only'));

            return back();
        }

        $canDisputeClose = $booking->isOpenReopenTicket()
            || in_array((string) ($booking->booking_status ?? ''), ['ongoing', 'on_hold'], true);
        if (! $canDisputeClose) {
            Toastr::error(translate('This_booking_is_not_an_open_reopen_ticket'));

            return back();
        }

        $current = strtolower(trim((string) ($booking->booking_status ?? '')));
        if (! booking_admin_status_transition_allowed_for_booking($booking, $current, 'completed')) {
            Toastr::error(translate('Resolve_reopen_invalid_status_for_complete'));

            return back();
        }

        $booking->loadMissing('booking_partial_payments');
        if (! booking_can_be_completed($booking)) {
            Toastr::error(translate('Booking cannot be completed until full payment is received.'));

            return back();
        }

        if ((string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL) {
            Toastr::error(translate('Change_financial_settlement_before_completing_visit_retained_is_cancel_only'));

            return back();
        }

        $validated = $request->validate([
            'reopen_resolve_complete_remarks' => ['required', 'string', 'max:5000'],
        ], [
            'reopen_resolve_complete_remarks.required' => translate('Reopen_resolve_remarks_required'),
        ]);

        $remarks = trim((string) $validated['reopen_resolve_complete_remarks']);
        if ($remarks === '') {
            throw ValidationException::withMessages([
                'reopen_resolve_complete_remarks' => [translate('Reopen_resolve_remarks_required')],
            ]);
        }

        $resolverUserId = $request->user()->id;

        try {
            DB::transaction(function () use ($booking, $remarks, $resolverUserId) {
                $booking->booking_status = 'completed';
                $booking->reopen_completion_allowed = false;
                $booking->reopen_resolved_at = now();
                $booking->reopen_resolved_by = $resolverUserId;
                $booking->reopen_resolve_remarks = $remarks;
                $booking->save();

                $this->logBookingStatusHistory(
                    null,
                    'completed',
                    (string) $resolverUserId,
                    $booking->id,
                    null,
                    null,
                    'reopen_resolved_complete: '.$remarks
                );
            });
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());

            return back()->withInput();
        }

        $booking->refresh();
        try {
            app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)->sendReopenCaseResolved($booking);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp reopen resolved notification failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
        }

        Toastr::success(translate('Reopen_resolve_complete_success'));

        return back();
    }

    public function reopenScenarioAllowCompletion(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->with(['reopenEvents'])->findOrFail($id);
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            Toastr::error(translate('Reopen_scenarios_single_booking_only'));

            return back();
        }
        $canDisputeClose = booking_admin_can_dispute_and_close($booking);
        if (! $canDisputeClose) {
            Toastr::error(translate('Dispute_and_close_only_for_ongoing_hold_or_reopen'));

            return back();
        }

        $booking->reopen_completion_allowed = true;
        $booking->save();

        Toastr::success(translate('Reopen_resolved_path_unlocked'));

        return back();
    }

    /**
     * Disputed reopen: cancel booking and record refund legs on the ledger (company vs provider pools),
     * plus a snapshot for reconciliation (who owes whom when refunds exceed each side's collected pool).
     */
    public function reopenScenarioDisputedRefund(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->with(['booking_partial_payments', 'reopenEvents', 'details_amounts'])->findOrFail($id);

        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            Toastr::error(translate('Reopen_scenarios_single_booking_only'));

            return back();
        }
        $canDisputeClose = booking_admin_can_dispute_and_close($booking);
        if (! $canDisputeClose) {
            Toastr::error(translate('Dispute_and_close_only_for_ongoing_hold_or_reopen'));

            return back();
        }
        if (in_array((string) $booking->booking_status, ['canceled', 'cancelled', 'refunded'], true)) {
            Toastr::error(translate('Booking_already_closed'));

            return back();
        }
        if ($this->bookingSuppressesAdminCustomerRefundCard($booking)) {
            Toastr::error(translate('Bfs_refund_not_available_after_visit_cancel'));

            return back();
        }

        $totalPaid = round((float) get_booking_total_paid($booking), 2);

        $validated = $request->validate([
            'refund_company_amount' => ['required', 'numeric', 'min:0'],
            'refund_provider_amount' => ['required', 'numeric', 'min:0'],
            'refund_company_transaction_id' => ['nullable', 'string', 'max:100'],
            'refund_provider_transaction_id' => ['nullable', 'string', 'max:100'],
            'reopen_dispute_remarks' => ['required', 'string', 'max:5000'],
        ], [
            'reopen_dispute_remarks.required' => translate('Reopen_resolve_remarks_required'),
        ]);

        $refundCompany = round((float) $validated['refund_company_amount'], 2);
        $refundProvider = round((float) $validated['refund_provider_amount'], 2);

        if ($refundCompany >= 0.01 && trim((string) ($validated['refund_company_transaction_id'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'refund_company_transaction_id' => [translate('Transaction_ID') . ' ' . translate('is_required')],
            ]);
        }
        if ($refundProvider >= 0.01 && trim((string) ($validated['refund_provider_transaction_id'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'refund_provider_transaction_id' => [translate('Transaction_ID') . ' ' . translate('is_required')],
            ]);
        }

        $split = booking_customer_paid_split_by_receiver($booking);
        $companyEligible = round((float) $split['company'] + (float) $split['unassigned'], 2);
        $providerEligible = round((float) $split['provider'], 2);
        $totalRefund = round($refundCompany + $refundProvider, 2);

        if ($refundCompany > $totalPaid + 0.02) {
            throw ValidationException::withMessages([
                'refund_company_amount' => [translate('Disputed_refund_amount_cannot_exceed_customer_paid')],
            ]);
        }
        if ($refundProvider > $totalPaid + 0.02) {
            throw ValidationException::withMessages([
                'refund_provider_amount' => [translate('Disputed_refund_amount_cannot_exceed_customer_paid')],
            ]);
        }

        if ($totalRefund < 0.01) {
            throw ValidationException::withMessages([
                'refund_company_amount' => [translate('Disputed_refund_total_must_be_positive')],
            ]);
        }
        if ($totalRefund > $totalPaid + 0.02) {
            throw ValidationException::withMessages([
                'refund_company_amount' => [translate('Disputed_refund_cannot_exceed_customer_paid')],
            ]);
        }

        $providerOwesCompanyRefundPool = max(0.0, round($refundCompany - $companyEligible, 2));
        $companyOwesProviderRefundPool = max(0.0, round($refundProvider - $providerEligible, 2));

        $detailRows = $booking->details_amounts->filter(fn ($r) => $r->booking_repeat_id === null);
        $baseAdminCommission = round((float) $detailRows->sum('admin_commission'), 2);
        $baseProviderEarning = round((float) $detailRows->sum('provider_earning'), 2);
        $retainedFromCustomer = max(0.0, round($totalPaid - $totalRefund, 2));
        $netRatio = $totalPaid > 0.0001 ? min(1.0, $retainedFromCustomer / $totalPaid) : 0.0;
        $finalAdminCommission = round($baseAdminCommission * $netRatio, 2);
        $finalProviderEarning = round($baseProviderEarning * $netRatio, 2);
        $providerTotalRemittanceToCompany = round($providerOwesCompanyRefundPool + $finalAdminCommission, 2);

        $remarks = trim((string) $validated['reopen_dispute_remarks']);
        if ($remarks === '') {
            throw ValidationException::withMessages([
                'reopen_dispute_remarks' => [translate('Reopen_resolve_remarks_required')],
            ]);
        }

        $tidCompany = AdminCompanyInflowPaymentService::truncateLedgerTransactionIdField(trim((string) ($validated['refund_company_transaction_id'] ?? '')));
        $tidProvider = AdminCompanyInflowPaymentService::truncateLedgerTransactionIdField(trim((string) ($validated['refund_provider_transaction_id'] ?? '')));

        $snapshot = [
            'type' => 'reopen_disputed_refund',
            'submitted_at' => now()->toIso8601String(),
            'customer_paid_split' => $split,
            'company_refund_pool_eligible' => $companyEligible,
            'provider_refund_pool_eligible' => $providerEligible,
            'customer_paid_total' => $totalPaid,
            'refund_company_amount' => $refundCompany,
            'refund_provider_amount' => $refundProvider,
            'refund_total' => $totalRefund,
            'provider_owes_company' => $providerOwesCompanyRefundPool,
            'provider_total_remittance_to_company' => $providerTotalRemittanceToCompany,
            'company_owes_provider' => $companyOwesProviderRefundPool,
            'retained_from_customer' => $retainedFromCustomer,
            'net_commission_ratio' => $netRatio,
            'base_admin_commission_before_dispute' => $baseAdminCommission,
            'base_provider_earning_before_dispute' => $baseProviderEarning,
            'final_net_to_customer' => $retainedFromCustomer,
            'final_admin_commission' => $finalAdminCommission,
            'final_provider_earning' => $finalProviderEarning,
        ];

        $date = Carbon::now()->toDateString();
        $resolverUserId = $request->user()->id;
        $userIdForHistory = (string) $resolverUserId;

        try {
            DB::transaction(function () use (
                $booking,
                $refundCompany,
                $refundProvider,
                $tidCompany,
                $tidProvider,
                $date,
                $userIdForHistory,
                $resolverUserId,
                $remarks,
                $snapshot,
                $totalPaid,
                $totalRefund,
                $finalAdminCommission,
                $finalProviderEarning,
                $providerTotalRemittanceToCompany,
                $companyOwesProviderRefundPool
            ) {
                if ($refundCompany >= 0.01) {
                    ledger_record_out([
                        'amount' => $refundCompany,
                        'transaction_id' => $tidCompany !== '' ? $tidCompany : null,
                        'booking_id' => $booking->id,
                        'reason' => LedgerTransaction::REASON_REFUND,
                        'date' => $date,
                        'received_by' => LedgerTransaction::RECEIVED_BY_COMPANY,
                        'reference_note' => 'reopen_disputed_refund:company_portion',
                        'created_by' => $resolverUserId,
                    ]);
                }
                if ($refundProvider >= 0.01) {
                    ledger_record_out([
                        'amount' => $refundProvider,
                        'transaction_id' => $tidProvider !== '' ? $tidProvider : null,
                        'booking_id' => $booking->id,
                        'reason' => LedgerTransaction::REASON_REFUND,
                        'date' => $date,
                        'received_by' => LedgerTransaction::RECEIVED_BY_PROVIDER,
                        'reference_note' => 'reopen_disputed_refund:provider_portion',
                        'created_by' => $resolverUserId,
                    ]);
                }

                record_reopen_disputed_refund_reconciliation(
                    (string) $booking->id,
                    $booking->provider_id ? (string) $booking->provider_id : null,
                    $providerTotalRemittanceToCompany,
                    $companyOwesProviderRefundPool
                );

                $this->distributeDisputedRefundNetAcrossDetailsAmounts($booking, $finalAdminCommission, $finalProviderEarning);

                $booking->refresh();
                $booking->load('details_amounts');
                if ($booking->provider_id) {
                    try {
                        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
                        if ($outcome !== '' && $outcome !== BookingFinancialSettlementService::OUTCOME_STANDARD) {
                            $booking->settlement_snapshot = app(BookingFinancialSettlementService::class)->buildPreview($booking);
                        }
                    } catch (\Throwable) {
                        // keep prior snapshot
                    }
                }

                $booking->reopen_disputed_snapshot = $snapshot;
                $booking->reopen_completion_allowed = false;
                $booking->reopen_resolved_at = now();
                $booking->reopen_resolved_by = $resolverUserId;
                $booking->reopen_resolve_remarks = $remarks;

                if ($totalRefund + 0.005 >= $totalPaid) {
                    $booking->booking_status = 'refunded';
                } else {
                    $booking->booking_status = 'canceled';
                }

                $booking->save();

                $this->logBookingStatusHistory(
                    null,
                    (string) $booking->booking_status,
                    $userIdForHistory,
                    $booking->id,
                    null,
                    null,
                    'reopen_disputed: '.$remarks
                );
            });
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());

            return back()->withInput();
        }

        $booking->refresh();
        try {
            app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)->sendReopenCaseResolved($booking);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp reopen disputed resolved notification failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
        }

        Toastr::success(translate('Disputed_reopen_recorded'));

        return back();
    }

    public function updateFollowup(Request $request, $id, $followupId): RedirectResponse
    {
        $this->authorize('booking_view');
        $booking = $this->booking->findOrFail($id);
        $followup = $booking->followups()->findOrFail($followupId);
        $validated = $request->validate([
            'status' => ['required', 'in:completed,rescheduled'],
            'remarks' => ['required_if:status,completed', 'nullable', 'string', 'max:2000'],
            'reschedule_reason' => ['required_if:status,rescheduled', 'nullable', 'string', 'max:500'],
            'reschedule_date' => ['required_if:status,rescheduled', 'nullable', 'date'],
            'add_another_followup' => ['nullable', 'in:1'],
            'add_another_date' => ['required_if:add_another_followup,1', 'nullable', 'date'],
            'add_another_for' => ['required_if:add_another_followup,1', 'nullable', 'in:customer,provider'],
            'add_another_reason' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validated['status'] === 'completed') {
            $followup->update([
                'status' => 'completed',
                'remarks' => $validated['remarks'] ?? '',
            ]);
            if (!empty($validated['add_another_followup']) && !empty($validated['add_another_date']) && !empty($validated['add_another_for'])) {
                \Modules\BookingModule\Entities\BookingFollowup::create([
                    'booking_id' => $booking->id,
                    'date' => Carbon::parse($validated['add_another_date'])->format('Y-m-d H:i:s'),
                    'reason' => $validated['add_another_reason'] ?? null,
                    'for' => $validated['add_another_for'],
                    'status' => 'scheduled',
                    'created_by' => auth()->id(),
                ]);
            }
        } else {
            $followup->update([
                'status' => 'rescheduled',
                'reschedule_reason' => $validated['reschedule_reason'] ?? '',
            ]);
            if (!empty($validated['reschedule_date'])) {
                \Modules\BookingModule\Entities\BookingFollowup::create([
                    'booking_id' => $booking->id,
                    'date' => Carbon::parse($validated['reschedule_date'])->format('Y-m-d H:i:s'),
                    'reason' => null,
                    'for' => $followup->for,
                    'status' => 'scheduled',
                    'created_by' => auth()->id(),
                ]);
            }
        }
        Toastr::success(translate('Follow_up_updated_successfully'));
        return redirect()->route('admin.booking.details', [$id, 'web_page' => 'followups']);
    }

    /**
     * Remove the specified booking from storage.
     *
     * @param int $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy($id): RedirectResponse
    {
        $this->authorize('booking_delete');

        $booking = $this->booking
            ->with([
                'detail',
                'details_amounts',
                'schedule_histories',
                'status_histories',
                'booking_offline_payments',
                'ignores',
                'reviews',
                'booking_partial_payments',
                'extra_services',
                'repeat.detail',
                'repeat.details_amounts',
                'repeat.statusHistories',
                'repeat.scheduleHistories',
                'repeat.repeatHistories',
            ])
            ->findOrFail($id);

        DB::transaction(function () use ($booking) {
            app(AdminBookingDeletionService::class)->deleteBookingAndRelations($booking);
        });

        Toastr::success(translate('Booking_deleted_successfully'));

        return redirect()->route('admin.booking.list', [
            'booking_status' => 'pending',
            'service_type' => 'all',
        ]);
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable|RedirectResponse
     * @throws AuthorizationException
     */
    public function repeatDetails($id, Request $request): Renderable|RedirectResponse
    {
        $this->authorize('booking_view');
        Validator::make($request->all(), [
            'web_page' => 'required|in:details,service_log',
        ]);
        $webPage = $request->has('web_page') ? $request['web_page'] : 'business_setup';

        $booking = $this->booking->with(['repeat.detail.service','repeat.scheduleHistories','repeat.repeatHistories', 'detail.service' => function ($query) {
            $query->withTrashed();
        }, 'detail.service.category', 'detail.service.subCategory', 'customer', 'provider',
            'serviceman', 'status_histories.user'])
            ->find($id);
        
        // Load variations for each detail with proper constraints (service_id and zone_id)
        if ($booking && $booking->detail) {
            foreach ($booking->detail as $detail) {
                if ($detail->variant_key && $detail->service_id && $booking->zone_id) {
                    $detail->variation = Variation::firstForBookingZone(
                        (string) $detail->service_id,
                        (string) $detail->variant_key,
                        (string) $booking->zone_id,
                        false
                    );
                }
            }
        }

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        $servicemen = $this->serviceman->with(['user'])
            ->where('provider_id', $booking?->provider_id)
            ->whereHas('user', function ($query) {
                $query->ofStatus(1);
            })
            ->latest()
            ->get();

        $category = $booking?->detail?->first()?->service?->category;
        $subCategory = $booking?->detail?->first()?->service?->subCategory;
        $services = Service::select('id', 'name')->where('category_id', $category->id)->where('sub_category_id', $subCategory->id)->get();

        $customerAddress = $this->userAddress->find($booking['service_address_id']);
        $zones = Zone::ofStatus(1)->withoutGlobalScope('translate')->get();

        $providers = $this->provider
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when(isset($booking->sub_category_id), function ($query) use ($request, $booking) {
                $query->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                    $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
                });
            })
            ->coveringLeafZone($booking->zone_id)
            ->withCount('bookings', 'reviews')
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->where('service_availability', 1)
            ->where('is_active_for_jobs', 1)
            ->withCount('reviews')
            ->ofApproval(1)->ofStatus(1)->get();

        $sort_by = 'default';
        $id = "325778a8-53bd-4de5-a6bb-826f62edf603";
        $zoneCenter = Zone::selectRaw("*,ST_AsText(ST_Centroid(`coordinates`)) as center")->withoutGlobalScope('translate')->find($id);

        $currentZone = [];
        $centerLat = [];
        $centerLng = [];
        $area = [];

        if (isset($zoneCenter)) {
            $currentZone = format_coordinates(json_decode($zoneCenter->coordinates[0]->toJson(), true));
            $centerLat = trim(explode(' ', $zoneCenter->center)[1], 'POINT()');
            $centerLng = trim(explode(' ', $zoneCenter->center)[0], 'POINT()');

            $area = json_decode($zoneCenter->coordinates[0]->toJson(), true);
        }

        if ($booking->repeat->isNotEmpty()) {
            $repeatHistoryCollection = $booking->repeat->flatMap(function ($repeat) {
                return $repeat->repeatHistories->map(function ($history) {
                    $history->log_details = json_decode($history->log_details);
                    return $history;
                });
            });

            $booking['repeatHistory'] = $repeatHistoryCollection->toArray();
            $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                $parts = explode('-', $repeat->readable_id);
                $suffix = end($parts);
                return $this->readableIdToNumber($suffix);
            });
            $booking['repeats'] = $sortedRepeats->values()->toArray();

            $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'ongoing');
            if (! $nextService) {
                $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'on_hold');
            }
            if (! $nextService) {
                $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'accepted');
            }
            if (! $nextService) {
                $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'pending');
            }

            $serviceSchedules = collect($booking['repeats'])->pluck('service_schedule')->flatten()->map(function ($schedule) {
                return Carbon::parse($schedule);
            });

            $booking['completeCancel'] = collect($booking['repeats'])->filter(function ($repeat) {
                return in_array($repeat['booking_status'], ['completed', 'canceled']);
            })->values()->toArray();

            $booking['upComing'] = collect($booking['repeats'])->filter(function ($repeat) use ($nextService) {

                if ($repeat['booking_status'] === 'pending') {
                    return in_array($repeat['booking_status'], ['accepted', 'pending']);
                }

                return in_array($repeat['booking_status'], ['accepted', 'pending']) && $repeat['readable_id'] !== $nextService['readable_id'];
            })->values()->toArray();


            $booking['nextService'] = $nextService;
            $booking['time'] = $serviceSchedules->max()->format('g:ia');
            $booking['startDate'] = $serviceSchedules->min()->format('d M, Y');
            $booking['endDate'] = $serviceSchedules->max()->format('d M, Y');
            $booking['totalCount'] = count($booking['repeats']);
            $booking['bookingType'] = $booking['repeats'][0]['booking_type'];

            if ($booking['bookingType'] == 'weekly') {
                $dayOrder = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

                $booking['weekNames'] = collect($booking['repeats'])
                    ->pluck('service_schedule')
                    ->map(function ($schedule) {
                        return \Carbon\Carbon::parse($schedule)->format('l');
                    })
                    ->unique()
                    ->sort(function ($a, $b) use ($dayOrder) {
                        return array_search($a, $dayOrder) - array_search($b, $dayOrder);
                    })
                    ->values()
                    ->toArray();
            }

            $booking['completedCount'] = collect($booking['repeats'])->where('booking_status', 'completed')->count();
            $booking['canceledCount'] = collect($booking['repeats'])->where('booking_status', 'canceled')->count();

            $booking['repeats'] = array_map(function ($repeat) {
                if (isset($repeat['repeat_histories'])) {
                    unset($repeat['repeat_histories']);
                }
                return $repeat;
            }, $booking['repeats']);
        }

        if ($webPage == 'details') {
            return view('bookingmodule::admin.booking.repeat-booking-details', array_merge(compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'), $this->bookingConfigurationReasonVariables()));

        }elseif ($webPage == 'service_log'){
            return view('bookingmodule::admin.booking.service-log', array_merge(compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'), $this->bookingConfigurationReasonVariables()));

        }

        Toastr::success(translate(ACCESS_DENIED['message']));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable|RedirectResponse
     * @throws AuthorizationException
     */
    public function repeatSingleDetails($id, Request $request): Renderable|RedirectResponse
    {
        $this->authorize('booking_view');
        if ($request->input('web_page') === 'status') {
            return redirect()->route('admin.booking.repeat_single_details', [$id, 'web_page' => 'history']);
        }
        Validator::make($request->all(), [
            'web_page' => 'required|in:details,history',
        ]);
        $webPage = $request->has('web_page') ? $request['web_page'] : 'business_setup';

        $booking = $this->bookingRepeat->with(['booking', 'detail.service' => function ($query) {
            $query->withTrashed();
        }, 'detail.service', 'scheduleHistories.user', 'statusHistories.user', 'booking.service_address', 'booking.customer', 'booking.provider', 'serviceman.user'])
            ->find($id);

        if (!$booking) {
            Toastr::error(translate('Booking not found'));
            return back();
        }

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking?->booking?->service_address;
        unset($booking->service_address_location);

        $servicemen = $this->serviceman->with(['user'])
            ->where('provider_id', $booking?->provider_id)
            ->whereHas('user', function ($query) {
                $query->ofStatus(1);
            })
            ->latest()
            ->get();

        $category = $booking?->detail?->first()?->service?->category;
        $subCategory = $booking?->detail?->first()?->service?->subCategory;
        $services = Service::select('id', 'name')->where('category_id', $category->id)->where('sub_category_id', $subCategory->id)->get();

        $customerAddress = $this->userAddress->find($booking['service_address_id']);
        $zones = Zone::ofStatus(1)->withoutGlobalScope('translate')->get();

        $providers = $this->provider
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                            ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when(isset($booking->booking->sub_category_id), function ($query) use ($request, $booking) {
                $query->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                    $query->where('sub_category_id', $booking->booking->sub_category_id)->where('is_subscribed', 1);
                });
            })
            ->coveringLeafZone($booking->booking->zone_id)
            ->withCount('bookings', 'reviews')
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->where('service_availability', 1)
            ->where('is_active_for_jobs', 1)
            ->withCount('reviews')
            ->ofApproval(1)->ofStatus(1)->get();

        $sort_by = 'default';
        $id = "325778a8-53bd-4de5-a6bb-826f62edf603";
        $zoneCenter = Zone::selectRaw("*,ST_AsText(ST_Centroid(`coordinates`)) as center")->withoutGlobalScope('translate')->find($id);

        $currentZone = [];
        $centerLat = [];
        $centerLng = [];
        $area = [];

        if (isset($zoneCenter)) {
            $currentZone = format_coordinates(json_decode($zoneCenter->coordinates[0]->toJson(), true));
            $centerLat = trim(explode(' ', $zoneCenter->center)[1], 'POINT()');
            $centerLng = trim(explode(' ', $zoneCenter->center)[0], 'POINT()');

            $area = json_decode($zoneCenter->coordinates[0]->toJson(), true);
        }
        if ($request->web_page == 'details') {
            return view('bookingmodule::admin.booking.rebooking-ongoing', array_merge(compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'), $this->bookingConfigurationReasonVariables()));

        } elseif ($request->web_page == 'history') {
            $mainBooking = $this->booking->with(['change_logs.changedBy'])->find($booking->booking_id);
            $changeLogs = $mainBooking?->change_logs ?? collect();

            return view('bookingmodule::admin.booking.repeat-history', compact('booking', 'webPage', 'changeLogs'));
        }

        Toastr::success(translate(ACCESS_DENIED['message']));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function statusUpdate($bookingId, Request $request): JsonResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->find($bookingId);
        $repeatBooking = $this->bookingRepeat->find($bookingId);

        if (! $booking && ! $repeatBooking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        $current = $booking ? (string) $booking->booking_status : (string) $repeatBooking->booking_status;
        $toInput = (string) $request->input('booking_status');

        $validated = $request->validate(array_merge(
            [
                'booking_status' => 'required|in:' . implode(',', array_column(BOOKING_STATUSES, 'key')),
            ],
            $this->adminBookingStatusReasonRules($current, $toInput, $booking ?? $repeatBooking)
        ));

        $to = $validated['booking_status'];

        try {
            if ($booking) {
                if (! booking_admin_status_transition_allowed_for_booking($booking, $current, $to)) {
                    return response()->json(response_formatter([
                        'response_code' => 'default_400',
                        'message' => translate('Invalid_booking_status_transition'),
                    ]), 422);
                }
                if ($to === 'completed' && ! booking_can_be_completed($booking)) {
                    return response()->json(response_formatter([
                        'response_code' => 'default_400',
                        'message' => translate('Booking cannot be completed until full payment is received.'),
                    ]), 422);
                }
                if ($to === 'completed'
                    && (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL) {
                    return response()->json(response_formatter([
                        'response_code' => 'default_400',
                        'message' => translate('Change_financial_settlement_before_completing_visit_retained_is_cancel_only'),
                    ]), 422);
                }
                if ($to === 'canceled' && $booking->isOpenReopenTicket()) {
                    return response()->json(response_formatter([
                        'response_code' => 'default_400',
                        'message' => translate('Reopened_booking_cannot_be_cancelled_use_dispute_and_close'),
                    ]), 422);
                }
                [$cId, $hId, $remarks] = $this->extractStatusChangeReasonMeta($validated, $current, $to);
                $holdEstimated = ($to === 'on_hold' && isset($validated['hold_estimated_service_schedule']))
                    ? Carbon::parse($validated['hold_estimated_service_schedule'])->toDateTimeString()
                    : null;
                $meta = [
                    'cancellation_reason_id' => $cId,
                    'hold_reopen_reason_id' => $hId,
                    'remarks' => $remarks,
                    'hold_estimated_service_schedule' => $holdEstimated,
                ];

                return $this->updateBookingStatus($booking, $to, $request, $meta);
            }

            if ($repeatBooking) {
                if (! booking_admin_status_transition_allowed_for_booking($repeatBooking, $current, $to)) {
                    return response()->json(response_formatter([
                        'response_code' => 'default_400',
                        'message' => translate('Invalid_booking_status_transition'),
                    ]), 422);
                }
                if ($to === 'completed' && ! booking_can_be_completed($repeatBooking)) {
                    return response()->json(response_formatter([
                        'response_code' => 'default_400',
                        'message' => translate('Booking cannot be completed until full payment is received.'),
                    ]), 422);
                }
                [$cId, $hId, $remarks] = $this->extractStatusChangeReasonMeta($validated, $current, $to);
                $holdEstimated = ($to === 'on_hold' && isset($validated['hold_estimated_service_schedule']))
                    ? Carbon::parse($validated['hold_estimated_service_schedule'])->toDateTimeString()
                    : null;
                $meta = [
                    'cancellation_reason_id' => $cId,
                    'hold_reopen_reason_id' => $hId,
                    'remarks' => $remarks,
                    'hold_estimated_service_schedule' => $holdEstimated,
                ];

                return $this->updateRepeatBookingStatus($repeatBooking, $to, $request, $meta);
            }
        } catch (\RuntimeException $e) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => $e->getMessage(),
            ]), 422);
        }

        return response()->json(response_formatter(DEFAULT_204), 204);
    }

    /**
     * @param  array{cancellation_reason_id: ?int, hold_reopen_reason_id: ?int, remarks: ?string, hold_estimated_service_schedule: ?string}  $meta
     */
    private function updateBookingStatus($booking, string $status, Request $request, array $meta = []): JsonResponse
    {
        $cancellationId = $meta['cancellation_reason_id'] ?? null;
        $holdReopenId = $meta['hold_reopen_reason_id'] ?? null;
        $remarks = $meta['remarks'] ?? null;
        $holdSchedule = $meta['hold_estimated_service_schedule'] ?? null;

        $previousParentStatus = (string) $booking->getOriginal('booking_status');
        $booking->booking_status = $status;
        if ($status === 'completed') {
            $booking->reopen_completion_allowed = false;
        }

        if ($booking->isDirty('booking_status')) {
            $hasRepeatSeries = BookingRepeat::query()->where('booking_id', $booking->id)->exists();

            DB::transaction(function () use ($booking, $status, $request, $cancellationId, $holdReopenId, $remarks, $holdSchedule) {
                $previousServiceSchedule = $booking->getOriginal('service_schedule');
                if ($status === 'on_hold' && $holdSchedule !== null) {
                    $booking->service_schedule = $holdSchedule;
                }

                if ($booking->repeat) {
                    foreach ($booking->repeat->whereIn('booking_status', ['pending', 'accepted', 'ongoing', 'on_hold']) as $repeat) {
                        $repeat->update([
                            'provider_id' => $request->provider_id,
                            'booking_status' => $status,
                            'serviceman_id' => null,
                        ]);

                        $this->logBookingStatusHistory($repeat->id, $status, $request->user()->id, $booking->id, $cancellationId, $holdReopenId, $remarks);
                    }

                    if ($status == 'canceled' && $booking->repeat->contains('booking_status', 'completed')) {
                        $booking->booking_status = 'completed';
                    }
                }

                $booking->save();
                $this->logBookingStatusHistory(null, $status, $request->user()->id, $booking->id, $cancellationId, $holdReopenId, $remarks);

                if ($status === 'on_hold' && $holdSchedule !== null && (string) ($previousServiceSchedule ?? '') !== (string) $holdSchedule) {
                    $bookingScheduleHistory = $this->bookingScheduleHistory->newInstance();
                    $bookingScheduleHistory->booking_id = $booking->id;
                    $bookingScheduleHistory->changed_by = $request->user()->id;
                    $bookingScheduleHistory->schedule = $holdSchedule;
                    $bookingScheduleHistory->save();
                    try {
                        $fresh = $this->booking->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments', 'serviceman.user'])->find($booking->id);
                        if ($fresh) {
                            app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                                ->sendBookingScheduleChange($fresh, $previousServiceSchedule ? (string) $previousServiceSchedule : null);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp booking schedule (hold) failed', ['booking_id' => $booking->id, 'message' => $e->getMessage()]);
                    }
                }
            });

            if ($hasRepeatSeries) {
                try {
                    $fresh = $this->booking->with([
                        'customer',
                        'provider.owner',
                        'service_address',
                        'detail',
                        'booking_partial_payments',
                    ])->find($booking->id);
                    if ($fresh) {
                        app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                            ->sendBookingStatusChange($fresh, $previousParentStatus);
                    }
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp booking status (admin parent bulk update) failed', [
                        'booking_id' => $booking->id ?? null,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
        }

        return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
    }

    /**
     * @param  array{cancellation_reason_id: ?int, hold_reopen_reason_id: ?int, remarks: ?string, hold_estimated_service_schedule: ?string}  $meta
     */
    private function updateRepeatBookingStatus($repeatBooking, string $status, Request $request, array $meta = []): JsonResponse
    {
        $cancellationId = $meta['cancellation_reason_id'] ?? null;
        $holdReopenId = $meta['hold_reopen_reason_id'] ?? null;
        $remarks = $meta['remarks'] ?? null;
        $holdSchedule = $meta['hold_estimated_service_schedule'] ?? null;

        $previousRepeatStatus = (string) $repeatBooking->booking_status;
        $repeatBooking->booking_status = $status;

        if ($repeatBooking->isDirty('booking_status')) {
            DB::transaction(function () use ($repeatBooking, $status, $request, $cancellationId, $holdReopenId, $remarks, $holdSchedule) {
                $previousServiceSchedule = $repeatBooking->getOriginal('service_schedule');
                if ($status === 'on_hold' && $holdSchedule !== null) {
                    $repeatBooking->service_schedule = $holdSchedule;
                }

                $repeatBooking->save();
                sync_repeat_series_additional_charges((string) $repeatBooking->booking_id);
                $this->logBookingStatusHistory($repeatBooking->id, $status, $request->user()->id, $repeatBooking->booking_id, $cancellationId, $holdReopenId, $remarks);

                if ($status === 'on_hold' && $holdSchedule !== null && (string) ($previousServiceSchedule ?? '') !== (string) $holdSchedule) {
                    $bookingRepeatScheduleHistory = $this->bookingScheduleHistory->newInstance();
                    $bookingRepeatScheduleHistory->booking_id = $repeatBooking->booking_id;
                    $bookingRepeatScheduleHistory->changed_by = $request->user()->id;
                    $bookingRepeatScheduleHistory->schedule = $holdSchedule;
                    $bookingRepeatScheduleHistory->booking_repeat_id = $repeatBooking->id;
                    $bookingRepeatScheduleHistory->save();
                    try {
                        $freshRepeat = $this->bookingRepeat->with([
                            'booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments',
                            'detail', 'provider.owner', 'serviceman.user', 'booking',
                        ])->find($repeatBooking->id);
                        if ($freshRepeat) {
                            app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                                ->sendBookingRepeatScheduleChange($freshRepeat, $previousServiceSchedule ? (string) $previousServiceSchedule : null);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp repeat schedule (hold) failed', ['booking_repeat_id' => $repeatBooking->id, 'message' => $e->getMessage()]);
                    }
                }

                $relatedRepeats = $this->bookingRepeat->where('booking_id', $repeatBooking->booking_id)->get();
                if ($relatedRepeats->every(fn ($repeat) => ! in_array($repeat->booking_status, ['pending', 'accepted', 'ongoing', 'on_hold'], true))) {
                    $repeatBooking->booking->update(['booking_status' => 'completed', 'is_paid' => 1]);
                }

                $repeatSt = (string) $repeatBooking->booking_status;
                if (in_array($repeatSt, ['ongoing', 'on_hold', 'completed', 'canceled'], true)) {
                    $parentSt = (string) $repeatBooking->booking->booking_status;
                    if (! in_array($parentSt, ['ongoing', 'on_hold', 'completed', 'canceled'], true)) {
                        $repeatBooking->booking->booking_status = $repeatSt === 'on_hold' ? 'on_hold' : 'ongoing';
                        $repeatBooking->booking->save();
                    }
                }
            });

            try {
                $freshRepeat = $this->bookingRepeat->with([
                    'booking.customer',
                    'booking.service_address',
                    'booking.detail',
                    'booking.booking_partial_payments',
                    'detail',
                    'provider.owner',
                ])->find($repeatBooking->id);
                if ($freshRepeat) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingRepeatStatusChange($freshRepeat, $previousRepeatStatus);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp repeat booking status failed', [
                    'booking_repeat_id' => $repeatBooking->id ?? null,
                    'message' => $e->getMessage(),
                ]);
            }

            return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
        }

        return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
    }

    private function logBookingStatusHistory(
        ?string $repeatId,
        string $status,
        string $changedBy,
        string $bookingId,
        ?int $cancellationReasonId = null,
        ?int $holdReopenReasonId = null,
        ?string $remarks = null,
    ): void {
        $remarksTrimmed = is_string($remarks) ? trim($remarks) : null;
        if ($remarksTrimmed === '') {
            $remarksTrimmed = null;
        }
        $this->bookingStatusHistory->create([
            'booking_id' => $bookingId,
            'booking_repeat_id' => $repeatId,
            'changed_by' => $changedBy,
            'booking_status' => $status,
            'booking_cancellation_reason_id' => $cancellationReasonId,
            'booking_hold_reopen_reason_id' => $holdReopenReasonId,
            'status_change_remarks' => $remarksTrimmed,
        ]);
    }


    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function upComingBookingCancel($bookingId, Request $request): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $repeatBooking = $this->bookingRepeat->where('id', $bookingId)->first();
        if (! $repeatBooking) {
            Toastr::success(translate(DEFAULT_204['message']));

            return back();
        }

        $from = (string) $repeatBooking->booking_status;
        $toInput = (string) $request->input('booking_status');

        if (! booking_admin_status_transition_allowed_for_booking($repeatBooking, $from, $toInput)) {
            Toastr::error(translate('Invalid_booking_status_transition'));

            return back();
        }

        $validated = Validator::make($request->all(), array_merge(
            [
                'booking_status' => 'required|in:' . implode(',', array_column(BOOKING_STATUSES, 'key')),
            ],
            $this->adminBookingStatusReasonRules($from, $toInput, $repeatBooking)
        ))->validate();

        [$cancellationId, $holdReopenId, $remarks] = $this->extractStatusChangeReasonMeta($validated, $from, $validated['booking_status']);

        $previousRepeatStatus = (string) $repeatBooking->booking_status;
        $repeatBooking->booking_status = $validated['booking_status'];

        if ($repeatBooking->isDirty('booking_status')) {
            DB::transaction(function () use ($repeatBooking, $request, $cancellationId, $holdReopenId, $remarks, $validated) {
                $repeatBooking->save();
                sync_repeat_series_additional_charges((string) $repeatBooking->booking_id);
                $this->bookingStatusHistory->create([
                    'booking_id' => $repeatBooking->booking_id,
                    'booking_repeat_id' => $repeatBooking->id,
                    'changed_by' => $request->user()->id,
                    'booking_status' => $validated['booking_status'],
                    'booking_cancellation_reason_id' => $cancellationId,
                    'booking_hold_reopen_reason_id' => $holdReopenId,
                    'status_change_remarks' => $remarks,
                ]);
            });

            try {
                $freshRepeat = $this->bookingRepeat->with([
                    'booking.customer',
                    'booking.service_address',
                    'booking.detail',
                    'booking.booking_partial_payments',
                    'detail',
                    'provider.owner',
                ])->find($repeatBooking->id);
                if ($freshRepeat) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingRepeatStatusChange($freshRepeat, $previousRepeatStatus);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp repeat booking status (upComingBookingCancel) failed', [
                    'booking_repeat_id' => $repeatBooking->id ?? null,
                    'message' => $e->getMessage(),
                ]);
            }

            Toastr::success(translate(DEFAULT_STATUS_UPDATE_200['message']));

            return back();
        }
        Toastr::success(translate(NO_CHANGES_FOUND['message']));

        return back();
    }

    public function verificationUpdate($bookingId, Request $request): JsonResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->where('id', $bookingId)->first();
        if (isset($booking)) {
            $booking->is_verified = 1;
            $booking->save();

            if (isset($booking->provider_id)) {
                $fcmToken = Provider::with('owner')->whereId($booking->provider_id)->first()->owner->fcm_token ?? null;
                $language_key = $this->provider->with('owner')->whereId($booking->provider_id)->first()->owner?->current_language_key;
                if (!is_null($fcmToken) && (!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $booking?->provider?->is_suspended == 0)) {
                    $title = get_push_notification_message('new_service_request_arrived', 'provider_notification', $language_key);
                    device_notification($fcmToken, $title, null, null, $booking->id, 'booking');
                }
            } else {
                $provider_ids = SubscribedService::where('sub_category_id', $booking->sub_category_id)->ofSubscription(1)->pluck('provider_id')->toArray();
                $providers = Provider::with('owner')->whereIn('id', $provider_ids)->coveringLeafZone($booking->zone_id)->get();
                foreach ($providers as $provider) {
                    $fcmToken = $provider->owner->fcm_token ?? null;
                    $title = get_push_notification_message('new_service_request_arrived', 'provider_notification', $provider?->owner?->current_language_key);
                    if (!is_null($fcmToken) && (!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $provider?->is_suspended == 0)) device_notification($fcmToken, $title, null, null, $booking->id, 'booking');
                }
            }
            return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }


    /**
     * @param $bookingId
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function verificationStatus($bookingId, Request $request): RedirectResponse
    {

        $this->authorize('booking_can_manage_status');

        $request->validate([
            'status' => 'required|in:approve,deny',
            'booking_deny_note' => 'required_if:status,deny|string|nullable',
            'status_change_remarks' => 'nullable|string|max:2000',
        ]);

        $booking = $this->booking->where('id', $bookingId)->first();
        if (isset($booking) && $request->status == 'deny') {
            $previousVerified = (int) $booking->is_verified;
            $booking->is_verified = 2;
            $booking->save();

            try {
                $fresh = $this->booking->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])->find($booking->id);
                if ($fresh) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingVerificationChange($fresh, $previousVerified, 'deny');
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp booking verification failed', ['booking_id' => $booking->id, 'message' => $e->getMessage()]);
            }

            $additionalInfo = new $this->bookingAdditionalInformation;
            $additionalInfo->booking_id = $booking->id;
            $additionalInfo->key = 'booking_deny_note';
            $additionalInfo->value = $request->booking_deny_note;
            $additionalInfo->save();

            Toastr::success(translate(DEFAULT_STORE_200['message']));
            return back();
        } elseif (isset($booking) && $request->status == 'approve') {
            $previousVerified = (int) $booking->is_verified;
            $booking->is_verified = 1;
            $booking->save();

            try {
                $fresh = $this->booking->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])->find($booking->id);
                if ($fresh) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingVerificationChange($fresh, $previousVerified, 'approve');
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp booking verification failed', ['booking_id' => $booking->id, 'message' => $e->getMessage()]);
            }

            if (isset($booking->provider_id)) {
                $fcmToken = Provider::with('owner')->whereId($booking->provider_id)->first()->owner->fcm_token ?? null;
                $language_key = $this->provider->with('owner')->whereId($booking->provider_id)->first()->owner?->current_language_key;
                if (!is_null($fcmToken) && (!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $booking?->provider?->is_suspended == 0)) {
                    $title = get_push_notification_message('new_service_request_arrived', 'provider_notification', $language_key);
                    device_notification($fcmToken, $title, null, null, $booking->id, 'booking');
                }
            } else {
                $provider_ids = SubscribedService::where('sub_category_id', $booking->sub_category_id)->ofSubscription(1)->pluck('provider_id')->toArray();
                $providers = Provider::with('owner')->whereIn('id', $provider_ids)->coveringLeafZone($booking->zone_id)->get();
                foreach ($providers as $provider) {
                    $fcmToken = $provider->owner->fcm_token ?? null;
                    $title = get_push_notification_message('booking_accepted', 'provider_notification', $provider?->owner?->current_language_key);
                    if (!is_null($fcmToken) && (!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $provider?->is_suspended == 0)) device_notification($fcmToken, $title, null, null, $booking->id, 'booking');
                }
            }

            Toastr::success(translate(DEFAULT_STATUS_UPDATE_200['message']));
            return back();
        }

        Toastr::success(translate(DEFAULT_404['message']));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paymentUpdate($bookingId, Request $request): JsonResponse
    {
        $this->authorize('booking_can_manage_status');

        Validator::make($request->all(), [
            'payment_status' => 'required|in:1,0',
        ]);

        $booking = $this->booking->where('id', $bookingId)->first();

        $repeatBooking = $this->bookingRepeat->where('id', $bookingId)->first();
        if (isset($booking)) {
            $booking->is_paid = $request->payment_status == '1' ? 1 : 0;

            if ($booking->isDirty('is_paid')) {
                $booking->save();
                return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
            }
            return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
        }
        if (isset($repeatBooking)) {
            $repeatBooking->is_paid = $request->payment_status == '1' ? 1 : 0;

            if ($repeatBooking->isDirty('is_paid')) {
                $repeatBooking->save();
                return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
            }
            return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function scheduleUpdate($bookingId, Request $request): JsonResponse
    {
        $this->authorize('booking_can_manage_status');

        Validator::make($request->all(), [
            'service_schedule' => 'required',
        ]);

        $booking = $this->booking->where('id', $bookingId)->first();
        $bookingRepeat = $this->bookingRepeat->where('id', $bookingId)->first();

        if (isset($booking)) {
            $booking->service_schedule = Carbon::parse($request->service_schedule)->toDateTimeString();

            $bookingScheduleHistory = $this->bookingScheduleHistory;
            $bookingScheduleHistory->booking_id = $bookingId;
            $bookingScheduleHistory->changed_by = $request->user()->id;
            $bookingScheduleHistory->schedule = $request['service_schedule'];

            if ($booking->isDirty('service_schedule')) {
                $previousSchedule = $booking->getOriginal('service_schedule');
                $booking->save();
                $bookingScheduleHistory->save();
                try {
                    $fresh = $this->booking->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments', 'serviceman.user'])->find($booking->id);
                    if ($fresh) {
                        app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                            ->sendBookingScheduleChange($fresh, $previousSchedule ? (string) $previousSchedule : null);
                    }
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp booking schedule failed', ['booking_id' => $booking->id, 'message' => $e->getMessage()]);
                }
                return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
            }
            return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
        }

        if (isset($bookingRepeat)) {
            $bookingRepeat->service_schedule = Carbon::parse($request->service_schedule)->toDateTimeString();

            $bookingRepeatScheduleHistory = $this->bookingScheduleHistory;
            $bookingRepeatScheduleHistory->booking_id = $bookingRepeat->booking_id;
            $bookingRepeatScheduleHistory->changed_by = $request->user()->id;
            $bookingRepeatScheduleHistory->schedule = $request['service_schedule'];
            $bookingRepeatScheduleHistory->booking_repeat_id = $bookingId;

            if ($bookingRepeat->isDirty('service_schedule')) {
                $previousSchedule = $bookingRepeat->getOriginal('service_schedule');
                $bookingRepeat->save();
                $bookingRepeatScheduleHistory->save();
                try {
                    $fresh = $this->bookingRepeat->with([
                        'booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments',
                        'detail', 'provider.owner', 'serviceman.user', 'booking',
                    ])->find($bookingRepeat->id);
                    if ($fresh) {
                        app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                            ->sendBookingRepeatScheduleChange($fresh, $previousSchedule ? (string) $previousSchedule : null);
                    }
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp repeat schedule failed', ['booking_repeat_id' => $bookingRepeat->id, 'message' => $e->getMessage()]);
                }
                return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
            }
            return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function upComingBookingScheduleUpdate($bookingId, Request $request): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        Validator::make($request->all(), [
            'service_schedule' => 'required',
        ]);

        $bookingRepeat = $this->bookingRepeat->where('id', $bookingId)->first();

        if (isset($bookingRepeat)) {
            $bookingRepeat->service_schedule = Carbon::parse($request->service_schedule)->toDateTimeString();

            $bookingRepeatScheduleHistory = $this->bookingScheduleHistory;
            $bookingRepeatScheduleHistory->booking_id = $bookingRepeat->booking_id;
            $bookingRepeatScheduleHistory->changed_by = $request->user()->id;
            $bookingRepeatScheduleHistory->schedule = $request['service_schedule'];
            $bookingRepeatScheduleHistory->booking_repeat_id = $bookingId;

            if ($bookingRepeat->isDirty('service_schedule')) {
                $previousSchedule = $bookingRepeat->getOriginal('service_schedule');
                $bookingRepeat->save();
                $bookingRepeatScheduleHistory->save();

                try {
                    $fresh = $this->bookingRepeat->with([
                        'booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments',
                        'detail', 'provider.owner', 'serviceman.user', 'booking',
                    ])->find($bookingRepeat->id);
                    if ($fresh) {
                        app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                            ->sendBookingRepeatScheduleChange($fresh, $previousSchedule ? (string) $previousSchedule : null);
                    }
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp repeat schedule failed', ['booking_repeat_id' => $bookingRepeat->id, 'message' => $e->getMessage()]);
                }

                Toastr::success(translate(DEFAULT_UPDATE_200['message']));
                return back();
            }
            Toastr::success(translate(NO_CHANGES_FOUND['message']));
            return back();
        }
        Toastr::success(translate(DEFAULT_204['message']));
        return back();
    }

    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function providerUpdate($bookingId, Request $request): JsonResponse
    {
        $this->authorize('booking_can_manage_status');

        Validator::make($request->all(), [
            'provider_id' => 'required|uuid',
        ]);

        $booking = $this->booking->where('id', $bookingId)->first();

        if (isset($booking)) {
            if (! booking_admin_can_reassign_provider($booking)) {
                return response()->json(response_formatter([
                    'response_code' => 'default_400',
                    'message' => translate('Provider_reassign_not_allowed_after_ongoing'),
                ]), 422);
            }

            $oldProviderId = $booking->provider_id;
            $booking->provider_id = $request->provider_id;

            if ($booking->isDirty('provider_id')) {
                $booking->booking_status = 'accepted';
                $booking->serviceman_id = null;
                $booking->assigned_by = 'admin';

                if (!is_null($booking->repeat)) {
                    foreach ($booking->repeat->whereIn('booking_status', ['pending', 'accepted', 'ongoing']) as $bookingRepeat) {
                        $bookingRepeat->provider_id = $request->provider_id;
                        $bookingRepeat->booking_status = 'accepted';
                        $bookingRepeat->serviceman_id = null;
                        $bookingRepeat->save();
                    }
                }

                $booking->save();

                if ((string) $oldProviderId !== (string) $request->provider_id) {
                    try {
                        $previousProvider = $oldProviderId ? $this->provider->with('owner')->find($oldProviderId) : null;
                        $booking->refresh();
                        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
                        app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                            ->sendBookingProviderChange($booking, $previousProvider);
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp provider change (admin booking providerUpdate) failed', [
                            'booking_id' => $booking->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
            }

            return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Display a listing of the resource.
     * @param $bookingId
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function servicemanUpdate(Request $request): JsonResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->where('id', $request->booking_id)->first();
        $bookingRepeat = $this->bookingRepeat->where('id', $request->booking_id)->with('booking')->first();

        if (isset($booking)) {
            $previousServicemanId = $booking->serviceman_id ? (string) $booking->serviceman_id : null;
            $booking->serviceman_id = $request->serviceman_id;
            $booking->save();

            if (!is_null($booking->repeat)) {
                foreach ($booking->repeat->whereIn('booking_status', ['pending', 'accepted', 'ongoing']) as $bookingRepeat) {
                    $bookingRepeat->serviceman_id = $request->serviceman_id;
                    $bookingRepeat->save();
                }
            }

            try {
                $fresh = $this->booking->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments', 'serviceman.user'])->find($booking->id);
                if ($fresh) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingServicemanChange($fresh, $previousServicemanId);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp booking serviceman failed', ['booking_id' => $booking->id, 'message' => $e->getMessage()]);
            }

            $search = $request->search;
            $servicemen = $this->serviceman
                ->where('provider_id', $bookingRepeat?->provider_id)
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    return $query->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhereHas('user', function ($query) use ($key) {
                                $query->where('first_name', 'LIKE', '%' . $key . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                                    ->orWhere('phone', 'LIKE', '%' . $key . '%')
                                    ->orWhere('email', 'LIKE', '%' . $key . '%');
                            });
                        }
                    });
                })
                ->whereHas('user', function ($query) {
                    $query->ofStatus(1);
                })->get();

            return response()->json([
                'view' => view('bookingmodule::admin.booking.partials.details.serviceman-info-modal-data', compact('servicemen', 'booking', 'search'))->render()
            ]);
        }
        if (isset($bookingRepeat)) {

            $previousServicemanId = $bookingRepeat->serviceman_id ? (string) $bookingRepeat->serviceman_id : null;
            $bookingRepeat->serviceman_id = $request->serviceman_id;
            $bookingRepeat->save();

            if ($bookingRepeat->booking) {
                $bookingRepeat->booking->serviceman_id = $request->serviceman_id;
                $bookingRepeat->booking->save();
            }

            try {
                $fresh = $this->bookingRepeat->with([
                    'booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments',
                    'detail', 'provider.owner', 'serviceman.user', 'booking',
                ])->find($bookingRepeat->id);
                if ($fresh) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                        ->sendBookingRepeatServicemanChange($fresh, $previousServicemanId);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp repeat serviceman failed', ['booking_repeat_id' => $bookingRepeat->id, 'message' => $e->getMessage()]);
            }

            $search = $request->search;
            $servicemen = $this->serviceman
                ->where('provider_id', $bookingRepeat?->provider_id)
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    return $query->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhereHas('user', function ($query) use ($key) {
                                $query->where('first_name', 'LIKE', '%' . $key . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                                    ->orWhere('phone', 'LIKE', '%' . $key . '%')
                                    ->orWhere('email', 'LIKE', '%' . $key . '%');
                            });
                        }
                    });
                })
                ->whereHas('user', function ($query) {
                    $query->ofStatus(1);
                })->get();

            $booking = $bookingRepeat;

            return response()->json([
                'view' => view('bookingmodule::admin.booking.partials.details.serviceman-info-modal-data', compact('servicemen', 'booking', 'search'))->render()
            ]);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Update booking info: assignee, booking_source, service_description.
     *
     * @param string $id Booking id
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function updateBookingInfo($id, Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        $data = $request->validate([
            'assignee_id' => ['nullable', 'exists:users,id'],
            'booking_source' => ['required', 'string', 'max:255'],
            'service_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking = $this->booking->findOrFail($id);
        $booking->assignee_id = $data['assignee_id'] ?? null;
        $booking->booking_source = strtolower($data['booking_source']);
        $booking->service_description = $data['service_description'] ?? null;
        $booking->save();

        Toastr::success(translate('Booking_information_updated_successfully'));
        return redirect()->back();
    }

    /**
     * Fixed company (admin) commission for this booking only; replaces tier rules everywhere commission is derived.
     */
    public function updateAdminCommissionOverride(string $id, Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        $booking = $this->booking->findOrFail($id);
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            Toastr::error(translate('Financial_settlement_applies_to_single_bookings_only'));

            return redirect()->back();
        }
        if ($booking->blocksAdminCommissionOverrideAndCompensation()) {
            Toastr::error(translate('Bfs_commission_override_and_compensation_not_for_special_settlement'));

            return redirect()->back();
        }
        if (SubscriptionBookingType::where('booking_id', $booking->id)->where('type', 'subscription')->exists()) {
            Toastr::error(translate('Booking_commission_override_not_for_subscription'));

            return redirect()->back();
        }

        if ($request->input('revert_to_tier_commission') === '1') {
            $request->validate([
                'revert_to_tier_commission' => ['required', 'in:1'],
            ]);
            if ($booking->admin_commission_override === null) {
                Toastr::info(translate('Booking_commission_override_nothing_to_revert'));

                return redirect()->back();
            }
            $booking->admin_commission_override = null;
        } else {
            $request->validate([
                'admin_commission_override' => ['required', 'numeric', 'min:0'],
            ]);
            $booking->admin_commission_override = round(max(0.0, (float) $request->input('admin_commission_override')), 2);
        }

        if (trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION) {
            $booking->settlement_outcome = null;
            $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            unset($cfg['custom_admin_commission']);
            $booking->settlement_config = $cfg === [] ? null : $cfg;
            $booking->settlement_snapshot = null;
        }

        $booking->save();
        $booking->resyncStoredCommissionAndSettlementSnapshot();

        Toastr::success(translate('Update_successfully'));

        return redirect()->back();
    }

    /**
     * Store an extra service item for a booking.
     */
    public function storeExtraService($id, Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:service,spare_part'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $booking = $this->booking->findOrFail($id);
        $data['booking_id'] = $booking->id;
        $data['discount'] = (float)($data['discount'] ?? 0);
        $item = new BookingExtraService($data);
        $item->recalculateTotal();
        $item->save();

        Toastr::success(translate('Extra_service_item_added'));
        return redirect()->back();
    }

    /**
     * Delete an extra service item.
     */
    public function destroyExtraService($id, $extraId): RedirectResponse
    {
        $this->authorize('booking_edit');

        $booking = $this->booking->findOrFail($id);
        $item = $booking->extra_services()->findOrFail($extraId);
        $item->delete();

        Toastr::success(translate('Extra_service_item_removed'));
        return redirect()->back();
    }

    /**
     * Update per-booking amounts for additional charge types marked customizable (rules still drive non-customizable lines).
     */
    public function updateBookingAdditionalCharges(string $id, Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        $booking = $this->booking->with(['detail.service.category', 'detail.service.subCategory'])->findOrFail($id);

        if (in_array($booking->booking_status, ['completed', 'canceled', 'refunded'], true)) {
            Toastr::error(translate('Access_denied'));

            return redirect()->back();
        }

        $request->validate([
            'ac_line_amount' => ['nullable', 'array'],
            'ac_line_amount.*' => ['nullable'],
        ]);

        $computed = compute_additional_charges_for_booking_details($booking);
        $allowed = collect($computed['lines'])->filter(fn ($l) => ! empty($l['customizable']))->keyBy('id');
        $requestAmountsRaw = (array) $request->input('ac_line_amount', []);
        $requestAmounts = [];
        foreach ($requestAmountsRaw as $reqKey => $reqVal) {
            $requestAmounts[(string) $reqKey] = $reqVal;
        }
        $storedById = collect($booking->additional_charges_breakdown ?? [])->keyBy(fn ($l) => (string) ($l['id'] ?? ''));
        $computedById = collect($computed['lines'])->keyBy(fn ($l) => (string) ($l['id'] ?? ''));

        $normalizeAcAmount = static function (mixed $value): float {
            if (is_array($value)) {
                $value = $value === [] ? null : reset($value);
            }
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return 0.0;
            }

            return max(0.0, round((float) $value, 2));
        };

        $filtered = [];
        foreach ($allowed as $chargeTypeId => $_line) {
            $typeKey = (string) $chargeTypeId;
            if (array_key_exists($typeKey, $requestAmounts)) {
                $filtered[$typeKey] = $normalizeAcAmount($requestAmounts[$typeKey]);

                continue;
            }
            $stored = $storedById->get($typeKey);
            if ($stored !== null && array_key_exists('amount', $stored)) {
                $filtered[$typeKey] = $normalizeAcAmount($stored['amount']);

                continue;
            }
            $cl = $computedById->get($typeKey);
            $filtered[$typeKey] = $normalizeAcAmount($cl['amount'] ?? 0);
        }

        $merged = merge_additional_charge_line_amount_overrides($computed['lines'], $filtered);
        apply_booking_additional_charges_snapshot($booking, $merged);

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));

        return redirect()->back();
    }

    /**
     * Display a listing of the resource.
     * @param $service_address_id
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function serviceAddressUpdate($service_address_id, Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        $validated = Validator::make($request->all(), [
            'address' => 'required|string',
            'address_label' => 'required|string|max:191',
            'landmark' => 'nullable|string|max:500',
            'latitude' => 'nullable|string|max:191',
            'longitude' => 'nullable|string|max:191',
            'zone_id' => 'nullable|uuid',
        ])->validate();

        $userAddress = $this->userAddress->findOrFail($service_address_id);
        $userAddress->address = $validated['address'];
        $userAddress->address_label = $validated['address_label'];
        $userAddress->landmark = $validated['landmark'] ?? null;
        $userAddress->lat = $validated['latitude'] ?? null;
        $userAddress->lon = $validated['longitude'] ?? null;
        $userAddress->city = null;
        $userAddress->street = null;
        $userAddress->zip_code = null;
        $userAddress->country = null;
        if (!empty($validated['zone_id'])) {
            $userAddress->zone_id = $validated['zone_id'];
        }
        $userAddress->save();

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }

    /**
     * @param Request $request
     * @return string|StreamedResponse
     * @throws AuthorizationException
     * @throws \OpenSpout\Common\Exception\IOException
     * @throws \OpenSpout\Common\Exception\InvalidArgumentException
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     * @throws \OpenSpout\Writer\Exception\WriterNotOpenedException
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('booking_view');
        $allowedBookingStatuses = array_merge(array_column(BOOKING_STATUSES, 'key'), ['all', 'reopened']);
        $request->validate([
            'booking_status' => 'nullable|in:' . implode(',', $allowedBookingStatuses),
        ]);

        $bookingStatus = $request->input('booking_status') ?: 'all';
        $assigneeIds = $this->normalizeAdminAssigneeFilterIds((array) $request->input('assignee_ids', []));

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;
        $items = $this->booking
            ->with(['customer'])
            ->search($request['search'], ['readable_id'])
            ->when($bookingStatus != 'all', function ($query) use ($bookingStatus, $maxBookingAmount, $request) {
                if ($bookingStatus === 'reopened') {
                    $query->reopenedChain();
                } else {
                    $query->when($bookingStatus == 'pending', function ($query) use ($maxBookingAmount) {
                        $query->adminPendingBookings($maxBookingAmount);
                    })->when($bookingStatus == 'accepted', function ($query) use ($maxBookingAmount) {
                        $query->adminAcceptedBookings($maxBookingAmount);
                    })->ofBookingStatus($bookingStatus);
                }
            })
            ->when($request['provider_assigned'] == 'assigned', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNotNull('provider_id')
                        ->orWhereHas('repeat', function ($q) {
                            $q->whereNotNull('provider_id');
                        });
                });
            })
            ->when($request['provider_assigned'] == 'unassigned', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('provider_id')
                        ->orWhereDoesntHave('repeat', function ($q) {
                            $q->whereNotNull('provider_id');
                        });
                });
            })
            ->filterByZoneIds($request['zone_ids'])
            ->filterBySubcategoryIds($request['sub_category_ids'])
            ->filterByCategoryIds($request['category_ids'])
            ->filterByDateRange($request['start_date'], $request['end_date'])
            ->filterByAssigneeIds($assigneeIds)
            ->latest()->get();

        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }


    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable
     */
    public function invoice($id, Request $request): Renderable
    {
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user', 'extra_services', 'booking_partial_payments', 'booking_offline_payments'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        $sub_total = $booking->detail->sum(fn ($item) => $item->service_cost * $item->quantity);
        $extraServicesTotal = ($booking->extra_services ?? collect())->sum('total');

        return view('bookingmodule::admin.booking.invoice', compact('booking', 'sub_total', 'extraServicesTotal'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function fullBookingInvoice($id): Renderable
    {
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user','repeat', 'extra_services'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function fullBookingSingleInvoice($id): Renderable
    {
        $booking = $this->bookingRepeat->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'booking.extra_services', 'provider', 'serviceman'])->find($id);

        $booking->booking->service_address = $booking->booking->service_address_location != null ? json_decode($booking->booking->service_address_location) : $booking->booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-single-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function customerFullBookingInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user','repeat', 'extra_services'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function customerFullBookingSingleInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->bookingRepeat->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'booking.extra_services', 'provider', 'serviceman'])->find($id);

        $booking->booking->service_address = $booking->booking->service_address_location != null ? json_decode($booking->booking->service_address_location) : $booking->booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-single-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function providerFullBookingInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user','repeat', 'extra_services'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function providerFullBookingSingleInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->bookingRepeat->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'booking.extra_services', 'provider', 'serviceman'])->find($id);

        $booking->booking->service_address = $booking->booking->service_address_location != null ? json_decode($booking->booking->service_address_location) : $booking->booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-single-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param $lang
     * @return Renderable
     */
    public function servicemanFullBookingSingleInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->bookingRepeat->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'booking.extra_services', 'provider', 'serviceman'])->find($id);

        $booking->booking->service_address = $booking->booking->service_address_location != null ? json_decode($booking->booking->service_address_location) : $booking->booking->service_address;

        return view('bookingmodule::admin.booking.fullbooking-single-invoice', compact('booking'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable
     */
    public function customerInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user', 'extra_services', 'booking_partial_payments'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        $sub_total = $booking->detail->sum(fn ($item) => $item->service_cost * $item->quantity);
        $extraServicesTotal = ($booking->extra_services ?? collect())->sum('total');

        return view('bookingmodule::admin.booking.invoice', compact('booking', 'sub_total', 'extraServicesTotal'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable
     */
    public function providerInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user', 'extra_services', 'booking_partial_payments'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        $sub_total = $booking->detail->sum(fn ($item) => $item->service_cost * $item->quantity);
        $extraServicesTotal = ($booking->extra_services ?? collect())->sum('total');

        return view('bookingmodule::admin.booking.invoice', compact('booking', 'sub_total', 'extraServicesTotal'));
    }

    /**
     * Display a listing of the resource.
     * @param $id
     * @param Request $request
     * @return Renderable
     */
    public function servicemanInvoice($id, $lang): Renderable
    {
        App::setLocale($lang);
        $booking = $this->booking->with(['detail.service' => function ($query) {
            $query->withTrashed();
        }, 'customer', 'provider', 'serviceman', 'status_histories.user', 'extra_services', 'booking_partial_payments'])->find($id);

        $booking->service_address = $booking->service_address_location != null ? json_decode($booking->service_address_location) : $booking->service_address;

        $sub_total = $booking->detail->sum(fn ($item) => $item->service_cost * $item->quantity);
        $extraServicesTotal = ($booking->extra_services ?? collect())->sum('total');

        return view('bookingmodule::admin.booking.invoice', compact('booking', 'sub_total', 'extraServicesTotal'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetVariant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|uuid',
            'service_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $variations = Variation::listForBookingZone(
            (string) $request['service_id'],
            (string) $request['zone_id']
        );

        $service = Service::query()->find($request['service_id']);
        $payload = response_formatter(DEFAULT_200, $variations, null);
        $payload['service_tax_percent'] = $service
            ? effective_service_tax_percentage($service)
            : company_default_tax_percentage();

        return response()->json($payload, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetServiceInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|uuid',
            'service_id' => 'required|uuid',
            'variant_key' => 'required',
            'quantity' => 'required|numeric',
            'booking_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $service = Service::active()
            ->with(['category.category_discount', 'category.campaign_discount', 'subCategory', 'service_discount'])
            ->where('id', $request['service_id'])
            ->first();

        $variation = Variation::firstForBookingZone(
            (string) $request['service_id'],
            (string) $request['variant_key'],
            (string) $request['zone_id']
        );

        if (!$service || !$variation) {
            return response()->json(response_formatter(DEFAULT_404, null), 200);
        }

        $quantity = $request['quantity'];
        $variation_price = $variation->price;

        $basic_discount = basic_discount_calculation($service, $variation_price * $quantity);
        $campaign_discount = campaign_discount_calculation($service, $variation_price * $quantity);
        $subtotal = round($variation_price * $quantity, 2);

        $applicable_discount = ($campaign_discount >= $basic_discount) ? $campaign_discount : $basic_discount;

        $tax = round((($variation_price * $quantity - $applicable_discount) * effective_service_tax_percentage($service)) / 100, 2);

        $basic_discount = $basic_discount > $campaign_discount ? $basic_discount : 0;
        $campaign_discount = $campaign_discount >= $basic_discount ? $campaign_discount : 0;

        $lineDiscountTotal = $basic_discount + $campaign_discount;
        $totalCost = round($subtotal - $basic_discount - $campaign_discount + $tax, 2);

        $data = collect([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'variant_key' => $variation->variant_key,
            'quantity' => $request['quantity'],
            'service_cost' => $variation_price,
            'total_discount_amount' => $lineDiscountTotal,
            'tax_percent' => effective_service_tax_percentage($service),
            'coupon_code' => null,
            'tax_amount' => round($tax, 2),
            'total_cost' => $totalCost,
            'zone_id' => $request['zone_id'],
            'discount_cost_bearer' => DiscountCostBearer::NONE,
        ]);

        $booking = $request->filled('booking_id')
            ? $this->booking->find($request['booking_id'])
            : null;
        $services = collect();
        if ($booking && $booking->provider_id) {
            $subIds = SubscribedService::query()
                ->where('provider_id', $booking->provider_id)
                ->where('is_subscribed', 1)
                ->pluck('sub_category_id');
            $services = Service::query()
                ->select('id', 'name', 'category_id', 'sub_category_id')
                ->whereIn('sub_category_id', $subIds)
                ->where('is_active', 1)
                ->orderBy('name')
                ->get();
        }
        $rowKey = 'new-' . Str::uuid()->toString();

        return response()->json([
            'view' => view('bookingmodule::admin.booking.partials.details.table-row', compact('data', 'booking', 'services', 'rowKey'))->render(),
        ]);
    }

    /**
     * Get billing summary for add-booking form (service cost, tax, total).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetBillingSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|uuid',
            'service_id' => 'required|uuid',
            'variant_key' => 'required',
            'quantity' => 'nullable|numeric',
            'ac_line_amount' => ['nullable', 'array'],
            'ac_line_amount.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $quantity = (float) ($request->input('quantity', 1));
        $acOverrides = array_map(
            static fn ($v) => is_numeric($v) ? (float) $v : 0.0,
            (array) $request->input('ac_line_amount', [])
        );
        $service = Service::active()
            ->with(['category.category_discount', 'category.campaign_discount', 'subCategory', 'service_discount'])
            ->where('id', $request['service_id'])
            ->first();

        $variation = Variation::firstForBookingZone(
            (string) $request['service_id'],
            (string) $request['variant_key'],
            (string) $request['zone_id']
        );

        if (!$service || !$variation) {
            return response()->json(response_formatter(DEFAULT_404, null), 200);
        }

        $variationPrice = $variation->price ?? 0;
        $basicDiscount = basic_discount_calculation($service, $variationPrice * $quantity);
        $campaignDiscount = campaign_discount_calculation($service, $variationPrice * $quantity);
        $subtotal = round($variationPrice * $quantity, 2);
        $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
        $tax = round((($variationPrice * $quantity - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);
        $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
        $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;
        $basisExTax = round($subtotal - $basicDiscount - $campaignDiscount, 2);
        $computed = compute_additional_charges_for_service_basis($basisExTax, $service);
        $mergedLines = merge_additional_charge_line_amount_overrides($computed['lines'], $acOverrides);
        $finalAc = finalize_additional_charge_lines($mergedLines);
        $extraFee = $finalAc['total'];
        $totalCost = round($subtotal - $basicDiscount - $campaignDiscount + $tax + $extraFee, 2);

        $data = [
            'service_cost' => round($variationPrice * $quantity, 2),
            'total_discount_amount' => round($basicDiscount + $campaignDiscount, 2),
            'tax_amount' => $tax,
            'extra_fee' => $extraFee,
            'additional_charges_lines' => $finalAc['lines'],
            'total_cost' => $totalCost,
        ];

        return response()->json(response_formatter(DEFAULT_200, $data, null), 200);
    }

    /**
     * Multi-line cart + optional extra services for admin create booking (totals match store/preview).
     */
    public function ajaxCreateBookingCartSummary(Request $request): JsonResponse
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            return response()->json(response_formatter(DEFAULT_403, null), 403);
        }

        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|uuid',
            'provider_id' => 'required|uuid',
            'lines' => 'required|array|min:1',
            'lines.*.service_id' => 'required|uuid',
            'lines.*.variant_key' => 'required|string',
            'lines.*.quantity' => 'nullable|integer|min:1',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.line_discount' => 'nullable|numeric|min:0',
            'lines.*.line_discount_cost_bearer' => 'nullable|string|in:both,admin,provider,none',
            'extras' => 'nullable|array',
            'extras.*.title' => 'nullable|string|max:255',
            'extras.*.type' => 'nullable|in:service,spare_part',
            'extras.*.quantity' => 'nullable|integer|min:1',
            'extras.*.price' => 'nullable|numeric|min:0',
            'extras.*.discount' => 'nullable|numeric|min:0',
            'extras.*.details' => 'nullable|string|max:2000',
            'ac_line_amount' => ['nullable', 'array'],
            'ac_line_amount.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $linesIn = [];
        foreach ((array) $request->input('lines', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $lineIn = [
                'service_id' => (string) ($row['service_id'] ?? ''),
                'variant_key' => (string) ($row['variant_key'] ?? ''),
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'line_discount' => max(0.0, (float) ($row['line_discount'] ?? 0)),
                'line_discount_cost_bearer' => DiscountCostBearer::normalize($row['line_discount_cost_bearer'] ?? null),
            ];
            if (isset($row['unit_price']) && is_numeric($row['unit_price']) && (float) $row['unit_price'] > 0) {
                $lineIn['unit_price'] = round((float) $row['unit_price'], 4);
            }
            $linesIn[] = $lineIn;
        }

        $extrasIn = [];
        foreach ((array) $request->input('extras', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $type = ($row['type'] ?? '') === BookingExtraService::TYPE_SPARE_PART
                ? BookingExtraService::TYPE_SPARE_PART
                : BookingExtraService::TYPE_SERVICE;
            $extrasIn[] = [
                'title' => mb_substr($title, 0, 255),
                'details' => isset($row['details']) ? mb_substr((string) $row['details'], 0, 2000) : null,
                'type' => $type,
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'price' => max(0.0, (float) ($row['price'] ?? 0)),
                'discount' => max(0.0, (float) ($row['discount'] ?? 0)),
            ];
        }

        $acOverrides = array_map(
            static fn ($v) => is_numeric($v) ? (float) $v : 0.0,
            (array) $request->input('ac_line_amount', [])
        );

        try {
            $cart = $this->buildAdminCreateBookingCartPricing(
                (string) $request->input('zone_id'),
                (string) $request->input('provider_id'),
                $linesIn,
                $acOverrides,
                $extrasIn
            );
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? translate('Something_went_wrong');

            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $msg]]), 200);
        }

        $linePayload = [];
        foreach ($cart['lines'] as $l) {
            /** @var Variation|null $varEntity */
            $varEntity = $l['variation'] ?? null;
            $linePayload[] = [
                'service_id' => $l['service_id'],
                'variant_key' => $l['variant_key'],
                'quantity' => $l['quantity'],
                'service_name' => $l['service_name'],
                'variant_label' => $l['variant_label'],
                'unit_price' => $l['service_cost_unit'],
                'catalog_unit_price' => round((float) ($varEntity->price ?? 0), 4),
                'discount_total' => round((float) $l['basic_discount'] + (float) $l['campaign_discount'], 2),
                'tax_amount' => $l['tax_amount'],
                'line_total' => $l['line_total_before_ac'],
            ];
        }

        $extrasServiceTotal = 0.0;
        $extrasSpareTotal = 0.0;
        $extrasDiscountSum = 0.0;
        foreach ($cart['extras'] as $ex) {
            $t = (float) ($ex['total'] ?? 0);
            if (($ex['type'] ?? '') === BookingExtraService::TYPE_SPARE_PART) {
                $extrasSpareTotal = round($extrasSpareTotal + $t, 2);
            } else {
                $extrasServiceTotal = round($extrasServiceTotal + $t, 2);
            }
            $extrasDiscountSum = round($extrasDiscountSum + (float) ($ex['discount'] ?? 0), 2);
        }

        $mainLineDiscount = round((float) $cart['sum_basic_discount'] + (float) $cart['sum_campaign_discount'], 2);
        $totalDiscountAmount = round($mainLineDiscount + $extrasDiscountSum, 2);
        $totalServiceCharges = round((float) $cart['sum_line_totals'] + $extrasServiceTotal, 2);

        /** @var Service $commissionContextService */
        $commissionContextService = $cart['lines'][0]['service'];
        $commissionPreview = $this->computeAdminCreateBookingCommissionPreview(
            $cart,
            (string) $request->input('provider_id'),
            $commissionContextService
        );

        return response()->json(response_formatter(DEFAULT_200, [
            'lines' => $linePayload,
            'extras' => $cart['extras'],
            'sum_line_totals' => $cart['sum_line_totals'],
            'extra_fee' => $cart['extra_fee'],
            'additional_charges_lines' => $cart['additional_charge_lines'],
            'extras_total' => $cart['extras_total'],
            'grand_total' => $cart['grand_total'],
            'total_service_charges' => $totalServiceCharges,
            'total_spare_part_charges' => $extrasSpareTotal,
            'total_discount_amount' => $totalDiscountAmount,
            'sum_tax' => $cart['sum_tax'],
            'company_commission' => $commissionPreview['company_commission'],
            'provider_commission' => $commissionPreview['provider_commission'],
        ], null), 200);
    }

    /**
     * Get categories by zone
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetCategories(Request $request): JsonResponse
    {
        $zoneIds = array_values(array_filter((array) $request->input('zone_ids', [])));
        if ($zoneIds === [] && $request->filled('zone_id')) {
            $zoneIds = [(string) $request->input('zone_id')];
        }

        $validator = Validator::make(array_merge(['zone_ids' => $zoneIds], $request->only('provider_id')), [
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'uuid',
            'provider_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        // category_zone pivot stores leaf zone IDs; parent selections must expand to descendant leaves.
        $leafZoneIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds($zoneIds);
        if ($leafZoneIds === []) {
            return response()->json(response_formatter(DEFAULT_200, collect(), null), 200);
        }

        $categories = $this->category
            ->withoutGlobalScope('translate')
            ->where('position', 1)
            ->where('is_active', 1)
            ->whereHas('zones', function ($query) use ($leafZoneIds) {
                $query->whereIn('zones.id', $leafZoneIds);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

        if ($request->filled('provider_id')) {
            $allowedSubIds = SubscribedService::query()
                ->where('provider_id', $request->input('provider_id'))
                ->where('is_subscribed', 1)
                ->pluck('sub_category_id')
                ->all();
            if ($allowedSubIds === []) {
                $categories = collect();
            } else {
                $parentIds = Category::query()
                    ->whereIn('id', $allowedSubIds)
                    ->where('position', 2)
                    ->where('is_active', 1)
                    ->pluck('parent_id')
                    ->unique()
                    ->filter()
                    ->values()
                    ->all();
                $categories = $categories->whereIn('id', $parentIds)->values();
            }
        }

        return response()->json(response_formatter(DEFAULT_200, $categories), 200);
    }

    /**
     * Get subcategories by category
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetSubcategories(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|uuid',
            'provider_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $subCategories = $this->category
            ->withoutGlobalScope('translate')
            ->where('position', 2)
            ->where('is_active', 1)
            ->where('parent_id', $request['category_id'])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        if ($request->filled('provider_id')) {
            $allowedSubIds = SubscribedService::query()
                ->where('provider_id', $request['provider_id'])
                ->where('is_subscribed', 1)
                ->pluck('sub_category_id')
                ->all();
            $subCategories = $subCategories->whereIn('id', $allowedSubIds)->values();
        }

        return response()->json(response_formatter(DEFAULT_200, $subCategories), 200);
    }

    /**
     * Get services by subcategory
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetServices(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $services = Service::where('sub_category_id', $request['sub_category_id'])
            ->where('is_active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json(response_formatter(DEFAULT_200, $services), 200);
    }

    /**
     * Get providers by subcategory with subscription status
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGetProviders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'nullable|uuid',
            'category_id' => 'nullable|uuid',
            'zone_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $subCategoryId = $request->input('sub_category_id');
        $categoryId = $request->input('category_id');
        $zoneId = $request->input('zone_id');

        if (! $subCategoryId && ! $categoryId) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'message' => translate('This_field_required'),
            ]]), 400);
        }

        // Zone must be selected to load eligible providers (prevents "show all").
        if (! $zoneId) {
            return response()->json(response_formatter(DEFAULT_200, collect([])), 200);
        }

        if ($subCategoryId) {
            $subscribedProviderIds = SubscribedService::query()
                ->where('sub_category_id', $subCategoryId)
                ->where('is_subscribed', 1)
                ->pluck('provider_id')
                ->unique()
                ->toArray();
        } else {
            $subCategoryIdsUnderParent = Category::query()
                ->where('parent_id', $categoryId)
                ->where('is_active', 1)
                ->pluck('id')
                ->all();

            $subscribedProviderIds = $subCategoryIdsUnderParent === []
                ? []
                : SubscribedService::query()
                    ->whereIn('sub_category_id', $subCategoryIdsUnderParent)
                    ->where('is_subscribed', 1)
                    ->pluck('provider_id')
                    ->unique()
                    ->toArray();
        }

        // Get only subscribed providers with contact person info
        $providers = $this->provider
            ->whereIn('id', $subscribedProviderIds)
            ->coveringZoneOrDescendants($zoneId)
            ->get(['id', 'company_name', 'contact_person_name', 'contact_person_phone', 'zone_id'])
            ->map(function ($provider) {
                $companyName = $provider->company_name ?? '';
                $contactPersonName = $provider->contact_person_name ?? '';
                $contactPersonPhone = $provider->contact_person_phone ?? '';
                
                return [
                    'id' => $provider->id,
                    'company_name' => $companyName,
                    'contact_person_name' => $contactPersonName,
                    'contact_person_phone' => $contactPersonPhone,
                    'is_subscribed' => true, // All are subscribed since we filtered
                ];
            })
            ->filter(function($provider) {
                // Filter out providers missing any required field
                return !empty($provider['company_name']) && 
                       !empty($provider['contact_person_name']) && 
                       !empty($provider['contact_person_phone']);
            })
            ->sortBy('company_name')
            ->values();

        return response()->json(response_formatter(DEFAULT_200, $providers), 200);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException|AuthorizationException
     */
    public function updateBookingService(Request $request): RedirectResponse
    {

        $this->authorize('booking_edit');

        Validator::make($request->all(), [
            'qty' => 'required|array',
            'qty.*' => 'int',
            'service_ids' => 'required|array',
            'service_ids.*' => 'uuid',
            'variant_keys' => 'required|array',
            'variant_keys.*' => 'string',
            'zone_id' => 'required|uuid',
            'booking_id' => 'required|uuid',
            'booking_detail_ids' => 'nullable|array',
            'booking_detail_ids.*' => 'nullable|string',
            'line_unit_prices' => 'nullable|array',
            'line_unit_prices.*' => 'nullable|numeric|min:0',
            'line_discount_amounts' => 'nullable|array',
            'line_discount_amounts.*' => 'nullable|numeric|min:0',
            'line_discount_cost_bearers' => 'nullable|array',
            'line_discount_cost_bearers.*' => 'nullable|string|in:both,admin,provider,none',
        ])->validate();

        $bookingForSubCheck = $this->booking->find($request['booking_id']);
        if ($bookingForSubCheck && $bookingForSubCheck->provider_id) {
            foreach ($request['service_ids'] as $service_id) {
                $service = Service::query()->find($service_id);
                if (!$service) {
                    throw ValidationException::withMessages(['service_ids' => [translate('Invalid service')]]);
                }
                $isSubscribed = SubscribedService::query()
                    ->where('provider_id', $bookingForSubCheck->provider_id)
                    ->where('sub_category_id', $service->sub_category_id)
                    ->where('is_subscribed', 1)
                    ->exists();
                if (!$isSubscribed) {
                    throw ValidationException::withMessages([
                        'service_ids' => [translate('Provider is not subscribed to this category')],
                    ]);
                }
            }
        }

        $bookingDetailIds = $request->input('booking_detail_ids', []);
        $zoneId = $request['zone_id'];
        $useDetailIds = !empty($bookingDetailIds) && count($bookingDetailIds) === count($request['service_ids']);

        if ($useDetailIds) {
            foreach ($request['service_ids'] as $key => $service_id) {
                $variant_key = $request['variant_keys'][$key] ?? null;
                $quantity = (int)($request['qty'][$key] ?? 0);
                $detail_id = isset($bookingDetailIds[$key]) && $bookingDetailIds[$key] !== '' && $bookingDetailIds[$key] !== null
                    ? $bookingDetailIds[$key]
                    : null;

                if ($detail_id) {
                    $detail = $this->bookingDetails->where('id', $detail_id)->where('booking_id', $request['booking_id'])->first();
                    if (!$detail) {
                        continue;
                    }
                    if ($quantity === 0) {
                        $request->merge([
                            'service_id' => $detail->service_id,
                            'variant_key' => $detail->variant_key,
                            'quantity' => 0,
                        ]);
                        $this->remove_service_from_booking($request);
                        continue;
                    }
                    $serviceOrVariantChanged = $detail->service_id !== $service_id || $detail->variant_key !== $variant_key;
                    if ($serviceOrVariantChanged) {
                        $this->updateDetailServiceAndVariation($detail, $service_id, $variant_key, $quantity, $zoneId);
                        continue;
                    }
                    if ($detail->quantity !== $quantity) {
                        $request->merge([
                            'service_id' => $service_id,
                            'variant_key' => $variant_key,
                            'old_quantity' => $detail->quantity,
                            'new_quantity' => $quantity,
                        ]);
                        if ($detail->quantity < $quantity) {
                            $this->increase_service_quantity_from_booking($request);
                        } else {
                            $this->decrease_service_quantity_from_booking($request);
                        }
                    }
                    continue;
                }

                if ($quantity > 0) {
                    $request->merge([
                        'service_id' => $service_id,
                        'variant_key' => $variant_key,
                        'quantity' => $quantity,
                    ]);
                    $this->addNewBookingService($request);
                }
            }
        } else {
            $service_info = [];
            foreach ($request['service_ids'] as $key => $sid) {
                $vk = $request['variant_keys'][$key] ?? null;
                $qty = $request['qty'][$key] ?? 0;
                $service_info[] = ['service_id' => $sid, 'variant_key' => $vk, 'quantity' => $qty];
            }
            $request->merge(['service_info' => collect($service_info)]);
            $existing_services = $this->bookingDetails->where('booking_id', $request['booking_id'])->get();
            foreach ($existing_services as $item) {
                if (!$request['service_info']->where('service_id', $item->service_id)->where('variant_key', $item->variant_key)->first()) {
                    $request['service_info']->push([
                        'service_id' => $item->service_id,
                        'variant_key' => $item->variant_key,
                        'quantity' => 0,
                    ]);
                }
            }
            foreach ($request['service_info'] as $item) {
                $existing_service = $this->bookingDetails
                    ->where('booking_id', $request['booking_id'])
                    ->where('service_id', $item['service_id'])
                    ->where('variant_key', $item['variant_key'])
                    ->first();
                if (!$existing_service) {
                    if ((int)$item['quantity'] > 0) {
                        $request->merge(['service_id' => $item['service_id'], 'variant_key' => $item['variant_key'], 'quantity' => $item['quantity']]);
                        $this->addNewBookingService($request);
                    }
                } elseif ((int)$item['quantity'] === 0) {
                    $request->merge(['service_id' => $item['service_id'], 'variant_key' => $item['variant_key'], 'quantity' => 0]);
                    $this->remove_service_from_booking($request);
                } elseif ($existing_service->quantity < (int)$item['quantity']) {
                    $request->merge([
                        'service_id' => $item['service_id'],
                        'variant_key' => $item['variant_key'],
                        'old_quantity' => $existing_service->quantity,
                        'new_quantity' => (int)$item['quantity'],
                    ]);
                    $this->increase_service_quantity_from_booking($request);
                } elseif ($existing_service->quantity > (int)$item['quantity']) {
                    $request->merge([
                        'service_id' => $item['service_id'],
                        'variant_key' => $item['variant_key'],
                        'old_quantity' => $existing_service->quantity,
                        'new_quantity' => (int)$item['quantity'],
                    ]);
                    $this->decrease_service_quantity_from_booking($request);
                }
            }
        }

        DB::transaction(function () use ($request) {
            $this->syncAdminBookingLinePricingFromAdminForm($request);
        });

        $bookingAfter = $this->booking->find($request['booking_id']);
        if ($bookingAfter && !(int) ($bookingAfter->is_repeated ?? 0)) {
            recalculate_and_apply_booking_additional_charges($bookingAfter);
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException|AuthorizationException
     */
    public function updateRepeatBookingService(Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        Validator::make($request->all(), [
            'qty' => 'required|array',
            'qty.*' => 'int',
            'service_ids' => 'required|array',
            'service_ids.*' => 'uuid',
            'variant_keys' => 'required|array',
            'variant_keys.*' => 'string',
            'zone_id' => 'required|uuid',
            'booking_id' => 'required|uuid',
        ])->validate();

        $service_info = [];
        foreach ($request['service_ids'] as $key => $service_id) {
            $variant_key = $request['variant_keys'][$key] ?? null;
            $quantity = $request['qty'][$key] ?? 0;

            $service_info[] = [
                'service_id' => $service_id,
                'variant_key' => $variant_key,
                'quantity' => $quantity,
            ];
        }
        $request->merge(['service_info' => collect($service_info)]);
        $booking = $this->bookingRepeat
            ->with('detail')
            ->where('id', $request['booking_repeat_id'])->first();

        $totalOldQuantity = 0;
        $totalNewQuantity = 0;
        $updateQuantity = [];

        foreach ($request['service_info'] as $key => $item) {
            $existingService = $this->bookingRepeatDetail
                ->where('booking_repeat_id', $request['booking_repeat_id'])
                ->where('service_id', $item['service_id'])
                ->where('variant_key', $item['variant_key'])
                ->first();

            if ($existingService) {
                $totalOldQuantity += $existingService->quantity;

                if ($existingService->quantity < $item['quantity']) {
                    $request['service_id'] = $item['service_id'];
                    $request['variant_key'] = $item['variant_key'];
                    $request['old_quantity'] = $existingService->quantity;
                    $request['new_quantity'] = (int)$item['quantity'];

                    $this->increase_service_quantity_from_booking_repeat($request);
                } else if ($existingService->quantity > $item['quantity']) {
                    $request['service_id'] = $item['service_id'];
                    $request['variant_key'] = $item['variant_key'];
                    $request['old_quantity'] = $existingService->quantity;
                    $request['new_quantity'] = (int)$item['quantity'];

                    $this->decrease_service_quantity_from_booking_repeat($request);
                }

                $totalNewQuantity += (int)$item['quantity'];
                $updateQuantity[] = [
                    'service_id' => $item['service_id'],
                    'quantity' => (int)$item['quantity'],
                    'variant_key' => $item['variant_key'],
                    'service_name' => $existingService->service_name,
                    'service_cost' => $existingService->service_cost,
                ];
            }
        }

        if ($request['next_all_booking_change'] == 1){
            $mainBooking = $this->booking->where('id', $request['booking_id'])->first();
            $sourceRepeatBooking = $this->bookingRepeat->where('id', $request['booking_repeat_id'])->first();
            $serviceFee = 0;

            if (Str::endsWith($sourceRepeatBooking->readable_id, '-A') && !Str::endsWith($sourceRepeatBooking->readable_id, '-AA')) {
                $serviceFee = $sourceRepeatBooking->extra_fee;
            }

            $targetRepeatBookings = $this->bookingRepeat
                ->where('booking_id', $request['booking_id'])
                ->whereIn('booking_status', ['accepted', 'ongoing'])
                ->where('id', '!=', $sourceRepeatBooking ? $sourceRepeatBooking->id : null)
                ->orderBy('readable_id')
                ->get();

            if ($sourceRepeatBooking) {
                $targetRepeatBookingsWithSource = new Collection($targetRepeatBookings->toArray());

                $targetRepeatBookingsWithSource->push($sourceRepeatBooking);
                $sortedReadableIds = $targetRepeatBookingsWithSource->pluck('readable_id')->sort()->values();
                $minReadableId = $sortedReadableIds->first();
                $maxReadableId = $sortedReadableIds->last();

                if ($totalOldQuantity != $totalNewQuantity) {
                    foreach ($updateQuantity as $key => $update) {
                        $existService = $this->bookingRepeatDetail
                            ->where('booking_repeat_id', $request['booking_repeat_id'])
                            ->where('service_id', $update['service_id'])
                            ->first();

                        if ($existService) {
                            $updateQuantity[$key]['discount_amount'] = $existService->discount_amount;
                            $updateQuantity[$key]['tax_amount'] = $existService->tax_amount;
                            $updateQuantity[$key]['total_cost'] = $existService->total_cost;
                            $updateQuantity[$key]['repeat_details_id'] = $existService->id;
                        }
                    }
                    $bookingRepeatHistory = $this->bookingRepeatHistory;
                    $bookingRepeatHistory->booking_id = $request['booking_id'];
                    $bookingRepeatHistory->booking_repeat_id = $request['booking_repeat_id'];
                    $bookingRepeatHistory->old_quantity = $totalOldQuantity;
                    $bookingRepeatHistory->new_quantity = $totalNewQuantity;
                    $bookingRepeatHistory->is_multiple = $request['next_all_booking_change'] ? 1 : 0;
                    $bookingRepeatHistory->readable_id = "$minReadableId - $maxReadableId";
                    $bookingRepeatHistory->log_details = json_encode($updateQuantity);
                    $bookingRepeatHistory->total_booking_amount = $sourceRepeatBooking->total_booking_amount - $serviceFee;
                    $bookingRepeatHistory->total_tax_amount = $sourceRepeatBooking->total_tax_amount;
                    $bookingRepeatHistory->total_discount_amount = $sourceRepeatBooking->total_discount_amount;
                    $bookingRepeatHistory->extra_fee = $sourceRepeatBooking->extra_fee;
                    $bookingRepeatHistory->save();
                }

                foreach ($targetRepeatBookings as $targetBooking) {
                    $targetBooking->total_booking_amount = $sourceRepeatBooking->total_booking_amount - $serviceFee;
                    $targetBooking->total_tax_amount = $sourceRepeatBooking->total_tax_amount;
                    $targetBooking->total_discount_amount = $sourceRepeatBooking->total_discount_amount;
                    $targetBooking->total_campaign_discount_amount = $sourceRepeatBooking->total_campaign_discount_amount;
                    $targetBooking->save();
                }

                foreach ($sourceRepeatBooking->detail as $sourceDetail) {
                    foreach ($targetRepeatBookings as $targetBooking) {
                        foreach ($targetBooking->detail as $targetDetail) {
                            $targetDetail->quantity = $sourceDetail->quantity;
                            $targetDetail->tax_amount = $sourceDetail->tax_amount;
                            $targetDetail->total_cost = $sourceDetail->total_cost;
                            $targetDetail->discount_amount = $sourceDetail->discount_amount;
                            $targetDetail->campaign_discount_amount = $sourceDetail->campaign_discount_amount;
                            $targetDetail->overall_coupon_discount_amount = 0;
                            $targetDetail->save();
                        }
                    }
                }

                foreach ($sourceRepeatBooking->details_amounts as $sourceAmount) {
                    foreach ($targetRepeatBookings as $targetBooking) {
                        foreach ($targetBooking->details_amounts as $targetAmount) {
                            $targetAmount->service_quantity = $sourceAmount->service_quantity;
                            $targetAmount->service_tax = $sourceAmount->service_tax;
                            $targetAmount->coupon_discount_by_admin = 0;
                            $targetAmount->coupon_discount_by_provider = 0;
                            $targetAmount->discount_by_admin = $sourceAmount->discount_by_admin;
                            $targetAmount->discount_by_provider = $sourceAmount->discount_by_provider;
                            $targetAmount->campaign_discount_by_admin = $sourceAmount->campaign_discount_by_admin;
                            $targetAmount->campaign_discount_by_provider = $sourceAmount->campaign_discount_by_provider;
                            $targetAmount->discount_cost_bearer = DiscountCostBearer::normalize($sourceAmount->discount_cost_bearer ?? null);
                            $targetAmount->save();
                        }
                    }
                }
            }


            $mainBooking->total_booking_amount = $targetRepeatBookings->sum('total_booking_amount') + $sourceRepeatBooking->total_booking_amount;
            $mainBooking->total_tax_amount = $targetRepeatBookings->sum('total_tax_amount') + $sourceRepeatBooking->total_tax_amount;
            $mainBooking->total_discount_amount = $targetRepeatBookings->sum('total_discount_amount') + $sourceRepeatBooking->total_discount_amount;
            $mainBooking->total_campaign_discount_amount = $targetRepeatBookings->sum('total_campaign_discount_amount') + $sourceRepeatBooking->total_campaign_discount_amount;
            $mainBooking->save();

        }else{
            $mainBooking = $this->booking->where('id', $request['booking_id'])->first();
            $sourceRepeatBooking = $this->bookingRepeat->where('id', $request['booking_repeat_id'])->first();
            $repeatBooking = $this->bookingRepeat->where('booking_id', $request['booking_id'])->get();

            $mainBooking->total_booking_amount = $repeatBooking->sum('total_booking_amount');
            $mainBooking->total_tax_amount = $repeatBooking->sum('total_tax_amount');
            $mainBooking->total_discount_amount = $repeatBooking->sum('total_discount_amount');
            $mainBooking->total_campaign_discount_amount = $repeatBooking->sum('total_campaign_discount_amount');
            $mainBooking->save();

            if ($totalOldQuantity != $totalNewQuantity) {
                foreach ($updateQuantity as $key => $update) {
                    $existService = $this->bookingRepeatDetail
                        ->where('booking_repeat_id', $request['booking_repeat_id'])
                        ->where('service_id', $update['service_id'])
                        ->first();

                    if ($existService) {
                        $updateQuantity[$key]['discount_amount'] = $existService->discount_amount;
                        $updateQuantity[$key]['tax_amount'] = $existService->tax_amount;
                        $updateQuantity[$key]['total_cost'] = $existService->total_cost;
                        $updateQuantity[$key]['repeat_details_id'] = $existService->id;
                    }
                }

                $bookingRepeatHistory = $this->bookingRepeatHistory;
                $bookingRepeatHistory->booking_id = $request['booking_id'];
                $bookingRepeatHistory->booking_repeat_id = $request['booking_repeat_id'];
                $bookingRepeatHistory->old_quantity = $totalOldQuantity;
                $bookingRepeatHistory->new_quantity = $totalNewQuantity;
                $bookingRepeatHistory->is_multiple = $request['next_all_booking_change'] ? 1 : 0;
                $bookingRepeatHistory->readable_id = $booking->readable_id;
                $bookingRepeatHistory->log_details = json_encode($updateQuantity);
                $bookingRepeatHistory->total_booking_amount = $sourceRepeatBooking->total_booking_amount;
                $bookingRepeatHistory->total_tax_amount = $sourceRepeatBooking->total_tax_amount;
                $bookingRepeatHistory->total_discount_amount = $sourceRepeatBooking->total_discount_amount;
                $bookingRepeatHistory->extra_fee = $sourceRepeatBooking->extra_fee;
                $bookingRepeatHistory->save();
            }
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }


    public function verifyOfflinePayment(Request $request)
    {
        $this->authorize('booking_can_manage_status');

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $booking = $this->booking->find($request['booking_id']);

        if (!$booking) {
            return response()->json(response_formatter(DEFAULT_404, 'Booking not found'), 404);
        }

        // Update booking payment status
        $isApproved = $request->payment_status == 'approved';
        $booking->is_paid = $isApproved ? 1 : 0;
        $booking->save();

        // Update offline payment status
        $offlinePayment = $booking->booking_offline_payments?->first();
        if ($offlinePayment) {
            $offlinePayment->payment_status = $request->payment_status;
            $offlinePayment->denied_note = !$isApproved ? ($request->note ?? null) : null;
            $offlinePayment->save();
        }

        // Handle notifications and transactions for approved payments
        if ($isApproved) {
            $user = $booking->customer;
            $offline = isNotificationActive(null, 'booking', 'notification', 'user');
            $title = get_push_notification_message('offline_payment_approved', 'customer_notification', $user?->current_language_key);
            if ($user?->fcm_token && $title && $offline) {
                device_notification($user?->fcm_token, $title, null, null, $booking->id, 'booking', null, $user->id);
            }

            placeBookingTransactionForDigitalPayment($booking);

            return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }

    /**
     * Admin: add a payment to a booking (e.g. before marking complete).
     * Fields: amount, received_by (company|provider — who collected cash), transaction_id (required if received_by=company), date (default today).
     * Loss-making (scaled): also split_amount_provider + split_amount_company = amount — economic split for loss/settlement (provider vs company share of recovery).
     */
    public function addPayment(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $this->authorize('booking_can_manage_status');
        $allowedInflow = AdminCompanyInflowPaymentService::allowedAdvanceMethodKeys();

        $preValidator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
        ]);
        if ($preValidator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($preValidator)), 400);
            }
            Toastr::error(implode(' ', $preValidator->errors()->all()));

            return back();
        }

        $booking = $this->booking->with('booking_partial_payments')->find($id);
        if (!$booking) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_404, 'Booking not found'), 404);
            }
            Toastr::error(translate('Booking not found'));
            return back();
        }

        $status = strtolower(trim((string) ($booking->booking_status ?? '')));
        $isScaledSettlement = $booking->isLossMakingFinancialSettlement();
        $remainingCap = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        $allowAddPaymentByStatus = $status === 'ongoing'
            || ($status === 'on_hold' && booking_on_hold_is_after_visit_from_ongoing($booking))
            || ($isScaledSettlement && $status === 'completed' && $remainingCap > 0.009);
        if (! $allowAddPaymentByStatus) {
            $msg = $isScaledSettlement
                ? translate('Add_payment_only_while_ongoing_or_loss_making_pending')
                : translate('Add_payment_only_while_booking_ongoing');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['booking_status' => [$msg]]), 400);
            }
            Toastr::error($msg);

            return back();
        }

        $rules = [
            'received_by' => 'required|in:company,provider',
        ];
        if ($request->input('received_by') === 'company') {
            if ($allowedInflow === []) {
                $msg = translate('No_active_payment_methods_for_advance');
                if ($request->wantsJson()) {
                    return response()->json(response_formatter(DEFAULT_400, null, ['advance_payment_method' => [$msg]]), 400);
                }
                Toastr::error($msg);

                return back();
            }
            $rules['advance_payment_method'] = ['required', 'string', Rule::in($allowedInflow)];
            $rules['advance_transaction_id'] = ['nullable', 'string', 'max:191'];
            $rules['advance_method_fields'] = ['nullable', 'array'];
            $rules['advance_method_fields.*'] = ['nullable', 'string', 'max:2000'];
            $rules['company_inflow_note'] = ['nullable', 'string', 'max:2000'];
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
            }
            Toastr::error(implode(' ', $validator->errors()->all()));

            return back();
        }
        if ($request->input('received_by') === 'company') {
            $choice = (string) $request->input('advance_payment_method');
            AdminCompanyInflowPaymentService::validateAdvanceFollowUp($request, $choice);
        }

        $isScaledSettlement = $booking->isLossMakingFinancialSettlement();
        $splitProvider = 0.0;
        $splitCompany = 0.0;
        if ($isScaledSettlement) {
            $splitValidator = Validator::make($request->all(), [
                'split_amount_provider' => 'required|numeric|min:0',
                'split_amount_company' => 'required|numeric|min:0',
            ]);
            if ($splitValidator->fails()) {
                if ($request->wantsJson()) {
                    return response()->json(response_formatter(DEFAULT_400, null, error_processor($splitValidator)), 400);
                }
                Toastr::error(implode(' ', $splitValidator->errors()->all()));

                return back();
            }
            $amountRounded = round((float) $request->input('amount'), 2);
            $splitProvider = round((float) $request->input('split_amount_provider'), 2);
            $splitCompany = round((float) $request->input('split_amount_company'), 2);
            if (round($splitProvider + $splitCompany, 2) !== $amountRounded) {
                $msg = translate('Bfs_add_payment_split_must_equal_total');
                if ($request->wantsJson()) {
                    return response()->json(response_formatter(DEFAULT_400, null, ['split_amount_provider' => [$msg]]), 400);
                }
                Toastr::error($msg);

                return back();
            }
            if ($splitProvider < 0.01 && $splitCompany < 0.01) {
                $msg = translate('Bfs_add_payment_split_need_positive_segment');
                if ($request->wantsJson()) {
                    return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$msg]]), 400);
                }
                Toastr::error($msg);

                return back();
            }
            $recoveryCaps = booking_admin_loss_recovery_split_caps($booking);
            if ($recoveryCaps !== null) {
                $sumCaps = round($recoveryCaps['provider'] + $recoveryCaps['company'], 2);
                if ($amountRounded > $sumCaps + 0.02) {
                    $msg = __('lang.Bfs_add_payment_amount_exceeds_remaining_loss_to_tag', [
                        'amount' => with_currency_symbol($amountRounded),
                        'max' => with_currency_symbol($sumCaps),
                    ]);
                    if ($request->wantsJson()) {
                        return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$msg]]), 400);
                    }
                    Toastr::error($msg);

                    return back();
                }
                if ($splitProvider > $recoveryCaps['provider'] + 0.02) {
                    $msg = __('lang.Bfs_add_payment_split_exceeds_provider_loss_cap', [
                        'max' => with_currency_symbol($recoveryCaps['provider']),
                    ]);
                    if ($request->wantsJson()) {
                        return response()->json(response_formatter(DEFAULT_400, null, ['split_amount_provider' => [$msg]]), 400);
                    }
                    Toastr::error($msg);

                    return back();
                }
                if ($splitCompany > $recoveryCaps['company'] + 0.02) {
                    $msg = __('lang.Bfs_add_payment_split_exceeds_company_loss_cap', [
                        'max' => with_currency_symbol($recoveryCaps['company']),
                    ]);
                    if ($request->wantsJson()) {
                        return response()->json(response_formatter(DEFAULT_400, null, ['split_amount_company' => [$msg]]), 400);
                    }
                    Toastr::error($msg);

                    return back();
                }
            }
        }

        $bookingTotal = get_booking_total_amount($booking);
        $totalPaid = get_booking_total_paid($booking);

        $useBfsScaledLossCap = $request->boolean('bfs_scaled_loss_cap');
        $useBfsVisitRetainedCap = ! $useBfsScaledLossCap
            && ($request->boolean('bfs_decided_charges_cap') || $request->boolean('bfs_visit_retained_cap'));
        $retainedCap = null;
        $payableCapForPartialDue = get_booking_payable_total_for_partial_dues($booking);

        if ($useBfsScaledLossCap) {
            $request->validate([
                'scaled_customer_paid_amount' => 'required|numeric|min:0',
                'bfs_settlement_outcome' => 'required|string|in:' . BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS,
            ]);
            $declaredPaid = round(min($bookingTotal, max(0.0, (float) $request->input('scaled_customer_paid_amount'))), 2);
            $dueAmount = round(max(0.0, $declaredPaid - $totalPaid), 2);
            $payableCapForPartialDue = $declaredPaid;
        } elseif ($useBfsVisitRetainedCap) {
            $request->validate([
                'visit_charges_paid' => 'required|numeric|min:0',
                'closing_amount_paid' => 'nullable|numeric|min:0',
                'bfs_settlement_outcome' => 'nullable|string|in:' . BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT . ',' . BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL,
            ]);
            $capConfig = ['visit_charges_paid' => (float) $request->input('visit_charges_paid')];
            if ($request->filled('closing_amount_paid')) {
                $capConfig['closing_amount_paid'] = (float) $request->input('closing_amount_paid');
            }
            $settlementService = app(BookingFinancialSettlementService::class);
            $bookingForCap = $booking;
            if ($request->filled('bfs_settlement_outcome')) {
                $bookingForCap = $booking->replicate();
                $bookingForCap->id = $booking->id;
                $bookingForCap->settlement_outcome = (string) $request->input('bfs_settlement_outcome');
            }
            $retainedCap = $settlementService->resolveRetainedVisitAmount($bookingForCap, $capConfig);
            $dueAmount = round(max(0.0, $retainedCap - $totalPaid), 2);
            $payableCapForPartialDue = round((float) $retainedCap, 2);
        } else {
            $dueAmount = get_booking_admin_add_payment_remaining_amount($booking);
            $outcomeTrim = trim((string) ($booking->settlement_outcome ?? ''));
            $payableCapForPartialDue = $outcomeTrim === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
                ? round((float) $bookingTotal, 2)
                : round((float) get_booking_payable_total_for_partial_dues($booking), 2);
        }

        $amount = round((float) $request->amount, 2);
        if ($amount > $dueAmount) {
            $message = translate('Amount cannot exceed the due amount. Due amount') . ': ' . with_currency_symbol($dueAmount);
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }
        $date = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : now()->toDateString();

        $receivedBy = (string) $request->input('received_by');
        $companyInflowForWhatsApp = [
            'ledger_payment_method' => '',
            'partial_transaction_id' => '',
        ];
        if ($receivedBy === 'company') {
            $choice = (string) $request->input('advance_payment_method');
            $inflow = AdminCompanyInflowPaymentService::resolveLedgerPayloadForCompanyInflow($request, $choice);
            $companyInflowForWhatsApp = [
                'ledger_payment_method' => (string) ($inflow['ledger_payment_method'] ?? ''),
                'partial_transaction_id' => (string) ($inflow['partial_transaction_id'] ?? ''),
            ];
        } else {
            $inflow = [
                'ledger_payment_method' => 'cash_after_service',
                'partial_transaction_id' => null,
                'ledger_transaction_id' => null,
                'ledger_reference_note' => null,
            ];
        }

        $newTotalPaidAfterThis = round($totalPaid + $amount, 2);
        $dueAfterThisPayment = round(max(0.0, $payableCapForPartialDue - $newTotalPaidAfterThis), 2);

        $partial = null;
        DB::transaction(function () use ($booking, $amount, $receivedBy, $inflow, $date, $dueAfterThisPayment, $isScaledSettlement, $splitProvider, $splitCompany, &$partial) {
            $attrs = [
                'paid_with' => 'admin_entry',
                'transaction_id' => $receivedBy === 'company' ? ($inflow['partial_transaction_id'] ?? null) : null,
                'paid_amount' => $amount,
                'due_amount' => $dueAfterThisPayment,
                'received_by' => $receivedBy,
            ];
            if ($isScaledSettlement) {
                $attrs['loss_allocation_provider'] = $splitProvider;
                $attrs['loss_allocation_company'] = $splitCompany;
            }
            $partial = $booking->booking_partial_payments()->create($attrs);

            if ($receivedBy === 'company') {
                ledger_record_in([
                    'amount' => $amount,
                    'transaction_id' => $inflow['ledger_transaction_id'] ?? null,
                    'booking_id' => $booking->id,
                    'payment_method' => $inflow['ledger_payment_method'],
                    'reference_note' => $inflow['ledger_reference_note'] ?? null,
                    'date' => $date,
                    'received_by' => LedgerTransaction::RECEIVED_BY_COMPANY,
                    'created_by' => auth()->id(),
                    'booking_partial_payment_id' => $partial->id,
                ]);
            } else {
                record_cross_party_booking_partial_transaction($booking, $amount, (string) $partial->id);
            }

            $freshBooking = $booking->fresh(['booking_partial_payments']);
            $totalPaidAfter = get_booking_total_paid($freshBooking);
            $paidThroughCap = round((float) get_booking_payable_total_for_partial_dues($freshBooking), 2);
            if ($totalPaidAfter + 0.00001 >= $paidThroughCap) {
                $freshBooking->is_paid = 1;
                $freshBooking->save();
            }

            if (trim((string) ($freshBooking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                $settlementSvc = app(BookingFinancialSettlementService::class);
                $grand = round(max(0.0, get_booking_total_amount($freshBooking)), 2);
                if ($grand > 0.009) {
                    [, $lossTotal] = $settlementSvc->resolveScaledLossBreakdown(
                        $freshBooking,
                        is_array($freshBooking->settlement_config) ? $freshBooking->settlement_config : [],
                        $grand,
                        $settlementSvc->totalPaidForMainBooking($freshBooking)
                    );
                    if ($lossTotal <= 0.009) {
                        $freshBooking->allow_complete_without_full_payment = false;
                        $freshBooking->save();
                    }
                }
            }
        });

        try {
            if ($partial && class_exists(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)) {
                $fresh = $this->booking->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])->find($booking->id);
                if ($fresh) {
                    app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)->sendBookingPaymentAdded($fresh, $partial, [
                        'date' => $date,
                        'payment_method' => (string) ($companyInflowForWhatsApp['ledger_payment_method'] ?? ''),
                        'reference_id' => (string) ($companyInflowForWhatsApp['partial_transaction_id'] ?? ''),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp booking payment added failed', ['booking_id' => $booking->id ?? null, 'message' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            $payload = response_formatter(DEFAULT_UPDATE_200, null);
            $payload['message'] = translate('Payment added successfully.');
            if ($partial) {
                $payload['booking_partial_payment_id'] = (string) $partial->id;
            }

            return response()->json($payload, 200);
        }
        Toastr::success(translate('Payment added successfully.'));
        return back();
    }

    /**
     * Undo admin partial payment rows recorded from the Special financial settlement modal when the modal is closed without saving settlement.
     *
     * @return JsonResponse
     */
    public function revertFinancialSettlementModalPartialPayments(Request $request, string $id)
    {
        $this->authorize('booking_can_manage_status');

        $validated = $request->validate([
            'partial_payment_ids' => 'required|array|min:1',
            'partial_payment_ids.*' => 'required|uuid',
        ]);

        $booking = $this->booking->with('booking_partial_payments')->find($id);
        if (! $booking) {
            return response()->json(response_formatter(DEFAULT_404, null, ['booking' => [translate('Booking not found')]]), 404);
        }
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            return response()->json(response_formatter(DEFAULT_400, null, ['booking' => [translate('Financial_settlement_applies_to_single_bookings_only')]]), 400);
        }

        $status = strtolower(trim((string) ($booking->booking_status ?? '')));
        $isScaledSettlement = $booking->isLossMakingFinancialSettlement();
        $remainingCap = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        $allowAddPaymentByStatus = $status === 'ongoing'
            || ($status === 'on_hold' && booking_on_hold_is_after_visit_from_ongoing($booking))
            || ($isScaledSettlement && $status === 'completed' && $remainingCap > 0.009);
        if (! $allowAddPaymentByStatus) {
            $msg = $isScaledSettlement
                ? translate('Add_payment_only_while_ongoing_or_loss_making_pending')
                : translate('Add_payment_only_while_booking_ongoing');

            return response()->json(response_formatter(DEFAULT_400, null, ['booking_status' => [$msg]]), 400);
        }

        $ids = array_values(array_unique($validated['partial_payment_ids']));

        try {
            $this->deleteAdminEntryPartialPaymentsForBooking($booking, $ids);
        } catch (ValidationException $e) {
            return response()->json(response_formatter(DEFAULT_400, null, $e->errors()), 400);
        }

        $payload = response_formatter(DEFAULT_UPDATE_200, null);
        $payload['message'] = translate('Update_successfully');

        return response()->json($payload, 200);
    }

    /**
     * Admin: remove a mistaken "Add payment" / installment row ({@see BookingPartialPayment} with {@code paid_with} admin_entry).
     * Reverses linked cross-party {@see Transaction} rows (when the provider received cash), deletes company ledger IN rows, rewrites installment due_amount, and refreshes is_paid / loss-making flags.
     */
    public function deleteAdminPartialPayment(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $validated = $request->validate([
            'partial_payment_id' => 'required|uuid',
        ]);

        $booking = $this->booking->with('booking_partial_payments')->find($id);
        if (! $booking) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_404, null, ['booking' => [translate('Booking not found')]]), 404);
            }
            Toastr::error(translate('Booking not found'));

            return back();
        }

        if (! $this->bookingAllowsAdminPartialPaymentDeletion($booking)) {
            $msg = translate('Delete_payment_entry_not_allowed_for_this_booking');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['booking' => [$msg]]), 400);
            }
            Toastr::error($msg);

            return back();
        }

        try {
            $this->deleteAdminEntryPartialPaymentsForBooking($booking, [(string) $validated['partial_payment_id']]);
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, $e->errors()), 400);
            }
            Toastr::error(collect($e->errors())->flatten()->implode(' '));

            return back();
        }

        if ($request->wantsJson()) {
            $payload = response_formatter(DEFAULT_UPDATE_200, null);
            $payload['message'] = translate('Payment_entry_deleted_successfully');

            return response()->json($payload, 200);
        }

        Toastr::success(translate('Payment_entry_deleted_successfully'));

        return back();
    }

    /**
     * Parent bookings only; not canceled / refunded repeat children.
     */
    private function bookingAllowsAdminPartialPaymentDeletion(Booking $booking): bool
    {
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            return false;
        }
        $status = strtolower(trim((string) ($booking->booking_status ?? '')));

        return ! in_array($status, ['canceled', 'cancelled', 'refunded'], true);
    }

    /**
     * @param  list<string>  $partialPaymentIds
     *
     * @throws ValidationException
     */
    private function deleteAdminEntryPartialPaymentsForBooking(Booking $booking, array $partialPaymentIds): void
    {
        $ids = array_values(array_unique($partialPaymentIds));
        $deletionService = app(AdminBookingDeletionService::class);

        DB::transaction(function () use ($booking, $ids, $deletionService) {
            foreach ($ids as $partialId) {
                $partial = BookingPartialPayment::query()
                    ->where('booking_id', $booking->id)
                    ->whereKey($partialId)
                    ->first();
                if (! $partial) {
                    throw ValidationException::withMessages([
                        'partial_payment_id' => [translate('Invalid_request')],
                    ]);
                }
                if (($partial->paid_with ?? '') !== 'admin_entry') {
                    throw ValidationException::withMessages([
                        'partial_payment_id' => [translate('Only_admin_entered_installments_can_be_deleted')],
                    ]);
                }

                $crossParty = Transaction::query()
                    ->where('booking_id', $booking->id)
                    ->where('reference_note', 'booking_partial_payment:'.$partial->id)
                    ->get();
                if ($crossParty->isNotEmpty()) {
                    $deletionService->reverseAccountsAndDeleteTransactions($crossParty);
                }

                LedgerTransaction::query()
                    ->where('booking_id', $booking->id)
                    ->where('booking_partial_payment_id', $partial->id)
                    ->delete();

                $partial->delete();
            }

            $booking->load('booking_partial_payments');
            $this->reconcileBookingPartialPaymentDueAmounts($booking);

            $booking->refresh(['booking_partial_payments']);
            $cap = round((float) get_booking_payable_total_for_partial_dues($booking), 2);
            $totalPaid = round((float) get_booking_total_paid($booking), 2);
            if ($totalPaid + 0.00001 < $cap) {
                $booking->is_paid = 0;
                $booking->save();
            }

            if (trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                $settlementSvc = app(BookingFinancialSettlementService::class);
                $grand = round(max(0.0, get_booking_total_amount($booking)), 2);
                if ($grand > 0.009) {
                    [, $lossTotal] = $settlementSvc->resolveScaledLossBreakdown(
                        $booking,
                        is_array($booking->settlement_config) ? $booking->settlement_config : [],
                        $grand,
                        $settlementSvc->totalPaidForMainBooking($booking)
                    );
                    if ($lossTotal > 0.009) {
                        $booking->allow_complete_without_full_payment = true;
                        $booking->save();
                    }
                }
            }
        });
    }

    /**
     * After add/remove partial rows, rewrite each row's {@see BookingPartialPayment::due_amount} to cap minus cumulative paid (same rule as {@see BookingController::addPayment}).
     */
    private function reconcileBookingPartialPaymentDueAmounts(Booking $booking): void
    {
        $booking->load('booking_partial_payments');
        $cap = round((float) get_booking_payable_total_for_partial_dues($booking), 2);
        $sorted = $booking->booking_partial_payments->sortBy('created_at')->values();
        $cum = 0.0;
        foreach ($sorted as $p) {
            $paid = round((float) $p->paid_amount, 2);
            $cum = round($cum + $paid, 2);
            $due = round(max(0.0, $cap - $cum), 2);
            if (abs((float) $p->due_amount - $due) > 0.001) {
                $p->due_amount = $due;
                $p->save();
            }
        }
    }

    /**
     * Loss-making (scaled): write off remaining loss (discount/waiver), without receiving customer money.
     * This reduces remaining scaled losses in settlement preview and marks the booking as recovered for reporting.
     */
    public function writeOffScaledLoss(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->with(['booking_partial_payments', 'details_amounts'])->findOrFail($id);
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            Toastr::error(translate('Reopen_scenarios_single_booking_only'));
            return back();
        }
        if (! $booking->isLossMakingFinancialSettlement()) {
            Toastr::error(translate('Invalid_request'));
            return back();
        }

        $svc = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
        $preview = $svc->buildPreview($booking);
        // The modal's "Due Balance (Invoice)" uses the admin add-payment remaining capacity (invoice remainder),
        // which can differ from preview amount_to_collect_from_customer for scaled settlement.
        $dueFromCustomer = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        $lossCapPr = round(max(0.0, (float) ($preview['scaled_loss_provider_share'] ?? 0)), 2);
        $lossCapCo = round(max(0.0, (float) ($preview['scaled_loss_company_share'] ?? 0)), 2);
        $lossRemain = round($lossCapPr + $lossCapCo, 2);
        if ($dueFromCustomer <= 0.009 || $lossRemain <= 0.009) {
            Toastr::success(translate('Update_successfully'));
            return back();
        }

        // No split required: this is a write-off/discount (no money received), it clears remaining scaled loss
        // and settles the remaining invoice recovery amount.
        $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];

        // Write off the remaining invoice balance (no payment received) and zero the remaining scaled loss shares.
        // Track both total and per-side write-off amounts for accurate remaining-loss math when partial recovery exists.
        $cfg['scaled_loss_writeoff_amount'] = round(((float) ($cfg['scaled_loss_writeoff_amount'] ?? 0)) + $dueFromCustomer, 2);
        $cfg['scaled_loss_writeoff_company_amount'] = round(((float) ($cfg['scaled_loss_writeoff_company_amount'] ?? 0)) + $lossCapCo, 2);
        $cfg['scaled_loss_writeoff_provider_amount'] = round(((float) ($cfg['scaled_loss_writeoff_provider_amount'] ?? 0)) + $lossCapPr, 2);

        $booking->settlement_config = $cfg === [] ? null : $cfg;
        // When scaled loss is fully written off, completion no longer needs special allowance.
        $booking->allow_complete_without_full_payment = false;
        $booking->save();

        Toastr::success(translate('Update_successfully'));
        return back();
    }

    /**
     * Loss-making (scaled): revert write-off (discount/waiver) fields from settlement_config.
     * Restores remaining invoice due and scaled loss preview to their pre-writeoff state (based on recorded partials).
     */
    public function revertWriteOffScaledLoss(Request $request, string $id): RedirectResponse
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->with(['booking_partial_payments'])->findOrFail($id);
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            Toastr::error(translate('Reopen_scenarios_single_booking_only'));
            return back();
        }
        if (trim((string) ($booking->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            Toastr::error(translate('Invalid_request'));
            return back();
        }

        $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
        $hasAny = isset($cfg['scaled_loss_writeoff_amount']) || isset($cfg['scaled_loss_writeoff_company_amount']) || isset($cfg['scaled_loss_writeoff_provider_amount']);
        if (! $hasAny) {
            Toastr::success(translate('Update_successfully'));
            return back();
        }

        unset($cfg['scaled_loss_writeoff_amount'], $cfg['scaled_loss_writeoff_company_amount'], $cfg['scaled_loss_writeoff_provider_amount']);
        $booking->settlement_config = $cfg === [] ? null : $cfg;

        // Recompute scaled remaining loss to restore allow_complete_without_full_payment flag.
        $svc = app(BookingFinancialSettlementService::class);
        $grand = round(max(0.0, get_booking_total_amount($booking)), 2);
        $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];
        [, $lossTotal] = $svc->resolveScaledLossBreakdown($booking, $config, $grand, $svc->totalPaidForMainBooking($booking));
        $booking->allow_complete_without_full_payment = $lossTotal > 0.009;
        $booking->save();

        Toastr::success(translate('Update_successfully'));
        return back();
    }

    /**
     * After “Cancel After Visit Decided Charges,” the admin “Refund customer” card is hidden and the refund action is blocked;
     * settlement already defined what stays with the business vs the customer.
     */
    protected function bookingSuppressesAdminCustomerRefundCard(Booking $booking): bool
    {
        return (bool) ($booking->after_visit_cancel ?? false)
            || (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
    }

    private function financialSettlementValidOutcomes(): array
    {
        return [
            BookingFinancialSettlementService::OUTCOME_STANDARD,
            BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT,
            BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS,
            BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, float>
     */
    private function financialSettlementConfigFromValidated(array $validated): array
    {
        $config = [];
        foreach ([
            'visit_fee_company_percent',
            'visit_fee_provider_percent',
            'visit_company_amount',
            'visit_provider_amount',
            'visit_charges_paid',
            'closing_amount_paid',
            'closing_company_share',
            'closing_provider_share',
            'retained_visit_amount',
            'custom_admin_commission',
            'scaled_customer_paid_amount',
            'scaled_loss_company_amount',
            'scaled_loss_provider_amount',
        ] as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $v = $validated[$key];
            if ($v === null || $v === '') {
                continue;
            }
            $config[$key] = (float) $v;
        }

        return $config;
    }

    /**
     * @param  array<string, float>  $config
     */
    /**
     * Financial settlement configuration and save-and-complete / save-and-cancel are allowed while ongoing
     * or while on hold after visit (same hold status, resumed from ongoing).
     */
    private function financialSettlementJsonErrorUnlessOngoing(Booking $booking): ?JsonResponse
    {
        $st = (string) ($booking->booking_status ?? '');
        $allowed = $st === 'ongoing' || ($st === 'on_hold' && booking_on_hold_is_after_visit_from_ongoing($booking));
        if (! $allowed) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Bfs_financial_settlement_only_while_ongoing'),
            ]), 422);
        }

        return null;
    }

    private function assertFinancialSettlementScaledLossSplitValid(Booking $booking, string $outcome, array $config): void
    {
        if ($outcome !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return;
        }
        $booking->loadMissing('booking_partial_payments');
        $msg = app(BookingFinancialSettlementService::class)->validateScaledLossSplit($booking, $config);
        if ($msg !== null) {
            throw ValidationException::withMessages([
                'scaled_loss_company_amount' => [$msg],
                'scaled_loss_provider_amount' => [$msg],
            ]);
        }
    }

    /**
     * Decided-charges scenarios (after-visit cancel, visit-only complete) are only valid when the retained
     * amount (visit + optional closing) is strictly below the booking invoice total.
     *
     * @param  array<string, mixed>  $validated
     */
    private function assertDecidedChargesRetainedStrictlyBelowBookingTotal(Booking $booking, string $outcome, array $validated): void
    {
        if (! BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges($outcome)) {
            return;
        }

        $visit = isset($validated['visit_charges_paid']) && $validated['visit_charges_paid'] !== null && $validated['visit_charges_paid'] !== ''
            ? round(max(0.0, (float) $validated['visit_charges_paid']), 2)
            : 0.0;
        $closing = isset($validated['closing_amount_paid']) && $validated['closing_amount_paid'] !== null && $validated['closing_amount_paid'] !== ''
            ? round(max(0.0, (float) $validated['closing_amount_paid']), 2)
            : 0.0;
        $retained = round($visit + $closing, 2);
        $grand = round(max(0.0, (float) get_booking_total_amount($booking)), 2);

        if ($retained >= $grand) {
            throw ValidationException::withMessages([
                'visit_charges_paid' => [translate('Bfs_decided_charges_sum_must_be_below_booking_total')],
                'closing_amount_paid' => [translate('Bfs_decided_charges_sum_must_be_below_booking_total')],
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function financialSettlementPreviewValidationRules(): array
    {
        return [
            'settlement_outcome' => 'required|string|in:' . implode(',', $this->financialSettlementValidOutcomes()),
            'visit_fee_company_percent' => 'nullable|numeric|min:0|max:100',
            'visit_fee_provider_percent' => 'nullable|numeric|min:0|max:100',
            'visit_company_amount' => 'nullable|numeric|min:0',
            'visit_provider_amount' => 'nullable|numeric|min:0',
            'visit_charges_paid' => 'nullable|numeric|min:0',
            'closing_amount_paid' => 'nullable|numeric|min:0',
            'closing_company_share' => 'nullable|numeric|min:0',
            'closing_provider_share' => 'nullable|numeric|min:0',
            'custom_admin_commission' => 'nullable|numeric|min:0',
            'retained_visit_amount' => 'nullable|numeric|min:0',
            'scaled_customer_paid_amount' => 'nullable|numeric|min:0',
            'scaled_loss_company_amount' => 'nullable|numeric|min:0',
            'scaled_loss_provider_amount' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Preview payout / commission for the selected settlement scenario (does not save).
     */
    public function financialSettlementPreview(Request $request, string $id): JsonResponse
    {
        $this->authorize('booking_can_manage_status');
        $booking = $this->booking->find($id);
        if (! $booking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }
        if ((int) ($booking->is_repeated ?? 0) === 1) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Financial_settlement_applies_to_single_bookings_only'),
            ]), 422);
        }
        if ($r = $this->financialSettlementJsonErrorUnlessOngoing($booking)) {
            return $r;
        }

        $validated = $request->validate($this->financialSettlementPreviewValidationRules());

        $outcome = $validated['settlement_outcome'];
        $this->assertDecidedChargesRetainedStrictlyBelowBookingTotal($booking, $outcome, $validated);

        $config = $this->financialSettlementConfigFromValidated($validated);

        $bookingForScaled = $this->booking->with('booking_partial_payments')->findOrFail($id);
        $this->assertFinancialSettlementScaledLossSplitValid($bookingForScaled, $outcome, $config);

        $forPreview = Booking::query()->findOrFail($id);
        $forPreview->settlement_outcome = $outcome === BookingFinancialSettlementService::OUTCOME_STANDARD ? null : $outcome;
        $forPreview->settlement_config = $config === [] ? null : $config;

        $service = app(BookingFinancialSettlementService::class);

        return response()->json([
            'preview' => $service->buildPreview($forPreview),
        ], 200);
    }

    /**
     * Save settlement scenario for this booking (admin configures before complete / cancel).
     */
    public function financialSettlementSave(Request $request, string $id): JsonResponse
    {
        $this->authorize('booking_can_manage_status');
        $booking = $this->booking->find($id);
        if (! $booking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }
        if ((int) ($booking->is_repeated ?? 0) === 1) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Financial_settlement_applies_to_single_bookings_only'),
            ]), 422);
        }
        if ($r = $this->financialSettlementJsonErrorUnlessOngoing($booking)) {
            return $r;
        }

        $validated = $request->validate(array_merge($this->financialSettlementPreviewValidationRules(), [
            'settlement_remarks' => 'nullable|string|max:2000',
        ]));

        $outcome = $validated['settlement_outcome'];
        if ($outcome === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Bfs_use_save_and_cancel_for_after_visit'),
            ]), 422);
        }
        if ($outcome === BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Bfs_use_save_and_complete_for_visit_only'),
            ]), 422);
        }
        $config = $this->financialSettlementConfigFromValidated($validated);

        $booking->loadMissing('booking_partial_payments');
        $this->assertFinancialSettlementScaledLossSplitValid($booking, $outcome, $config);

        $service = app(BookingFinancialSettlementService::class);
        $service->saveSettlement(
            $booking,
            $outcome,
            $config,
            $validated['settlement_remarks'] ?? null
        );

        $booking->refresh();
        if ($booking->isOpenReopenTicket()) {
            $booking->reopen_completion_allowed = true;
            $booking->save();
        }

        $payload = response_formatter(DEFAULT_UPDATE_200, null);
        $payload['message'] = translate('Financial_settlement_saved');
        $payload['snapshot'] = $booking->fresh()->settlement_snapshot;

        return response()->json($payload, 200);
    }

    /**
     * Apply “Cancel after visit” settlement and cancel the booking in one step (single booking only).
     */
    public function financialSettlementSaveAndCancel(Request $request, string $id): JsonResponse
    {
        $this->authorize('booking_can_manage_status');
        $booking = $this->booking->find($id);
        if (! $booking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }
        if ((int) ($booking->is_repeated ?? 0) === 1) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Financial_settlement_applies_to_single_bookings_only'),
            ]), 422);
        }
        if ($r = $this->financialSettlementJsonErrorUnlessOngoing($booking)) {
            return $r;
        }

        $validated = $request->validate(array_merge($this->financialSettlementPreviewValidationRules(), [
            'settlement_outcome' => 'required|string|in:' . BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL,
            'booking_cancellation_reason_id' => [
                'required',
                Rule::exists('booking_cancellation_reasons', 'id')->where(fn ($q) => $q->where('is_active', 1)),
            ],
        ]));

        $this->assertDecidedChargesRetainedStrictlyBelowBookingTotal($booking, $validated['settlement_outcome'], $validated);

        $config = $this->financialSettlementConfigFromValidated($validated);
        $service = app(BookingFinancialSettlementService::class);

        $bookingForDue = $this->booking->with('booking_partial_payments')->findOrFail($booking->id);
        $bookingForDue->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
        $bookingForDue->settlement_config = $config === [] ? null : $config;
        $amountStillDue = round((float) ($service->buildPreview($bookingForDue)['amount_to_collect_from_customer'] ?? 0), 2);
        if ($amountStillDue > 0) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Bfs_collect_payment_before_cancel'),
            ]), 422);
        }

        DB::transaction(function () use ($booking, $config, $validated, $request, $service) {
            $booking->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
            $booking->settlement_config = $config === [] ? null : $config;
            $booking->settlement_remarks = null;
            $booking->allow_complete_without_full_payment = false;
            $booking->after_visit_cancel = true;
            $booking->settlement_snapshot = $service->buildPreview($booking);
            $booking->booking_status = 'canceled';
            $booking->save();

            $this->logBookingStatusHistory(
                null,
                'canceled',
                (string) $request->user()->id,
                $booking->id,
                (int) $validated['booking_cancellation_reason_id'],
                null,
                null
            );
        });

        $payload = response_formatter(DEFAULT_UPDATE_200, null);
        $payload['message'] = translate('Bfs_save_and_cancel_success');
        $payload['snapshot'] = $booking->fresh()->settlement_snapshot;

        return response()->json($payload, 200);
    }

    /**
     * Apply financial settlement and mark the booking completed in one step: visit-only decided charges, or loss-making (scaled) partial pay (single booking only).
     */
    public function financialSettlementSaveAndComplete(Request $request, string $id): JsonResponse
    {
        $this->authorize('booking_can_manage_status');
        $booking = $this->booking->find($id);
        if (! $booking) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }
        if ((int) ($booking->is_repeated ?? 0) === 1) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => translate('Financial_settlement_applies_to_single_bookings_only'),
            ]), 422);
        }
        if ($r = $this->financialSettlementJsonErrorUnlessOngoing($booking)) {
            return $r;
        }

        $validated = $request->validate(array_merge($this->financialSettlementPreviewValidationRules(), [
            'settlement_outcome' => 'required|string|in:' . BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT . ',' . BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS,
            'settlement_remarks' => 'nullable|string|max:2000',
        ]));

        $outcome = $validated['settlement_outcome'];
        $this->assertDecidedChargesRetainedStrictlyBelowBookingTotal($booking, $outcome, $validated);

        $config = $this->financialSettlementConfigFromValidated($validated);
        $service = app(BookingFinancialSettlementService::class);

        $remarks = isset($validated['settlement_remarks']) && is_string($validated['settlement_remarks'])
            ? trim($validated['settlement_remarks']) : null;
        if ($remarks === '') {
            $remarks = null;
        }

        $successMessage = translate('Bfs_save_and_complete_success');

        if ($outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $booking->loadMissing('booking_partial_payments');
            $this->assertFinancialSettlementScaledLossSplitValid($booking, $outcome, $config);

            $bookingForComplete = $this->booking->with('booking_partial_payments')->findOrFail($booking->id);
            $bookingForComplete->settlement_outcome = $outcome;
            $bookingForComplete->settlement_config = $config === [] ? null : $config;

            if (! booking_can_be_completed($bookingForComplete)) {
                return response()->json(response_formatter([
                    'response_code' => 'default_400',
                    'message' => translate('Booking cannot be completed until full payment is received.'),
                ]), 422);
            }

            try {
                DB::transaction(function () use ($booking, $config, $remarks, $request, $service) {
                    $booking->booking_status = 'completed';
                    $service->saveSettlement(
                        $booking,
                        BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS,
                        $config,
                        $remarks
                    );

                    $this->logBookingStatusHistory(
                        null,
                        'completed',
                        (string) $request->user()->id,
                        $booking->id,
                        null,
                        null,
                        null
                    );
                });
            } catch (\RuntimeException $e) {
                return response()->json(response_formatter([
                    'response_code' => 'default_400',
                    'message' => $e->getMessage(),
                ]), 422);
            }

            $successMessage = translate('Bfs_save_and_complete_loss_making_success');
        } else {
            $bookingForDue = $this->booking->with('booking_partial_payments')->findOrFail($booking->id);
            $bookingForDue->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
            $bookingForDue->settlement_config = $config === [] ? null : $config;
            $amountStillDue = round((float) ($service->buildPreview($bookingForDue)['amount_to_collect_from_customer'] ?? 0), 2);
            if ($amountStillDue > 0) {
                return response()->json(response_formatter([
                    'response_code' => 'default_400',
                    'message' => translate('Bfs_collect_payment_before_complete'),
                ]), 422);
            }

            if (! booking_can_be_completed($bookingForDue)) {
                return response()->json(response_formatter([
                    'response_code' => 'default_400',
                    'message' => translate('Booking cannot be completed until full payment is received.'),
                ]), 422);
            }

            try {
                DB::transaction(function () use ($booking, $config, $validated, $request, $service) {
                    $booking->settlement_outcome = BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
                    $booking->settlement_config = $config === [] ? null : $config;
                    $booking->settlement_remarks = isset($validated['settlement_remarks']) && is_string($validated['settlement_remarks'])
                        ? trim($validated['settlement_remarks']) : null;
                    if ($booking->settlement_remarks === '') {
                        $booking->settlement_remarks = null;
                    }
                    $booking->allow_complete_without_full_payment = false;
                    $booking->settlement_snapshot = $service->buildPreview($booking);
                    $booking->booking_status = 'completed';
                    $booking->save();

                    $this->logBookingStatusHistory(
                        null,
                        'completed',
                        (string) $request->user()->id,
                        $booking->id,
                        null,
                        null,
                        null
                    );
                });
            } catch (\RuntimeException $e) {
                return response()->json(response_formatter([
                    'response_code' => 'default_400',
                    'message' => $e->getMessage(),
                ]), 422);
            }
        }

        $payload = response_formatter(DEFAULT_UPDATE_200, null);
        $payload['message'] = $successMessage;
        $payload['snapshot'] = $booking->fresh()->settlement_snapshot;

        return response()->json($payload, 200);
    }

    /**
     * Admin: refund customer for a canceled booking. Max refund = amount paid by customer.
     * Records an OUT transaction (refund) and sets booking status to refunded.
     */
    public function refund(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $this->authorize('booking_can_manage_status');
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'required|string|max:100',
            'reference_note' => 'nullable|string|max:2000',
            'date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
            }
            Toastr::error(implode(' ', $validator->errors()->all()));
            return back();
        }

        $booking = $this->booking->find($id);
        if (!$booking) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_404, 'Booking not found'), 404);
            }
            Toastr::error(translate('Booking not found'));
            return back();
        }

        if (! in_array((string) $booking->booking_status, ['canceled', 'cancelled', 'refunded'], true)) {
            $message = translate('Refund is only available for canceled bookings.');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        if ($this->bookingSuppressesAdminCustomerRefundCard($booking)) {
            $message = translate('Bfs_refund_not_available_after_visit_cancel');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        $refundTotals = get_booking_refund_display_totals($booking);
        $remainingRefundable = round((float) ($refundTotals['refundable_remaining'] ?? 0), 2);
        if ($remainingRefundable <= 0) {
            $message = translate('This booking has already been fully refunded.');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        $amount = round((float) $request->amount, 2);
        if ($amount > $remainingRefundable) {
            $message = translate('Refund amount cannot exceed amount paid by customer. Max') . ': ' . with_currency_symbol($remainingRefundable);
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        if ($amount < 0.01) {
            $message = translate('Refund amount must be greater than zero.');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        $transactionId = AdminCompanyInflowPaymentService::truncateLedgerTransactionIdField(trim((string) $request->transaction_id));
        $refundNote = trim((string) $request->input('reference_note', ''));
        $date = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : now()->toDateString();

        $ledgerAmountToRecord = $amount;

        $fullyRefundedAfter = false;
        DB::transaction(function () use ($booking, $ledgerAmountToRecord, $transactionId, $refundNote, $date, &$fullyRefundedAfter) {
            ledger_record_out([
                'amount' => $ledgerAmountToRecord,
                'transaction_id' => $transactionId !== '' ? $transactionId : null,
                'booking_id' => $booking->id,
                'reason' => LedgerTransaction::REASON_REFUND,
                'date' => $date,
                'reference_note' => $refundNote !== '' ? $refundNote : null,
            ]);

            $booking->refresh();
            $afterTotals = get_booking_refund_display_totals($booking);
            $fullyRefundedAfter = round((float) ($afterTotals['refundable_remaining'] ?? 0), 2) <= 0;

            if ($fullyRefundedAfter) {
                if ((string) $booking->booking_status !== 'refunded') {
                    $booking->booking_status = 'refunded';
                    $booking->save();
                    $this->logBookingStatusHistory(null, 'refunded', request()->user()->id, $booking->id);
                }
            } else {
                $booking->booking_status = 'canceled';
                $booking->save();
            }
        });

        if ($request->wantsJson()) {
            return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
        }
        $successMessage = $fullyRefundedAfter
            ? translate('Refund recorded successfully. Booking status updated to Refunded.')
            : translate('Refund recorded successfully. You can record another refund until the balance is zero.');
        Toastr::success($successMessage);
        return back();
    }

    /**
     * Admin: add compensation to a completed booking.
     *
     * Types:
     * - company_to_customer: ledger + transactions (company ↔ customer)
     * - company_to_provider: ledger + transactions (company ↔ provider)
     * - provider_to_customer: transactions only (no ledger; customer ↔ provider)
     */
    public function addCompensation(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $this->authorize('booking_view');

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:company_to_customer,company_to_provider,provider_to_customer',
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'required|string|max:100',
            'reference_note' => 'nullable|string|max:2000',
            'date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
            }
            Toastr::error(implode(' ', $validator->errors()->all()));
            return back();
        }

        /** @var \Modules\BookingModule\Entities\Booking|null $booking */
        $booking = $this->booking->with(['customer', 'provider.owner.account'])->find($id);
        if (! $booking) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_404, translate('No_data_available')), 404);
            }
            Toastr::error(translate('No_data_available'));
            return back();
        }

        if ((string) ($booking->booking_status ?? '') !== 'completed') {
            $msg = translate('Compensation is only available for completed bookings.');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['type' => [$msg]]), 400);
            }
            Toastr::error($msg);
            return back();
        }

        if ($booking->blocksAdminCommissionOverrideAndCompensation()) {
            $msg = translate('Bfs_commission_override_and_compensation_not_for_special_settlement');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['type' => [$msg]]), 400);
            }
            Toastr::error($msg);

            return back();
        }

        $type = (string) $request->type;
        $amount = round((float) $request->amount, 2);
        $transactionId = \Modules\BookingModule\Services\AdminCompanyInflowPaymentService::truncateLedgerTransactionIdField(trim((string) $request->transaction_id));
        $note = trim((string) $request->input('reference_note', ''));
        $date = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : now()->toDateString();

        $admin_user_id = \Modules\UserManagement\Entities\User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;

        DB::transaction(function () use ($booking, $type, $amount, $transactionId, $note, $date, $admin_user_id) {
            $comp = \Modules\BookingModule\Entities\BookingCompensation::create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'provider_id' => $booking->provider_id,
                'from_party' => str_starts_with($type, 'company_') ? \Modules\BookingModule\Entities\BookingCompensation::PARTY_COMPANY : \Modules\BookingModule\Entities\BookingCompensation::PARTY_PROVIDER,
                'to_party' => str_ends_with($type, '_customer') ? \Modules\BookingModule\Entities\BookingCompensation::PARTY_CUSTOMER : \Modules\BookingModule\Entities\BookingCompensation::PARTY_PROVIDER,
                'amount' => $amount,
                'transaction_id' => $transactionId !== '' ? $transactionId : null,
                'reference_note' => $note !== '' ? $note : null,
                'date' => $date,
                'created_by' => auth()->check() ? auth()->id() : null,
            ]);

            if ($type === 'company_to_customer') {
                ledger_record_out([
                    'amount' => $amount,
                    'transaction_id' => $transactionId !== '' ? $transactionId : null,
                    'booking_id' => $booking->id,
                    'reason' => \Modules\TransactionModule\Entities\LedgerTransaction::REASON_COMPENSATION,
                    'date' => $date,
                    'reference_note' => $note !== '' ? $note : 'booking_compensation:' . $comp->id,
                ]);

                $adminAccount = \Modules\TransactionModule\Entities\Account::where('user_id', $admin_user_id)->lockForUpdate()->first();
                if ($adminAccount && (float) $adminAccount->balance_pending >= $amount) {
                    $adminAccount->balance_pending -= $amount;
                    $adminAccount->save();
                }

                $primary_transaction = \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => null,
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_OUT,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $adminAccount?->balance_pending ?? 0,
                    'from_user_id' => $admin_user_id,
                    'to_user_id' => $admin_user_id,
                    'from_user_account' => ACCOUNT_STATES[0]['value'],
                    'to_user_account' => null,
                    'reference_note' => 'compensation:company_to_customer;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);

                $customer = \Modules\UserManagement\Entities\User::lockForUpdate()->find($booking->customer_id);
                if ($customer) {
                    $customer->wallet_balance += $amount;
                    $customer->save();
                }

                \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => $primary_transaction->id,
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_OUT,
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $customer?->wallet_balance ?? 0,
                    'from_user_id' => $admin_user_id,
                    'to_user_id' => $booking->customer_id,
                    'from_user_account' => null,
                    'to_user_account' => 'user_wallet',
                    'reference_note' => 'compensation:company_to_customer;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);
            } elseif ($type === 'company_to_provider') {
                $providerUserId = $booking->provider?->user_id;
                if (! $providerUserId) {
                    throw new \RuntimeException('Provider user not found for this booking.');
                }

                // Ledger OUT (company ↔ provider)
                ledger_record_out([
                    'amount' => $amount,
                    'transaction_id' => $transactionId !== '' ? $transactionId : null,
                    'booking_id' => $booking->id,
                    'provider_id' => $booking->provider_id,
                    'reason' => \Modules\TransactionModule\Entities\LedgerTransaction::REASON_COMPENSATION,
                    'date' => $date,
                    'reference_note' => $note !== '' ? $note : 'booking_compensation:' . $comp->id,
                    'created_by' => auth()->check() ? auth()->id() : null,
                ]);

                // Mirror recordPaymentToProvider mechanics but with compensation reason & booking_id.
                $providerAccount = \Modules\TransactionModule\Entities\Account::where('user_id', $providerUserId)->lockForUpdate()->first();
                $adminAccount = \Modules\TransactionModule\Entities\Account::where('user_id', $admin_user_id)->lockForUpdate()->first();
                if (! $providerAccount || ! $adminAccount) {
                    throw new \RuntimeException('Provider or admin account not found.');
                }

                // Ensure we can represent the payout even when receivable/payable are behind.
                $shortfall = max(0.0, $amount - (float) $providerAccount->account_receivable);
                if ($shortfall > 0.009) {
                    $providerAccount->account_receivable += $shortfall;
                }
                $adminLift = max(0.0, $amount - (float) $adminAccount->account_payable);
                if ($adminLift > 0.009) {
                    $adminAccount->account_payable += $adminLift;
                }
                if ($shortfall > 0.009 || $adminLift > 0.009) {
                    $providerAccount->save();
                    $adminAccount->save();
                }

                $providerAccount->account_receivable -= $amount;
                $providerAccount->save();

                $primary_transaction = \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => null,
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $providerAccount->account_receivable,
                    'from_user_id' => $providerUserId,
                    'to_user_id' => $providerUserId,
                    'from_user_account' => ACCOUNT_STATES[3]['value'],
                    'to_user_account' => null,
                    'reference_note' => 'compensation:company_to_provider;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);

                $providerAccount->total_withdrawn += $amount;
                $providerAccount->save();

                \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => $primary_transaction['id'],
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $providerAccount->total_withdrawn,
                    'from_user_id' => $providerUserId,
                    'to_user_id' => $admin_user_id,
                    'from_user_account' => ACCOUNT_STATES[4]['value'],
                    'to_user_account' => null,
                    'reference_note' => 'compensation:company_to_provider;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);

                $adminAccount->account_payable -= $amount;
                $adminAccount->save();

                \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => $primary_transaction['id'],
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_OUT,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $adminAccount->account_payable,
                    'from_user_id' => $providerUserId,
                    'to_user_id' => $admin_user_id,
                    'from_user_account' => null,
                    'to_user_account' => ACCOUNT_STATES[2]['value'],
                    'reference_note' => 'compensation:company_to_provider;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);
            } else {
                // provider_to_customer: transactions only (no ledger)
                $providerUserId = $booking->provider?->user_id;
                if (! $providerUserId) {
                    throw new \RuntimeException('Provider user not found for this booking.');
                }

                $customer = \Modules\UserManagement\Entities\User::lockForUpdate()->find($booking->customer_id);
                if ($customer) {
                    $customer->wallet_balance += $amount;
                    $customer->save();
                }

                $primary_transaction = \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => null,
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_NONE,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => 0,
                    'from_user_id' => $providerUserId,
                    'to_user_id' => $booking->customer_id,
                    'from_user_account' => null,
                    'to_user_account' => 'user_wallet',
                    'reference_note' => 'compensation:provider_to_customer;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);

                // Mirror row to keep "transactions all" listings consistent.
                \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => $primary_transaction->id,
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_compensation'],
                    'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_NONE,
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $customer?->wallet_balance ?? 0,
                    'from_user_id' => $providerUserId,
                    'to_user_id' => $booking->customer_id,
                    'from_user_account' => null,
                    'to_user_account' => 'user_wallet',
                    'reference_note' => 'compensation:provider_to_customer;trx:' . ($transactionId ?: '—') . ';row:' . $comp->id,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
        }
        Toastr::success(translate('Compensation recorded successfully.'));
        return back();
    }

    public function reBookingDetails(Request $request, $id)
    {
        $this->authorize('booking_view');
        Validator::make($request->all(), [
            'web_page' => 'required|in:details,status',
        ]);
        $webPage = $request->has('web_page') ? $request['web_page'] : 'business_setup';

        if ($request->web_page == 'details') {

            $booking = $this->booking->with(['detail.service' => function ($query) {
                $query->withTrashed();
            }, 'detail.service.category', 'detail.service.subCategory', 'customer', 'provider', 'service_address', 'serviceman', 'service_address', 'status_histories.user'])->find($id);
            
            // Load variations for each detail with proper constraints (service_id and zone_id)
            if ($booking && $booking->detail) {
                foreach ($booking->detail as $detail) {
                    if ($detail->variant_key && $detail->service_id && $booking->zone_id) {
                        $detail->variation = Variation::firstForBookingZone(
                            (string) $detail->service_id,
                            (string) $detail->variant_key,
                            (string) $booking->zone_id,
                            false
                        );
                    }
                }
            }

            $servicemen = $this->serviceman->with(['user'])
                ->where('provider_id', $booking?->provider_id)
                ->whereHas('user', function ($query) {
                    $query->ofStatus(1);
                })
                ->latest()
                ->get();

            $category = $booking?->detail?->first()?->service?->category;
            $subCategory = $booking?->detail?->first()?->service?->subCategory;
            $services = Service::select('id', 'name')->where('category_id', $category->id)->where('sub_category_id', $subCategory->id)->get();

            $customerAddress = $this->userAddress->find($booking['service_address_id']);
            $zones = Zone::ofStatus(1)->withoutGlobalScope('translate')->get();

            $providers = $this->provider
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    return $query->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                                ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                                ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                        }
                    });
                })
                ->when(isset($booking->sub_category_id), function ($query) use ($request, $booking) {
                    $query->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                        $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
                    });
                })
                ->coveringLeafZone($booking->zone_id)
                ->withCount('bookings', 'reviews')
                ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                    $query->where('is_suspended', 0);
                })
                ->where('service_availability', 1)
                ->where('is_active_for_jobs', 1)
                ->withCount('reviews')
                ->ofApproval(1)->ofStatus(1)->get();

            $sort_by = 'default';
            $id = "325778a8-53bd-4de5-a6bb-826f62edf603";
            $zoneCenter = Zone::selectRaw("*,ST_AsText(ST_Centroid(`coordinates`)) as center")->withoutGlobalScope('translate')->find($id);

            $currentZone = [];
            $centerLat = [];
            $centerLng = [];
            $area = [];

            if (isset($zoneCenter)) {
                $currentZone = format_coordinates(json_decode($zoneCenter->coordinates[0]->toJson(), true));
                $centerLat = trim(explode(' ', $zoneCenter->center)[1], 'POINT()');
                $centerLng = trim(explode(' ', $zoneCenter->center)[0], 'POINT()');

                $area = json_decode($zoneCenter->coordinates[0]->toJson(), true);
            }

            return view('bookingmodule::admin.booking.rebooking-details', compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'));
        } elseif ($request->web_page == 'status') {
            $booking = $this->booking->with(['detail.service', 'customer', 'provider', 'service_address', 'serviceman.user', 'service_address', 'status_histories.user'])->find($id);
            $servicemen = $this->serviceman->with(['user'])
                ->where('provider_id', $booking?->provider_id)
                ->whereHas('user', function ($query) {
                    $query->ofStatus(1);
                })
                ->latest()
                ->get();
            $category = $booking?->detail?->first()?->service?->category;
            $subCategory = $booking?->detail?->first()?->service?->subCategory;
            $services = Service::select('id', 'name')->where('category_id', $category->id)->where('sub_category_id', $subCategory->id)->get();
            $customerAddress = $this->userAddress->find($booking['service_address_id']);
            $zones = Zone::ofStatus(1)->withoutGlobalScope('translate')->get();

            $providers = $this->provider
                ->when($request->has('search'), function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    return $query->where(function ($query) use ($keys) {
                        foreach ($keys as $key) {
                            $query->orWhere('company_phone', 'LIKE', '%' . $key . '%')
                                ->orWhere('company_email', 'LIKE', '%' . $key . '%')
                                ->orWhere('company_name', 'LIKE', '%' . $key . '%');
                        }
                    });
                })
                ->when(isset($booking->sub_category_id), function ($query) use ($request, $booking) {
                    $query->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                        $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
                    });
                })
                ->coveringLeafZone($booking->zone_id)
                ->withCount('bookings', 'reviews')
                ->ofApproval(1)->ofStatus(1)->get();
            $sort_by = 'default';
            return view('bookingmodule::admin.booking.service-log', array_merge(compact('booking', 'webPage', 'servicemen', 'customerAddress', 'category', 'subCategory', 'services', 'providers', 'zones', 'sort_by'), $this->bookingConfigurationReasonVariables()));
        }

        Toastr::success(translate(ACCESS_DENIED['message']));
        return back();
    }

    public function reBookingOngoing()
    {
        return view('bookingmodule::admin.booking.rebooking-ongoing');
    }

    public function switchPaymentMethod($bookingId, Request $request)
    {
        $this->authorize('booking_can_manage_status');

        $validated = $request->validate([
            'payment_method' => 'required'
        ]);

        $booking = $this->booking->find($bookingId);
        $booking->payment_method = $request->payment_method;
        $booking->is_verified = 1;
        $booking->save();

        return response()->json(response_formatter(PAYMENT_METHOD_UPDATE_200), 200);
    }

    public function changeServiceLocation($bookingId, Request $request)
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->booking->find($bookingId);

        if (!$booking) {
            Toastr::error(translate('Booking not found'));
            return back();
        }

        $serviceAtProviderPlace = (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0);

        if ($serviceAtProviderPlace == 0 && $request->service_location == 'provider') {
            Toastr::error(translate('Cannot switch to provider when provider service location is off'));
            return back();
        }

        if ($request->service_location == 'customer') {
            $existingAddress = json_decode($booking->service_address_location, true) ?? [];

            // Update only the changed values, keeping others untouched
            $updatedAddress = array_merge($existingAddress, [
                "lat" => $request->latitude ?? $existingAddress['lat'] ?? null,
                "lon" => $request->longitude ?? $existingAddress['lon'] ?? null,
                "city" => null,
                "street" => "",
                "zip_code" => "",
                "country" => null,
                "address" => $request->address ?? $existingAddress['address'] ?? null,
                "landmark" => $request->landmark ?? $existingAddress['landmark'] ?? null,
                "updated_at" => now()->toISOString(),
                "address_type" => $request->address_type ?? $existingAddress['address_type'] ?? "others",
                "contact_person_name" => $request->contact_person_name ?? $existingAddress['contact_person_name'] ?? null,
                "contact_person_number" => $request->contact_person_number ?? $existingAddress['contact_person_number'] ?? null,
                "address_label" => $request->address_label ?? $existingAddress['address_label'] ?? null,
                "zone_id" => $request->zone_id ?? $existingAddress['zone_id'] ?? null,
                "is_guest" => $request->is_guest ?? $existingAddress['is_guest'] ?? 0,
                "house" => $request->house ?? $existingAddress['house'] ?? "",
                "floor" => $request->floor ?? $existingAddress['floor'] ?? null,
            ]);

            $updateData = [
                'service_location' => 'customer',
                'service_address_location' => json_encode($updatedAddress), // Store updated JSON
            ];

        } else {
            $updateData = [
                'service_location' => 'provider',
            ];
        }

        $booking->update($updateData);

        if ($request->has('next_all_booking_change')){
            $this->bookingRepeat
                ->where('booking_id', $booking->id)
                ->whereIn('booking_status', ['accepted', 'ongoing'])
                ->update($updateData);
        }else{
            if ($booking->repeat->isNotEmpty()) {
                $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                    $parts = explode('-', $repeat->readable_id);
                    $suffix = end($parts);
                    return $this->readableIdToNumber($suffix);
                });

                // Keep original collection for update
                $booking['repeats'] = $sortedRepeats->values()->toArray();

                // Work with the original model collection
                $sortedModelRepeats = $sortedRepeats->values();

                $nextService = $sortedModelRepeats->firstWhere('booking_status', 'ongoing')
                    ?? $sortedModelRepeats->firstWhere('booking_status', 'on_hold')
                    ?? $sortedModelRepeats->firstWhere('booking_status', 'accepted')
                    ?? $sortedModelRepeats->firstWhere('booking_status', 'pending');

                if ($nextService) {
                    $nextService->update($updateData);
                }
            }
        }

        $user = $booking?->customer;
        $repeatOrRegular = $booking?->is_repeated ? 'repeat' : 'regular';
        if (isset($user) && $user?->fcm_token && $user?->is_active) {
            try {
                device_notification($user?->fcm_token, translate('service location updated'), null, null, $booking->id, 'booking', null, null, null, null, $repeatOrRegular);
            }catch (\Exception $exception) {
                //
            }
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }

    public function repeatChangeServiceLocation($bookingId, Request $request)
    {
        $this->authorize('booking_can_manage_status');

        $booking = $this->bookingRepeat->find($bookingId);

        if (!$booking) {
            Toastr::error(translate('Booking not found'));
            return back();
        }

        $serviceAtProviderPlace = (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0);

        if ($serviceAtProviderPlace == 0 && $request->service_location == 'provider') {
            Toastr::error(translate('Cannot switch to provider when provider service location is off'));
            return back();
        }

        if ($request->service_location == 'customer') {
            $existingAddress = json_decode($booking->service_address_location, true) ?? [];

            // Update only the changed values, keeping others untouched
            $updatedAddress = array_merge($existingAddress, [
                "lat" => $request->latitude ?? $existingAddress['lat'] ?? null,
                "lon" => $request->longitude ?? $existingAddress['lon'] ?? null,
                "city" => null,
                "street" => "",
                "zip_code" => "",
                "country" => null,
                "address" => $request->address ?? $existingAddress['address'] ?? null,
                "landmark" => $request->landmark ?? $existingAddress['landmark'] ?? null,
                "updated_at" => now()->toISOString(),
                "address_type" => $request->address_type ?? $existingAddress['address_type'] ?? "others",
                "contact_person_name" => $request->contact_person_name ?? $existingAddress['contact_person_name'] ?? null,
                "contact_person_number" => $request->contact_person_number ?? $existingAddress['contact_person_number'] ?? null,
                "address_label" => $request->address_label ?? $existingAddress['address_label'] ?? null,
                "zone_id" => $request->zone_id ?? $existingAddress['zone_id'] ?? null,
                "is_guest" => $request->is_guest ?? $existingAddress['is_guest'] ?? 0,
                "house" => $request->house ?? $existingAddress['house'] ?? "",
                "floor" => $request->floor ?? $existingAddress['floor'] ?? null,
            ]);

            $updateData = [
                'service_location' => 'customer',
                'service_address_location' => json_encode($updatedAddress), // Store updated JSON
            ];

        } else {
            $updateData = [
                'service_location' => 'provider',
            ];
        }

        $booking->update($updateData);

        $mainBooking = $this->booking->find($booking->booking_id);
        if ($mainBooking) {
            $mainBooking->update($updateData);
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return back();
    }

    public function downloadBookingVerificationList(Request $request)
    {
        $request->validate([
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
            'type' => 'in:pending,denied'
        ]);
        $request['booking_status'] = $request['booking_status'] ?? 'pending';

        $queryParams = [];
        $type = $request->type ?? 'pending';

        if ($request->has('zone_ids')) {
            $zoneIds = $request['zone_ids'];
            $queryParams['zone_ids'] = $zoneIds;
        }

        if ($request->has('category_ids')) {
            $categoryIds = $request['category_ids'];
            $queryParams['category_ids'] = $categoryIds;
        }

        if ($request->has('sub_category_ids')) {
            $subCategoryIds = $request['sub_category_ids'];
            $queryParams['sub_category_ids'] = $subCategoryIds;
        }

        if ($request->has('start_date')) {
            $startDate = $request['start_date'];
            $queryParams['start_date'] = $startDate;
        } else {
            $queryParams['start_date'] = null;
        }

        if ($request->has('end_date')) {
            $endDate = $request['end_date'];
            $queryParams['end_date'] = $endDate;
        } else {
            $queryParams['end_date'] = null;
        }

        if ($request->has('search')) {
            $search = $request['search'];
            $queryParams['search'] = $search;
        }

        $queryParams['type'] = $type;

        if ($request->has('booking_status')) {
            $bookingStatus = $request['booking_status'];
            $queryParams['booking_status'] = $bookingStatus;
        } else {
            $queryParams['booking_status'] = 'pending';
        }

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;

        $bookings = $this->booking->with(['customer'])
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($bookingStatus == 'pending', function ($query) use ($maxBookingAmount, $type) {
                $query->when($type == 'pending', function ($query) {
                    $query->where('is_verified', '0');
                })->when($type == 'denied', function ($query) {
                    $query->where('is_verified', '2');
                })
                    ->where('payment_method', 'cash_after_service')
                    ->Where('total_booking_amount', '>', $maxBookingAmount)
                    ->whereIn('booking_status', ['pending', 'accepted']);
            })
            ->when($request->has('zone_ids'), function ($query) use ($request) {
                $query->whereIn('zone_id', $request['zone_ids']);
            })->when($queryParams['start_date'] != null && $queryParams['end_date'] != null, function ($query) use ($request) {
                if ($request['start_date'] == $request['end_date']) {
                    $query->whereDate('created_at', Carbon::parse($request['start_date'])->startOfDay());
                } else {
                    $query->whereBetween('created_at', [Carbon::parse($request['start_date'])->startOfDay(), Carbon::parse($request['end_date'])->endOfDay()]);
                }
            })->when($request->has('sub_category_ids'), function ($query) use ($request) {
                $query->whereIn('sub_category_id', $request['sub_category_ids']);
            })->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereIn('category_id', $request['category_ids']);
            })
            ->latest()->get();

        return (new FastExcel($bookings))->download(time() . '-file.xlsx');

    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertValidWhatsappReservedReadableId(array $data): void
    {
        $reserved = trim((string) ($data['whatsapp_reserved_readable_id'] ?? ''));
        if ($reserved === '') {
            return;
        }
        if (!BookingReadableIdAllocator::isAppReadableIdFormat($reserved)) {
            throw ValidationException::withMessages([
                'whatsapp_reserved_readable_id' => [translate('Invalid_data')],
            ]);
        }
        if (Booking::query()->where('readable_id', $reserved)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp_reserved_readable_id' => [translate('This_value_is_already_taken')],
            ]);
        }
        $wa = WhatsAppBooking::query()->where('booking_id', $reserved)->first();
        if (!$wa) {
            return;
        }
        $cust = User::query()->find($data['customer_id'] ?? null);
        if (!$cust) {
            return;
        }
        $cloud = app(WhatsAppCloudService::class);
        $w = $cloud->normalizeRecipientPhone((string) $wa->phone);
        $c = $cloud->normalizeRecipientPhone((string) ($cust->phone ?? ''));
        if ($w && $c && $w !== $c) {
            throw ValidationException::withMessages([
                'whatsapp_reserved_readable_id' => [translate('Invalid_data')],
            ]);
        }
    }

    private function syncWhatsAppBookingRowAfterAdminCreate(?string $reservedReadableId, string $systemBookingUuid): void
    {
        $reservedReadableId = trim((string) ($reservedReadableId ?? ''));
        if ($reservedReadableId === '' || !BookingReadableIdAllocator::isAppReadableIdFormat($reservedReadableId)) {
            return;
        }
        try {
            $wa = WhatsAppBooking::query()->where('booking_id', $reservedReadableId)->first();
            if ($wa) {
                $wa->system_booking_id = $systemBookingUuid;
                $wa->status = WhatsAppBooking::STATUS_HUMAN_CONFIRMED;
                $wa->save();

                if ($wa->lead_id) {
                    $this->syncCustomerLeadHistoryWithSystemBooking((int) $wa->lead_id, $systemBookingUuid);
                }
            }
        } catch (Throwable $e) {
            Log::warning('WhatsApp booking row link after admin create failed', [
                'readable_id' => $reservedReadableId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Attach the system booking to the customer lead type history (booked/completed + booking_id).
     */
    protected function syncCustomerLeadHistoryWithSystemBooking(int $leadId, string $systemBookingId): void
    {
        $lead = Lead::query()->find($leadId);
        if (!$lead || $lead->lead_type !== Lead::TYPE_CUSTOMER) {
            return;
        }

        $bookedOrCompletedStatus = \Modules\LeadManagement\Entities\CustomerLeadStatus::where('base_type', 'booked')->first()
            ?: \Modules\LeadManagement\Entities\CustomerLeadStatus::where('base_type', 'completed')->first();

        $history = \Modules\LeadManagement\Entities\LeadTypeHistory::where('lead_id', $lead->id)
            ->where('type', 'customer')
            ->latest()
            ->first();

        $dataHistory = $history && is_array($history->data) ? $history->data : [];
        if ($bookedOrCompletedStatus) {
            $dataHistory['customer_lead_status_id'] = $bookedOrCompletedStatus->id;
        }
        $dataHistory['booking_id'] = $systemBookingId;

        if ($history) {
            $history->data = $dataHistory;
            $history->save();
        } else {
            \Modules\LeadManagement\Entities\LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'customer',
                'data' => $dataHistory,
            ]);
        }
    }

    /**
     * Scale persisted {@see BookingDetailsAmount} commission rows to net amounts after disputed reopen refund.
     */
    private function distributeDisputedRefundNetAcrossDetailsAmounts(Booking $booking, float $finalAdmin, float $finalProvider): void
    {
        $rows = BookingDetailsAmount::query()
            ->where('booking_id', $booking->id)
            ->whereNull('booking_repeat_id')
            ->orderBy('id')
            ->get();
        if ($rows->isEmpty()) {
            return;
        }

        $sumA = round((float) $rows->sum('admin_commission'), 2);
        $sumP = round((float) $rows->sum('provider_earning'), 2);
        $n = $rows->count();
        $remainA = $finalAdmin;
        $remainP = $finalProvider;

        foreach ($rows as $i => $row) {
            if ($i === $n - 1) {
                $row->admin_commission = round($remainA, 2);
                $row->provider_earning = round($remainP, 2);
            } else {
                $wa = $sumA > 0.0001 ? ((float) $row->admin_commission / $sumA) : (1 / $n);
                $wp = $sumP > 0.0001 ? ((float) $row->provider_earning / $sumP) : (1 / $n);
                $da = round($finalAdmin * $wa, 2);
                $dp = round($finalProvider * $wp, 2);
                $row->admin_commission = $da;
                $row->provider_earning = $dp;
                $remainA = round($remainA - $da, 2);
                $remainP = round($remainP - $dp, 2);
            }
            $row->save();
        }
    }

}
