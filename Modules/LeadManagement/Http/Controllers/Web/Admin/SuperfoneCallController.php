<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use App\Support\AdminMenuCounts;
use App\Support\SeekableRecordingResponse;
use App\Support\StoragePathPrefix;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Entities\SuperfoneCallContact;
use Modules\LeadManagement\Services\SuperfoneCallService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        if (in_array($request->query('outcome'), ['answered', 'missed', 'unanswered', 'needs_callback', 'contacted', 'other', 'unknown'], true)) {
            if ($request->query('outcome') === 'unknown') {
                $query->whereNull('outcome');
            } elseif ($request->query('outcome') === 'needs_callback') {
                $query->awaitingCallback();
            } elseif ($request->query('outcome') === 'contacted') {
                $query->contacted();
            } elseif ($request->query('outcome') === 'unanswered') {
                $query->where(function ($inner) {
                    $inner->where('outcome', 'unanswered')
                        ->orWhere(function ($legacy) {
                            $legacy->where('outcome', 'missed')->where('direction', 'OUTBOUND');
                        });
                });
            } elseif ($request->query('outcome') === 'missed') {
                $query->missed();
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
        $awaitingCallbackIds = SuperfoneCall::query()
            ->awaitingCallback()
            ->whereIn('superfone_calls.id', $calls->getCollection()->pluck('id'))
            ->pluck('superfone_calls.id')
            ->flip()
            ->all();
        $contactMarks = SuperfoneCallContact::marksForCalls($calls->getCollection());
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
            'awaitingCallbackIds' => $awaitingCallbackIds,
            'contactMarks' => $contactMarks,
            'agents' => $agents,
            'sort' => $sort,
            'dir' => $direction,
            'bookingChoices' => $bookingChoices,
        ]);
    }

    public function searchLeads(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $idTerm = ltrim($term, '#');
        $isId = $idTerm !== '' && ctype_digit($idTerm);
        if (mb_strlen($term) < 2 && ! $isId) {
            return response()->json(['leads' => []]);
        }

        $digits = SuperfoneCallService::lastTenDigits($term);
        $leads = Lead::query()
            ->with('source:id,name')
            ->where(function ($query) use ($term, $digits) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('phone_number', 'like', '%'.$term.'%');
                $idTerm = ltrim($term, '#');
                if ($idTerm !== '' && ctype_digit($idTerm)) {
                    $query->orWhere('id', (int) $idTerm);
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
        $idTerm = ltrim($term, '#');
        $isId = $idTerm !== '' && ctype_digit($idTerm);
        if (mb_strlen($term) < 2 && ! $isId) {
            return response()->json(['bookings' => []]);
        }

        $bookings = Booking::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'provider:id,company_name,contact_person_name',
                'category:id,name',
                'subCategory:id,name',
            ])
            ->where(function ($query) use ($term, $idTerm, $isId) {
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
                if ($isId) {
                    $query->orWhere('id', (int) $idTerm);
                }
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

    public function markContacted(Request $request, SuperfoneCall $call): RedirectResponse
    {
        $stillOpen = SuperfoneCall::query()->awaitingCallback()->whereKey($call->id)->exists();
        if (! $stillOpen) {
            toastr()->error(translate('This_call_no_longer_needs_a_callback'));

            return back();
        }

        $validator = Validator::make($request->all(), [
            'channel' => ['required', 'in:phone,chat'],
            'phone_result' => ['nullable', 'in:picked,not_picked'],
            'remarks' => ['required', 'string', 'max:2000'],
            'recording' => ['nullable', 'file', 'mimes:mp3,wav,m4a,aac,ogg,webm,mp4', 'max:40960'],
            'screenshot' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('contact_modal_action', route('admin.calls.contact', $call))
                ->with('contact_modal_phone', $call->customer_phone);
        }

        $validated = $validator->validated();
        $channel = $validated['channel'] === 'phone' && ($validated['phone_result'] ?? 'picked') === 'not_picked'
            ? 'not_picked'
            : $validated['channel'];
        $file = $channel === 'chat' ? $request->file('screenshot') : $request->file('recording');
        $attachment = $file instanceof UploadedFile ? $this->storeContactAttachment($file) : [];

        SuperfoneCallContact::query()->create([
            'superfone_call_id' => $call->id,
            'customer_phone_digits' => $call->customer_phone_digits !== '' ? $call->customer_phone_digits : null,
            'channel' => $channel,
            'remarks' => trim($validated['remarks']),
            'employee_user_id' => $request->user()?->id,
            'contacted_at' => now('Asia/Kolkata')->format('Y-m-d H:i:s'),
            ...$attachment,
        ]);
        AdminMenuCounts::forget();

        toastr()->success(translate('Marked_as_contacted'));

        return back();
    }

    /**
     * @return array{attachment_disk: string, attachment_path: string, attachment_mime: string, attachment_original_name: string}
     */
    private function storeContactAttachment(UploadedFile $file): array
    {
        $disk = getDisk();
        $dir = StoragePathPrefix::apply('superfone-call-contacts/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $name = now()->format('YmdHis').'-'.uniqid().'.'.$extension;
        Storage::disk($disk)->putFileAs($dir, $file, $name);

        return [
            'attachment_disk' => $disk,
            'attachment_path' => $name,
            'attachment_mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'attachment_original_name' => $file->getClientOriginalName() ?: $name,
        ];
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

    public function recording(SuperfoneCall $call): BinaryFileResponse
    {
        abort_unless($call->hasRecording(), 404);

        $path = $call->recordingStoragePath();
        abort_unless($path, 404);

        return SeekableRecordingResponse::fromDisk(
            $call->recordingDisk(),
            $path,
            $call->recording_mime ?: 'audio/mpeg',
            $call->recording_original_name ?: basename($path)
        );
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
