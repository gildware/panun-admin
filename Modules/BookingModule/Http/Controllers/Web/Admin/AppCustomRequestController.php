<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\BookingModule\Entities\AppCustomRequest;

class AppCustomRequestController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $requests = AppCustomRequest::query()
            ->with(['lead.source', 'customer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('category_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(pagination_limit())
            ->withQueryString();

        return view('bookingmodule::admin.app-custom-request.index', compact('requests', 'search'));
    }

    public function show(int $id): View
    {
        $customRequest = AppCustomRequest::query()
            ->with(['lead.source', 'customer'])
            ->findOrFail($id);

        return view('bookingmodule::admin.app-custom-request.show', compact('customRequest'));
    }
}
