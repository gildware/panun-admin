<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use App\Support\AdminMenuCounts;
use App\Support\SeekableRecordingResponse;
use App\Support\StoragePathPrefix;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Entities\SuperfoneCallContact;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\LeadManagement\Services\SuperfoneCallExcelExport;
use Modules\LeadManagement\Services\SuperfoneCallReport;
use Modules\LeadManagement\Services\SuperfoneCallService;
use Modules\LeadManagement\Services\SuperfoneLeadCharts;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperfoneCallController extends Controller
{
    public function __construct(private readonly SuperfoneCallService $calls)
    {
    }

    public function index(Request $request, SuperfoneCallExcelExport $export): View|JsonResponse
    {
        [$sort, $direction] = $export->resolveSort($request);
        $query = $export->query($request);

        $rowsOnly = $request->boolean('rows');
        $page = $rowsOnly ? max(1, (int) $request->query('page', 1)) : 1;
        $calls = $query->paginate(20, ['*'], 'page', $page)->withQueryString();
        $awaitingCallbackIds = SuperfoneCall::query()
            ->awaitingCallback()
            ->whereIn('superfone_calls.id', $calls->getCollection()->pluck('id'))
            ->pluck('superfone_calls.id')
            ->flip()
            ->all();
        $contactMarks = SuperfoneCallContact::marksForCalls($calls->getCollection());
        $agents = $rowsOnly ? collect() : $export->agents();

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

        $reopenLeadByCall = [];
        $openStatus = app(LeadOpenStatusService::class);
        foreach ($calls as $call) {
            $digits = (string) ($call->customer_phone_digits ?: '');
            if (strlen($digits) < 8) {
                continue;
            }
            $reopen = $openStatus->firstReopenableNonResponsiveLead(
                $openStatus->leadsForPhoneDigits($digits)
            );
            if ($reopen) {
                $reopenLeadByCall[$call->id] = $reopen;
            }
        }

        $rowData = [
            'calls' => $calls,
            'awaitingCallbackIds' => $awaitingCallbackIds,
            'contactMarks' => $contactMarks,
            'bookingChoices' => $bookingChoices,
            'reopenLeadByCall' => $reopenLeadByCall,
            'rowsOnly' => $rowsOnly,
        ];

        if ($rowsOnly) {
            return response()->json([
                'html' => view('leadmanagement::admin.calls._rows', $rowData)->render(),
                'has_more' => $calls->hasMorePages(),
                'next_page' => $calls->currentPage() + 1,
            ]);
        }

        return view('leadmanagement::admin.calls.index', $rowData + [
            'agents' => $agents,
            'sort' => $sort,
            'dir' => $direction,
        ]);
    }

    public function export(Request $request, SuperfoneCallExcelExport $export): Renderable
    {
        $step = (int) $request->input('step', 1);
        if (! in_array($step, [1, 2, 3], true)) {
            $step = 1;
        }

        $keys = $export->resolveKeys($request);
        if ($step === 3 && $keys === []) {
            $step = 2;
            toastr()->error(translate('Select_at_least_one_column'));
        }

        $sharedQuery = $export->sharedQuery($request);
        [$sort, $direction] = $export->resolveSort($request);
        $columns = $export->flatColumns();
        $total = 0;
        $previewRows = [];
        if ($step === 3) {
            $query = $export->query($request);
            $total = (clone $query)->count();
            $previewCalls = (clone $query)->limit(SuperfoneCallExcelExport::PREVIEW_LIMIT)->get();
            $previewRows = $export->valueRows($previewCalls, $keys);
        }

        return view('leadmanagement::admin.calls.export', [
            'step' => $step,
            'groups' => $export->groups(),
            'columns' => $columns,
            'selectedKeys' => $keys,
            'previewRows' => $previewRows,
            'total' => $total,
            'sharedQuery' => $sharedQuery,
            'agents' => $export->agents(),
            'outcomeOptions' => $export->outcomeOptions(),
            'sortOptions' => $export->sortOptions(),
            'sort' => $sort,
            'dir' => $direction,
            'q' => trim((string) $request->input('q', '')),
            'outcome' => (string) $request->input('outcome', ''),
            'agent' => (string) $request->input('agent', ''),
            'matched' => (string) $request->input('matched', ''),
            'callDirection' => (string) $request->input('direction', ''),
            'from' => (string) $request->input('from', ''),
            'to' => (string) $request->input('to', ''),
            'backUrl' => route('admin.calls.index', $sharedQuery),
        ]);
    }

    public function exportDownload(Request $request, SuperfoneCallExcelExport $export): StreamedResponse|RedirectResponse
    {
        $keys = $export->resolveKeys($request);
        if ($keys === []) {
            toastr()->error(translate('Select_at_least_one_column'));

            return back();
        }

        $calls = $export->query($request)->get();
        if ($calls->isEmpty()) {
            toastr()->error(translate('No_results_found'));

            return back();
        }

        $rows = $export->labeledRows($calls, $keys);
        $fileName = 'calls-'.now('Asia/Kolkata')->format('Y-m-d-His').'.xlsx';

        return (new FastExcel(collect($rows)))->download($fileName);
    }

    public function report(Request $request, SuperfoneCallReport $report, SuperfoneLeadCharts $leadCharts)
    {
        try {
            $data = $report->build();
            try {
                $data['leadCharts'] = $leadCharts->build($request);
            } catch (\Throwable $e) {
                report($e);
                $data['leadCharts'] = [
                    'filters' => ['period' => 'overall', 'category' => null, 'service' => null, 'zone' => null],
                    'options' => ['categories' => [], 'services' => [], 'zones' => []],
                    'total' => 0,
                    'types' => ['summary' => []],
                    'customer' => ['total' => 0, 'summary' => []],
                    'provider' => ['total' => 0, 'summary' => []],
                    'bookings' => ['total' => 0, 'lead_count' => 0, 'summary' => []],
                    'breakdown' => [
                        'category' => ['categories' => [], 'series' => []],
                        'service' => ['categories' => [], 'series' => []],
                        'zone' => ['categories' => [], 'series' => []],
                    ],
                ];
            }

            return response(view('leadmanagement::admin.calls.report', $data)->render());
        } catch (\Throwable $e) {
            report($e);

            return response($e::class.': '.$e->getMessage(), 500);
        }
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

    /**
     * Staff override: force a new Unknown lead for this call.
     */
    public function createLead(SuperfoneCall $call): RedirectResponse
    {
        $digits = (string) ($call->customer_phone_digits ?: '');
        if (strlen($digits) < 8) {
            toastr()->error(translate('Phone_number_is_required'));

            return back();
        }

        try {
            $fresh = $this->calls->createLeadFromCall($call);
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Something_went_wrong'));

            return back();
        }

        toastr()->success(translate('New_lead_created_from_call'));

        if ($fresh->lead_id) {
            return redirect()->route('admin.lead.show', $fresh->lead_id);
        }

        return back();
    }

    /**
     * Staff override: reopen a non-responsive lead and attach this call.
     */
    public function reopenAndLink(Request $request, SuperfoneCall $call): RedirectResponse
    {
        $validated = $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
        ]);

        $lead = Lead::query()->findOrFail($validated['lead_id']);
        if (! $lead->isNonResponsive()) {
            toastr()->error(translate('This_lead_is_not_closed_as_non_responsive'));

            return back();
        }

        try {
            $this->calls->linkToLead($call, $lead);
        } catch (\InvalidArgumentException $e) {
            toastr()->error($e->getMessage());

            return back();
        }

        toastr()->success(translate('Lead_reopened_and_call_linked'));

        return redirect()->route('admin.lead.show', $lead->id);
    }

    public function destroy(SuperfoneCall $call): RedirectResponse
    {
        abort_unless(is_super_admin(), 403);

        $this->calls->delete($call, Auth::id() !== null ? (string) Auth::id() : null);

        toastr()->success(translate('Call_moved_to_deleted_calls'));

        return redirect()->route('admin.calls.deleted');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless(is_super_admin(), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:2'],
            'ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        $deleted = $this->calls->deleteMany(
            $validated['ids'],
            Auth::id() !== null ? (string) Auth::id() : null
        );

        if ($deleted === 0) {
            toastr()->error(translate('No_calls_selected'));

            return back();
        }

        toastr()->success(translate('Calls_moved_to_deleted_calls'));

        return redirect()->route('admin.calls.deleted');
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

    public function show(int $call): View
    {
        $model = SuperfoneCall::withTrashed()->findOrFail($call);
        $model->load(['lead', 'followup', 'employee', 'provider', 'booking', 'customer', 'deletedByUser']);
        $bookingChoices = collect();
        if (! $model->trashed() && ! $model->booking_id && ! $model->needsLeadLink()) {
            $open = $this->calls->openBookings($model);
            if ($open->count() > 1) {
                $bookingChoices = $open;
            }
        }

        $reopenLead = null;
        $digits = (string) ($model->customer_phone_digits ?: '');
        if (! $model->trashed() && strlen($digits) >= 8) {
            $openStatus = app(LeadOpenStatusService::class);
            $reopenLead = $openStatus->firstReopenableNonResponsiveLead(
                $openStatus->leadsForPhoneDigits($digits)
            );
        }

        return view('leadmanagement::admin.calls.show', [
            'call' => $model,
            'bookingChoices' => $bookingChoices,
            'reopenLead' => $reopenLead,
        ]);
    }

    public function recording(int $call): BinaryFileResponse
    {
        $model = SuperfoneCall::withTrashed()->findOrFail($call);
        abort_unless($model->hasRecording(), 404);

        $path = $model->recordingStoragePath();
        abort_unless($path, 404);

        return SeekableRecordingResponse::fromDisk(
            $model->recordingDisk(),
            $path,
            $model->recording_mime ?: 'audio/mpeg',
            $model->recording_original_name ?: basename($path)
        );
    }

    public function downloadRecording(int $call): RedirectResponse
    {
        $model = SuperfoneCall::withTrashed()->findOrFail($call);
        abort_if($model->trashed(), 404);

        $this->calls->downloadRecording($model);
        $model->refresh();

        if ($model->hasRecording()) {
            toastr()->success(translate('Recording_saved'));
        } else {
            toastr()->error($model->recording_error ?: translate('Recording_download_failed'));
        }

        return redirect()->route('admin.calls.show', $model);
    }
}
