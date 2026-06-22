<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BookingModule\Entities\Booking;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;
use Modules\CallCenterModule\Transformers\BookingTransformer;

class CustomerBookingController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly BookingTransformer $bookingTransformer,
    ) {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $limit = min(50, max(1, (int) $request->query('limit', 10)));
        $statusFilter = $request->query('status');

        $bookings = Booking::query()
            ->where('customer_id', $profile->user_id)
            ->when($statusFilter === 'active', function ($q) {
                $q->whereIn('booking_status', ['pending', 'accepted', 'ongoing', 'on_hold']);
            })
            ->when($statusFilter === 'confirmed', fn ($q) => $q->where('booking_status', 'accepted'))
            ->when(in_array($statusFilter, ['cancelled', 'canceled'], true), fn ($q) => $q->whereIn('booking_status', ['canceled', 'cancelled']))
            ->when($statusFilter === 'completed', fn ($q) => $q->where('booking_status', 'completed'))
            ->orderByDesc('service_schedule')
            ->limit($limit)
            ->get();

        return $this->ok([
            'data' => $bookings->map(fn (Booking $b) => $this->bookingTransformer->transform($b))->values()->all(),
        ]);
    }

    public function orders(int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        return $this->ok(['data' => []]);
    }

    public function summary(int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $lastBooking = Booking::query()
            ->where('customer_id', $profile->user_id)
            ->orderByDesc('service_schedule')
            ->first();

        $openComplaints = \Modules\ProviderManagement\Entities\CustomerIncident::query()
            ->where('customer_id', $profile->user_id)
            ->where('incident_type', 'COMPLAINT')
            ->count();

        return $this->ok([
            'last_booking' => $lastBooking ? [
                'booking_ref' => $lastBooking->readable_id,
                'status' => $this->bookingTransformer->transform($lastBooking)['status'],
                'scheduled_at' => $lastBooking->service_schedule?->utc()->toIso8601String(),
                'service_type' => $this->bookingTransformer->transform($lastBooking)['service_type'],
            ] : null,
            'last_order' => null,
            'open_complaints_count' => $openComplaints,
            'previous_interaction_summary' => $profile->ai_summary,
        ]);
    }
}
