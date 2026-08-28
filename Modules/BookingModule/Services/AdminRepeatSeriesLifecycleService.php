<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingStatusHistory;

class AdminRepeatSeriesLifecycleService
{
    public function __construct(
        private AdminRepeatBookingScheduleService $schedule,
        private AdminRepeatBookingWriter $writer
    ) {
    }

    public function canStop(Booking $booking): bool
    {
        if ((int) ($booking->is_repeated ?? 0) !== 1) {
            return false;
        }
        if (! empty($booking->repeat_stopped_at)) {
            return false;
        }
        $status = (string) ($booking->booking_status ?? '');

        return ! in_array($status, ['canceled', 'cancelled', 'refunded'], true);
    }

    public function canAddVisit(Booking $booking): bool
    {
        if ((int) ($booking->is_repeated ?? 0) !== 1) {
            return false;
        }
        if (! empty($booking->repeat_stopped_at)) {
            return false;
        }
        $status = (string) ($booking->booking_status ?? '');
        if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            return false;
        }

        $booking->loadMissing('repeat');

        return $booking->repeat->count() < AdminRepeatBookingScheduleService::MAX_VISITS;
    }

    public function canExtend(Booking $booking): bool
    {
        return $this->canAddVisit($booking);
    }

    public function canEditSeriesDates(Booking $booking): bool
    {
        return $this->canStop($booking);
    }

    public function updateSeriesDates(Booking $booking, Carbon $start, ?Carbon $end): void
    {
        if (! $this->canEditSeriesDates($booking)) {
            throw ValidationException::withMessages([
                'series_start_date' => [translate('This_repeat_series_dates_cannot_be_changed')],
            ]);
        }

        $startDay = $start->copy()->startOfDay();
        $endDay = $end ? $end->copy()->startOfDay() : null;
        if ($endDay && $endDay->lt($startDay)) {
            throw ValidationException::withMessages([
                'series_end_date' => [translate('Repeat_end_date_must_be_on_or_after_start')],
            ]);
        }

        $booking->loadMissing('repeat');
        $activeVisits = $booking->repeat->filter(function ($repeat) {
            $status = (string) ($repeat->booking_status ?? '');
            if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
                return false;
            }

            return ! empty($repeat->service_schedule);
        });

        if ($activeVisits->isNotEmpty()) {
            $days = $activeVisits->map(function ($repeat) {
                return Carbon::parse($repeat->service_schedule)->startOfDay();
            })->sortBy(function ($day) {
                return $day->timestamp;
            })->values();
            $earliest = $days->first();
            $latest = $days->last();
            if ($earliest && $startDay->gt($earliest)) {
                throw ValidationException::withMessages([
                    'series_start_date' => [translate('Series_start_cannot_be_after_existing_visits')],
                ]);
            }
            if ($endDay && $latest && $endDay->lt($latest)) {
                throw ValidationException::withMessages([
                    'series_end_date' => [translate('Series_end_cannot_be_before_existing_visits')],
                ]);
            }
        }

        $this->writer->updateSeriesDates($booking, $startDay, $endDay);
    }

    public function stop(Booking $booking, int|string|null $changedBy): int
    {
        if (! $this->canStop($booking)) {
            throw ValidationException::withMessages([
                'repeat_until_stopped' => [translate('This_repeat_series_cannot_be_stopped')],
            ]);
        }

        $booking->loadMissing('repeat');
        $canceled = 0;
        foreach ($booking->repeat as $repeat) {
            $st = (string) $repeat->booking_status;
            if (! in_array($st, ['pending', 'accepted'], true)) {
                continue;
            }
            $repeat->booking_status = 'canceled';
            $repeat->save();
            $canceled++;

            $history = new BookingStatusHistory();
            $history->booking_id = $booking->id;
            $history->booking_repeat_id = $repeat->id;
            $history->changed_by = $changedBy;
            $history->booking_status = 'canceled';
            $history->status_change_remarks = translate('Repeat_series_stopped');
            $history->save();
        }

        $booking->refresh();
        $booking->load('repeat');
        $booking->repeat_until_stopped = 0;
        $booking->repeat_stopped_at = now();

        $hasCompleted = $booking->repeat->contains(
            fn ($repeat) => (string) $repeat->booking_status === 'completed'
        );
        $hasInProgress = $booking->repeat->contains(
            fn ($repeat) => in_array((string) $repeat->booking_status, ['ongoing', 'on_hold'], true)
        );

        // Series booking is completed only here (Stop series), never when a visit is completed.
        $booking->booking_status = ($hasCompleted || $hasInProgress) ? 'completed' : 'canceled';
        if ($booking->booking_status === 'completed') {
            $booking->reopen_completion_allowed = false;
        }

        $booking->save();

        $parentHistory = new BookingStatusHistory();
        $parentHistory->booking_id = $booking->id;
        $parentHistory->changed_by = $changedBy;
        $parentHistory->booking_status = $booking->booking_status;
        $parentHistory->status_change_remarks = translate('Repeat_series_stopped');
        $parentHistory->save();

        return $canceled;
    }

    public function addVisit(Booking $booking, Carbon $at, int|string|null $changedBy, ?string $visitRemarks = null, string $kind = 'attended'): int
    {
        if (! $this->canAddVisit($booking)) {
            throw ValidationException::withMessages([
                'service_schedule' => [translate('This_repeat_series_cannot_be_extended')],
            ]);
        }

        $kind = $kind === 'scheduled' ? 'scheduled' : 'attended';
        $this->schedule->assertVisitFitsCadence($booking, $at);

        return $this->writer->addVisitAt($booking, $at, $changedBy, $visitRemarks, $kind);
    }

    public function rescheduleVisit(BookingRepeat $repeat, Carbon $at, int|string|null $changedBy): bool
    {
        $status = (string) ($repeat->booking_status ?? '');
        if (! in_array($status, ['pending', 'accepted', 'ongoing', 'on_hold'], true)) {
            throw ValidationException::withMessages([
                'service_schedule' => [translate('This_visit_cannot_be_rescheduled')],
            ]);
        }

        $booking = $repeat->booking;
        if (! $booking) {
            throw ValidationException::withMessages([
                'service_schedule' => [translate('Booking not found')],
            ]);
        }

        $this->schedule->assertVisitFitsCadence($booking, $at, (string) $repeat->id);

        return $this->writer->rescheduleVisit($repeat, $at, $changedBy);
    }
}
