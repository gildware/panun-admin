<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

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
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\ZoneManagement\Entities\Zone;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\Source;
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
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('booking_view');
        $request->validate([
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
        ]);

        $queryParams = $request->only(['zone_ids', 'category_ids', 'sub_category_ids', 'start_date', 'end_date', 'search']);
        $filterCounter = collect($queryParams)->filter()->count();
        $bookingStatus = $queryParams['booking_status'] = $request->input('booking_status', 'pending');
        $queryParams['booking_type'] = $request->input('booking_type', '');
        $queryParams['service_type'] = $request->input('service_type', '');
        $queryParams['provider_assigned'] = $request->input('provider_assigned', '');

        if (empty($queryParams['start_date'])) {
            $queryParams['start_date'] = null;
        }
        if (empty($queryParams['end_date'])) {
            $queryParams['end_date'] = null;
        }

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;
        $bookings = $this->booking
            ->with(['customer', 'assignee', 'followups', 'extra_services'])
            ->search($request['search'], ['readable_id'])
            ->when($bookingStatus != 'all', function ($query) use ($bookingStatus, $maxBookingAmount, $request) {
                $query->when($bookingStatus == 'pending', function ($query) use ($maxBookingAmount) {
                    $query->adminPendingBookings($maxBookingAmount);
                })->when($bookingStatus == 'accepted', function ($query) use ($maxBookingAmount) {
                    $query->adminAcceptedBookings($maxBookingAmount);
                })->ofBookingStatus($request['booking_status']);
            })
            ->when($request['service_type'] != 'all', function ($query) use ($request) {
                return $query->ofRepeatBookingStatus($request['service_type'] === 'repeat' ? 1 : ($request['service_type'] === 'regular' ? 0 : null));
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

        return view('bookingmodule::admin.booking.list', compact('bookings', 'zones', 'categories', 'subCategories', 'queryParams', 'filterCounter'));
    }

    /**
     * Show the form for creating a new booking from admin panel.
     *
     * @param Request $request
     * @return Factory|View|Application
     * @throws AuthorizationException
     */
    public function create(Request $request): Factory|View|Application
    {
        try {
            $this->authorize('booking_view');
        } catch (AuthorizationException $e) {
            Toastr::error(translate('Access_denied'));
            return redirect()->route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all']);
        }

        // Merge query parameters with old input for form pre-filling
        $request->merge(array_merge($request->query(), $request->old()));

        return $this->buildBookingCreateView($request, 'bookingmodule::admin.booking.create');
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
            return redirect()->route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all']);
        }

        $leadModel = Lead::with(['source'])->findOrFail($lead);

        if ($leadModel->lead_type !== \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER) {
            Toastr::error(translate('Lead_is_not_a_customer_type'));
            return redirect()->route('admin.lead.show', $leadModel->id);
        }

        // Try to find existing customer by phone; otherwise create one
        $customer = User::where('user_type', 'customer')
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
            $customer->is_active = 1;
            $customer->save();
        }

        // Load latest customer-type history data for this lead (service info, estimated date, etc.)
        $typeHistory = \Modules\LeadManagement\Entities\LeadTypeHistory::where('lead_id', $leadModel->id)
            ->where('type', 'customer')
            ->latest()
            ->first();

        $customerData = is_array($typeHistory->data ?? null) ? $typeHistory->data : [];

        // Prefill booking form values from lead data
        $prefill = [
            'lead_id' => $leadModel->id,
            'customer_id' => $customer->id,
            'zone_id' => $customerData['zone_id'] ?? null,
            'category_id' => $customerData['service_category'] ?? null,
            'sub_category_id' => $customerData['service_subcategory'] ?? null,
            'service_id' => $customerData['service_name'] ?? null,
            'variant_key' => $customerData['variant_key'] ?? null,
            'service_description' => $customerData['service_description'] ?? null,
            'service_schedule' => $customerData['estimated_service_at'] ?? null,
            'booking_source' => $leadModel->source?->name ?? null,
        ];

        // Merge prefill data with query params and old input (so user edits win)
        $request->merge(array_merge($prefill, $request->query(), $request->old()));

        return $this->buildBookingCreateView($request, 'bookingmodule::admin.booking.create-from-lead');
    }

    /**
     * Build data and view for booking create form (used by both standard create and create-from-lead flows).
     *
     * @param Request $request
     * @param string $view
     * @return Factory|View|Application
     */
    protected function buildBookingCreateView(Request $request, string $view): Factory|View|Application
    {
        $zones = $this->zone->withoutGlobalScope('translate')->select('id', 'name', 'parent_id')->get();
        $zoneTreeOptions = Zone::flatTreeOptionsForSelect($zones);
        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();
        $subCategories = $this->category->select('id', 'parent_id', 'name')->where('position', 2)->get();
        $providers = $this->provider->with('owner')->get();
        $servicemen = $this->serviceman->with('user')->get();
        $customers = User::where('user_type', 'customer')
            ->orderByDesc('created_at')
            ->select('id', 'first_name', 'last_name', 'phone')
            ->limit(100)
            ->get();

        // Lead sources for unified "Booking Source" options (same as Add Lead Source)
        $sources = Source::active()->orderBy('name')->get(['id', 'name']);

        // Assignees: super-admins and admin employees
        $assignees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
            ->get();

        $currentAdmin = auth()->user();

        $additionalChargeEnabled = (bool) (business_config('booking_additional_charge', 'booking_setup'))?->live_values;
        $additionalChargeLabel = (string) (business_config('additional_charge_label_name', 'booking_setup'))?->live_values ?: translate('extra_fee');
        $additionalChargeDefaultAmount = (float) (business_config('additional_charge_fee_amount', 'booking_setup'))?->live_values ?: 0;

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
            'additionalChargeEnabled',
            'additionalChargeLabel',
            'additionalChargeDefaultAmount',
            'sources'
        ));
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
                'advance_transaction_id' => [
                    'nullable',
                    'string',
                    'max:191',
                    Rule::requiredIf(fn () => (float) ($request->input('advance_paid_amount') ?? 0) > 0),
                ],
                'assignee_id' => ['nullable', 'exists:users,id'],
                'service_description' => ['nullable', 'string', 'max:2000'],
                'extra_fee' => ['nullable', 'numeric', 'min:0'],
                'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
                'in_modal' => ['nullable', 'boolean'],
            ], [
                'advance_transaction_id.required' => translate('Transaction_ID_is_required_when_advance_paid_is_greater_than_zero'),
            ]);
            
            // If service location is provider, clear service_address_id
            if ($data['service_location'] === 'provider') {
                $data['service_address_id'] = null;
            }
            $additionalChargeEnabled = (bool) (business_config('booking_additional_charge', 'booking_setup'))?->live_values;
            if (!$additionalChargeEnabled) {
                $data['extra_fee'] = 0;
            } else {
                $data['extra_fee'] = (float) ($data['extra_fee'] ?? 0);
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        // Normalize booking source for display
        $data['booking_source'] = strtolower($data['booking_source']);

        // Load related data for preview
        $customer = User::find($data['customer_id']);
        $provider = $this->provider->with('owner')->find($data['provider_id']);
        $zone = $this->zone->find($data['zone_id']);
        $category = $this->category->find($data['category_id']);
        $subCategory = $this->category->find($data['sub_category_id']);
        $service = Service::find($data['service_id']);
        $address = $data['service_address_id'] ? $this->userAddress->find($data['service_address_id']) : null;
        $assignee = $data['assignee_id'] ? User::find($data['assignee_id']) : null;
        $variation = Variation::where('service_id', $data['service_id'])
            ->where('zone_id', $data['zone_id'])
            ->where('variant_key', $data['variant_key'])
            ->first();

        $totalBilling = 0;
        $dueBalance = 0;
        if ($variation && $service) {
            $serviceForCalc = Service::active()
                ->with(['category.category_discount', 'category.campaign_discount', 'service_discount'])
                ->find($data['service_id']);
            if ($serviceForCalc) {
                $quantity = 1;
                $variationPrice = $variation->price ?? 0;
                $basicDiscount = basic_discount_calculation($serviceForCalc, $variationPrice * $quantity);
                $campaignDiscount = campaign_discount_calculation($serviceForCalc, $variationPrice * $quantity);
                $subtotal = round($variationPrice * $quantity, 2);
                $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
                $tax = round((($variationPrice * $quantity - $applicableDiscount) * $serviceForCalc->tax) / 100, 2);
                $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
                $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;
                $extraFee = (float) ($data['extra_fee'] ?? 0);
                $totalBilling = round($subtotal - $basicDiscount - $campaignDiscount + $tax + $extraFee, 2);
                $advance = (float) ($data['advance_paid_amount'] ?? 0);
                $dueBalance = max(0, $totalBilling - $advance);
            }
        }

        $view = !empty($data['lead_id'])
            ? 'bookingmodule::admin.booking.preview-from-lead'
            : 'bookingmodule::admin.booking.preview';

        return view($view,
            compact('data', 'customer', 'provider', 'zone', 'category', 'subCategory', 'service', 'address', 'assignee', 'variation', 'totalBilling', 'dueBalance'));
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
            'advance_transaction_id' => [
                'nullable',
                'string',
                'max:191',
                Rule::requiredIf(fn () => (float) ($request->input('advance_paid_amount') ?? 0) > 0),
            ],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'service_description' => ['nullable', 'string', 'max:2000'],
            'extra_fee' => ['nullable', 'numeric', 'min:0'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'in_modal' => ['nullable', 'boolean'],
        ], [
            'advance_transaction_id.required' => translate('Transaction_ID_is_required_when_advance_paid_is_greater_than_zero'),
        ]);
        
        // If service location is provider, clear service_address_id
        if ($data['service_location'] === 'provider') {
            $data['service_address_id'] = null;
        }
        $additionalChargeEnabled = (bool) (business_config('booking_additional_charge', 'booking_setup'))?->live_values;
        $data['extra_fee'] = $additionalChargeEnabled ? (float) ($data['extra_fee'] ?? 0) : 0;

        // Normalize booking source
        $data['booking_source'] = strtolower($data['booking_source']);

        DB::beginTransaction();

        try {
            // Get service and variation for price calculation
            $service = Service::active()
                ->with(['category.category_discount', 'category.campaign_discount', 'service_discount'])
                ->find($data['service_id']);
            
            $variation = Variation::where('service_id', $data['service_id'])
                ->where('zone_id', $data['zone_id'])
                ->where('variant_key', $data['variant_key'])
                ->first();

            if (!$service || !$variation) {
                throw new \Exception('Service or variation not found');
            }

            // Calculate pricing
            $quantity = 1; // Default quantity for admin-created bookings
            $variationPrice = $variation->price ?? 0;
            
            if ($variationPrice <= 0) {
                throw new \Exception('Variation price must be greater than 0');
            }
            $basicDiscount = basic_discount_calculation($service, $variationPrice * $quantity);
            $campaignDiscount = campaign_discount_calculation($service, $variationPrice * $quantity);
            $subtotal = round($variationPrice * $quantity, 2);
            
            $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
            $tax = round((($variationPrice * $quantity - $applicableDiscount) * $service->tax) / 100, 2);
            
            $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
            $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;
            
            $extraFee = (float) ($data['extra_fee'] ?? 0);
            $totalCost = round($subtotal - $basicDiscount - $campaignDiscount + $tax + $extraFee, 2);

            $advanceAmount = (float) ($data['advance_paid_amount'] ?? 0);
            if ($advanceAmount > $totalCost) {
                throw ValidationException::withMessages([
                    'advance_paid_amount' => [translate('Advance_amount_cannot_exceed_total_billing_amount')],
                ]);
            }

            // Create booking
            $booking = new Booking();
            $booking->customer_id = $data['customer_id'];
            $booking->provider_id = $data['provider_id'];
            $booking->zone_id = $data['zone_id'];
            $booking->category_id = $data['category_id'];
            $booking->sub_category_id = $data['sub_category_id'];
            $booking->booking_status = 'pending';
            $booking->payment_method = 'cash_after_service';
            $booking->is_paid = 0;
            $booking->service_schedule = $data['service_schedule'];
            $booking->service_address_id = $data['service_address_id'] ?? null;
            $booking->service_location = $data['service_location']; // 'customer' or 'provider'
            $booking->booking_source = $data['booking_source'];
            $booking->assignee_id = $data['assignee_id'] ?? null;
            $booking->service_description = $data['service_description'] ?? null;
            $booking->booking_otp = rand(100000, 999999);
            $booking->extra_fee = $extraFee;
            $booking->lead_id = $data['lead_id'] ?? null;
            
            // Set booking totals (total_booking_amount excludes extra_fee; get_booking_total_amount() adds it)
            $booking->total_booking_amount = $totalCost - $extraFee;
            $booking->total_tax_amount = $tax;
            $booking->total_discount_amount = $basicDiscount;
            $booking->total_campaign_discount_amount = $campaignDiscount;
            $booking->total_coupon_discount_amount = 0;
            
            $booking->save();

            // Record advance payment as an offline partial payment if provided (always received by company)
            if (!empty($data['advance_paid_amount']) && $data['advance_paid_amount'] > 0) {
                $paidAmount = min($data['advance_paid_amount'], $totalCost);
                $dueAmount = max($totalCost - $paidAmount, 0);

                BookingPartialPayment::create([
                    'booking_id' => $booking->id,
                    'paid_with' => 'offline',
                    'transaction_id' => $data['advance_transaction_id'] ?? null,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'received_by' => 'company',
                ]);

                ledger_record_in([
                    'amount' => $paidAmount,
                    'transaction_id' => $data['advance_transaction_id'] ?? null,
                    'booking_id' => $booking->id,
                    'payment_method' => 'advance_on_booking_create',
                    'date' => now()->toDateString(),
                    'received_by' => LedgerTransaction::RECEIVED_BY_COMPANY,
                    'created_by' => auth()->id(),
                ]);
            }

            // Create booking detail
            $detail = new BookingDetail();
            $detail->booking_id = $booking->id;
            $detail->service_id = $data['service_id'];
            $detail->service_name = $service->name ?? 'service-not-found';
            $detail->variant_key = $data['variant_key'];
            $detail->quantity = $quantity;
            $detail->service_cost = $variationPrice;
            $detail->discount_amount = $basicDiscount;
            $detail->campaign_discount_amount = $campaignDiscount;
            $detail->overall_coupon_discount_amount = 0;
            $detail->tax_amount = $tax;
            $detail->total_cost = $totalCost; // includes extra_fee in total
            $detail->save();

            // Create booking details amount
            // For admin-created bookings, discount splits default to 0
            // These can be adjusted later if needed
            $bookingDetailsAmount = new BookingDetailsAmount();
            $bookingDetailsAmount->booking_details_id = $detail->id;
            $bookingDetailsAmount->booking_id = $booking->id;
            $bookingDetailsAmount->service_unit_cost = $variationPrice;
            $bookingDetailsAmount->service_quantity = $quantity;
            $bookingDetailsAmount->service_tax = $tax;
            $bookingDetailsAmount->discount_by_admin = 0;
            $bookingDetailsAmount->discount_by_provider = 0;
            $bookingDetailsAmount->campaign_discount_by_admin = 0;
            $bookingDetailsAmount->campaign_discount_by_provider = 0;
            $bookingDetailsAmount->coupon_discount_by_admin = 0;
            $bookingDetailsAmount->coupon_discount_by_provider = 0;
            $bookingDetailsAmount->admin_commission = 0; // Will be calculated later if needed
            $bookingDetailsAmount->save();

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
            $statusHistory->booking_status = 'pending';
            $statusHistory->save();

            DB::commit();

            \Log::info('ADMIN_BOOKING_STORE_SUCCESS', [
                'booking_id' => $booking->id,
                'readable_id' => $booking->readable_id,
            ]);

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
                    $lead = \Modules\LeadManagement\Entities\Lead::find($leadId);
                    if ($lead && $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER) {
                        // Prefer "booked" status if configured; fallback to "completed"
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
                        // Store booking id created from this lead
                        $dataHistory['booking_id'] = $booking->id;

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
            return redirect()->route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all']);
        }

        $booking = $this->booking->find($id);
        
        if (!$booking) {
            Toastr::error(translate('Booking_not_found'));
            return redirect()->route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all']);
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
        $webPage = $request->input('web_page', 'details');
        if (!in_array($webPage, ['details', 'status', 'followups'], true)) {
            $webPage = 'details';
        }
        $request->merge(['web_page' => $webPage]);

        if ($webPage === 'details') {

            $booking = $this->booking->with(['detail.service' => function ($query) {
                $query->withTrashed();
            }, 'detail.service.variations', 'detail.service.category', 'detail.service.subCategory', 'customer', 'provider', 'serviceman', 'assignee', 'status_histories.user', 'booking_partial_payments', 'followups', 'extra_services'])
                ->find($id);

            if (!$booking) {
                return redirect()->route('admin.booking.list')->withErrors(['message' => translate('Booking not found')]);
            }

            // Load variations for each detail with proper constraints (service_id and zone_id)
            if ($booking->detail) {
                foreach ($booking->detail as $detail) {
                    if ($detail->variant_key && $detail->service_id && $booking->zone_id) {
                        $detail->variation = Variation::where('variant_key', $detail->variant_key)
                            ->where('service_id', $detail->service_id)
                            ->where('zone_id', $booking->zone_id)
                            ->first();
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
            $services = Service::select('id', 'name')->where('category_id', $category?->id)->where('sub_category_id', $subCategory?->id)->get();

            $customerAddress = $this->userAddress->find($booking['service_address_id']);
            $zones = Zone::ofStatus(1)->withoutGlobalScope('translate')->get();

            $allProviders = $this->provider
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
                ->ofApproval(1)->ofStatus(1)
                ->whereNot('id', $booking->provider_id)
                ->get();

            $providers = [];

            foreach ($allProviders as $provider) {
                $serviceLocation = getProviderSettings(providerId: $provider->id, key: 'service_location', type: 'provider_config');

                if (in_array($booking->service_location, $serviceLocation)) {
                    $providers[] = $provider;
                }
            }

            $currentlyAssignProvider = $booking->provider_id
                ? $this->provider->withCount('bookings', 'reviews')->find($booking->provider_id)
                : null;

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
            $customerName = $booking->customer ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? '')) : ($customerAddress->contact_person_name ?? '');
            $customerPhone = $booking->customer ? ($booking->customer->phone ?? '') : ($customerAddress->contact_person_number ?? '');

            $totalPaidFromPartials = (float) $booking->booking_partial_payments->sum('paid_amount');
            $remainingDueForAddPayment = round(max(0, get_booking_total_amount($booking) - $totalPaidFromPartials), 2);
            $maxRefundAmount = $booking->booking_status === 'canceled' ? get_booking_total_paid($booking) : 0;

            try {
                return view('bookingmodule::admin.booking.details', compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by', 'currentlyAssignProvider', 'assignees', 'nextFollowupCustomer', 'nextFollowupProvider', 'customerName', 'customerPhone', 'remainingDueForAddPayment', 'maxRefundAmount'));
            } catch (Throwable $e) {
                Log::error('Booking details view failed: ' . $e->getMessage(), ['exception' => $e, 'booking_id' => $id]);
                Toastr::error(translate('Unable to load booking details. Please try again.'));
                return redirect()->route('admin.booking.list');
            }
        } elseif ($webPage === 'status') {
            $booking = $this->booking->with(['detail.service', 'customer', 'provider', 'service_address', 'serviceman.user', 'service_address', 'status_histories.user', 'followups'])->find($id);

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

            $allProviders = $this->provider
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
                ->ofApproval(1)->ofStatus(1)
                ->whereNot('id', $booking->provider_id)
                ->get();

            $providers = [];

            foreach ($allProviders as $provider) {
                $serviceLocation = getProviderSettings(providerId: $provider->id, key: 'service_location', type: 'provider_config');

                if (in_array($booking->service_location, $serviceLocation)) {
                    $providers[] = $provider;
                }
            }

            $currentlyAssignProvider = $booking->provider_id
                ? $this->provider->withCount('bookings', 'reviews')->find($booking->provider_id)
                : null;

            $sort_by = 'default';
            $scheduledNext = ($booking->followups ?? collect())->where('status', 'scheduled')->sortBy('date');
            $nextFollowupCustomer = $scheduledNext->where('for', 'customer')->first();
            $nextFollowupProvider = $scheduledNext->where('for', 'provider')->first();
            $customerName = $booking->customer ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? '')) : ($customerAddress->contact_person_name ?? '');
            $customerPhone = $booking->customer ? ($booking->customer->phone ?? '') : ($customerAddress->contact_person_number ?? '');
            return view('bookingmodule::admin.booking.status', compact('booking', 'webPage', 'servicemen', 'customerAddress', 'category', 'subCategory', 'services', 'providers', 'zones', 'sort_by', 'currentlyAssignProvider', 'nextFollowupCustomer', 'nextFollowupProvider', 'customerName', 'customerPhone'));
        } elseif ($webPage === 'followups') {
            $booking = $this->booking->with(['followups.createdBy', 'customer', 'provider', 'service_address'])->find($id);
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
            $repeatIds = $booking->repeat->pluck('id')->toArray();

            // Delete transactions for this booking (and its repeats) and reverse account balances
            $txQuery = Transaction::where('booking_id', $booking->id);
            if (!empty($repeatIds)) {
                $txQuery->orWhereIn('booking_repeat_id', $repeatIds);
            }
            $transactions = $txQuery->get();

            $accountDeltas = []; // [user_id => [account_key => delta]]
            foreach ($transactions as $tx) {
                // Credit to to_user's to_user_account (standard case)
                if ($tx->to_user_id && $tx->to_user_account && $tx->credit > 0) {
                    $accountDeltas[$tx->to_user_id][$tx->to_user_account] = ($accountDeltas[$tx->to_user_id][$tx->to_user_account] ?? 0) - $tx->credit;
                }
                // Credit to from_user's from_user_account when to_user_account is null (e.g. provider account_payable, admin account_receivable)
                if ($tx->from_user_id && $tx->from_user_account && $tx->credit > 0 && empty($tx->to_user_account)) {
                    $accountDeltas[$tx->from_user_id][$tx->from_user_account] = ($accountDeltas[$tx->from_user_id][$tx->from_user_account] ?? 0) - $tx->credit;
                }
                // Debit from from_user's from_user_account
                if ($tx->from_user_id && $tx->from_user_account && $tx->debit > 0) {
                    $accountDeltas[$tx->from_user_id][$tx->from_user_account] = ($accountDeltas[$tx->from_user_id][$tx->from_user_account] ?? 0) + $tx->debit;
                }
                // Debit from to_user's to_user_account when from_user_account is null
                if ($tx->to_user_id && $tx->to_user_account && $tx->debit > 0 && empty($tx->from_user_account)) {
                    $accountDeltas[$tx->to_user_id][$tx->to_user_account] = ($accountDeltas[$tx->to_user_id][$tx->to_user_account] ?? 0) + $tx->debit;
                }
            }

            foreach ($accountDeltas as $userId => $deltas) {
                $account = Account::where('user_id', $userId)->first();
                if ($account) {
                    foreach ($deltas as $accountKey => $delta) {
                        if (in_array($accountKey, ['balance_pending', 'received_balance', 'account_payable', 'account_receivable', 'total_withdrawn'])) {
                            $account->$accountKey = max(0, ($account->$accountKey ?? 0) + $delta);
                        }
                    }
                    $account->save();
                }
            }

            $txDeleteQuery = Transaction::where('booking_id', $booking->id);
            if (!empty($repeatIds)) {
                $txDeleteQuery->orWhereIn('booking_repeat_id', $repeatIds);
            }
            $txDeleteQuery->delete();

            $ledgerQuery = LedgerTransaction::where('booking_id', $booking->id);
            if (!empty($repeatIds)) {
                $ledgerQuery->orWhereIn('booking_repeat_id', $repeatIds);
            }
            $ledgerQuery->delete();

            foreach ($booking->repeat as $repeat) {
                $repeat->detail()->delete();
                $repeat->details_amounts()->delete();
                $repeat->statusHistories()->delete();
                $repeat->scheduleHistories()->delete();
                $repeat->repeatHistories()->delete();
                $repeat->delete();
            }

            $booking->extra_services()->delete();
            $booking->detail()->delete();
            $booking->details_amounts()->delete();
            $booking->schedule_histories()->delete();
            $booking->status_histories()->delete();
            $booking->booking_offline_payments()->delete();
            $booking->ignores()->delete();
            $booking->reviews()->delete();
            $booking->booking_partial_payments()->delete();

            $booking->delete();
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
                    $detail->variation = Variation::where('variant_key', $detail->variant_key)
                        ->where('service_id', $detail->service_id)
                        ->where('zone_id', $booking->zone_id)
                        ->first();
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
            if (!$nextService) {
                $nextService = collect($booking['repeats'])->firstWhere('booking_status', 'accepted');
            }
            if (!$nextService) {
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
            return view('bookingmodule::admin.booking.repeat-booking-details', compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'));

        }elseif ($webPage == 'service_log'){
            return view('bookingmodule::admin.booking.service-log', compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'));

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
        Validator::make($request->all(), [
            'web_page' => 'required|in:details,status',
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
            return view('bookingmodule::admin.booking.rebooking-ongoing', compact('zoneCenter', 'currentZone', 'centerLat', 'centerLng', 'area', 'booking', 'servicemen', 'webPage', 'customerAddress', 'services', 'zones', 'category', 'subCategory', 'providers', 'sort_by'));

        }elseif ($request->web_page == 'status') {
            return view('bookingmodule::admin.booking.repeat-status', compact('booking', 'webPage', 'servicemen', 'customerAddress', 'category', 'subCategory', 'services', 'providers', 'zones', 'sort_by'));
        }
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

        $validated = $request->validate([
            'booking_status' => 'required|in:' . implode(',', array_column(BOOKING_STATUSES, 'key')),
        ]);

        $booking = $this->booking->find($bookingId);
        $repeatBooking = $this->bookingRepeat->find($bookingId);

        try {
            if ($booking) {
                if($booking->booking_status == 'ongoing' && $request['booking_status'] == 'canceled'){
                    return response()->json(BOOKING_ALREADY_ONGOING, 200);
                }
                return $this->updateBookingStatus($booking, $validated['booking_status'], $request);
            }

            if ($repeatBooking) {
                return $this->updateRepeatBookingStatus($repeatBooking, $validated['booking_status'], $request);
            }
        } catch (\RuntimeException $e) {
            return response()->json(response_formatter([
                'response_code' => 'default_400',
                'message' => $e->getMessage(),
            ]), 422);
        }

        return response()->json(response_formatter(DEFAULT_204), 204);
    }

    private function updateBookingStatus($booking, string $status, Request $request): JsonResponse
    {
        $booking->booking_status = $status;

        if ($booking->isDirty('booking_status')) {
            DB::transaction(function () use ($booking, $status, $request) {
                if ($booking->repeat) {
                    foreach ($booking->repeat->whereIn('booking_status', ['pending', 'accepted', 'ongoing']) as $repeat) {
                        $repeat->update([
                            'provider_id' => $request->provider_id,
                            'booking_status' => $status,
                            'serviceman_id' => null,
                        ]);

                        $this->logBookingStatusHistory($repeat->id, $status, $request->user()->id, $booking->id);
                    }

                    if ($status == 'canceled' && $booking->repeat->contains('booking_status', 'completed')) {
                        $booking->booking_status = 'completed';
                    }
                }

                $booking->save();
                $this->logBookingStatusHistory(null, $status, $request->user()->id, $booking->id);
            });

            return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
        }

        return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
    }

    private function updateRepeatBookingStatus($repeatBooking, string $status, Request $request): JsonResponse
    {
        $repeatBooking->booking_status = $status;

        if ($status == 'canceled' && $repeatBooking->extra_fee > 0){

            $booking = $this->booking->where('id', $repeatBooking->booking_id)->first();
            $sortedRepeats = $booking->repeat->sortBy(function ($repeat) {
                $parts = explode('-', $repeat->readable_id);
                $suffix = end($parts);
                return $this->readableIdToNumber($suffix);
            });

            $booking['repeats'] = $sortedRepeats->values()->toArray();

            $nextService = collect($booking['repeats'])
                ->where('booking_status', 'ongoing')
                ->skip(1)
                ->first();

            if (!$nextService) {
                $nextService = collect($booking['repeats'])
                    ->where('booking_status', 'accepted')
                    ->skip(1)
                    ->first();
            }

            if (!$nextService) {
                $nextService = collect($booking['repeats'])
                    ->where('booking_status', 'pending')
                    ->skip(1)
                    ->first();
            }

            if (isset($nextService)) {
                $nextServiceId = $nextService['id'];
                $nextServiceFee = $this->bookingRepeat->where('id', $nextServiceId)->first();
                $nextServiceFee->extra_fee = $repeatBooking->extra_fee;
                $nextServiceFee->total_booking_amount += $repeatBooking->extra_fee;
                $nextServiceFee->save();
            }

            $repeatBooking->total_booking_amount -= $repeatBooking->extra_fee;
            $repeatBooking->extra_fee = 0;
        }

        if ($repeatBooking->isDirty('booking_status')) {
            DB::transaction(function () use ($repeatBooking, $status, $request) {

                $repeatBooking->save();
                $this->logBookingStatusHistory($repeatBooking->id, $status, $request->user()->id, $repeatBooking->booking_id);

                $relatedRepeats = $this->bookingRepeat->where('booking_id', $repeatBooking->booking_id)->get();
                if ($relatedRepeats->every(fn($repeat) => !in_array($repeat->booking_status, ['pending', 'accepted', 'ongoing']))) {
                    $repeatBooking->booking->update(['booking_status' => 'completed', 'is_paid' => 1]);
                }

                if (in_array($repeatBooking->booking_status, ['ongoing', 'completed', 'canceled'])) {
                    if ($repeatBooking->booking->booking_status != 'ongoing' && $repeatBooking->booking->booking_status != 'completed' && $repeatBooking->booking->booking_status != 'canceled') {
                        $repeatBooking->booking->booking_status = 'ongoing';
                        $repeatBooking->booking->save();
                    }
                }
            });

            return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
        }

        return response()->json(response_formatter(NO_CHANGES_FOUND), 200);
    }

    private function logBookingStatusHistory(?string $repeatId, string $status, string $changedBy, string $bookingId): void
    {
        $this->bookingStatusHistory->create([
            'booking_id' => $bookingId,
            'booking_repeat_id' => $repeatId,
            'changed_by' => $changedBy,
            'booking_status' => $status,
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

        Validator::make($request->all(), [
            'booking_status' => 'required|in:' . implode(',', array_column(BOOKING_STATUSES, 'key')),
        ]);

        $repeatBooking = $this->bookingRepeat->where('id', $bookingId)->first();
        if (isset($repeatBooking)){
            $repeatBooking->booking_status = $request['booking_status'];

            $bookingStatusHistory = $this->bookingStatusHistory;
            $bookingStatusHistory->booking_id = $bookingId;
            $bookingStatusHistory->changed_by = $request->user()->id;
            $bookingStatusHistory->booking_status = $request['booking_status'];
            $bookingStatusHistory->booking_repeat_id = $repeatBooking->id;

            if ($repeatBooking->isDirty('booking_status')) {
                DB::transaction(function () use ($bookingStatusHistory, $repeatBooking) {
                    $repeatBooking->save();
                    $bookingStatusHistory->save();
                });

                Toastr::success(translate(DEFAULT_STATUS_UPDATE_200['message']));
                return back();
            }
            Toastr::success(translate(NO_CHANGES_FOUND['message']));
            return back();
        }
        Toastr::success(translate(DEFAULT_204['message']));
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
            'status' => 'required|in:approve,deny,cancel',
            'booking_deny_note' => 'required_if:status,deny|string|nullable'
        ]);

        $booking = $this->booking->where('id', $bookingId)->first();
        if (isset($booking) && $request->status == 'deny') {
            $booking->is_verified = 2;
            $booking->save();

            $additionalInfo = new $this->bookingAdditionalInformation;
            $additionalInfo->booking_id = $booking->id;
            $additionalInfo->key = 'booking_deny_note';
            $additionalInfo->value = $request->booking_deny_note;
            $additionalInfo->save();

            Toastr::success(translate(DEFAULT_STORE_200['message']));
            return back();
        } elseif (isset($booking) && $request->status == 'approve') {
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
                    $title = get_push_notification_message('booking_accepted', 'provider_notification', $provider?->owner?->current_language_key);
                    if (!is_null($fcmToken) && (!business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values || $provider?->is_suspended == 0)) device_notification($fcmToken, $title, null, null, $booking->id, 'booking');
                }
            }

            Toastr::success(translate(DEFAULT_STATUS_UPDATE_200['message']));
            return back();
        } elseif (isset($booking) && $request->status == 'cancel') {
            $booking->booking_status = 'canceled';
            $booking->is_verified = 3;

            $bookingStatusHistory = $this->bookingStatusHistory;
            $bookingStatusHistory->booking_id = $bookingId;
            $bookingStatusHistory->changed_by = $request->user()->id;
            $bookingStatusHistory->booking_status = 'canceled';

            if ($booking->isDirty('booking_status')) {
                DB::transaction(function () use ($bookingStatusHistory, $booking) {
                    $booking->save();
                    $bookingStatusHistory->save();
                });

                Toastr::success(translate(DEFAULT_STATUS_UPDATE_200['message']));
                return back();
            }
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
                $booking->save();
                $bookingScheduleHistory->save();
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
                $bookingRepeat->save();
                $bookingRepeatScheduleHistory->save();
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
                $bookingRepeat->save();
                $bookingRepeatScheduleHistory->save();

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
            $booking->serviceman_id = $request->serviceman_id;
            $booking->save();

            if (!is_null($booking->repeat)) {
                foreach ($booking->repeat->whereIn('booking_status', ['pending', 'accepted', 'ongoing']) as $bookingRepeat) {
                    $bookingRepeat->serviceman_id = $request->serviceman_id;
                    $bookingRepeat->save();
                }
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

            $bookingRepeat->serviceman_id = $request->serviceman_id;
            $bookingRepeat->save();

            if ($bookingRepeat->booking) {
                $bookingRepeat->booking->serviceman_id = $request->serviceman_id;
                $bookingRepeat->booking->save();
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
     * Display a listing of the resource.
     * @param $service_address_id
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function serviceAddressUpdate($service_address_id, Request $request): RedirectResponse
    {
        $this->authorize('booking_edit');

        Validator::make($request->all(), [
            'city' => 'required',
            'street' => 'required',
            'zip_code' => 'required',
            'country' => 'required',
            'address' => 'required',
            'contact_person_name' => 'required',
            'contact_person_number' => 'required',
            'address_label' => 'required',
            'zone_id' => 'required|uuid',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $userAddress = $this->userAddress->find($service_address_id);
        $userAddress->city = $request['city'];
        $userAddress->street = $request['street'];
        $userAddress->zip_code = $request['zip_code'];
        $userAddress->country = $request['country'];
        $userAddress->address = $request['address'];
        $userAddress->contact_person_name = $request['contact_person_name'];
        $userAddress->contact_person_number = $request['contact_person_number'];
        $userAddress->address_label = $request['address_label'];
        $userAddress->zone_id = $request['zone_id'];
        $userAddress->lat = $request['latitude'];
        $userAddress->lon = $request['longitude'];
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
        $request->validate([
            'booking_status' => 'in:' . implode(',', array_column(BOOKING_STATUSES, 'key')) . ',all',
        ]);

        $bookingStatus = $request->input('booking_status', 'pending');

        $maxBookingAmount = (business_config('max_booking_amount', 'booking_setup'))->live_values;
        $items = $this->booking
            ->with(['customer'])
            ->search($request['search'], ['readable_id'])
            ->when($bookingStatus != 'all', function ($query) use ($bookingStatus, $maxBookingAmount, $request) {
                $query->when($bookingStatus == 'pending', function ($query) use ($maxBookingAmount) {
                    $query->adminPendingBookings($maxBookingAmount);
                })->when($bookingStatus == 'accepted', function ($query) use ($maxBookingAmount) {
                    $query->adminAcceptedBookings($maxBookingAmount);
                })->ofBookingStatus($request['booking_status']);
            })
            ->when($request['service_type'] != 'all', function ($query) use ($request) {
                return $query->ofRepeatBookingStatus($request['service_type'] === 'repeat' ? 1 : ($request['service_type'] === 'regular' ? 0 : null));
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
        }, 'customer', 'provider', 'serviceman', 'status_histories.user', 'extra_services', 'booking_partial_payments'])->find($id);

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

        $variations = Variation::where('service_id', $request['service_id'])
            ->where('zone_id', $request['zone_id'])
            ->where('price', '>', 0)
            ->get();
        return response()->json(response_formatter(DEFAULT_200, $variations, null), 200);
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
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $service = Service::active()
            ->with(['category.category_discount', 'category.campaign_discount', 'service_discount'])
            ->where('id', $request['service_id'])
            ->with(['variations' => fn($query) => $query->where('variant_key', $request['variant_key'])->where('zone_id', $request['zone_id'])])
            ->first();

        $quantity = $request['quantity'];
        $variation_price = $service?->variations[0]?->price;

        $basic_discount = basic_discount_calculation($service, $variation_price * $quantity);
        $campaign_discount = campaign_discount_calculation($service, $variation_price * $quantity);
        $subtotal = round($variation_price * $quantity, 2);

        $applicable_discount = ($campaign_discount >= $basic_discount) ? $campaign_discount : $basic_discount;

        $tax = round((($variation_price * $quantity - $applicable_discount) * $service['tax']) / 100, 2);

        $basic_discount = $basic_discount > $campaign_discount ? $basic_discount : 0;
        $campaign_discount = $campaign_discount >= $basic_discount ? $campaign_discount : 0;

        $data = collect([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'variant_key' => $service?->variations[0]?->variant_key,
            'quantity' => $request['quantity'],
            'service_cost' => $variation_price,
            'total_discount_amount' => $basic_discount + $campaign_discount,
            'coupon_code' => null,
            'tax_amount' => round($tax, 2),
            'total_cost' => round($subtotal - $basic_discount - $campaign_discount + $tax, 2),
            'zone_id' => $request['zone_id']
        ]);

        return response()->json([
            'view' => view('bookingmodule::admin.booking.partials.details.table-row', compact('data'))->render()
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
            'extra_fee' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 200);
        }

        $quantity = (float) ($request->input('quantity', 1));
        $extraFee = (float) ($request->input('extra_fee', 0));
        $service = Service::active()
            ->with(['category.category_discount', 'category.campaign_discount', 'service_discount'])
            ->where('id', $request['service_id'])
            ->with(['variations' => fn($q) => $q->where('variant_key', $request['variant_key'])->where('zone_id', $request['zone_id'])])
            ->first();

        if (!$service || !isset($service->variations[0])) {
            return response()->json(response_formatter(DEFAULT_404, null), 200);
        }

        $variationPrice = $service->variations[0]->price ?? 0;
        $basicDiscount = basic_discount_calculation($service, $variationPrice * $quantity);
        $campaignDiscount = campaign_discount_calculation($service, $variationPrice * $quantity);
        $subtotal = round($variationPrice * $quantity, 2);
        $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
        $tax = round((($variationPrice * $quantity - $applicableDiscount) * $service->tax) / 100, 2);
        $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
        $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;
        $totalCost = round($subtotal - $basicDiscount - $campaignDiscount + $tax + $extraFee, 2);

        $data = [
            'service_cost' => round($variationPrice * $quantity, 2),
            'total_discount_amount' => round($basicDiscount + $campaignDiscount, 2),
            'tax_amount' => $tax,
            'extra_fee' => $extraFee,
            'total_cost' => $totalCost,
        ];

        return response()->json(response_formatter(DEFAULT_200, $data, null), 200);
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

        $validator = Validator::make(['zone_ids' => $zoneIds], [
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $categories = $this->category
            ->withoutGlobalScope('translate')
            ->where('position', 1)
            ->where('is_active', 1)
            ->whereHas('zones', function ($query) use ($zoneIds) {
                $query->whereIn('zones.id', $zoneIds);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

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
            'sub_category_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        // Get subscribed provider IDs for this subcategory
        $subscribedProviderIds = SubscribedService::where('sub_category_id', $request['sub_category_id'])
            ->where('is_subscribed', 1)
            ->pluck('provider_id')
            ->toArray();

        // Get only subscribed providers with contact person info
        $providers = $this->provider
            ->whereIn('id', $subscribedProviderIds)
            ->get()
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
        ])->validate();

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
     * Fields: amount, received_by (company|provider), transaction_id (required if received_by=company), date (default today).
     */
    public function addPayment(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $this->authorize('booking_can_manage_status');
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'received_by' => 'required|in:company,provider',
            'transaction_id' => 'required_if:received_by,company|nullable|string|max:100',
            'date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
            }
            Toastr::error(implode(' ', $validator->errors()->all()));
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

        $bookingTotal = get_booking_total_amount($booking);
        $totalPaid = get_booking_total_paid($booking);
        $dueAmount = round(max(0, $bookingTotal - $totalPaid), 2);

        $amount = (float) $request->amount;
        if ($amount > $dueAmount) {
            $message = translate('Amount cannot exceed the due amount. Due amount') . ': ' . with_currency_symbol($dueAmount);
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }
        $receivedBy = $request->received_by;
        $transactionId = $request->transaction_id;
        $date = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : now()->toDateString();

        DB::transaction(function () use ($booking, $amount, $receivedBy, $transactionId, $date) {
            $paidWith = 'admin_entry';
            $booking->booking_partial_payments()->create([
                'paid_with' => $paidWith,
                'transaction_id' => $receivedBy === 'company' ? $transactionId : null,
                'paid_amount' => $amount,
                'due_amount' => 0,
                'received_by' => $receivedBy,
            ]);

            if ($receivedBy === 'company') {
                ledger_record_in([
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                    'booking_id' => $booking->id,
                    'payment_method' => $paidWith,
                    'date' => $date,
                    'received_by' => LedgerTransaction::RECEIVED_BY_COMPANY,
                    'created_by' => auth()->id(),
                ]);
            }

            $totalPaid = get_booking_total_paid($booking->fresh());
            $bookingTotal = get_booking_total_amount($booking);
            if ($totalPaid >= $bookingTotal) {
                $booking->is_paid = 1;
                $booking->save();
            }
        });

        if ($request->wantsJson()) {
            return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
        }
        Toastr::success(translate('Payment added successfully.'));
        return back();
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

        if ($booking->booking_status !== 'canceled') {
            $message = translate('Refund is only available for canceled bookings.');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        if ($booking->booking_status === 'refunded') {
            $message = translate('This booking has already been refunded.');
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        $maxRefund = get_booking_total_paid($booking);
        $amount = (float) $request->amount;
        if ($amount > $maxRefund) {
            $message = translate('Refund amount cannot exceed amount paid by customer. Max') . ': ' . with_currency_symbol($maxRefund);
            if ($request->wantsJson()) {
                return response()->json(response_formatter(DEFAULT_400, null, ['amount' => [$message]]), 400);
            }
            Toastr::error($message);
            return back();
        }

        $transactionId = $request->transaction_id;
        $date = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : now()->toDateString();

        DB::transaction(function () use ($booking, $amount, $transactionId, $date) {
            ledger_record_out([
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'booking_id' => $booking->id,
                'reason' => LedgerTransaction::REASON_REFUND,
                'date' => $date,
            ]);

            $booking->booking_status = 'refunded';
            $booking->save();
            $this->logBookingStatusHistory(null, 'refunded', request()->user()->id, $booking->id);
        });

        if ($request->wantsJson()) {
            return response()->json(response_formatter(DEFAULT_UPDATE_200, null), 200);
        }
        Toastr::success(translate('Refund recorded successfully. Booking status updated to Refunded.'));
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
                        $detail->variation = Variation::where('variant_key', $detail->variant_key)
                            ->where('service_id', $detail->service_id)
                            ->where('zone_id', $booking->zone_id)
                            ->first();
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
            return view('bookingmodule::admin.booking.service-log', compact('booking', 'webPage', 'servicemen', 'customerAddress', 'category', 'subCategory', 'services', 'providers', 'zones', 'sort_by'));
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
                "city" => $request->city ?? $existingAddress['city'] ?? null,
                "street" => $request->street ?? $existingAddress['street'] ?? "",
                "zip_code" => $request->zip_code ?? $existingAddress['zip_code'] ?? "",
                "country" => $request->country ?? $existingAddress['country'] ?? null,
                "address" => $request->address ?? $existingAddress['address'] ?? null,
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
                "city" => $request->city ?? $existingAddress['city'] ?? null,
                "street" => $request->street ?? $existingAddress['street'] ?? "",
                "zip_code" => $request->zip_code ?? $existingAddress['zip_code'] ?? "",
                "country" => $request->country ?? $existingAddress['country'] ?? null,
                "address" => $request->address ?? $existingAddress['address'] ?? null,
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

}
