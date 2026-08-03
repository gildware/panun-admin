<?php

namespace Modules\TaskBoardModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\TaskBoardModule\Services\TaskBoardService;

class TaskBoardController extends Controller
{
    public function __construct(
        private readonly TaskBoardService $boardService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->input('search'),
            'assignee_ids' => array_filter((array) $request->input('assignee_ids', [])),
            'my_tickets' => $request->boolean('my_tickets'),
            'overdue' => $request->boolean('overdue'),
            'end_date_from' => $request->input('end_date_from'),
            'end_date_to' => $request->input('end_date_to'),
            'start_date_from' => $request->input('start_date_from'),
            'start_date_to' => $request->input('start_date_to'),
            'link_type' => $request->input('link_type'),
            'link_id' => $request->input('link_id'),
            'sort' => $request->input('sort', 'position'),
        ];

        $payload = $this->boardService->boardPayload($filters);

        return view('taskboardmodule::admin.index', $payload);
    }

    public function trash(): View
    {
        if (! $this->boardService->canRestore()) {
            Toastr::error(translate('You_are_not_authorized_to_view_trash'));
            abort(403);
        }

        $tickets = TaskTicket::onlyTrashed()
            ->with(['assignees', 'column' => fn ($q) => $q->withTrashed(), 'activityLogs' => fn ($q) => $q->latest()->limit(5)])
            ->latest('deleted_at')
            ->paginate(30);

        return view('taskboardmodule::admin.trash', [
            'tickets' => $tickets,
            'canRestore' => true,
        ]);
    }

    public function searchBookings(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $like = $q !== '' ? '%'.$q.'%' : null;

        $results = Booking::query()
            ->when($like, fn ($query) => $query->where(function ($inner) use ($like) {
                $inner->where('readable_id', 'like', $like)
                    ->orWhere('id', 'like', $like);
            }))
            ->latest()
            ->limit(12)
            ->get(['id', 'readable_id', 'booking_status'])
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'text' => ($booking->readable_id ?: $booking->id).' · '.translate($booking->booking_status),
            ]);

        return response()->json(['results' => $results]);
    }

    public function searchLeads(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $like = $q !== '' ? '%'.$q.'%' : null;

        $results = Lead::query()
            ->when($like, fn ($query) => $query->where(function ($inner) use ($like, $q) {
                $inner->where('name', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
                if (ctype_digit($q)) {
                    $inner->orWhere('id', $q);
                }
            }))
            ->latest()
            ->limit(12)
            ->get(['id', 'name', 'phone_number'])
            ->map(fn (Lead $lead) => [
                'id' => (string) $lead->id,
                'text' => ($lead->name ?: '#'.$lead->id).' · '.($lead->phone_number ?? ''),
            ]);

        return response()->json(['results' => $results]);
    }
}
