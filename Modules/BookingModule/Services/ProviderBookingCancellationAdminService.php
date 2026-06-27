<?php

namespace Modules\BookingModule\Services;

use App\Support\AdminMenuCounts;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingIgnore;
use Modules\BookingModule\Entities\BookingScheduleHistory;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\UserManagement\Entities\User;

class ProviderBookingCancellationAdminService
{
    /**
     * Admin confirms provider withdrawal — mark original booking as canceled.
     */
    public function adminCancelBooking(Booking $booking, User $admin, ?string $adminNote = null, bool $notifyCustomer = true): Booking
    {
        $this->assertCanAdminActOnWithdrawal($booking);

        return DB::transaction(function () use ($booking, $admin, $adminNote, $notifyCustomer) {
            $requestHistory = $this->resolveWithdrawalHistory($booking);
            $withdrawingProviderId = $this->resolveWithdrawingProviderId($booking);

            if ($withdrawingProviderId !== '') {
                BookingIgnore::query()->firstOrCreate([
                    'booking_id' => $booking->id,
                    'provider_id' => $withdrawingProviderId,
                ]);
                if ($booking->provider_cancelled_at === null) {
                    $booking->provider_cancelled_at = now();
                }
                if (empty($booking->provider_cancelled_by_provider_id)) {
                    $booking->provider_cancelled_by_provider_id = $withdrawingProviderId;
                }
            }

            $booking->provider_id = null;
            $booking->serviceman_id = null;
            $booking->booking_status = 'canceled';
            $booking->save();

            $history = new BookingStatusHistory;
            $history->booking_id = $booking->id;
            $history->changed_by = $admin->id;
            $history->booking_status = 'canceled';
            $history->booking_provider_cancellation_reason_id = $requestHistory?->booking_provider_cancellation_reason_id;
            $history->status_change_remarks = $adminNote
                ?: ($requestHistory?->status_change_remarks ?: translate('Provider_cancellation_confirmed_by_admin'));
            $history->save();

            if ($notifyCustomer) {
                $this->notifyCustomerProviderCancellationConfirmed($booking->fresh());
            }

            AdminMenuCounts::forget();

            return $booking->fresh();
        });
    }

    /**
     * Clone booking for a new provider; original is marked canceled.
     */
    public function adminCloneAndAssignProvider(Booking $booking, User $admin, string $newProviderId, ?string $adminNote = null): Booking
    {
        $this->assertCanAdminActOnWithdrawal($booking);

        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            throw new \RuntimeException(translate('Provider_cancellation_clone_repeat_not_supported'));
        }

        $withdrawingProviderId = $this->resolveWithdrawingProviderId($booking);
        if ($newProviderId === (string) ($booking->provider_id ?? '')) {
            throw new \RuntimeException(translate('Select_a_different_provider_for_replacement'));
        }
        if ($withdrawingProviderId !== '' && $newProviderId === $withdrawingProviderId) {
            throw new \RuntimeException(translate('Select_a_different_provider_for_replacement'));
        }

