<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderIncident;
use Modules\ProviderManagement\Services\FeedbackScoreConfigService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;

class ProviderPerformanceController
{
    public function __construct(
        private readonly ProviderPerformanceService $performanceService,
        private readonly FeedbackScoreConfigService $feedbackScoreConfigService,
    ) {
    }

    public function storeFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'context_booking_id' => ['required', 'string', 'max:36'], // booking.id or booking_repeat.id
            'provider_id' => ['required', 'string', 'max:36'],
            'action_type' => ['required', Rule::in(['completed', 'cancelled', 'canceled', 'provider_changed'])],
            'incident_type' => ['required', Rule::in(['complaint', 'positive_feedback', 'non_complaint'])],
            'tags' => ['required', 'array', 'min:1'],
            'tags.*' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $contextBookingId = $validated['context_booking_id'];
        $providerId = $validated['provider_id'];

        // Resolve the “main booking_id” for the incidents.
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
            FeedbackScoreConfigService::ENTITY_PROVIDER,
            $feedbackType
        );

        foreach ($tags as $tag) {
            if (!in_array($tag, $allowedTags, true)) {
                return response()->json(['message' => 'Invalid tag selection for incident type'], 422);
            }
        }

        $providerScoreDelta = $this->feedbackScoreConfigService->calculateScoreDelta(
            FeedbackScoreConfigService::ENTITY_PROVIDER,
            $feedbackType,
            $tags
        );

        DB::transaction(function () use ($validated, $booking, $providerId, $incidentType, $actionType, $tags, $providerScoreDelta) {
            ProviderIncident::query()->create([
                'provider_id' => $providerId,
                'booking_id' => $booking->id,
                'action_type' => $actionType,
                'incident_type' => $incidentType,
                'tags' => $tags,
                'score_delta' => $providerScoreDelta,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->performanceService->evaluateAndUpdateProviderPerformanceStatus($providerId);
        });

        return response()->json(['message' => 'Feedback stored successfully'], 200);
    }

    public function updateManualStatus(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'provider_id' => ['required', 'string', 'max:36', Rule::exists('providers', 'id')],
            'manual_status' => ['required', Rule::in(['active', 'suspended', 'blacklisted'])],
        ]);

        $provider = Provider::query()->with('owner')->findOrFail($validated['provider_id']);
        $owner = $provider->owner;

        $status = $validated['manual_status'];
        $suspendedUntil = $status === 'suspended' ? Carbon::now()->addDays(30) : null;

        DB::transaction(function () use ($provider, $owner, $status, $suspendedUntil) {
            $provider->manual_performance_status = $status;
            $provider->performance_suspended_until = $suspendedUntil;

            if ($status === 'blacklisted') {
                $provider->is_suspended = 1;
                $provider->performance_status = 'blacklisted';
                $provider->is_active = 0;
            } elseif ($status === 'suspended') {
                $provider->is_suspended = 1;
                $provider->is_active = 1;
                $provider->performance_status = 'warning';
            } else {
                $provider->is_suspended = 0;
                $provider->is_active = 1;
                $provider->performance_status = 'active';
            }

            $provider->save();

            if ($owner) {
                if ($status === 'blacklisted') {
                    $owner->is_active = 0;
                } elseif ($status === 'active') {
                    $owner->is_active = 1;
                }

                $owner->manual_performance_status = $status;
                $owner->performance_suspended_until = $suspendedUntil;
                $owner->save();
                $owner->tokens()->update(['revoked' => true]);
            }
        });

        if (!$request->expectsJson()) {
            return redirect()->back()->with('success', 'Provider performance status updated successfully');
        }

        return response()->json([
            'message' => 'Provider performance status updated successfully',
            'status' => $status,
            'suspended_until' => $suspendedUntil?->toDateTimeString(),
        ]);
    }
}

