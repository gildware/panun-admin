<?php

namespace Modules\BookingModule\Services;

use Illuminate\Support\Facades\DB;
use App\Support\AdminMenuCounts;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingIgnore;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\UserManagement\Entities\User;

class ProviderBookingWithdrawalService
{
    /**
     * Provider-initiated cancel on an accepted booking: remove provider, keep booking active for reassignment.
     */
    public function withdrawParentBooking(
        Booking $booking,
        User $actor,
        ?int $providerCancellationReasonId,
        ?string $statusChangeRemarks,
        string $providerId,
    ): Booking {
        return DB::transaction(function () use ($booking, $actor, $providerCancellationReasonId, $statusChangeRemarks, $providerId) {
            $this->ensureBookingIgnored($booking->id, $providerId);

            $booking->provider_id = null;
            $booking->serviceman_id = null;
            $booking->booking_status = 'pending';
            $booking->provider_cancelled_at = now();
            $booking->provider_cancelled_by_provider_id = $providerId;
            $booking->save();

            foreach ($booking->repeat()->whereIn('booking_status', ['pending', 'accepted', 'ongoing'])->get() as $repeat) {
                $this->withdrawRepeatInstance($repeat, $actor, $providerCancellationReasonId, $statusChangeRemarks, $providerId, false);
            }

            $this->recordParentWithdrawalHistory(
                $booking,
                $actor,
                $providerCancellationReasonId,
                $statusChangeRemarks,
            );

            $this->notifyCustomerProviderWithdrew($booking);

            AdminMenuCounts::forget();

            return $booking->fresh();
        });
    }

    /**
     * Provider-initiated cancel on a single repeat visit from accepted: remove provider from that visit only.
     */
    public function withdrawRepeatBooking(
        BookingRepeat $repeat,
        User $actor,
        ?int $providerCancellationReasonId,
        ?string $statusChangeRemarks,
        string $providerId,
    ): BookingRepeat {
        return DB::transaction(function () use ($repeat, $actor, $providerCancellationReasonId, $statusChangeRemarks, $providerId) {
            $parent = $repeat->booking;
            if ($parent) {
                $this->ensureBookingIgnored($parent->id, $providerId);
                $parent->provider_cancelled_at = now();
                $parent->provider_cancelled_by_provider_id = $providerId;

                if ((string) $parent->provider_id === (string) $providerId) {
                    $parent->provider_id = null;
                    $parent->serviceman_id = null;
                    $parent->booking_status = 'pending';
                }

                $parent->save();
            }

            $repeat = $this->withdrawRepeatInstance(
                $repeat,
                $actor,
                $providerCancellationReasonId,
                $statusChangeRemarks,
                $providerId,
                true,
            );

            if ($parent) {
                $this->notifyCustomerProviderWithdrew($parent->fresh());
            }

            AdminMenuCounts::forget();

            return $repeat;
        });
    }

    private function withdrawRepeatInstance(
        BookingRepeat $repeat,
        User $actor,
        ?int $providerCancellationReasonId,
        ?string $statusChangeRemarks,
        string $providerId,
        bool $recordHistory,
    ): BookingRepeat {
        if ((string) $repeat->provider_id !== (string) $providerId) {
            return $repeat;
        }

        $repeat->provider_id = null;
        $repeat->serviceman_id = null;
        $repeat->booking_status = 'pending';
        $repeat->save();

        if ($recordHistory) {
            $history = new BookingStatusHistory;
            $history->booking_id = $repeat->booking_id;
            $history->booking_repeat_id = $repeat->id;
            $history->changed_by = $actor->id;
            $history->booking_status = 'pending';
            $history->booking_provider_cancellation_reason_id = $providerCancellationReasonId;
            $history->status_change_remarks = $statusChangeRemarks;
            $history->save();
        }

        sync_repeat_series_additional_charges((string) $repeat->booking_id);

        return $repeat;
    }

    private function recordParentWithdrawalHistory(
        Booking $booking,
        User $actor,
        ?int $providerCancellationReasonId,
        ?string $statusChangeRemarks,
    ): void {
        $history = new BookingStatusHistory;
        $history->booking_id = $booking->id;
        $history->changed_by = $actor->id;
        $history->booking_status = 'pending';
        $history->booking_provider_cancellation_reason_id = $providerCancellationReasonId;
        $history->status_change_remarks = $statusChangeRemarks;
        $history->save();
    }

    private function ensureBookingIgnored(string $bookingId, string $providerId): void
    {
        BookingIgnore::query()->firstOrCreate([
            'booking_id' => $bookingId,
            'provider_id' => $providerId,
        ]);
    }

    private function notifyCustomerProviderWithdrew(Booking $booking): void
    {
        $booking->loadMissing('customer');
        $fcmToken = $booking->customer?->fcm_token;
        if (! $fcmToken) {
            return;
        }

        $notification = isNotificationActive(null, 'booking', 'notification', 'user');
        if (! $notification) {
            return;
        }

        $repeatOrRegular = $booking->is_repeated ? 'repeat' : 'regular';
        device_notification(
            $fcmToken,
            translate('Provider_withdrew_from_booking_title'),
            translate('Provider_withdrew_from_booking_body'),
            null,
            $booking->id,
            'booking_ignored',
            null,
            null,
            null,
            null,
            $repeatOrRegular,
        );
    }
}
