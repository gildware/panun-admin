<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\LeadManagement\Services\LeadOtherPhoneLinkService;
use Modules\LeadManagement\Services\PartyPhoneDirectory;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class LeadOtherPhoneLinkController extends Controller
{
    public function __construct(
        private readonly LeadOtherPhoneLinkService $linker,
        private readonly PartyPhoneDirectory $phones,
    ) {}

    public function searchPeople(Request $request): JsonResponse
    {
        $type = (string) $request->query('owner_type', PartyAlternatePhone::OWNER_CUSTOMER);
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['people' => []]);
        }

        if ($type === PartyAlternatePhone::OWNER_PROVIDER) {
            $digits = PartyPhoneDirectory::normalizeDigits($term) ?? '';
            $excludeLeadId = (int) $request->query('exclude_lead_id', 0);
            $providers = Provider::query()
                ->where(function ($query) use ($term, $digits) {
                    $query->where('company_name', 'like', '%'.$term.'%')
                        ->orWhere('contact_person_name', 'like', '%'.$term.'%')
                        ->orWhere('company_phone', 'like', '%'.$term.'%')
                        ->orWhere('contact_person_phone', 'like', '%'.$term.'%');
                    if ($digits !== '') {
                        $query->orWhere('company_phone', 'like', '%'.$digits)
                            ->orWhere('contact_person_phone', 'like', '%'.$digits);
                    }
                })
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(['id', 'company_name', 'contact_person_name', 'company_phone', 'contact_person_phone']);

            $people = $providers->map(function (Provider $provider) {
                $name = trim((string) ($provider->company_name ?: $provider->contact_person_name)) ?: (string) $provider->id;
                $phone = $provider->company_phone ?: $provider->contact_person_phone;

                return [
                    'id' => (string) $provider->id,
                    'owner_type' => PartyAlternatePhone::OWNER_PROVIDER,
                    'label' => $name,
                    'phone' => $phone,
                    'tag' => 'provider',
                    'tag_label' => translate('Provider'),
                    'meta' => '',
                ];
            });

            // Also open provider leads (not yet registered as providers).
            $leadQuery = Lead::query()
                ->where('lead_type', Lead::TYPE_PROVIDER)
                ->where(function ($query) use ($term, $digits) {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone_number', 'like', '%'.$term.'%');
                    if ($digits !== '') {
                        $query->orWhere('phone_number', 'like', '%'.$digits);
                    }
                })
                ->orderByDesc('updated_at')
                ->limit(20);

            if ($excludeLeadId > 0) {
                $leadQuery->where('id', '!=', $excludeLeadId);
            }

            $openStatus = app(\Modules\LeadManagement\Services\LeadOpenStatusService::class);
            $leadPeople = $leadQuery->get(['id', 'name', 'phone_number', 'lead_type'])
                ->filter(fn (Lead $lead) => $openStatus->isLeadOpen($lead))
                ->take(8)
                ->map(function (Lead $lead) {
                    $name = trim((string) ($lead->name ?? '')) ?: ('Lead #'.$lead->id);
                    $typeKey = strtolower((string) ($lead->lead_type ?: Lead::TYPE_UNKNOWN));
                    $typeLabel = Lead::leadTypes()[$typeKey] ?? ucfirst($typeKey);

                    return [
                        'id' => (string) $lead->id,
                        'owner_type' => PartyAlternatePhone::OWNER_PROVIDER_LEAD,
                        'label' => $name,
                        'phone' => $lead->phone_number,
                        'tag' => 'lead',
                        'tag_label' => translate('Lead'),
                        'lead_type' => $typeKey,
                        'lead_type_label' => translate($typeLabel) ?: $typeLabel,
                        'meta' => '#'.$lead->id,
                    ];
                })
                ->values();

            return response()->json([
                'people' => $people->concat($leadPeople)->values(),
            ]);
        }

        $digits = PartyPhoneDirectory::normalizeDigits($term) ?? '';
        $customers = User::query()
            ->whereIn('user_type', CUSTOMER_USER_TYPES)
            ->where(function ($query) use ($term, $digits) {
                $query->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%')
                    ->orWhere(DB::raw("CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))"), 'like', '%'.$term.'%');
                if ($digits !== '') {
                    $query->orWhere('phone', 'like', '%'.$digits);
                }
            })
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        return response()->json([
            'people' => $customers->map(function (User $user) {
                $name = trim((string) (($user->first_name ?? '').' '.($user->last_name ?? '')));

                return [
                    'id' => (string) $user->id,
                    'owner_type' => PartyAlternatePhone::OWNER_CUSTOMER,
                    'label' => $name !== '' ? $name : (string) $user->phone,
                    'phone' => $user->phone,
                    'tag' => 'customer',
                    'tag_label' => translate('Customer'),
                    'meta' => '',
                ];
            })->values(),
        ]);
    }

    public function options(Request $request, int $id): JsonResponse
    {
        $ownerType = (string) $request->query('owner_type', '');
        $ownerId = (string) $request->query('owner_id', '');
        if (! in_array($ownerType, [
            PartyAlternatePhone::OWNER_CUSTOMER,
            PartyAlternatePhone::OWNER_PROVIDER,
            PartyAlternatePhone::OWNER_PROVIDER_LEAD,
        ], true)
            || $ownerId === ''
        ) {
            return response()->json(['open_leads' => [], 'bookings' => []]);
        }

        $openLeads = $this->linker->openLeadsForOwner($ownerType, $ownerId);
        $bookings = $this->linker->bookingsForOwner($ownerType, $ownerId);

        return response()->json([
            'open_leads' => $openLeads->map(fn (Lead $lead) => [
                'id' => (int) $lead->id,
                'label' => '#'.$lead->id.' — '.($lead->name ?: translate('Lead')),
                'phone' => $lead->phone_number,
                'type' => $lead->lead_type,
            ])->values(),
            'bookings' => $bookings->map(function (Booking $booking) {
                $status = (string) $booking->booking_status;
                $closed = in_array($status, Booking::CLOSED_STATUSES_FOR_INBOUND_LEAD_MATCH, true);

                return [
                    'id' => (string) $booking->id,
                    'label' => '#'.($booking->readable_id ?: $booking->id).' — '.$status,
                    'status' => $status,
                    'is_open' => ! $closed,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request, int $id): RedirectResponse|\Illuminate\Http\Response
    {
        $lead = Lead::query()->findOrFail($id);
        if ($lead->trashed()) {
            toastr()->error(translate('Cannot_link_other_phone_on_deleted_lead') ?: 'Cannot link other phone on a deleted lead');

            return redirect()->route('admin.lead.show', $id);
        }

        $validated = $request->validate([
            'owner_type' => ['required', 'in:customer,provider,provider_lead'],
            'owner_id' => ['required', 'string'],
            'action' => ['required', 'in:keep_lead,merge_lead,attach_booking'],
            'target_lead_id' => ['nullable', 'integer'],
            'booking_id' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->linker->link($lead, $validated);
        } catch (\InvalidArgumentException $e) {
            toastr()->error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Something_went_wrong') ?: 'Something went wrong');

            return redirect()->back()->withInput();
        }

        toastr()->success($result['message']);

        if ($request->boolean('in_modal')) {
            return response()->view('leadmanagement::admin.leads.partials._close-preview-modal', [
                'message' => $result['message'],
            ]);
        }

        if (! empty($result['redirect_lead_id'])) {
            return redirect()->route('admin.lead.show', $result['redirect_lead_id']);
        }

        return redirect()->route('admin.lead.deleted');
    }

    /**
     * Move remaining history from a lead whose phone is already linked into the
     * linked provider lead, then archive the source (fixes incomplete merges).
     */
    public function finishMerge(Request $request, int $id): RedirectResponse|\Illuminate\Http\Response
    {
        $lead = Lead::query()->findOrFail($id);
        if ($lead->trashed()) {
            toastr()->error(translate('Cannot_link_other_phone_on_deleted_lead') ?: 'Cannot merge a deleted lead');

            return redirect()->route('admin.lead.show', $id);
        }

        try {
            $result = $this->linker->finishMergeIntoLinkedProviderLead($lead);
        } catch (\InvalidArgumentException $e) {
            toastr()->error($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Something_went_wrong') ?: 'Something went wrong');

            return redirect()->back();
        }

        toastr()->success($result['message']);

        if ($request->boolean('in_modal')) {
            return response()->view('leadmanagement::admin.leads.partials._close-preview-modal', [
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('admin.lead.show', $result['redirect_lead_id']);
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $digits = PartyPhoneDirectory::normalizeDigits((string) $request->query('phone', ''));
        if ($digits === null) {
            return response()->json(['owner' => null]);
        }

        $owner = $this->phones->findOwner($digits);

        return response()->json(['owner' => $owner]);
    }
}
