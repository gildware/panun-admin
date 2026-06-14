<?php

namespace Modules\CartModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CustomerCartContact;
use Modules\UserManagement\Entities\User;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CartController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a customer-grouped listing of items currently sitting in carts.
     *
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function index(Request $request): View|Factory|Application
    {
        $this->authorize('customer_view');

        $search = (string)$request->get('search', '');
        $from = (string)$request->get('from', '');
        $to = (string)$request->get('to', '');
        $minItems = (string)$request->get('min_items', '');
        $sortBy = (string)($request->get('sort_by') ?: 'recent');
        $contactStatus = (string)($request->get('contact_status') ?: 'all');

        $queryParam = [
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'min_items' => $minItems,
            'sort_by' => $sortBy,
            'contact_status' => $contactStatus,
        ];

        $carts = $this->baseQuery($search, $from, $to, $minItems, $sortBy, $contactStatus)
            ->paginate(pagination_limit())
            ->appends($queryParam);

        $summary = $this->summary($search, $from, $to, $minItems);
        $employees = $this->employees();

        return view('cartmodule::admin.list', compact('carts', 'queryParam', 'search', 'summary', 'contactStatus', 'employees'));
    }

    /**
     * Show every cart item belonging to a single customer.
     *
     * @param string $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function show(string $id): View|Factory|Application
    {
        $this->authorize('customer_view');

        $customer = User::findOrFail($id);

        $items = Cart::with(['service', 'category', 'sub_category', 'provider', 'serviceAddress'])
            ->where('customer_id', $id)
            ->where('is_guest', 0)
            ->orderByDesc('created_at')
            ->get();

        $contact = CustomerCartContact::with('contactedBy')->where('customer_id', $id)->first();
        $employees = $this->employees();

        return view('cartmodule::admin.detail', compact('customer', 'items', 'contact', 'employees'));
    }

    /**
     * Active admin staff that can be selected as the contacting agent.
     */
    private function employees()
    {
        return User::whereIn('user_type', ['super-admin', 'admin', 'admin-employee'])
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    /**
     * Mark a customer's cart as contacted by the current admin.
     *
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function markContacted(Request $request, string $id): RedirectResponse
    {
        $this->authorize('customer_view');

        if (env('APP_ENV') === 'demo') {
            Toastr::info(translate('Action_not_available_in_demo'));
            return back();
        }

        $contactedBy = $request->get('contacted_by');
        $isValidEmployee = $contactedBy && User::whereIn('user_type', ['super-admin', 'admin', 'admin-employee'])
                ->where('id', $contactedBy)->exists();

        CustomerCartContact::updateOrCreate(
            ['customer_id' => $id],
            [
                'contacted_by' => $isValidEmployee ? $contactedBy : auth()->id(),
                'contacted_at' => now(),
                'note' => $request->get('note'),
            ]
        );

        Toastr::success(translate('Marked_as_contacted'));
        return back();
    }

    /**
     * Remove the contacted status from a customer's cart.
     *
     * @param string $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function unmarkContacted(string $id): RedirectResponse
    {
        $this->authorize('customer_view');

        if (env('APP_ENV') === 'demo') {
            Toastr::info(translate('Action_not_available_in_demo'));
            return back();
        }

        CustomerCartContact::where('customer_id', $id)->delete();

        Toastr::success(translate('Marked_as_not_contacted'));
        return back();
    }

    /**
     * Export the customer-grouped cart list to excel.
     *
     * @param Request $request
     * @return StreamedResponse
     * @throws AuthorizationException
     */
    public function download(Request $request): StreamedResponse
    {
        $this->authorize('customer_view');

        $search = (string)$request->get('search', '');
        $from = (string)$request->get('from', '');
        $to = (string)$request->get('to', '');
        $minItems = (string)$request->get('min_items', '');
        $sortBy = (string)($request->get('sort_by') ?: 'recent');
        $contactStatus = (string)($request->get('contact_status') ?: 'all');

        $rows = $this->baseQuery($search, $from, $to, $minItems, $sortBy, $contactStatus)->get();

        $data = $rows->map(function ($row) {
            return [
                translate('Customer') => trim(($row->customer_first_name ?? '') . ' ' . ($row->customer_last_name ?? '')) ?: translate('Unknown'),
                translate('Phone') => $row->customer_phone ?? '',
                translate('Email') => $row->customer_email ?? '',
                translate('Items_in_cart') => (int)$row->items_count,
                translate('Total_quantity') => (int)$row->total_quantity,
                translate('Estimated_value') => round((float)$row->total_value, 2),
                translate('Services') => $row->services_preview ?? '',
                translate('First_added_at') => $row->first_added_at ? date('d M Y h:i A', strtotime($row->first_added_at)) : '',
                translate('Last_updated_at') => $row->last_added_at ? date('d M Y h:i A', strtotime($row->last_added_at)) : '',
                translate('Contacted') => $row->contacted_at ? translate('Yes') : translate('No'),
                translate('Contacted_by') => trim(($row->contacted_by_first_name ?? '') . ' ' . ($row->contacted_by_last_name ?? '')),
                translate('Contacted_at') => $row->contacted_at ? date('d M Y h:i A', strtotime($row->contacted_at)) : '',
            ];
        });

        return (new FastExcel($data))->download('customer-cart-' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Build the shared, filtered, customer-grouped cart query.
     */
    private function baseQuery(string $search, string $from, string $to, $minItems, string $sortBy, string $contactStatus = 'all')
    {
        $query = Cart::query()
            ->leftJoin('services', 'services.id', '=', 'carts.service_id')
            ->leftJoin('users', 'users.id', '=', 'carts.customer_id')
            ->leftJoin('customer_cart_contacts as ccc', 'ccc.customer_id', '=', 'carts.customer_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'ccc.contacted_by')
            ->whereNotNull('carts.customer_id')
            ->where('carts.is_guest', 0)
            ->when($contactStatus === 'contacted', function ($q) {
                return $q->whereNotNull('ccc.contacted_at');
            })
            ->when($contactStatus === 'pending', function ($q) {
                return $q->whereNull('ccc.contacted_at');
            })
            ->when($from !== '', function ($q) use ($from) {
                return $q->whereDate('carts.updated_at', '>=', $from);
            })
            ->when($to !== '', function ($q) use ($to) {
                return $q->whereDate('carts.updated_at', '<=', $to);
            })
            ->when($search !== '', function ($q) use ($search) {
                $keys = explode(' ', trim($search));
                return $q->where(function ($q2) use ($keys) {
                    foreach ($keys as $key) {
                        $q2->orWhere('users.first_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('users.last_name', 'LIKE', '%' . $key . '%')
                            ->orWhere('users.phone', 'LIKE', '%' . $key . '%')
                            ->orWhere('users.email', 'LIKE', '%' . $key . '%')
                            ->orWhere('services.name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->groupBy(
                'carts.customer_id', 'users.first_name', 'users.last_name', 'users.phone', 'users.email', 'users.is_active',
                'ccc.contacted_at', 'ccc.note', 'ccc.contacted_by', 'cu.first_name', 'cu.last_name'
            )
            ->select(
                'carts.customer_id',
                'users.first_name as customer_first_name',
                'users.last_name as customer_last_name',
                'users.phone as customer_phone',
                'users.email as customer_email',
                'users.is_active as customer_is_active',
                'ccc.contacted_at as contacted_at',
                'ccc.note as contact_note',
                'ccc.contacted_by as contacted_by_id',
                'cu.first_name as contacted_by_first_name',
                'cu.last_name as contacted_by_last_name',
                DB::raw('COUNT(carts.id) as items_count'),
                DB::raw('COALESCE(SUM(carts.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(carts.total_cost), 0) as total_value'),
                DB::raw('MIN(carts.created_at) as first_added_at'),
                DB::raw('MAX(carts.updated_at) as last_added_at'),
                DB::raw("GROUP_CONCAT(DISTINCT services.name ORDER BY services.name SEPARATOR ', ') as services_preview")
            )
            ->when($minItems !== '' && (int)$minItems > 0, function ($q) use ($minItems) {
                return $q->havingRaw('COUNT(carts.id) >= ?', [(int)$minItems]);
            });

        switch ($sortBy) {
            case 'oldest':
                $query->orderByRaw('MAX(carts.updated_at) asc');
                break;
            case 'most_items':
                $query->orderByRaw('COUNT(carts.id) desc');
                break;
            case 'least_items':
                $query->orderByRaw('COUNT(carts.id) asc');
                break;
            case 'highest_value':
                $query->orderByRaw('COALESCE(SUM(carts.total_cost), 0) desc');
                break;
            case 'recent':
            default:
                $query->orderByRaw('MAX(carts.updated_at) desc');
                break;
        }

        return $query;
    }

    /**
     * Lightweight headline stats for the filtered cart set.
     */
    private function summary(string $search, string $from, string $to, $minItems): array
    {
        $sub = $this->baseQuery($search, $from, $to, $minItems, 'recent');

        $stats = DB::query()->fromSub($sub, 'grouped')->selectRaw(
            'COUNT(*) as customers, COALESCE(SUM(items_count), 0) as items, COALESCE(SUM(total_value), 0) as value'
        )->first();

        return [
            'customers' => (int)($stats->customers ?? 0),
            'items' => (int)($stats->items ?? 0),
            'value' => (float)($stats->value ?? 0),
        ];
    }
}