        return DB::transaction(function () use ($booking, $admin, $newProviderId, $adminNote) {
            $booking->loadMissing(['detail', 'extra_services']);

            $cloneAttrs = $booking->only([
                'customer_id',
                'zone_id',
                'area_id',
                'category_id',
                'sub_category_id',
                'service_schedule',
                'service_address_id',
                'service_address_location',
                'service_location',
                'total_booking_amount',
                'total_tax_amount',
                'total_discount_amount',
                'total_campaign_discount_amount',
                'total_coupon_discount_amount',
                'coupon_code',
                'payment_method',
                'is_paid',
                'is_verified',
                'is_checked',
                'additional_charge',
                'additional_tax_amount',
                'additional_discount_amount',
                'additional_campaign_discount_amount',
                'service_description',
                'lead_id',
                'booking_source',
                'assignee_id',
            ]);

            /** @var Booking $newBooking */
            $newBooking = Booking::query()->create(array_merge($cloneAttrs, [
                'provider_id' => $newProviderId,
                'serviceman_id' => null,
                'booking_status' => 'accepted',
                'originated_from_booking_id' => $booking->id,
                'service_description' => trim(($booking->service_description ?? '') . "\n" . translate('Cloned_after_provider_cancellation_request')),
            ]));

            foreach ($booking->detail as $detail) {
                BookingDetail::query()->create([
                    'booking_id' => $newBooking->id,
                    'service_id' => $detail->service_id,
                    'variant_key' => $detail->variant_key,
                    'quantity' => $detail->quantity,
                    'service_cost' => $detail->service_cost,
                    'discount_amount' => $detail->discount_amount,
                    'tax_amount' => $detail->tax_amount,
                    'total_cost' => $detail->total_cost,
                    'campaign_discount_amount' => $detail->campaign_discount_amount,
                    'overall_coupon_discount_amount' => $detail->overall_coupon_discount_amount,
                    'type' => $detail->type ?? BookingDetail::TYPE_SERVICE,
                ]);
            }

            foreach ($booking->extra_services as $extra) {
                BookingExtraService::query()->create([
                    'booking_id' => $newBooking->id,
                    'title' => $extra->title,
                    'details' => $extra->details,
                    'type' => $extra->type,
                    'quantity' => $extra->quantity,
                    'price' => $extra->price,
                    'discount' => $extra->discount,
                    'total' => $extra->total,
                ]);
            }

            $schedule = new BookingScheduleHistory;
            $schedule->booking_id = $newBooking->id;
            $schedule->changed_by = $admin->id;
            $schedule->schedule = $newBooking->service_schedule ?? now()->addDay()->toDateTimeString();
            $schedule->save();

            $acceptedHistory = new BookingStatusHistory;
            $acceptedHistory->booking_id = $newBooking->id;
            $acceptedHistory->changed_by = $admin->id;
            $acceptedHistory->booking_status = 'accepted';
            $acceptedHistory->status_change_remarks = $adminNote ?: translate('Cloned_after_provider_cancellation_request');
            $acceptedHistory->save();

            $this->adminCancelBooking(
                $booking,
                $admin,
                $adminNote ?: translate('Canceled_after_cloning_for_new_provider'),
                notifyCustomer: false,
            );

            $newBooking->refresh();
            $this->notifyNewProviderAssigned($newBooking);
            $this->notifyCustomerReplacementBooking($newBooking);

            return $newBooking;
        });
    }

    public function isWithdrawalAwaitingAdmin(Booking $booking): bool
    {
        if ((string) ($booking->booking_status ?? '') === 'pending_cancellation') {
            return true;
        }

        return (string) ($booking->booking_status ?? '') === 'pending'
            && $booking->provider_cancelled_at !== null
            && $booking->provider_cancelled_by_provider_id !== null
            && empty($booking->provider_id);
    }

    private function assertCanAdminActOnWithdrawal(Booking $booking): void
    {
        if (! $this->isWithdrawalAwaitingAdmin($booking)) {
            throw new \RuntimeException(translate('No_pending_cancellation_request_for_this_booking'));
        }
    }

    private function resolveWithdrawalHistory(Booking $booking): ?BookingStatusHistory
    {
        $booking->loadMissing([
            'latestPendingCancellationRequestHistory',
            'latestParentProviderCancellationStatusHistory',
        ]);

        if ((string) ($booking->booking_status ?? '') === 'pending_cancellation') {
            return $booking->latestPendingCancellationRequestHistory;
        }

        return $booking->latestParentProviderCancellationStatusHistory;
    }

    private function resolveWithdrawingProviderId(Booking $booking): string
    {
        return (string) ($booking->provider_cancelled_by_provider_id ?: $booking->provider_id ?: '');
    }

    private function notifyCustomerProviderCancellationConfirmed(Booking $booking): void
    {
        $booking->loadMissing('customer');
        $fcmToken = $booking->customer?->fcm_token;
        if (! $fcmToken || ! isNotificationActive(null, 'booking', 'notification', 'user')) {
            return;
        }

        device_notification(
            $fcmToken,
            translate('Booking_cancelled_title'),
            translate('Provider_cancellation_confirmed_customer_body'),
            null,
            $booking->id,
            'booking',
        );
    }

    private function notifyNewProviderAssigned(Booking $booking): void
    {
        $booking->loadMissing('provider.owner');
        $fcmToken = $booking->provider?->owner?->fcm_token;
        if (! $fcmToken || ! isNotificationActive(null, 'booking', 'notification', 'provider')) {
            return;
        }

        device_notification(
            $fcmToken,
            translate('new_service_request_arrived'),
            get_push_notification_description('new_service_request_arrived', 'provider_notification', $booking->provider?->owner?->current_language_key) ?: translate('new_service_request_arrived'),
            null,
            $booking->id,
            'booking',
        );
    }

    private function notifyCustomerReplacementBooking(Booking $booking): void
    {
        $booking->loadMissing('customer', 'provider');
        $fcmToken = $booking->customer?->fcm_token;
        if (! $fcmToken || ! isNotificationActive(null, 'booking', 'notification', 'user')) {
            return;
        }

        device_notification(
            $fcmToken,
            translate('Provider_cancellation_replacement_booking_title'),
            translate('Provider_cancellation_replacement_booking_body'),
            null,
            $booking->id,
            'booking',
        );
    }
}
