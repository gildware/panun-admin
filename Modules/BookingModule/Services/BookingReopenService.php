<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingReopenEvent;
use Modules\BookingModule\Entities\BookingScheduleHistory;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\ProviderManagement\Entities\ProviderIncident;
use Modules\ProviderManagement\Services\FeedbackScoreConfigService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\UserManagement\Entities\User;

class BookingReopenService
{
    public function __construct(
        private readonly FeedbackScoreConfigService $feedbackScoreConfigService,
        private readonly ProviderPerformanceService $performanceService,
    ) {
    }

    /**
     * @return array{event: BookingReopenEvent, booking: Booking}
     */
    public function reopenInPlace(Booking $source, User $actor, string $complaintNotes, string $targetStatus, ?int $holdReopenReasonId = null, ?string $newServiceSchedule = null): array
    {
        if (!in_array($targetStatus, ['pending', 'accepted'], true)) {
            throw new \InvalidArgumentException('Invalid target status for reopen.');
        }

        if ((int) ($source->is_repeated ?? 0) !== 0) {
            throw new \RuntimeException(translate('Reopen is only supported for non-repeat bookings in this version.'));
        }

        // Disputed-close is an end state; do not allow reopening again.
        if (!empty($source->reopen_disputed_snapshot) && is_array($source->reopen_disputed_snapshot)) {
            throw new \RuntimeException(translate('Reopen_not_allowed_after_disputed_close'));
        }

        if (($source->booking_status ?? '') !== 'completed') {
            throw new \RuntimeException(translate('Only completed bookings can be reopened this way.'));
        }

        if ($source->isLossMakingFinancialSettlement()) {
            throw new \RuntimeException(translate('Loss_making_completed_booking_cannot_be_reopened'));
        }

        if ($source->blocksAdminReopenDueToDecidedChargesSpecialSettlement()) {
            throw new \RuntimeException(translate('Bfs_decided_charges_settlement_booking_cannot_be_reopened'));
        }

        return DB::transaction(function () use ($source, $actor, $complaintNotes, $targetStatus, $holdReopenReasonId, $newServiceSchedule) {
            $event = BookingReopenEvent::query()->create([
                'source_booking_id' => $source->id,
                'actor_user_id' => $actor->id,
                'resolution' => BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE,
                'complaint_notes' => $complaintNotes ?: null,
                'child_booking_id' => null,
                'target_status' => $targetStatus,
                'booking_hold_reopen_reason_id' => $holdReopenReasonId,
            ]);

            $source->last_reopen_event_at = now();
            $source->reopened_by = $actor->id;
            $source->reopen_resolved_at = null;
            $source->reopen_resolved_by = null;
            $source->reopen_resolve_remarks = null;
            $source->booking_status = $targetStatus;
            $source->serviceman_id = null;

            // Reopen must revert any per-booking custom commission so default commission rules apply again.
            $source->admin_commission_override = null;
            if (trim((string) ($source->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION) {
                $source->settlement_outcome = null;
                $cfg = is_array($source->settlement_config) ? $source->settlement_config : [];
                unset($cfg['custom_admin_commission']);
                $source->settlement_config = $cfg === [] ? null : $cfg;
                $source->settlement_snapshot = null;
            }

            if ($newServiceSchedule !== null && $newServiceSchedule !== '') {
                $parsed = Carbon::parse($newServiceSchedule)->toDateTimeString();
                $source->service_schedule = $parsed;
            }

            $source->save();
            $source->resyncStoredCommissionAndSettlementSnapshot();

            if ($newServiceSchedule !== null && $newServiceSchedule !== '' && $source->wasChanged('service_schedule')) {
                $history = new BookingScheduleHistory();
                $history->booking_id = $source->id;
                $history->changed_by = $actor->id;
                $history->schedule = $source->service_schedule;
                $history->save();
            }

            BookingStatusHistory::query()->create([
                'booking_id' => $source->id,
                'booking_repeat_id' => null,
                'changed_by' => $actor->id,
                'booking_status' => $targetStatus,
                'booking_hold_reopen_reason_id' => $holdReopenReasonId,
                'status_change_remarks' => $complaintNotes !== '' ? $complaintNotes : null,
            ]);

            $this->recordProviderReopenIncident($source, $actor, $complaintNotes, 'reopen_in_place');

            return ['event' => $event, 'booking' => $source->fresh()];
        });
    }

    /**
     * Link an admin-created booking to a completed source after the user filled the normal "add booking" form.
     * Caller must run inside an open DB transaction if atomicity with booking insert is required.
     */
    public function linkNewBookingFromReopenedCompleted(
        Booking $source,
        Booking $newBooking,
        User $actor,
        string $complaintNotes,
        ?int $holdReopenReasonId = null,
    ): BookingReopenEvent {
        if ((int) ($source->is_repeated ?? 0) !== 0) {
            throw new \RuntimeException(translate('Creating a follow-up booking from a repeat series is not supported in this version.'));
        }

        // Disputed-close is an end state; do not allow reopening again.
        if (!empty($source->reopen_disputed_snapshot) && is_array($source->reopen_disputed_snapshot)) {
            throw new \RuntimeException(translate('Reopen_not_allowed_after_disputed_close'));
        }

        if (($source->booking_status ?? '') !== 'completed') {
            throw new \RuntimeException(translate('Only completed bookings can spawn a follow-up booking this way.'));
        }

        if ($source->isLossMakingFinancialSettlement()) {
            throw new \RuntimeException(translate('Loss_making_completed_booking_cannot_be_reopened'));
        }

        if ($source->blocksAdminReopenDueToDecidedChargesSpecialSettlement()) {
            throw new \RuntimeException(translate('Bfs_decided_charges_settlement_booking_cannot_be_reopened'));
        }

        $newBooking->originated_from_booking_id = $source->id;
        $newBooking->save();

        $event = BookingReopenEvent::query()->create([
            'source_booking_id' => $source->id,
            'actor_user_id' => $actor->id,
            'resolution' => BookingReopenEvent::RESOLUTION_NEW_BOOKING,
            'complaint_notes' => $complaintNotes ?: null,
            'child_booking_id' => $newBooking->id,
            'target_status' => null,
            'booking_hold_reopen_reason_id' => $holdReopenReasonId,
        ]);

        $source->last_reopen_event_at = now();
        $source->reopened_by = $actor->id;
        $source->reopen_resolved_at = null;
        $source->reopen_resolved_by = null;
        $source->reopen_resolve_remarks = null;
        $source->save();

        $this->recordProviderReopenIncident($source, $actor, $complaintNotes, 'new_booking', $newBooking->id);

        return $event;
    }

    private function recordProviderReopenIncident(
        Booking $source,
        User $actor,
        string $complaintNotes,
        string $resolutionLabel,
        ?string $childBookingId = null,
    ): void {
        if (empty($source->provider_id)) {
            return;
        }

        $tags = ['reopened'];
        $score = $this->feedbackScoreConfigService->calculateScoreDelta(
            FeedbackScoreConfigService::ENTITY_PROVIDER,
            FeedbackScoreConfigService::FEEDBACK_TYPE_COMPLAINT,
            $tags
        );

        $notes = trim($complaintNotes . "\n[" . $resolutionLabel . ($childBookingId ? ' → ' . $childBookingId : '') . ']', "\n");

        ProviderIncident::query()->create([
            'provider_id' => $source->provider_id,
            'booking_id' => $source->id,
            'action_type' => ProviderPerformanceService::ACTION_REOPENED,
            'incident_type' => ProviderPerformanceService::INCIDENT_COMPLAINT,
            'tags' => $tags,
            'score_delta' => $score,
            'notes' => $notes ?: null,
            'created_by' => $actor->id,
        ]);

        $this->performanceService->evaluateAndUpdateProviderPerformanceStatus((string) $source->provider_id);
    }
}
