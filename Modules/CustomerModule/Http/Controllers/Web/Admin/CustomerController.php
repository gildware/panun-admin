<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use App\Traits\UploadSizeHelperTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\CustomerModule\Emails\CustomerRegistrationMail;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\ProviderManagement\Services\CustomerPerformanceService;
use Modules\ReviewModule\Entities\Review;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\UserManagement\Entities\UserVerification;
use Modules\ZoneManagement\Services\ZoneGeometryService;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerController extends Controller
{
    protected User $user;
    private Booking $booking;
    private Review $review;
    private UserAddress $address;
    private UserVerification $userVerification;

    use AuthorizesRequests;
    use UploadSizeHelperTrait;

    public function __construct(Booking $booking, User $user, Review $review, UserAddress $address, UserVerification $userVerification)
    {
        $this->booking = $booking;
        $this->user = $user;
        $this->review = $review;
        $this->address = $address;
        $this->userVerification = $userVerification;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function create(Request $request): View|Factory|Application
    {
        $this->authorize('customer_add');
        return view('customermodule::admin.create');
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function index(Request $request): View|Factory|Application
    {
        $this->authorize('customer_view');
        $search = $request->has('search') ? $request['search'] : '';
        $status = $request->has('status') ? $request['status'] : 'all';
        $from = $request->get('from', '');
        $to = $request->get('to', '');
        $sort_by = $request->get('sort_by', 'latest');
        $limit = $request->get('limit');

        $queryParam = ['search' => $search, 'status' => $status, 'from' => $from, 'to' => $to, 'sort_by' => $sort_by, 'limit' => $limit];

        $query = $this->user->withCount(['bookings'])->inCustomerDirectory()
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('email', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($status != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })
            ->when($from, function ($query) use ($from) {
                return $query->whereDate('created_at', '>=', $from);
            })
            ->when($to, function ($query) use ($to) {
                return $query->whereDate('created_at', '<=', $to);
            })
            ->when($sort_by === 'latest', function ($query) {
                return $query->latest();
            })
            ->when($sort_by === 'oldest', function ($query) {
                return $query->oldest();
            })
            ->when($sort_by === 'ascending', function ($query) {
                return $query->orderBy('first_name', 'asc');
            })
            ->when($sort_by === 'descending', function ($query) {
                return $query->orderBy('first_name', 'desc');
            });

        if (isset($limit) && $limit > 0) {
            $customers = $query->take($limit)->get(); // limit results
            $perPage = pagination_limit();
            $page =  $request?->page ?? 1;
            $offset = ($page - 1) * $perPage;
            $itemsForCurrentPage = $customers->slice($offset, $perPage);
            $customers = new \Illuminate\Pagination\LengthAwarePaginator(
                $itemsForCurrentPage,
                $customers->count(),
                $perPage,
                $page,
                ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
            );
        } else {
            $customers = $query
                ->paginate(pagination_limit())
                ->appends($queryParam);
        }


        return view('customermodule::admin.list', compact('customers', 'search', 'status', 'queryParam'));
    }

    /**
     * Show top customers by performance score (highest first), among those with at least one completed booking.
     */
    public function topCustomers(Request $request): View|Factory|Application
    {
        $this->authorize('customer_view');

        $customers = $this->user
            ->inCustomerDirectory()
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->ofBookingStatus('completed');
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        $performanceService = app(CustomerPerformanceService::class);
        $metrics = $performanceService->getAggregatedCustomerPerformanceMetrics($customers->pluck('id')->all());

        $customers = $customers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->values()
            ->take(20)
            ->map(function ($customer) use ($metrics) {
                $customer->performance_score = (int) ($metrics->get($customer->id)->performance_score ?? 0);

                return $customer;
            });

        return view('customermodule::admin.top_customers', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('customer_add');

        $check = $this->validateUploadedFile($request, ['profile_image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|unique:users,phone',
            'password' => 'required|min:6',
            'confirm_password' => 'same:password',
            'gender' => 'in:male,female,others',
            'profile_image' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        $password = $request->password;

        $user = $this->user;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->profile_image = $request->has('profile_image') ? file_uploader('user/profile_image/', APPLICATION_IMAGE_FORMAT, $request->profile_image) : 'default.png';
        $user->date_of_birth = $request->date_of_birth;
        $user->gender = $request->gender ?? 'male';
        $user->password = bcrypt($request->password);
        $user->user_type = 'customer';
        $user->customer_app_access = true;
        $user->is_active = 1;
        $user->save();

        try {
            $otp = env('APP_ENV') != 'live' ? '1234' : rand(1000, 9999);

            $webUrl = business_config('web_url', 'landing_button_and_links');
            $token = base64_encode(json_encode(["identity" => $user->email, "identity_type" => "email", "otp" => $otp, "from_url" => 1]));

            if (str_ends_with($webUrl->live_values, '/')) {
                $url = $webUrl->live_values . 'change-password?token=' . urlencode($token);
            } else {
                $url = $webUrl->live_values . '/change-password?token=' . urlencode($token);
            }
            $regByAdmin = isNotificationActive(null, 'registration', 'email', 'user');
            if ($regByAdmin) {
                $emailStatus = business_config('email_config_status', 'email_config')->live_values;

                if($emailStatus){
                    try {
                        Mail::to($user->email)->send(new CustomerRegistrationMail($user, $password, $otp, $url));
                    }catch (\Exception $exception){
                        //
                    }
                }
            }

            $this->userVerification->updateOrCreate([
                'identity' => $user->email,
                'identity_type' => "email"
            ], [
                'identity' => $user->email,
                'identity_type' => 'email',
                'user_id' => null,
                'otp' => $otp,
                'expires_at' => now()->addMinute(60),
            ]);


        } catch (\Exception $exception) {
            info($exception);
        }

        Toastr::success(translate(REGISTRATION_200['message']));
        return back();
    }

    /**
     * Quickly store a new customer from admin (e.g., booking form) with a default password.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('customer_add');

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|unique:users,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $defaultPassword = config('app.default_customer_password', '12345678');

        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->profile_image = 'default.png';
        $user->gender = 'male';
        $user->password = bcrypt($defaultPassword);
        $user->user_type = 'customer';
        $user->customer_app_access = true;
        $user->is_active = 1;
        $user->save();

        return response()->json([
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'phone' => $user->phone,
        ], 200);
    }

    /**
     * Get all addresses for a given customer.
     *
     * @param string $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function addresses(string $id): JsonResponse
    {
        $this->authorize('customer_view');

        $addresses = $this->address
            ->where('user_id', $id)
            ->get(['id', 'address_label', 'address', 'landmark', 'lat', 'lon']);

        return response()->json($addresses, 200);
    }

    /**
     * Single address for admin quick edit (booking flow, customer detail).
     */
    public function quickShowAddress(string $id, string $addressId): JsonResponse
    {
        $this->authorize('customer_view');

        $address = $this->address
            ->where('user_id', $id)
            ->where('id', $addressId)
            ->first(['id', 'address', 'address_label', 'landmark', 'lat', 'lon']);

        if (!$address) {
            return response()->json(['message' => translate('not_found')], 404);
        }

        return response()->json($address, 200);
    }

    /**
     * Update address using the same fields as quickStoreAddress.
     */
    public function quickUpdateAddress(Request $request, string $id, string $addressId): JsonResponse
    {
        if (!$request->user()->can('customer_update') && !$request->user()->can('customer_add')) {
            throw new AuthorizationException();
        }

        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'address_label' => 'required|string|max:191',
            'landmark' => 'nullable|string|max:500',
            'lat' => 'nullable|string|max:191',
            'lon' => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $address = $this->address
            ->where('user_id', $id)
            ->where('id', $addressId)
            ->first();

        if (!$address) {
            return response()->json(['message' => translate('not_found')], 404);
        }

        $address->address = $request->address;
        $address->address_label = $request->address_label;
        $address->landmark = $request->landmark;
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = null;
        $address->street = null;
        $address->zip_code = null;
        $address->country = null;
        $this->applyZoneIdFromCoords($address, $request->lat, $request->lon);
        $address->save();

        return response()->json([
            'id' => $address->id,
            'label' => $address->address_label,
            'full_address' => $this->formatSimplifiedFullAddress($address),
        ], 200);
    }

    /**
     * Quickly store a new address for a customer.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function quickStoreAddress(Request $request, string $id): JsonResponse
    {
        $this->authorize('customer_add');

        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'address_label' => 'required|string|max:191',
            'landmark' => 'nullable|string|max:500',
            'lat' => 'nullable|string|max:191',
            'lon' => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $address = $this->address;
        $address->user_id = $id;
        $address->address = $request->address;
        $address->address_label = $request->address_label;
        $address->landmark = $request->landmark;
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = null;
        $address->street = null;
        $address->zip_code = null;
        $address->country = null;
        $address->zone_id = $this->resolveZoneIdForAddressCoords($request->lat, $request->lon);
        $address->save();

        return response()->json([
            'id' => $address->id,
            'label' => $address->address_label,
            'full_address' => $this->formatSimplifiedFullAddress($address),
        ], 200);
    }

    public function overview(Request $request, string $id): JsonResponse
    {
        $search = $request->has('search') ? $request['search'] : '';
        $webPage = $request->has('web_page') ? 'review' : 'general';
        $queryParam = ['search' => $search, 'web_page' => $webPage];

        $customer = $this->user->where(['id' => $id])->with(['bookings', 'addresses', 'reviews'])->first();
        $totalBookingPlaced = $this->booking->where(['customer_id' => $id])->count();
        $totalBookingAmount = $this->booking->where(['customer_id' => $id])->sum('total_booking_amount');
        $completeBookings = $this->booking->where(['customer_id' => $id, 'booking_status' => 'completed'])->count();
        $canceledBookings = $this->booking->where(['customer_id' => $id, 'booking_status' => 'canceled'])->count();
        $ongoingBookings = $this->booking->where(['customer_id' => $id, 'booking_status' => 'ongoing'])->count();

        $data = [
            'total_booking_placed' => $totalBookingPlaced,
            'total_booking_amount' => $totalBookingAmount,
            'complete_bookings' => $completeBookings,
            'canceled_bookings' => $canceledBookings,
            'ongoing_bookings' => $ongoingBookings,
            'customer_details' => $customer
        ];

        return response()->json(response_formatter(DEFAULT_200, $data), 200);
    }

    public function bookings(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $bookings = $this->booking->with(['provider.owner'])->where(['customer_id' => $id])
            ->when($request->has('string'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $keys = explode(' ', base64_decode($request['string']));
                    foreach ($keys as $key) {
                        $query->orWhere('readable_id', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->orderBy('created_at', 'desc')->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $bookings), 200);
    }

    public function reviews(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $reviews = $this->review->where(['customer_id' => $id])->orderBy('created_at', 'desc')->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $reviews), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function edit(string $id): Application|Factory|View
    {
        $this->authorize('customer_update');
        $customer = $this->user->inCustomerDirectory()->find($id);
        return view('customermodule::admin.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return Application|Redirector|RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, string $id): Redirector|RedirectResponse|Application
    {
        $this->authorize('customer_update');

        $check = $this->validateUploadedFile($request, ['profile_image']);
        if ($check !== true) {
            return $check;
        }

        $customer = $this->user->inCustomerDirectory()->find($id);

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'confirm_password' => !is_null($request->password) ? 'required|same:password' : '',
            'gender' => 'in:male,female,others',
            'profile_image' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
        ]);

        if (User::where('email', $request['email'])->where('id', '!=', $customer->id)->exists()) {
            Toastr::error(translate('Email already taken'));
            return back();
        }
        if (User::where('phone', $request['phone'])->where('id', '!=', $customer->id)->exists()) {
            Toastr::error(translate('Phone already taken'));
            return back();
        }

        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->profile_image = $request->has('profile_image') ? file_uploader('user/profile_image/', APPLICATION_IMAGE_FORMAT, $request->profile_image) : $customer->profile_image;
        $customer->date_of_birth = $request->date_of_birth;
        $customer->gender = $request->has('gender') ? $request->gender : $customer->gender;
        $customer->save();

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));
        return redirect('admin/customer/list');
    }


    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $this->authorize('customer_delete');
        $user = $this->user->inCustomerDirectory()->where('id', $id)->first();
        if (isset($user)) {
            if ($user->provider()->exists()) {
                Toastr::error(translate('This user is linked to a provider business and cannot be deleted as a customer.'));

                return back();
            }
            file_remover('user/profile_image/', $user->profile_image);
            foreach ($user->identification_image as $image_name) {
                file_remover('user/identity/', $image_name);
            }
            $user->delete();

            Toastr::success(translate(DEFAULT_DELETE_200['message']));
            return back();
        }
        Toastr::success(translate(DEFAULT_204['message']));
        return back();
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function statusUpdate(Request $request, $id): JsonResponse
    {
        $this->authorize('customer_manage_status');
        $user = $this->user->inCustomerDirectory()->where('id', $id)->first();
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function storeAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'address_label' => 'required|string|max:191',
            'landmark' => 'nullable|string|max:500',
            'lat' => 'nullable|string|max:191',
            'lon' => 'nullable|string|max:191',
            'address_type' => 'nullable|in:service,billing',
            'contact_person_name' => 'nullable|string|max:191',
            'contact_person_number' => 'nullable|string|max:191',
            'customer_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $address = $this->address;
        $address->user_id = $request['customer_id'];
        $address->address = $request->address;
        $address->address_label = $request->address_label;
        $address->landmark = $request->landmark;
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = null;
        $address->street = null;
        $address->zip_code = null;
        $address->country = null;
        $address->address_type = $request->input('address_type', 'service');
        $address->contact_person_name = $request->input('contact_person_name', '');
        $address->contact_person_number = $request->input('contact_person_number', '');
        $address->zone_id = $this->resolveZoneIdForAddressCoords($request->lat, $request->lon);
        $address->save();

        return response()->json(response_formatter(DEFAULT_STORE_200), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return JsonResponse
     */
    public function editAddress(string $id): JsonResponse
    {
        $address = $this->address->where(['user_id' => $id])->where('id', $id)->first();
        if (isset($address)) {
            return response()->json(response_formatter(DEFAULT_200, $address), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateAddress(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'address_label' => 'required|string|max:191',
            'landmark' => 'nullable|string|max:500',
            'lat' => 'nullable|string|max:191',
            'lon' => 'nullable|string|max:191',
            'address_type' => 'nullable|in:service,billing',
            'contact_person_name' => 'nullable|string|max:191',
            'contact_person_number' => 'nullable|string|max:191',
            'customer_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $address = $this->address->where(['user_id' => $request['customer_id']])->where('id', $id)->first();
        if (!isset($address)) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }

        $address->address = $request->address;
        $address->address_label = $request->address_label;
        $address->landmark = $request->landmark;
        $address->lat = $request->lat;
        $address->lon = $request->lon;
        $address->city = null;
        $address->street = null;
        $address->zip_code = null;
        $address->country = null;
        $address->address_type = $request->input('address_type', $address->address_type ?? 'service');
        $address->contact_person_name = $request->input('contact_person_name', $address->contact_person_name ?? '');
        $address->contact_person_number = $request->input('contact_person_number', $address->contact_person_number ?? '');
        $this->applyZoneIdFromCoords($address, $request->lat, $request->lon);
        $address->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroyAddress(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $address = $this->address->where(['user_id' => $request['customer_id']])->where('id', $id)->first();
        if (!isset($address)) {
            return response()->json(response_formatter(DEFAULT_204), 200);
        }
        $address->delete();
        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return string|StreamedResponse
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('customer_export');

        $search = $request->has('search') ? $request['search'] : '';
        $status = $request->has('status') ? $request['status'] : 'all';
        $from = $request->get('from', '');
        $to = $request->get('to', '');
        $sort_by = $request->get('sort_by', 'latest');
        $limit = $request->get('limit');

        $query = $this->user->withCount(['bookings'])->inCustomerDirectory()
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('email', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($status != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })
            ->when($from, function ($query) use ($from) {
                return $query->whereDate('created_at', '>=', $from);
            })
            ->when($to, function ($query) use ($to) {
                return $query->whereDate('created_at', '<=', $to);
            })
            ->when($sort_by === 'latest', function ($query) {
                return $query->latest();
            })
            ->when($sort_by === 'oldest', function ($query) {
                return $query->oldest();
            })
            ->when($sort_by === 'ascending', function ($query) {
                return $query->orderBy('first_name', 'asc');
            })
            ->when($sort_by === 'descending', function ($query) {
                return $query->orderBy('first_name', 'desc');
            });

        if (isset($limit) && $limit > 0) {
            $customers = $query->take($limit)->get();
        }else{
            $customers = $query->get();
        }

        $formatted = $customers->map(function ($item, $key) {
            return [
                'Sl' => $key + 1,
                'Name' => $item->first_name . ' ' . $item->last_name,
                'Phone' => $item->phone,
                'Email' => $item->email,
                'Gender' => $item->gender,
                'Join Date' => $item->created_at->format('d M Y h:i A'),
            ];
        });

        return (new FastExcel($formatted))->download(time() . '-file.xlsx');
    }

    public function show($id, Request $request)
    {
        $this->authorize('customer_view');
        $request->validate([
            'web_page' => 'nullable|in:overview,bookings,reviews,performance,payments',
        ]);

        $webPage = $request->has('web_page') ? $request['web_page'] : 'overview';

        if ($webPage === 'overview') {
            $customer = $this->user->inCustomerDirectory()->with(['account', 'addresses'])->withCount(['bookings'])->find($id);
            $totalBookingAmount = $this->booking->where('customer_id', $id)->sum('total_booking_amount');

            $booking_overview = DB::table('bookings')->where('customer_id', $id)
                ->select('booking_status', DB::raw('count(*) as total'))
                ->groupBy('booking_status')
                ->get();

            $status = ['pending', 'accepted', 'ongoing', 'completed', 'canceled'];
            $total = [];
            foreach ($status as $item) {
                if ($booking_overview->where('booking_status', $item)->first() !== null) {
                    $total[] = $booking_overview->where('booking_status', $item)->first()->total;
                } else {
                    $total[] = 0;
                }
            }

            $bookingOverviewStatuses = ['pending', 'accepted', 'ongoing', 'completed', 'canceled'];
            $bookingOverviewChartLabels = [];
            foreach ($bookingOverviewStatuses as $idx => $statusKey) {
                $bookingOverviewChartLabels[] = translate($statusKey).' ('.(int) ($total[$idx] ?? 0).')';
            }

            return view('customermodule::admin.detail.overview', compact(
                'customer',
                'totalBookingAmount',
                'webPage',
                'total',
                'bookingOverviewChartLabels'
            ));

        } elseif ($webPage == 'bookings') {

            $search = $request->has('search') ? $request['search'] : '';
            $queryParam = ['web_page' => $webPage, 'search' => $search];

            $bookings = $this->booking->with(['provider.owner'])
                ->where('customer_id', $id)
                ->where(function ($query) use ($request) {
                    $keys = explode(' ', $request['search']);
                    foreach ($keys as $key) {
                        $query->where('readable_id', 'LIKE', '%' . $key . '%');
                    }
                })
                ->latest()
                ->paginate(pagination_limit())->appends($queryParam);

            $customer = $this->user->inCustomerDirectory()->find($id);

            return view('customermodule::admin.detail.bookings', compact('bookings', 'webPage', 'customer', 'search'));

        } elseif ($webPage == 'reviews') {
            $search = $request->has('search') ? $request['search'] : '';
            $queryParam = ['web_page' => $webPage];
            $bookingIds = $this->booking->where('customer_id', $id)->pluck('id')->toArray();
            $reviews = $this->review->with(['booking'])
                ->whereIn('booking_id', $bookingIds)
                ->latest()
                ->paginate(pagination_limit())->appends($queryParam);
            $customer = $this->user->inCustomerDirectory()->find($id);
            return view('customermodule::admin.detail.reviews', compact('reviews', 'webPage', 'customer', 'search'));

        } elseif ($webPage == 'performance') {
            $customer = $this->user->inCustomerDirectory()->find($id);
            $performanceService = app(CustomerPerformanceService::class);
            $metricsRow = $performanceService->getAggregatedCustomerPerformanceMetrics([$id])->get($id);
            $metrics = (object) ($metricsRow ? (array) $metricsRow : []);

            $incidents = CustomerIncident::query()
                ->where('customer_id', $id)
                ->with(['createdBy', 'booking'])
                ->latest()
                ->paginate(20)
                ->withQueryString();

            return view('customermodule::admin.detail.performance', compact('customer', 'webPage', 'metrics', 'incidents'));
        } elseif ($webPage == 'payments') {
            $customer = $this->user->inCustomerDirectory()->find($id);

            $bookingIds = $this->booking->where('customer_id', $id)->pluck('id')->toArray();
            $totals = (object) [
                'customer_paid_to_provider' => 0.0,
                'customer_paid_to_company' => 0.0,
                'company_paid_to_customer' => 0.0,
            ];
            $paymentTransactions = collect();
            $bookingReportRows = collect();

            if (!empty($bookingIds)) {
                $allBookings = $this->booking->with(['booking_partial_payments', 'extra_services'])
                    ->whereIn('id', $bookingIds)
                    ->latest()
                    ->get();

                foreach ($allBookings as $bookingRow) {
                    $settlement = get_booking_received_and_settlement($bookingRow);
                    $totals->customer_paid_to_company += (float) ($settlement['amount_received_by_company'] ?? 0);
                    $totals->customer_paid_to_provider += (float) ($settlement['amount_received_by_provider'] ?? 0);
                    $isLossMaking = trim((string) ($bookingRow->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
                    $pendingDebitLossMaking = 0.0;
                    if ($isLossMaking && ! in_array((string) ($bookingRow->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
                        $grand = get_booking_total_amount($bookingRow);
                        $paid = get_booking_total_paid($bookingRow);
                        $pendingDebitLossMaking = round(max(0.0, $grand - $paid), 2);
                    }
                    $bookingReportRows->push((object) [
                        'booking_id' => $bookingRow->id,
                        'readable_id' => $bookingRow->readable_id ?? $bookingRow->id,
                        'total_amount' => round((float) get_booking_total_amount($bookingRow), 2),
                        'paid_to_provider' => round((float) ($settlement['amount_received_by_provider'] ?? 0), 2),
                        'paid_to_company' => round((float) ($settlement['amount_received_by_company'] ?? 0), 2),
                        'balance' => round((float) get_booking_invoice_due_amount($bookingRow), 2),
                        'is_loss_making' => $isLossMaking,
                        'pending_debit_loss_making' => $pendingDebitLossMaking,
                    ]);
                }

                $totals->company_paid_to_customer = (float) LedgerTransaction::query()
                    ->whereIn('booking_id', $bookingIds)
                    ->where('type', LedgerTransaction::TYPE_OUT)
                    ->where('reason', LedgerTransaction::REASON_REFUND)
                    ->sum('amount');

                $bookingMap = $allBookings->keyBy('id');

                $partials = DB::table('booking_partial_payments')
                    ->whereIn('booking_id', $bookingIds)
                    ->where('paid_amount', '>', 0)
                    ->orderByDesc('created_at')
                    ->get();

                foreach ($partials as $partial) {
                    $receivedBy = $partial->received_by ?: 'company';
                    $flow = $receivedBy === 'provider' ? 'customer_paid_to_provider' : 'customer_paid_to_company';
                    $mapRow = $bookingMap->get($partial->booking_id);
                    $paymentTransactions->push((object) [
                        'date' => $partial->created_at,
                        'booking_id' => $partial->booking_id,
                        'booking_readable_id' => ($mapRow?->readable_id) ?? $partial->booking_id,
                        'flow' => $flow,
                        'amount' => (float) $partial->paid_amount,
                        'channel' => (string) ($partial->paid_with ?? 'N/A'),
                        'transaction_id' => (string) ($partial->transaction_id ?? ''),
                        'source' => 'partial_payment',
                    ]);
                }

                $refundRows = LedgerTransaction::query()
                    ->whereIn('booking_id', $bookingIds)
                    ->where('type', LedgerTransaction::TYPE_OUT)
                    ->where('reason', LedgerTransaction::REASON_REFUND)
                    ->where('amount', '>', 0)
                    ->orderByDesc('date')
                    ->orderByDesc('created_at')
                    ->get(['booking_id', 'amount', 'transaction_id', 'date', 'created_at']);

                foreach ($refundRows as $refund) {
                    $mapRow = $bookingMap->get($refund->booking_id);
                    $paymentTransactions->push((object) [
                        'date' => $refund->date ?? $refund->created_at,
                        'booking_id' => $refund->booking_id,
                        'booking_readable_id' => ($mapRow?->readable_id) ?? $refund->booking_id,
                        'flow' => 'company_paid_to_customer',
                        'amount' => (float) $refund->amount,
                        'channel' => 'refund',
                        'transaction_id' => (string) ($refund->transaction_id ?? ''),
                        'source' => 'ledger_refund',
                    ]);
                }
            }

            $paymentTransactions = $paymentTransactions
                ->filter(fn ($row) => (float) ($row->amount ?? 0) > 0)
                ->sortByDesc(fn ($row) => strtotime((string) $row->date))
                ->values();

            $perPage = 20;
            $page = (int) ($request->get('page', 1));
            $paginatedTransactions = new \Illuminate\Pagination\LengthAwarePaginator(
                $paymentTransactions->forPage($page, $perPage)->values(),
                $paymentTransactions->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'page']
            );
            $paginatedTransactions->withQueryString();

            $reportPerPage = 20;
            $reportPage = max(1, (int) $request->get('report_page', 1));
            $paginatedBookingReport = new \Illuminate\Pagination\LengthAwarePaginator(
                $bookingReportRows->forPage($reportPage, $reportPerPage)->values(),
                $bookingReportRows->count(),
                $reportPerPage,
                $reportPage,
                ['path' => $request->url(), 'pageName' => 'report_page']
            );
            $paginatedBookingReport->withQueryString();

            $pendingBadDebtLossMaking = customer_pending_bad_debt_loss_making_bookings_total((string) $id);

            return view('customermodule::admin.detail.payments', compact(
                'customer',
                'webPage',
                'totals',
                'paginatedTransactions',
                'paginatedBookingReport',
                'pendingBadDebtLossMaking'
            ));
        }


    }

    private function resolveZoneIdForAddressCoords($lat, $lon): ?string
    {
        if ($lat === null || $lat === '' || $lon === null || $lon === '') {
            return null;
        }
        if (!is_numeric($lat) || !is_numeric($lon)) {
            return null;
        }
        $point = new Point((float) $lat, (float) $lon);

        return app(ZoneGeometryService::class)->resolveLeafZoneForPoint($point)?->id;
    }

    private function applyZoneIdFromCoords(UserAddress $address, $lat, $lon): void
    {
        if ($lat !== null && $lat !== '' && $lon !== null && $lon !== '' && is_numeric($lat) && is_numeric($lon)) {
            $address->zone_id = $this->resolveZoneIdForAddressCoords($lat, $lon);

            return;
        }
        if (($lat === null || $lat === '') && ($lon === null || $lon === '')) {
            return;
        }
        $address->zone_id = null;
    }

    private function formatSimplifiedFullAddress(UserAddress $address): string
    {
        $parts = array_filter([$address->address, $address->landmark]);

        return implode(', ', $parts);
    }

}
