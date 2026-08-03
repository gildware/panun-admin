<?php

namespace Modules\BookingModule\Services;

use Illuminate\Support\Facades\DB;
use App\Support\AdminMenuCounts;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingIgnore;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Events\ProviderWithdrewFromBooking;
use Modules\UserManagement\Entities\User;

class ProviderBookingWithdrawalService
{
    /**
     * Provider requests cancellation on an accepted booking — awaits admin approval.
     */
    public function requestParentCancellation(
        Booking $booking,
        User $actor,
        ?int $providerCancellationReasonId,
        ?string $statusChangeRemarks,
        string $providerId,
    ): Booking {
        return DB::transaction(function () use ($booking, $actor, $providerCancellationReasonId, $statusChangeRemarks, $providerId) {
            $booking->booking_status = 'pending_cancellation';
            $booking->save();

            foreach ($booking->repeat()->whereIn('booking_status', ['pending', 'accepted'])->get() as $repeat) {
                if ((string) $repeat->provider_id === (string) $providerId) {
                    $repeat->booking_status = 'pending_cancellation';
                    $repeat->save();
                }
            }

            $history = new BookingStatusHistory;
            $history->booking_id = $booking->id;
            $history->changed_by = $actor->id;
            $history->booking_status = 'pending_cancellation';
            $history->booking_provider_cancellation_reason_id = $providerCancellationReasonId;
            $history->status_change_remarks = $statusChangeRemarks;
            $history->save();

            AdminMenuCounts::forget();

            $fresh = $booking->fresh(['provider', 'customer']);
            if ($fresh && function_exists('admin_inbox_notify_booking_pending_cancellation')) {
                admin_inbox_notify_booking_pending_cancellation($fresh);
            }

            return $fresh;
        });
    }

    /**
     * Provider requests cancellation on a single accepted repeat visit — awaits admin approval.
     */
    public function requestRepeatCancellation(
        BookingRepeat $repeat,
        User $actor,
        ?int $providerCancellationReasonId,
        ?string $statusChangeRemarks,
        string $providerId,
    ): BookingRepeat {
        return DB::transaction(function () use ($repeat, $actor, $providerCancellationReasonId, $statusChangeRemarks, $providerId) {
            if ((string) $repeat->provider_id === (string) $providerId) {
                $repeat->booking_status = 'pending_cancellation';
                $repeat->save();
            }

            $parent = $repeat->booking;
            if ($parent && (string) $parent->provider_id === (string) $providerId) {
                $parent->booking_status = 'pending_cancellation';
                $parent->save();
            }

            $history = new BookingStatusHistory;
            $history->booking_id = $repeat->booking_id;
            $history->booking_repeat_id = $repeat->id;
            $history->changed_by = $actor->id;
            $history->booking_status = 'pending_cancellation';
            $history->booking_provider_cancellation_reason_id = $providerCancellationReasonId;
            $history->status_change_remarks = $statusChangeRemarks;
            $history->save();

            AdminMenuCounts::forget();

            $freshRepeat = $repeat->fresh();
            $parent = $freshRepeat->booking?->fresh(['provider', 'customer']);
            if ($parent && function_exists('admin_inbox_notify_booking_pending_cancellation')) {
                admin_inbox_notify_booking_pending_cancellation($parent);
            }

            return $freshRepeat;
        });
    }

    /**
     * Admin approved provider cancellation — execute withdrawal.
     */
    public function approveParentCancellation(Booking $booking, User $admin): Booking
    {
        $booking->loadMissing('latestPendingCancellationRequestHistory');
        $requestHistory = $booking->latestPendingCancellationRequestHistory;
        $providerId = (string) ($booking->provider_id ?? '');

        return $this->withdrawParentBooking(
            $booking,
            $admin,
            $requestHistory?->booking_provider_cancellation_reason_id ? (int) $requestHistory->booking_provider_cancellation_reason_id : null,
            $requestHistory?->status_change_remarks,
            $providerId,
        );
    }

    /**
     * Admin approved provider cancellation for a repeat visit.
     */
    public function approveRepeatCancellation(BookingRepeat $repeat, User $admin): BookingRepeat
    {
        $requestHistory = BookingStatusHistory::query()
            ->where('booking_repeat_id', $repeat->id)
            ->where('booking_status', 'pending_cancellation')
            ->whereNotNull('booking_provider_cancellation_reason_id')
            ->latest('created_at')
            ->first();
        $providerId = (string) ($repeat->provider_id ?? '');

        return $this->withdrawRepeatBooking(
            $repeat,
            $admin,
            $requestHistory?->booking_provider_cancellation_reason_id ? (int) $requestHistory->booking_provider_cancellation_reason_id : null,
            $requestHistory?->status_change_remarks,
            $providerId,
        );
    }

    /**
     * Admin rejected provider cancellation request — restore accepted status.
     */
    public function rejectParentCancellation(Booking $booking, User $admin, ?string $rejectNote = null): Booking
    {
        return DB::transaction(function () use ($booking, $admin, $rejectNote) {
            $booking->booking_status = 'accepted';
            $booking->save();

            foreach ($booking->repeat()->where('booking_status', 'pending_cancellation')->get() as $repeat) {
                $repeat->booking_status = 'accepted';
                $repeat->save();
            }

            $history = new BookingStatusHistory;
            $history->booking_id = $booking->id;
            $history->changed_by = $admin->id;
            $history->booking_status = 'accepted';
            $history->status_change_remarks = $rejectNote ?: translate('Provider_cancellation_request_rejected_by_admin');
            $history->save();

            $this->notifyProviderCancellationRejected($booking);

            AdminMenuCounts::forget();

            return $booking->fresh();
        });
    }

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

            send_provider_removed_from_booking_notification($booking->fresh(), $providerId);

            event(new ProviderWithdrewFromBooking($booking->fresh(), $providerId));

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
                event(new ProviderWithdrewFromBooking($parent->fresh(), $providerId));
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
        $customer = $booking->customer;
        if (! $customer || ! user_has_fcm_devices($customer)) {
            return;
        }

        $notification = isNotificationActive(null, 'booking', 'notification', 'user');
        if (! $notification) {
            return;
        }

        $repeatOrRegular = $booking->is_repeated ? 'repeat' : 'regular';
        device_notification_for_user(
            $customer,
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

    private function notifyProviderCancellationRejected(Booking $booking): void
    {
        $booking->loadMissing('provider.owner');
        $owner = $booking->provider?->owner;
        if (! $owner || ! user_has_fcm_devices($owner)) {
            return;
        }

        $notification = isNotificationActive(null, 'booking', 'notification', 'provider');
        if (! $notification) {
            return;
        }

        device_notification_for_user(
            $owner,
            translate('Provider_cancellation_request_rejected_title'),
            translate('Provider_cancellation_request_rejected_body'),
            null,
            $booking->id,
            'booking',
        );
    }
}
