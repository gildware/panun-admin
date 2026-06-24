<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadOutboundEnquiryStatus;
use Modules\UserManagement\Entities\User;

class LeadOutboundEnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));

        $query = LeadOutboundEnquiry::with(['createdBy', 'handledBy', 'statusConfig', 'lead', 'relatedLead', 'booking'])
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
        $employees = $this->activeEmployees();
        $currentEmployeeId = Auth::id();
        $statuses = LeadOutboundEnquiryStatus::active()->orderBy('name')->get(['id', 'name', 'link_type']);

        return view('leadmanagement::admin.outbound-enquiries.create', compact('employees', 'currentEmployeeId', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOutboundEnquiry($request);
        $this->createOutboundEnquiry($validated);

        toastr()->success(translate('Outbound enquiry created successfully'));

        return redirect()->route('admin.lead.outbound-enquiry.index');
    }

    public function storeFromLead(Request $request, int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        if ($lead->lead_type !== Lead::TYPE_FUTURE_CUSTOMER) {
            abort(422, 'Outbound enquiries can only be added to future customer leads.');
        }

        $validated = $this->validateOutboundEnquiry($request);
        $validated['lead_id'] = $lead->id;
        $validated['customer_name'] = $validated['customer_name'] ?? $lead->name ?? '';
        $validated['phone_number'] = $validated['phone_number'] ?? $lead->phone_number;

        $this->createOutboundEnquiry($validated);

        toastr()->success(translate('Outbound enquiry created successfully'));

        $redirectParams = [];
        if ($request->boolean('in_modal')) {
            $redirectParams['in_modal'] = 1;
        }

        return redirect()->route('admin.lead.show', array_merge(['id' => $lead->id], $redirectParams));
    }

    public function searchLeads(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));
        $selectedId = $request->get('selected');

        $query = Lead::query()->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereRaw('CAST(id AS CHAR) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($selectedId) {
            $query->where(function ($sub) use ($selectedId) {
                $sub->where('id', $selectedId);
            });
        }

        $leads = $query->limit(20)->get(['id', 'name', 'phone_number', 'lead_type']);

        return response()->json([
            'results' => $leads->map(function (Lead $lead) {
                $typeLabel = Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type;

                return [
                    'id' => $lead->id,
                    'text' => sprintf('#%s — %s (%s) — %s', $lead->id, $lead->name ?: '—', $lead->phone_number, $typeLabel),
                ];
            })->values(),
        ]);
    }

    public function searchBookings(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));
        $selectedId = $request->get('selected');

        $query = Booking::query()->with('customer:id,first_name,last_name,phone')->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('readable_id', 'like', "%{$search}%")
                    ->orWhereRaw('CAST(id AS CHAR) LIKE ?', ['%' . $search . '%'])
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($selectedId) {
            $query->where('id', $selectedId);
        }

        $bookings = $query->limit(20)->get(['id', 'readable_id', 'booking_status', 'customer_id']);

        return response()->json([
            'results' => $bookings->map(function (Booking $booking) {
                $customer = $booking->customer;
                $customerName = $customer
                    ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                    : '—';
                $phone = $customer?->phone ?? '—';

                return [
                    'id' => $booking->id,
                    'text' => sprintf('%s — %s (%s) — %s', $booking->readable_id ?: $booking->id, $customerName ?: '—', $phone, ucfirst((string) $booking->booking_status)),
                ];
            })->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateOutboundEnquiry(Request $request): array
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:32',
            'contacted_through' => 'required|in:message,call',
            'remarks' => 'nullable|string|max:1000',
            'status_id' => 'required|exists:lead_outbound_enquiry_statuses,id',
            'handled_by' => 'required|string|max:64',
            'contacted_at' => 'required|date',
            'lead_id' => 'nullable|exists:leads,id',
            'related_lead_id' => 'nullable|exists:leads,id',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        $status = LeadOutboundEnquiryStatus::find($validated['status_id']);
        if ($status?->requiresLeadLink() && empty($validated['related_lead_id'])) {
            throw ValidationException::withMessages([
                'related_lead_id' => translate('Please_select_a_lead'),
            ]);
        }

        if ($status?->requiresBookingLink() && empty($validated['booking_id'])) {
            throw ValidationException::withMessages([
                'booking_id' => translate('Please_select_a_booking'),
            ]);
        }

        if (!$status?->requiresLeadLink()) {
            unset($validated['related_lead_id']);
        }

        if (!$status?->requiresBookingLink()) {
            unset($validated['booking_id']);
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function createOutboundEnquiry(array $validated): LeadOutboundEnquiry
    {
        $validated['created_by'] = Auth::id();

        $status = LeadOutboundEnquiryStatus::find($validated['status_id']);
        $validated['status'] = $status?->name ?? $validated['status_id'];

        return LeadOutboundEnquiry::create($validated);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    protected function activeEmployees()
    {
        return User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
