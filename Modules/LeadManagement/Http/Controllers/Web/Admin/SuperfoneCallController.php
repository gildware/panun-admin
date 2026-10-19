<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Services\SuperfoneCallService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperfoneCallController extends Controller
{
    public function __construct(private readonly SuperfoneCallService $calls)
    {
    }

    public function index(Request $request): View
    {
        $sortable = [
            'started_at' => 'superfone_calls.started_at',
            'direction' => 'superfone_calls.direction',
            'phone' => 'superfone_calls.customer_phone',
            'employee' => 'superfone_calls.staff_name',
            'outcome' => 'superfone_calls.outcome',
            'duration' => 'superfone_calls.duration_seconds',
            'recording' => 'superfone_calls.recording_path',
            'lead' => 'superfone_calls.lead_id',
        ];
        $requestedSort = (string) $request->query('sort');
        $sort = (array_key_exists($requestedSort, $sortable) || in_array($requestedSort, ['party', 'booking'], true))
            ? $requestedSort
            : 'started_at';
        $direction = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $query = SuperfoneCall::query()->with([
            'lead:id,name,phone_number,lead_type',
            'employee:id,first_name,last_name',
            'provider:id,company_name,contact_person_name',
            'booking:id,readable_id,booking_status',
            'customer:id,first_name,last_name,phone',
        ]);

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $digits = SuperfoneCallService::lastTenDigits($term);
            $query->where(function ($inner) use ($term, $digits) {
                $inner->where('customer_phone', 'like', '%'.$term.'%')
                    ->orWhere('staff_name', 'like', '%'.$term.'%')
                    ->orWhere('cdr_uuid', 'like', '%'.$term.'%');
                if ($digits !== '') {
                    $inner->orWhere('customer_phone_digits', 'like', '%'.$digits.'%');
                }
                $inner->orWhereHas('lead', function ($lead) use ($term) {
                    $lead->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone_number', 'like', '%'.$term.'%');
                });
            });
        }

        if (in_array($request->query('direction'), ['INBOUND', 'OUTBOUND'], true)) {
            $query->where('direction', $request->query('direction'));
        }

        if (in_array($request->query('outcome'), ['answered', 'missed', 'unanswered', 'other', 'unknown'], true)) {
            if ($request->query('outcome') === 'unknown') {
                $query->whereNull('outcome');
            } elseif ($request->query('outcome') === 'unanswered') {
                $query->where(function ($inner) {
                    $inner->where('outcome', 'unanswered')
                        ->orWhere(function ($legacy) {
                            $legacy->where('outcome', 'missed')->where('direction', 'OUTBOUND');
                        });
                });
            } elseif ($request->query('outcome') === 'missed') {
                $query->where('outcome', 'missed')->where(function ($inner) {
                    $inner->whereNull('direction')->orWhere('direction', '!=', 'OUTBOUND');
                });
            } else {
                $query->where('outcome', $request->query('outcome'));
            }
        }

        if ($request->query('matched') === 'yes') {
            $query->where(function ($inner) {
                $inner->whereNotNull('lead_id')->orWhereNotNull('booking_id');
            });
        } elseif ($request->query('matched') === 'no') {
            $query->whereNull('lead_id')->whereNull('booking_id');
        }

        if ($request->filled('agent')) {
            $query->where('staff_name', $request->query('agent'));
        }

        if ($request->filled('from')) {
            $query->whereDate('started_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('started_at', '<=', $request->query('to'));
        }

        if ($sort === 'party') {
            $query->leftJoin('providers as call_providers', 'call_providers.id', '=', 'superfone_calls.provider_id')
                ->leftJoin('users as call_customers', 'call_customers.id', '=', 'superfone_calls.customer_user_id')
                ->select('superfone_calls.*')
                ->orderByRaw("COALESCE(NULLIF(call_providers.company_name, ''), NULLIF(call_providers.contact_person_name, ''), NULLIF(TRIM(CONCAT(COALESCE(call_customers.first_name, ''), ' ', COALESCE(call_customers.last_name, ''))), '')) {$direction}");
        } elseif ($sort === 'booking') {
            $query->leftJoin('bookings as call_bookings', 'call_bookings.id', '=', 'superfone_calls.booking_id')
                ->select('superfone_calls.*')
                ->orderByRaw('call_bookings.readable_id IS NULL')
                ->orderBy('call_bookings.readable_id', $direction);
        } else {
            $column = $sortable[$sort] ?? 'superfone_calls.started_at';
            $query->orderByRaw($column.' IS NULL')->orderBy($column, $direction);
        }
        $query->orderByDesc('superfone_calls.id');

        $calls = $query->paginate(25)->withQueryString();
        $agents = SuperfoneCall::query()
            ->whereNotNull('staff_name')
            ->where('staff_name', '!=', '')
            ->distinct()
            ->orderBy('staff_name')
            ->pluck('staff_name');

        $bookingChoices = [];
        foreach ($calls as $call) {
            if ($call->booking_id || $call->needsLeadLink()) {
                continue;
            }
            $open = $this->calls->openBookings($call);
            if ($open->count() > 1) {
                $bookingChoices[$call->id] = $open;
            }
        }

        return view('leadmanagement::admin.calls.index', [
            'calls' => $calls,
            'agents' => $agents,
            'sort' => $sort,
            'dir' => $direction,
            'bookingChoices' => $bookingChoices,
        ]);
    }

    public function searchLeads(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['leads' => []]);
        }

        $digits = SuperfoneCallService::lastTenDigits($term);
        $leads = Lead::query()
            ->with('source:id,name')
            ->where(function ($query) use ($term, $digits) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('phone_number', 'like', '%'.$term.'%');
                if (ctype_digit($term)) {
                    $query->orWhere('id', (int) $term);
                }
                if ($digits !== '') {
                    $query->orWhere('phone_number', 'like', '%'.$digits);
                }
            })
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get([
                'id',
                'name',
                'phone_number',
                'lead_type',
                'lead_temperature',
                'source_id',
                'date_time_of_lead_received',
                'next_followup_at',
                'remarks',
            ]);

        return response()->json([
            'leads' => $leads->map(fn (Lead $lead) => $this->leadLinkOption($lead))->values(),
        ]);
    }

    public function searchBookings(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['bookings' => []]);
        }

        $bookings = Booking::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'provider:id,company_name,contact_person_name',
                'category:id,name',
                'subCategory:id,name',
            ])
            ->where(function ($query) use ($term) {
                $query->where('readable_id', 'like', '%'.$term.'%')
                    ->orWhere('id', 'like', '%'.$term.'%')
                    ->orWhereHas('customer', function ($customer) use ($term) {
                        $customer->where('first_name', 'like', '%'.$term.'%')
                            ->orWhere('last_name', 'like', '%'.$term.'%')
                            ->orWhere('phone', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('provider', function ($provider) use ($term) {
                        $provider->where('company_name', 'like', '%'.$term.'%')
                            ->orWhere('contact_person_name', 'like', '%'.$term.'%');
                    });
            })
            ->orderByDesc('created_at')
            ->limit(12)
            ->get([
                'id',
                'readable_id',
                'booking_status',
                'customer_id',
                'provider_id',
                'category_id',
                'sub_category_id',
                'service_schedule',
                'total_booking_amount',
                'is_paid',
                'created_at',
            ]);

        return response()->json([
            'bookings' => $bookings->map(fn (Booking $booking) => $this->bookingLinkOption($booking))->values(),
        ]);
    }

    public function linkOptions(SuperfoneCall $call): JsonResponse
    {
        $call->loadMissing(['provider:id,company_name,contact_person_name', 'customer:id,first_name,last_name,phone', 'lead:id,name,lead_type']);
        $existing = $this->calls->existingLinks($call);

        if ($call->provider) {
            $party = 'provider';
            $partyName = trim((string) ($call->provider->company_name ?: $call->provider->contact_person_name)) ?: translate('Provider');
        } elseif ($call->customer) {
            $party = 'customer';
            $partyName = trim((string) ($call->customer->first_name.' '.$call->customer->last_name)) ?: translate('Customer');
        } elseif ($call->lead) {
            $party = 'lead';
            $partyName = $call->lead->name ?: translate('Lead');
        } else {
            $party = 'none';
            $partyName = '';
        }

        return response()->json([
            'party' => $party,
            'party_name' => $partyName,
            'phone' => $call->customer_phone,
            'leads' => $existing['leads']->map(fn (Lead $lead) => $this->leadLinkOption($lead))->values(),
            'bookings' => $existing['bookings']->map(fn (Booking $booking) => $this->bookingLinkOption($booking))->values(),
        ]);
    }

    /**
     * @return array{label: string, value: string}|null
     */
    private function linkDetail(string $label, ?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return ['label' => $label, 'value' => $value];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadLinkOption(Lead $lead): array
    {
        $type = (string) ($lead->lead_type ?: Lead::TYPE_UNKNOWN);
        $typeLabel = Lead::leadTypes()[$type] ?? ucfirst($type);
        $typeLabel = translate(str_replace(' ', '_', $typeLabel));
        $remarks = trim((string) $lead->remarks);
        if (mb_strlen($remarks) > 140) {
            $remarks = mb_substr($remarks, 0, 137).'…';
        }

        $details = array_values(array_filter([
            $this->linkDetail(translate('Type'), $typeLabel),
            $this->linkDetail(translate('Phone'), $lead->phone_number),
            $this->linkDetail(translate('Temperature'), $lead->lead_temperature ? Lead::leadTemperatureShortLabel($lead->lead_temperature) : null),
            $this->linkDetail(translate('Source'), $lead->source?->name),
            $this->linkDetail(translate('Received'), $lead->date_time_of_lead_received?->format('d M Y, h:i A')),
            $this->linkDetail(translate('Next_followup'), $lead->next_followup_at?->format('d M Y, h:i A')),
            $this->linkDetail(translate('Remarks'), $remarks !== '' ? $remarks : null),
        ]));

        return [
            'id' => $lead->id,
            'name' => $lead->name ?: 'Unknown',
            'phone_number' => $lead->phone_number,
            'lead_type' => $type,
            'lead_type_label' => $typeLabel,
            'title' => '#'.$lead->id.' '.($lead->name ?: translate('Unknown')),
            'details' => $details,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingLinkOption(Booking $booking): array
    {
        $customer = $booking->customer;
        $customerName = trim((string) (($customer->first_name ?? '').' '.($customer->last_name ?? '')));
        $providerName = trim((string) ($booking->provider->company_name ?? $booking->provider->contact_person_name ?? ''));
        $service = trim(implode(' · ', array_filter([
            $booking->category?->name,
            $booking->subCategory?->name,
        ])));
        $when = $booking->service_schedule ?: null;
        $whenLabel = $when ? \Carbon\Carbon::parse($when)->format('d M Y, h:i A') : '';
        $amount = $booking->total_booking_amount;
        $status = ucfirst(str_replace('_', ' ', (string) $booking->booking_status));

        $details = array_values(array_filter([
            $this->linkDetail(translate('Status'), $status),
            $this->linkDetail(translate('Customer'), trim($customerName.($customer?->phone ? ' · '.$customer->phone : ''))),
            $this->linkDetail(translate('Provider'), $providerName),
            $this->linkDetail(translate('Schedule'), $whenLabel),
            $this->linkDetail(translate('Service'), $service),
            $this->linkDetail(translate('Amount'), $amount !== null && $amount !== '' ? number_format((float) $amount, 0) : null),
            $this->linkDetail(translate('Payment'), $booking->is_paid ? translate('Paid') : translate('Unpaid')),
        ]));

        return [
            'id' => $booking->id,
            'readable_id' => $booking->readable_id ?: $booking->id,
            'status' => $status,
            'title' => (string) ($booking->readable_id ?: $booking->id),
            'details' => $details,
        ];
    }

    public function linkLead(Request $request, SuperfoneCall $call): RedirectResponse
    {
        $validated = $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
        ]);

        try {
            $this->calls->linkToLead($call, Lead::query()->findOrFail($validated['lead_id']));
        } catch (\InvalidArgumentException $e) {
            toastr()->error($e->getMessage());

            return back();
        }

        toastr()->success(translate('Call_linked_to_lead'));

        return back();
    }

    public function linkBooking(Request $request, SuperfoneCall $call): RedirectResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
        ]);

        try {
            $this->calls->linkToBooking($call, $validated['booking_id']);
        } catch (\InvalidArgumentException $e) {
            toastr()->error($e->getMessage());

            return back();
        }

        toastr()->success(translate('Call_linked_to_booking'));

        return back();
    }

    public function unmatch(SuperfoneCall $call): RedirectResponse
    {
        $this->calls->unmatch($call);
        toastr()->success(translate('Call_unmatched'));

        return back();
    }

    public function show(SuperfoneCall $call): View
    {
        $call->load(['lead', 'followup', 'employee', 'provider', 'booking', 'customer']);
        $bookingChoices = collect();
        if (! $call->booking_id && ! $call->needsLeadLink()) {
            $open = $this->calls->openBookings($call);
            if ($open->count() > 1) {
                $bookingChoices = $open;
            }
        }

        return view('leadmanagement::admin.calls.show', [
            'call' => $call,
            'bookingChoices' => $bookingChoices,
        ]);
    }

    public function recording(SuperfoneCall $call): StreamedResponse
    {
        abort_unless($call->hasRecording(), 404);

        $path = $call->recordingStoragePath();
        $disk = Storage::disk($call->recordingDisk());
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, $call->recording_original_name ?: basename($path), [
            'Content-Type' => $call->recording_mime ?: 'audio/mpeg',
        ]);
    }

    public function downloadRecording(SuperfoneCall $call): RedirectResponse
    {
        $this->calls->downloadRecording($call);
        $call->refresh();

        if ($call->hasRecording()) {
            toastr()->success(translate('Recording_saved'));
        } else {
            toastr()->error($call->recording_error ?: translate('Recording_download_failed'));
        }

        return redirect()->route('admin.calls.show', $call);
    }
}
