<?php

namespace Modules\BidModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\BidModule\Entities\Post;
use Modules\BidModule\Entities\PostAdditionalInformation;
use Modules\BidModule\Entities\PostAdditionalInstruction;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ServiceManagement\Entities\Service;
use Rap2hpoutre\FastExcel\FastExcel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private Post $post,
        private PostAdditionalInformation $postAdditionalInformation,
        private PostAdditionalInstruction $postAdditionalInstruction,
        private PushNotification $pushNotification,
        private Category $category
    )
    {
    }

    /**
     * Show the form for creating a new custom service request (bidding) on behalf of a customer.
     */
    public function create(Request $request): Renderable
    {
        $this->authorize('booking_view');

        $request->merge(array_merge($request->query(), $request->old()));

        $zones = Zone::withoutGlobalScope('translate')->select('id', 'name')->get();
        $categories = Category::select('id', 'parent_id', 'name')->where('position', 1)->get();
        $customers = User::query()->inCustomerDirectory()
            ->orderByDesc('created_at')
            ->select('id', 'first_name', 'last_name', 'phone')
            ->limit(200)
            ->get();
        $assignees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->orderBy('first_name')->orderBy('last_name')
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
            ->get();

        return view('bidmodule::admin.create', compact('zones', 'categories', 'customers', 'assignees'));
    }

    /**
     * Preview the custom service request before creating.
     */
    public function preview(Request $request): Renderable|RedirectResponse
    {
        $this->authorize('booking_view');

        try {
            $data = $request->validate([
                'customer_id' => ['required', 'exists:users,id'],
                'service_address_id' => ['required', 'integer', 'exists:user_addresses,id'],
                'zone_id' => ['required', 'uuid'],
                'category_id' => ['required', 'uuid'],
                'sub_category_id' => ['required', 'uuid'],
                'service_id' => ['nullable', 'uuid', 'exists:services,id'],
                'service_description' => ['required', 'string', 'max:5000'],
                'booking_schedule' => ['required', 'date'],
                'booking_source' => ['required', 'in:whatsapp,call,social_media'],
                'assignee_id' => ['nullable', 'exists:users,id'],
                'additional_instructions' => ['nullable', 'array'],
                'additional_instructions.*' => ['nullable', 'string', 'max:500'],
            ]);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $customer = User::find($data['customer_id']);
        $address = UserAddress::find($data['service_address_id']);
        $zone = Zone::find($data['zone_id']);
        $category = Category::find($data['category_id']);
        $subCategory = Category::find($data['sub_category_id']);
        $service = $data['service_id'] ? Service::find($data['service_id']) : null;
        $assignee = $data['assignee_id'] ? User::find($data['assignee_id']) : null;

        return view('bidmodule::admin.preview', compact('data', 'customer', 'address', 'zone', 'category', 'subCategory', 'service', 'assignee'));
    }

    /**
     * Store a new custom service request (bidding) on behalf of a customer.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('booking_add');

        try {
            $data = $request->validate([
                'customer_id' => ['required', 'exists:users,id'],
                'service_address_id' => ['required', 'integer', 'exists:user_addresses,id'],
                'zone_id' => ['required', 'uuid'],
                'category_id' => ['required', 'uuid'],
                'sub_category_id' => ['required', 'uuid'],
                'service_id' => ['nullable', 'uuid', 'exists:services,id'],
                'service_description' => ['required', 'string', 'max:5000'],
                'booking_schedule' => ['required', 'date'],
                'booking_source' => ['required', 'in:whatsapp,call,social_media'],
                'assignee_id' => ['nullable', 'exists:users,id'],
                'additional_instructions' => ['nullable', 'array'],
                'additional_instructions.*' => ['nullable', 'string', 'max:500'],
            ]);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $subCategory = Category::find($data['sub_category_id']);
        $serviceId = $data['service_id'] ?? Service::where('sub_category_id', $data['sub_category_id'])->value('id');

        $post = new Post();
        $post->customer_user_id = $data['customer_id'];
        $post->service_address_id = $data['service_address_id'];
        $post->zone_id = $data['zone_id'];
        $post->category_id = $data['category_id'];
        $post->sub_category_id = $data['sub_category_id'];
        $post->service_id = $serviceId;
        $post->service_description = $data['service_description'];
        $post->booking_schedule = $data['booking_schedule'];
        $post->booking_source = $data['booking_source'];
        $post->assignee_id = $data['assignee_id'] ?? null;
        $post->is_booked = 0;
        $post->is_checked = 0;
        $post->save();

        if (!empty($data['additional_instructions'])) {
            $instructions = [];
            foreach (array_filter($data['additional_instructions']) as $item) {
                $instructions[] = [
                    'id' => Uuid::uuid4(),
                    'details' => $item,
                    'post_id' => $post->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($instructions)) {
                PostAdditionalInstruction::insert($instructions);
            }
        }

        $providerIds = SubscribedService::where('sub_category_id', $post->sub_category_id)->ofSubscription(1)->pluck('provider_id')->toArray();
        $providers = \Modules\ProviderManagement\Entities\Provider::with('owner')
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, fn($q) => $q->where('is_suspended', 0))
            ->whereIn('id', $providerIds)
            ->where('zone_id', $post->zone_id)
            ->get();

        $bookingNotificationStatus = business_config('booking', 'notification_settings')->live_values ?? [];
        foreach ($providers as $provider) {
            $fcmToken = $provider->owner->fcm_token ?? null;
            $title = get_push_notification_message('new_service_request_arrived', 'provider_notification', $provider->owner?->current_language_key);
            if ($fcmToken && $provider->service_availability && $title && ($bookingNotificationStatus['push_notification_booking'] ?? false)) {
                device_notification_for_bidding($fcmToken, $title, null, null, 'bidding', null, $post->id, null, [
                    'user_name' => $post->customer?->first_name . ' ' . $post->customer?->last_name,
                ]);
            }
        }

        Toastr::success(translate('Custom_service_request_created_successfully'));
        return redirect()->route('admin.booking.post.list', ['type' => 'all']);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return RedirectResponse|Renderable
     * @throws ValidationException
     */
    public function index(Request $request): Renderable|RedirectResponse
    {
        $this->authorize('booking_view');

        Validator::make($request->all(), [
            'type' => 'in:all,new_booking_request,placed_offer',
            'category_id' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'select_date' => 'nullable|string|in:all,today,this_week,this_month,custom_range',
        ])->validate();

        $queryParams = $request->only([
            'search',
            'category_id',
            'start_date',
            'end_date',
            'select_date',
            'type'
        ]);

        $filterCounter = collect($queryParams)->filter(function ($value) {
            return !is_null($value) && $value !== '';
        })->count();

        $posts = $this->post
            ->with(['bids.provider', 'addition_instructions', 'service', 'category', 'sub_category', 'booking', 'customer'])
            ->where('is_booked', 0)
            ->when($request->has('type') && $request->input('type') != 'new_booking_request' && $request->input('type') != 'all', function ($query) use ($request) {
                $query->whereHas('bids', function ($query) use ($request) {
                    if ($request->input('type') == 'placed_offer') {
                        $query->where('status', 'pending');
                    } elseif ($request->input('type') == 'booking_placed') {
                        $query->where('status', 'accepted');
                    }
                });
            })
            ->when($request->has('type') && $request->input('type') == 'new_booking_request', function ($query) {
                $query->whereDoesntHave('bids');
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                return $query->whereHas('customer', function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request->has('category_id') && $request->input('category_id') != null, function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->input('select_date') == 'custom_range' && $request->has('start_date') && $request->input('start_date') != null && $request->has('end_date') && $request->input('end_date') != null, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->input('start_date') . ' 00:00:00',
                    $request->input('end_date') . ' 23:59:59'
                ]);
            })
            ->when($request->input('select_date') == 'today', function ($query) {
                $query->whereDate('created_at', today());
            })
            ->when($request->input('select_date') == 'this_week', function ($query) {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->when($request->input('select_date') == 'this_month', function ($query) {
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            })
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParams);

        $type = $request->input('type');
        $search = $request->input('search');
        $category_id = $request->input('category_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $select_date = $request->input('select_date');

        $categories = $this->category->select('id', 'parent_id', 'name')->where('position', 1)->get();

        $this->post->where('is_checked', 0)->update(['is_checked' => 1]);

        return view('bidmodule::admin.customize-list', compact('posts', 'type', 'search', 'category_id', 'start_date', 'end_date', 'select_date', 'queryParams', 'categories','filterCounter'));
    }

    public function export(Request $request): StreamedResponse|string
    {
        $this->authorize('booking_export');
        Validator::make($request->all(), [
            'type' => 'in:all,new_booking_request,placed_offer',
            'search' => 'max:255'
        ])->validate();

        $posts = $this->post
            ->with(['bids.provider', 'addition_instructions', 'service', 'category', 'sub_category', 'booking', 'customer'])
            ->where('is_booked', 0)
            ->when($request->has('type') && $request->input('type') != 'new_booking_request' && $request->input('type') != 'all', function ($query) use ($request) {
                $query->whereHas('bids', function ($query) use ($request) {
                    if ($request->input('type') == 'placed_offer') {
                        $query->where('status', 'pending');
                    } elseif ($request->input('type') == 'booking_placed') {
                        $query->where('status', 'accepted');
                    }
                });
            })
            ->when($request->has('type') && $request->input('type') == 'new_booking_request', function ($query) {
                $query->whereDoesntHave('bids');
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $keys = explode(' ', $request->input('search'));
                return $query->whereHas('customer', function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('phone', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($request->has('category_id') && $request->input('category_id') != null, function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->input('select_date') == 'custom_range' && $request->has('start_date') && $request->input('start_date') != null && $request->has('end_date') && $request->input('end_date') != null, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->input('start_date') . ' 00:00:00',
                    $request->input('end_date') . ' 23:59:59'
                ]);
            })
            ->when($request->input('select_date') == 'today', function ($query) {
                $query->whereDate('created_at', today());
            })
            ->when($request->input('select_date') == 'this_week', function ($query) {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->when($request->input('select_date') == 'this_month', function ($query) {
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            })
            ->latest()
            ->get();

        return (new FastExcel($posts))->download(time() . '-file.xlsx');
    }

    /**
     * Display a listing of the resource.
     * @param $post_id
     * @return RedirectResponse|Renderable
     */
    public function details($post_id): Renderable|RedirectResponse
    {
        $post = $this->post
            ->with(['bids', 'addition_instructions', 'service', 'category', 'sub_category', 'booking', 'customer'])
            ->where('id', $post_id)
            ->first();

        $coordinates = auth()->user()->provider->coordinates ?? null;
        $distance = null;
        if (!is_null($coordinates) && $post->service_address) {
            $distance = get_distance(
                [$coordinates['latitude'] ?? null, $coordinates['longitude'] ?? null],
                [$post->service_address?->lat, $post->service_address?->lon]
            );
            $distance = ($distance) ? number_format($distance, 2) . ' km' : null;
        }

        if (!isset($post)) {
            Toastr::success(translate(DEFAULT_404['message']));
            return back();
        }

        return view('bidmodule::admin.details', compact('post', 'distance'));
    }

    /**
     * Display a listing of the resource.
     * @param $post_id
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete($post_id, Request $request): RedirectResponse
    {
        $this->authorize('booking_delete');
        $request->validate([
            'post_delete_note' => 'required|string',
        ]);

        $post = $this->post->where('id', $post_id)->first();

        if (!isset($post)) {
            Toastr::success(translate(DEFAULT_404['message']));
            return redirect()->route('admin.booking.post.list');
        }

        $additionalInfo = new $this->postAdditionalInformation;
        $additionalInfo->post_id = $post->id;
        $additionalInfo->key = 'post_delete_note';
        $additionalInfo->value = $request->post_delete_note;
        $additionalInfo->save();

        $pushNotification = $this->pushNotification;
        $pushNotification->title = translate('Your post has been deleted');
        $pushNotification->description = $additionalInfo->value;
        $pushNotification->to_users = ['customer'];
        $pushNotification->zone_ids = [];
        $pushNotification->is_active = 1;
        $pushNotification->save();

        $customer = $post?->customer;
        $fcmToken = $customer?->fcm_token ?? null;
        $languageKey = $customer?->current_language_key;
        $permission = isNotificationActive(null, 'booking', 'notification', 'user');
        if (!is_null($fcmToken) && $permission) {
            $title = get_push_notification_message('customized_booking_request_delete', 'customer_notification', $languageKey);
            device_notification($fcmToken, $title, null, null, null, 'general');
        }

        $post->delete();

        Toastr::success(translate(DEFAULT_DELETE_200['message']));
        return redirect()->back();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function multiDelete(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'post_ids' => 'required|array',
            'post_ids.*' => 'uuid',
        ])->validate();

        $deletedPosts = $this->post->whereIn('id', $request['post_ids'])->get();

        foreach ($deletedPosts as $post) {
            $customer = $post?->customer;
            $fcmToken = $customer?->fcm_token ?? null;

            if (!is_null($fcmToken)) {
                $languageKey = $customer?->current_language_key;
                $title = get_push_notification_message('customized_booking_request_delete', 'customer_notification', $languageKey);
                device_notification($fcmToken, $title, null, null, null, 'bidding');
            }
        }

        $this->post->whereIn('id', $request['post_ids'])->delete();

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }

}
