<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadOutboundEnquiryStatus;
use Modules\UserManagement\Entities\User;

class LeadOutboundEnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));

        $query = LeadOutboundEnquiry::with(['createdBy', 'handledBy', 'statusConfig'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('contacted_through', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('contacted_at')
            ->orderByDesc('id');

        $enquiries = $query->paginate(pagination_limit())->appends(['search' => $search]);

        return view('leadmanagement::admin.outbound-enquiries.index', compact('enquiries', 'search'));
    }

    public function create(): View
    {
        $employees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $currentEmployeeId = Auth::id();

        $statuses = LeadOutboundEnquiryStatus::active()->orderBy('name')->get(['id', 'name']);

        return view('leadmanagement::admin.outbound-enquiries.create', compact('employees', 'currentEmployeeId', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:32',
            'contacted_through' => 'required|in:message,call',
            'remarks' => 'nullable|string|max:1000',
            'status_id' => 'required|exists:lead_outbound_enquiry_statuses,id',
            'handled_by' => 'required|string|max:64',
            'contacted_at' => 'required|date',
        ]);

        $validated['created_by'] = Auth::id();

        $status = LeadOutboundEnquiryStatus::find($validated['status_id']);
        $validated['status'] = $status?->name ?? $validated['status_id'];

        LeadOutboundEnquiry::create($validated);

        toastr()->success(translate('Outbound enquiry created successfully'));

        return redirect()->route('admin.lead.outbound-enquiry.index');
    }
}

