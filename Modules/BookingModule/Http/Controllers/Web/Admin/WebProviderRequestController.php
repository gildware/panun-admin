<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\WebProviderRequest;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadOpenStatusService;

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

        $leadDisplayData = $this->buildLeadDisplayData(
            $requests->getCollection()->pluck('lead')->filter()
        );

        return view('bookingmodule::admin.web-provider-request.index', compact('requests', 'search', 'leadDisplayData'));
    }

    public function show(int $id): View
    {
        $providerRequest = WebProviderRequest::query()
            ->with(['lead.source'])
            ->findOrFail($id);

        $leadDisplayData = $providerRequest->lead
            ? $this->buildLeadDisplayData(collect([$providerRequest->lead]))
            : [];

        return view('bookingmodule::admin.web-provider-request.show', compact('providerRequest', 'leadDisplayData'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorize('booking_delete');

        $providerRequest = WebProviderRequest::query()->findOrFail($id);
        $providerRequest->delete();

        Toastr::success(translate(DEFAULT_DELETE_200['message']));

        return redirect()->route('admin.booking.web-provider-requests.index');
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<int, array{status_name: string, status_color: string, open_label: string, open_badge_class: string}>
     */
    protected function buildLeadDisplayData(Collection $leads): array
    {
        $leads = $leads->filter()->values();
        if ($leads->isEmpty()) {
            return [];
        }

        $leadIds = $leads->pluck('id')->all();
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', Lead::TYPE_PROVIDER)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $statusIds = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (!empty($data['provider_lead_status_id'])) {
                $statusIds[] = (int) $data['provider_lead_status_id'];
            }
        }

        $statuses = $statusIds !== []
            ? ProviderLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy('id')
            : collect();

        $openMeta = app(LeadOpenStatusService::class)->buildLeadStatusMeta($leads);

        $displayData = [];
        foreach ($leads as $lead) {
            $history = $histories->get($lead->id);
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $data['provider_lead_status_id'] ?? null;
            $status = $statusId ? $statuses->get((int) $statusId) : null;
            $open = $openMeta[(int) $lead->id] ?? ['label' => '—', 'badge_class' => 'bg-secondary'];

            $displayData[(int) $lead->id] = [
                'status_name' => $status?->name ?? '—',
                'status_color' => $status && !empty($status->color) ? $status->color : '#0d6efd',
                'open_label' => $open['label'],
                'open_badge_class' => $open['badge_class'],
            ];
        }

        return $displayData;
    }
}
