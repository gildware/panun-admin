<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\BookingModule\Entities\WebProviderRequest;

class WebProviderRequestController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $requests = WebProviderRequest::query()
            ->with(['lead.source'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('service_category', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(pagination_limit())
            ->withQueryString();

        return view('bookingmodule::admin.web-provider-request.index', compact('requests', 'search'));
    }

    public function show(int $id): View
    {
        $providerRequest = WebProviderRequest::query()
            ->with(['lead.source'])
            ->findOrFail($id);

        return view('bookingmodule::admin.web-provider-request.show', compact('providerRequest'));
    }
}
