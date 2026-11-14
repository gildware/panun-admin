<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\WebBooking;
use Modules\LeadManagement\Entities\Lead;
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

    public function destroy(int $id): RedirectResponse
    {
        $this->authorize('booking_delete');

        $booking = WebBooking::query()->findOrFail($id);
        $booking->delete();

        Toastr::success(translate(DEFAULT_DELETE_200['message']));

        return redirect()->route('admin.booking.web-bookings.index');
    }

    /**
     * Same Open / Closed / Non Responsive badges as the leads list "Lead Status" column.
     *
     * @param  Collection<int, Lead>  $leads
     * @return array<int, array{open_label: string, open_badge_class: string}>
     */
    protected function buildLeadDisplayData(Collection $leads): array
    {
        $leads = $leads->filter()->values();
        if ($leads->isEmpty()) {
            return [];
        }

        $openMeta = app(LeadOpenStatusService::class)->buildLeadStatusMeta($leads);

        $displayData = [];
        foreach ($leads as $lead) {
            $open = $openMeta[(int) $lead->id] ?? ['label' => '—', 'badge_class' => 'bg-secondary'];

            $displayData[(int) $lead->id] = [
                'open_label' => $open['label'],
                'open_badge_class' => $open['badge_class'],
            ];
        }

        return $displayData;
    }
}
