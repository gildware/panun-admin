<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;

class BookingFollowupService
{
    public const SUPERSEDED_NOTE = 'Superseded by newer follow-up';

    public const BOOKING_CLOSED_NOTE = 'Booking closed — follow-up cancelled';

    /**
     * Cancel open scheduled follow-ups for one party on a booking.
     */
    public function cancelScheduledForParty(
        Booking $booking,
        string $for,
        string $reason = self::SUPERSEDED_NOTE
    ): int {
        $rows = BookingFollowup::query()
            ->where('booking_id', $booking->id)
            ->where('for', $for)
            ->where('status', 'scheduled')
            ->get();

        foreach ($rows as $row) {
            $this->markCancelled($row, $reason);
        }

        return $rows->count();
    }

    /**
     * Cancel all open scheduled follow-ups on a booking (e.g. completed / canceled).
     */
    public function cancelAllScheduled(
        Booking $booking,
        string $reason = self::BOOKING_CLOSED_NOTE
    ): int {
        $rows = BookingFollowup::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'scheduled')
            ->get();

        foreach ($rows as $row) {
            $this->markCancelled($row, $reason);
        }

        return $rows->count();
    }

    public function schedule(
        Booking $booking,
        Carbon|string $date,
        string $for = 'customer',
        ?string $reason = null,
        ?string $createdBy = null,
        ?string $urgency = null
    ): BookingFollowup {
        $this->cancelScheduledForParty($booking, $for);

        return BookingFollowup::create([
            'booking_id' => $booking->id,
            'date' => Carbon::parse($date)->format('Y-m-d H:i:s'),
            'reason' => $reason,
            'for' => $for,
            'status' => 'scheduled',
            'urgency' => $urgency ?? BookingFollowup::URGENCY_MEDIUM,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    /**
     * Enforce at most one open scheduled follow-up per party (customer / provider).
     * Keeps the earliest due date and cancels newer duplicates.
     */
    public function dedupeScheduledForParty(Booking $booking, string $for): ?BookingFollowup
    {
        $rows = BookingFollowup::query()
            ->where('booking_id', $booking->id)
            ->where('for', $for)
            ->where('status', 'scheduled')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $keep = $rows->first();
        foreach ($rows->skip(1) as $row) {
            $this->markCancelled($row, self::SUPERSEDED_NOTE);
        }

        return $keep;
    }

    public function dedupeAllScheduledParties(Booking $booking): void
    {
        foreach (['customer', 'provider'] as $for) {
            $this->dedupeScheduledForParty($booking, $for);
        }

        if ($booking->relationLoaded('followups')) {
            $booking->load('followups');
        }
    }

    private function markCancelled(BookingFollowup $row, string $reason): void
    {
        $remarks = trim((string) ($row->remarks ?? ''));

        $row->update([
            'status' => 'cancelled',
            'remarks' => $remarks === '' ? $reason : $remarks.' | '.$reason,
        ]);
    }

    /**
     * Follow-up was scheduled before today (missed / overdue).
     */
    public function pendingFollowupIsOverdue(Carbon $followupDate): bool
    {
        return ! $followupDate->isToday() && $followupDate->isPast();
    }

    /**
     * Whether a scheduled follow-up is due today or earlier on an open booking.
     */
    public function scheduledFollowupIsPending(BookingFollowup $followup, bool $bookingOpen): bool
    {
        if (! $bookingOpen || $followup->status !== 'scheduled' || ! $followup->date) {
            return false;
        }

        $date = $followup->date instanceof Carbon
            ? $followup->date
            : Carbon::parse($followup->date);

        return $date->toDateString() <= Carbon::today()->toDateString();
    }

    /**
     * @return array{customer: ?BookingFollowup, provider: ?BookingFollowup}
     */
    public function nextScheduledFollowups(Booking $booking): array
    {
        $this->dedupeAllScheduledParties($booking);

        $scheduled = ($booking->followups ?? BookingFollowup::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'scheduled')
            ->orderBy('date')
            ->orderBy('id')
            ->get())
            ->where('status', 'scheduled')
            ->sortBy('date');

        return [
            'customer' => $scheduled->where('for', 'customer')->first(),
            'provider' => $scheduled->where('for', 'provider')->first(),
        ];
    }

    /**
     * List-cell styling for scheduled follow-ups (missed = danger, upcoming = warning).
     *
     * @return array{status: string, label: string, badge_class: string, date_class: string, cell_class: string}|null
     */
    public function buildFollowupListCellMeta(?BookingFollowup $followup, bool $bookingOpen): ?array
    {
        if (! $followup || ! $bookingOpen || ! $followup->date || $followup->status !== 'scheduled') {
            return null;
        }

        $date = $followup->date instanceof Carbon
            ? $followup->date
            : Carbon::parse($followup->date);

        if ($this->pendingFollowupIsOverdue($date)) {
            return [
                'status' => 'missed',
                'label' => 'Missed_Follow_up',
                'badge_class' => 'bg-danger',
                'date_class' => 'text-danger fw-semibold',
                'cell_class' => 'booking-followup-cell--missed',
            ];
        }

        return [
            'status' => 'upcoming',
            'label' => $date->isToday() ? 'Follow_up_due' : 'Follow_up_due_soon',
            'badge_class' => 'bg-warning text-dark',
            'date_class' => 'text-warning fw-semibold',
            'cell_class' => 'booking-followup-cell--upcoming',
        ];
    }

    /**
     * Follow-up badges for one scheduled row (missed, due today, due soon).
     *
     * @return array{status: string, label: string, badge_class: string}|null
     */
    public function buildFollowupBadgeMeta(
        ?BookingFollowup $followup,
        bool $bookingOpen,
        int $dueSoonHours = 24
    ): ?array {
        $cellMeta = $this->buildFollowupListCellMeta($followup, $bookingOpen);
        if ($cellMeta === null) {
            return null;
        }

        if ($cellMeta['status'] === 'upcoming' && $followup?->date) {
            $date = $followup->date instanceof Carbon
                ? $followup->date
                : Carbon::parse($followup->date);
            $dueSoonUntil = Carbon::now()->addHours(max(1, $dueSoonHours));

            if (! $date->isToday() && $date->gt($dueSoonUntil)) {
                return null;
            }
        }

        return [
            'status' => $cellMeta['status'],
            'label' => $cellMeta['label'],
            'badge_class' => $cellMeta['badge_class'],
        ];
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array{customer?: array{status: string, label: string, badge_class: string}, provider?: array{status: string, label: string, badge_class: string}}>
     */
    public function buildBookingFollowupListMeta(Collection $bookings, int $dueSoonHours = 24): array
    {
        $meta = [];

        foreach ($bookings as $booking) {
            $open = $booking->requiresMandatoryNextFollowup();
            $next = $this->nextScheduledFollowups($booking);
            $bookingMeta = [];

            foreach (['customer', 'provider'] as $party) {
                $cellMeta = $this->buildFollowupListCellMeta($next[$party], $open);
                if ($cellMeta) {
                    $bookingMeta[$party] = $cellMeta;
                }
            }

            if ($bookingMeta !== []) {
                $meta[(int) $booking->id] = $bookingMeta;
            }
        }

        return $meta;
    }

    /**
     * @return array{
     *     customer: array{followup: ?BookingFollowup, has_pending: bool, is_overdue: bool, badge: ?array},
     *     provider: array{followup: ?BookingFollowup, has_pending: bool, is_overdue: bool, badge: ?array},
     *     has_any_pending: bool,
     *     has_any_overdue: bool
     * }
     */
    public function buildBookingFollowupDetailMeta(
        Booking $booking,
        ?BookingFollowup $nextCustomer = null,
        ?BookingFollowup $nextProvider = null
    ): array {
        $open = $booking->requiresMandatoryNextFollowup();

        if ($nextCustomer === null || $nextProvider === null) {
            $next = $this->nextScheduledFollowups($booking);
            $nextCustomer = $nextCustomer ?? $next['customer'];
            $nextProvider = $nextProvider ?? $next['provider'];
        }

        $partyMeta = function (?BookingFollowup $followup) use ($open): array {
            $hasPending = $followup
                ? $this->scheduledFollowupIsPending($followup, $open)
                : false;
            $isOverdue = false;

            if ($hasPending && $followup?->date) {
                $date = $followup->date instanceof Carbon
                    ? $followup->date
                    : Carbon::parse($followup->date);
                $isOverdue = $this->pendingFollowupIsOverdue($date);
            }

            return [
                'followup' => $followup,
                'has_pending' => $hasPending,
                'is_overdue' => $isOverdue,
                'badge' => $this->buildFollowupBadgeMeta($followup, $open),
            ];
        };

        $customer = $partyMeta($nextCustomer);
        $provider = $partyMeta($nextProvider);

        return [
            'customer' => $customer,
            'provider' => $provider,
            'has_any_pending' => $customer['has_pending'] || $provider['has_pending'],
            'has_any_overdue' => $customer['is_overdue'] || $provider['is_overdue'],
        ];
    }

    /**
     * @param  Collection<int, BookingFollowup>  $followups
     * @return array<int, array{due_at: ?Carbon, on_time: ?bool, delay_minutes: ?int, delay_label: ?string}>
     */
    public function buildFollowupDelayMeta(Collection $followups): array
    {
        if ($followups->isEmpty()) {
            return [];
        }

        $meta = [];

        foreach ($followups
            ->filter(fn (BookingFollowup $followup) => in_array($followup->status, ['completed', 'rescheduled'], true))
            ->sortBy(fn (BookingFollowup $followup) => $followup->followup_at ?? $followup->created_at) as $followup) {
            $takenAt = $followup->followup_at instanceof Carbon
                ? $followup->followup_at
                : ($followup->followup_at ? Carbon::parse($followup->followup_at) : null);

            if (! $takenAt && $followup->isRescheduled()) {
                $takenAt = $followup->created_at instanceof Carbon
                    ? $followup->created_at
                    : Carbon::parse($followup->created_at);
            }

            $dueAt = $followup->due_followup_at ?? $followup->date;
            if ($dueAt && ! $dueAt instanceof Carbon) {
                $dueAt = Carbon::parse($dueAt);
            }

            $onTime = null;
            $delayMinutes = null;
            $delayLabel = null;

            if ($dueAt && $takenAt && ! $followup->isRescheduled()) {
                if ($takenAt->lte($dueAt)) {
                    $onTime = true;
                    $delayMinutes = 0;
                } else {
                    $onTime = false;
                    $delayMinutes = (int) round($dueAt->diffInMinutes($takenAt));
                    $delayLabel = $this->formatDelayDuration($delayMinutes);
                }
            }

            $meta[(int) $followup->id] = [
                'due_at' => $dueAt,
                'on_time' => $onTime,
                'delay_minutes' => $delayMinutes,
                'delay_label' => $delayLabel,
            ];
        }

        return $meta;
    }

    public function formatDelayDuration(int $totalMinutes): string
    {
        $days = intdiv($totalMinutes, 1440);
        $hours = intdiv($totalMinutes % 1440, 60);

        if ($days > 0 && $hours > 0) {
            return $days.' '.translate('days').' '.$hours.' '.translate('hours');
        }
        if ($days > 0) {
            return $days.' '.translate('days');
        }
        if ($hours > 0) {
            return $hours.' '.translate('hours');
        }

        return translate('less_than_an_hour');
    }
}
