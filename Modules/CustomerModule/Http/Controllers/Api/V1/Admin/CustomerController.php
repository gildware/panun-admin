<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\BookingModule\Entities\Booking;
use Modules\ReviewModule\Entities\Review;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Services\ZoneGeometryService;

class CustomerController extends Controller
{
    protected User $user;
    private $booking;
    private $review;
    private $address;

    public function __construct(Booking $booking, User $user, Review $review, UserAddress $address)
    {
        $this->booking = $booking;
        $this->user = $user;
        $this->review = $review;
        $this->address = $address;
    }


    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all',
            'string' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customers = $this->user->withCount(['bookings'])->inCustomerDirectory()
            ->when($request->has('string'), function ($query) use ($request) {
                $keys = explode(' ', base64_decode($request['string']));
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->orWhere('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('email', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->orderBy('created_at', 'desc')->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $customers), 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'password' => 'required|min:6',
            'gender' => 'in:male,female,others',
            'confirm_password' => 'required|same:password',
            'profile_image' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 403);
        }

        //email & phone check
        if (User::where('email', $request['email'])->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [["error_code"=>"email","message"=>translate('Email already taken')]]), 400);
        }
        if (User::where('phone', $request['phone'])->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [["error_code"=>"phone","message"=>translate('Phone already taken')]]), 400);
        }

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

        return response()->json(response_formatter(REGISTRATION_200), 200);
    }

    public function overview(string $id): JsonResponse
    {
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
     * @return JsonResponse
     */
    public function edit(string $id): JsonResponse
    {
        $customer = $this->user->inCustomerDirectory()->find($id);
        return response()->json(response_formatter(DEFAULT_200, $customer), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = $this->user->inCustomerDirectory()->find($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'password' => 'min:6',
            'gender' => 'in:male,female,others',
            'confirm_password' => $request->has('password') ? 'required|same:password' : '',
            'profile_image' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 403);
        }

        //email & phone check
        if (User::where('email', $request['email'])->where('id', '!=', $customer->id)->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [["error_code"=>"email","message"=>translate('Email already taken')]]), 400);
        }
        if (User::where('phone', $request['phone'])->where('id', '!=', $customer->id)->exists()) {
            return response()->json(response_formatter(DEFAULT_400, null, [["error_code"=>"phone","message"=>translate('Phone already taken')]]), 400);
        }

        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->profile_image = $request->has('profile_image') ? file_uploader('user/profile_image/', APPLICATION_IMAGE_FORMAT, $request->profile_image) : $customer->profile_image;
        $customer->date_of_birth = $request->date_of_birth;
        $customer->gender = $request->has('gender') ? $request->gender : $customer->gender;
        if ($request->has('password')) {
            $customer->password = bcrypt($request->password);
        }
        $customer->save();

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_ids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customersQuery = $this->user->inCustomerDirectory()->whereIn('id', $request['customer_ids']);
        if ($customersQuery->count() > 0) {
            $toDelete = $customersQuery->get();
            foreach ($toDelete as $customer) {
                if ($customer->provider()->exists()) {
                    return response()->json(response_formatter(DEFAULT_400, null, [[
                        'message' => translate('Cannot delete users linked to a provider business.'),
                    ]]), 400);
                }
            }
            foreach ($toDelete as $customer) {
                file_remover('user/profile_image/', $customer->profile_image);
                foreach ($customer->identification_image as $image_name) {
                    file_remover('user/identity/', $image_name);
                }
            }
            $customersQuery->delete();
            return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
        }
        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function statusUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:1,0',
            'customer_ids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->user->inCustomerDirectory()
            ->whereIn('id', $request['customer_ids'])
            ->update(['is_active' => $request['status']]);
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

}
