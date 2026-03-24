<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\ProviderManagement\Services\BookingAdminFeedbackService;
use Modules\ProviderManagement\Services\FeedbackScoreConfigService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\UserManagement\Entities\User;

class CustomerPerformanceController
{
    public function __construct(
        private readonly FeedbackScoreConfigService $feedbackScoreConfigService,
    ) {
    }

    public function storeBookingFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'context_booking_id' => ['required', 'string', 'max:36'],
            'customer_id' => ['required', 'string', 'max:36'],
            'action_type' => ['required', Rule::in(['completed', 'cancelled', 'canceled', 'provider_changed'])],
            'incident_type' => ['required', Rule::in(['complaint', 'positive_feedback', 'non_complaint'])],
            'tags' => ['required', 'array', 'min:1'],
            'tags.*' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $contextBookingId = $validated['context_booking_id'];
        $customerId = $validated['customer_id'];

        $booking = Booking::query()->where('id', $contextBookingId)->first();
        if (!$booking) {
            $bookingRepeat = BookingRepeat::query()
                ->with('booking')
                ->where('id', $contextBookingId)
                ->first();
            $booking = $bookingRepeat?->booking;
        }

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ((string) $booking->customer_id !== (string) $customerId) {
            return response()->json(['message' => 'Customer does not match booking'], 422);
        }

        $feedbackType = $validated['incident_type'];
        $incidentType = match ($feedbackType) {
            'complaint' => ProviderPerformanceService::INCIDENT_COMPLAINT,
            'positive_feedback' => ProviderPerformanceService::INCIDENT_POSITIVE_FEEDBACK,
            default => ProviderPerformanceService::INCIDENT_NON_COMPLAINT,
        };

        $rawAction = $validated['action_type'] === 'canceled' ? 'cancelled' : $validated['action_type'];
        $actionType = match ($rawAction) {
            'cancelled' => ProviderPerformanceService::ACTION_CANCELLED,
            'provider_changed' => ProviderPerformanceService::ACTION_PROVIDER_CHANGED,
            default => ProviderPerformanceService::ACTION_COMPLETED,
        };

        $tags = array_values(array_unique($validated['tags']));

        $allowedTags = $this->feedbackScoreConfigService->getAllowedTagKeys(
            FeedbackScoreConfigService::ENTITY_CUSTOMER,
            $feedbackType
        );

        foreach ($tags as $tag) {
            if (!in_array($tag, $allowedTags, true)) {
                return response()->json(['message' => 'Invalid tag selection for incident type'], 422);
            }
        }

        $scoreDelta = $this->feedbackScoreConfigService->calculateScoreDelta(
            FeedbackScoreConfigService::ENTITY_CUSTOMER,
            $feedbackType,
            $tags
        );

        DB::transaction(function () use ($validated, $booking, $customerId, $incidentType, $actionType, $tags, $scoreDelta) {
            CustomerIncident::query()->create([
                'customer_id' => $customerId,
                'booking_id' => $booking->id,
                'action_type' => $actionType,
                'incident_type' => $incidentType,
                'tags' => $tags,
                'score_delta' => $scoreDelta,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });

        return response()->json(['message' => 'Feedback stored successfully'], 200);
    }

    public function skipBookingAdminFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'string', Rule::exists('bookings', 'id')],
            'side' => ['required', Rule::in(['provider', 'customer'])],
        ]);

        $booking = Booking::query()->findOrFail($validated['booking_id']);
        $service = app(BookingAdminFeedbackService::class);

        if (!$service->isTerminalBooking($booking)) {
            return response()->json(['message' => 'Feedback can only be skipped for completed or canceled bookings'], 422);
        }

        $column = $validated['side'] === 'provider'
            ? 'admin_provider_feedback_skipped_at'
            : 'admin_customer_feedback_skipped_at';

        if ($validated['side'] === 'provider' && empty($booking->provider_id)) {
            return response()->json(['message' => 'No provider on this booking'], 422);
        }

        if ($validated['side'] === 'customer' && empty($booking->customer_id)) {
            return response()->json(['message' => 'No customer on this booking'], 422);
        }

        $booking->forceFill([$column => now()])->save();

        return response()->json(['message' => 'OK'], 200);
    }

    public function updateManualStatus(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'string', 'max:36', Rule::exists('users', 'id')],
            'manual_status' => ['required', Rule::in(['active', 'suspended', 'blacklisted'])],
        ]);

        $customer = User::query()->whereIn('user_type', CUSTOMER_USER_TYPES)->findOrFail($validated['customer_id']);
        $status = $validated['manual_status'];
        $suspendedUntil = $status === 'suspended' ? Carbon::now()->addDays(30) : null;

        DB::transaction(function () use ($customer, $status, $suspendedUntil) {
            $customer->manual_performance_status = $status;
            $customer->performance_suspended_until = $suspendedUntil;
            $customer->is_active = $status === 'blacklisted' ? 0 : 1;
            $customer->save();
            $customer->tokens()->update(['revoked' => true]);
        });

        if (!$request->expectsJson()) {
            return redirect()->back()->with('success', 'Customer performance status updated successfully');
        }

        return response()->json([
            'message' => 'Customer performance status updated successfully',
            'status' => $status,
            'suspended_until' => $suspendedUntil?->toDateTimeString(),
        ]);
    }
}
