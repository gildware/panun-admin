<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\WebBooking;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Services\LeadOpenStatusService;

class WebBookingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $bookings = WebBooking::query()
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
            $bookings->getCollection()->pluck('lead')->filter()
        );

        return view('bookingmodule::admin.web-booking.index', compact('bookings', 'search', 'leadDisplayData'));
    }

    public function show(int $id): View
    {
        $booking = WebBooking::query()
            ->with(['lead.source'])
            ->findOrFail($id);

        $leadDisplayData = $booking->lead
            ? $this->buildLeadDisplayData(collect([$booking->lead]))
            : [];

        return view('bookingmodule::admin.web-booking.show', compact('booking', 'leadDisplayData'));
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
            ->where('type', Lead::TYPE_CUSTOMER)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $statusIds = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (!empty($data['customer_lead_status_id'])) {
                $statusIds[] = (int) $data['customer_lead_status_id'];
            }
        }

        $statuses = $statusIds !== []
            ? CustomerLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy('id')
            : collect();

        $openMeta = app(LeadOpenStatusService::class)->buildLeadStatusMeta($leads);

        $displayData = [];
        foreach ($leads as $lead) {
            $history = $histories->get($lead->id);
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $data['customer_lead_status_id'] ?? null;
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
