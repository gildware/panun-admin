<?php

namespace Modules\BookingModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\UserManagement\Entities\User;

class CustomerBookingCancellationService
{
    /**
     * Cancel a booking on behalf of the customer with reason and optional refund preference.
     */
    public function cancelParentBooking(
        Booking $booking,
        User $customer,
        int $customerCancellationReasonId,
        ?string $statusChangeRemarks = null,
        ?string $refundMethod = null,
    ): Booking {
        $this->assertCustomerCanCancel($booking, $customer);

        $booking->loadMissing(['repeat', 'booking_partial_payments', 'customer']);
        $refundBreakdown = get_booking_customer_refund_channel_breakdown($booking);
        $walletPaid = (float) ($refundBreakdown['wallet_paid'] ?? 0);
        $digitalPaid = (float) ($refundBreakdown['digital_paid'] ?? 0);
        $requiresRefundChoice = (bool) ($refundBreakdown['requires_digital_refund_choice'] ?? false);
        $paidTotal = round((float) get_booking_total_paid($booking), 2);

        if ($requiresRefundChoice) {
            $refundMethod = strtolower(trim((string) $refundMethod));
            if (! in_array($refundMethod, ['wallet', 'transfer'], true)) {
                throw new \InvalidArgumentException(translate('Refund_method_is_required'));
            }
            if ($refundMethod === 'wallet' && ! customer_wallet_feature_enabled()) {
                throw new \InvalidArgumentException(translate('Customer_wallet_is_not_enabled'));
            }
        } else {
            $refundMethod = null;
        }

        return DB::transaction(function () use ($booking, $customer, $customerCancellationReasonId, $statusChangeRemarks, $refundMethod, $requiresRefundChoice, $walletPaid, $digitalPaid, $paidTotal, $refundBreakdown) {
            $booking->booking_status = 'canceled';
            $booking->save();

            $history = new BookingStatusHistory;
            $history->booking_id = $booking->id;
            $history->changed_by = $customer->id;
            $history->booking_status = 'canceled';
            $history->booking_customer_cancellation_reason_id = $customerCancellationReasonId;
            $history->status_change_remarks = $this->buildStatusRemarks($statusChangeRemarks, $refundMethod, $refundBreakdown);
            $history->save();

            if ($walletPaid > 0.009 && customer_wallet_feature_enabled()) {
                processBookingWalletRefund($booking, $walletPaid, 'customer_cancel_wallet_refund');
                $booking->refresh();
            }

            if ($requiresRefundChoice && $refundMethod === 'wallet' && $digitalPaid > 0.009 && customer_wallet_feature_enabled()) {
                processBookingWalletRefund($booking, $digitalPaid, 'customer_cancel_digital_wallet_refund');
                $booking->refresh();
            }

            if ($paidTotal > 0.009) {
                $afterTotals = get_booking_refund_display_totals($booking);
                if (round((float) ($afterTotals['refundable_remaining'] ?? 0), 2) <= 0) {
                    $booking->booking_status = 'refunded';
                    $booking->save();
                }
            }

            if ($booking->repeat->isNotEmpty()) {
                foreach ($booking->repeat as $repeat) {
                    if ((string) ($repeat->booking_status ?? '') === 'canceled') {
                        continue;
                    }
                    $repeat->booking_status = 'canceled';
                    $repeat->setAttribute('skipNotification', false);
                    unset($repeat->skipNotification);
                    $repeat->save();

                    $repeatHistory = new BookingStatusHistory;
                    $repeatHistory->booking_id = 0;
                    $repeatHistory->booking_repeat_id = $repeat->id;
                    $repeatHistory->changed_by = $customer->id;
                    $repeatHistory->booking_status = 'canceled';
                    $repeatHistory->booking_customer_cancellation_reason_id = $customerCancellationReasonId;
                    $repeatHistory->status_change_remarks = $history->status_change_remarks;
                    $repeatHistory->save();
                }
            }

            return $booking->fresh();
        });
    }

    /**
     * Cancel a single repeat booking instance.
     */
    public function cancelRepeatBooking(
        Booking $parentBooking,
        $repeat,
        User $customer,
        int $customerCancellationReasonId,
        ?string $statusChangeRemarks = null,
    ): void {
        if ((string) ($repeat->booking_status ?? '') === 'canceled') {
            throw new \RuntimeException(translate('Booking_already_canceled'));
        }

        DB::transaction(function () use ($parentBooking, $repeat, $customer, $customerCancellationReasonId, $statusChangeRemarks) {
            $repeat->booking_status = 'canceled';
            $repeat->save();

            $history = new BookingStatusHistory;
            $history->booking_id = $parentBooking->id;
            $history->booking_repeat_id = $repeat->id;
            $history->changed_by = $customer->id;
            $history->booking_status = 'canceled';
            $history->booking_customer_cancellation_reason_id = $customerCancellationReasonId;
            $history->status_change_remarks = $statusChangeRemarks;
            $history->save();
        });
    }

    private function assertCustomerCanCancel(Booking $booking, User $customer): void
    {
        if ((string) ($booking->customer_id ?? '') !== (string) $customer->id) {
            throw new \RuntimeException(translate('Access_denied'));
        }

        $status = (string) ($booking->booking_status ?? '');
        if ($status === 'accepted') {
            throw new \RuntimeException(translate('Booking_already_accepted'));
        }
        if ($status === 'ongoing') {
            throw new \RuntimeException(translate('Booking_already_ongoing'));
        }
        if ($status === 'completed') {
            throw new \RuntimeException(translate('Booking_already_completed'));
        }
        if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            throw new \RuntimeException(translate('Booking_already_canceled'));
        }
    }

    private function buildStatusRemarks(?string $remarks, ?string $refundMethod, array $refundBreakdown = []): ?string
    {
        $remarks = trim((string) ($remarks ?? ''));
        $walletPaid = round((float) ($refundBreakdown['wallet_paid'] ?? 0), 2);
        $digitalPaid = round((float) ($refundBreakdown['digital_paid'] ?? 0), 2);

        $parts = [];
        if ($walletPaid > 0.009) {
            $parts[] = translate('Wallet') . ': ' . with_currency_symbol($walletPaid) . ' → ' . translate('Refund_to_wallet');
        }
        if ($digitalPaid > 0.009) {
            if ($refundMethod === 'wallet') {
                $parts[] = translate('Digital_payment') . ': ' . with_currency_symbol($digitalPaid) . ' → ' . translate('Refund_to_wallet');
            } elseif ($refundMethod === 'transfer') {
                $parts[] = translate('Digital_payment') . ': ' . with_currency_symbol($digitalPaid) . ' → ' . translate('Transfer_to_customer');
            } else {
                $parts[] = translate('Digital_payment') . ': ' . with_currency_symbol($digitalPaid);
            }
        }

        $refundSummary = $parts !== [] ? implode('; ', $parts) : null;
        if ($refundMethod !== null && $refundSummary === null) {
            $refundSummary = $refundMethod === 'wallet'
                ? translate('Refund_to_wallet')
                : translate('Transfer_to_customer');
        }

        if ($refundSummary === null) {
            return $remarks !== '' ? $remarks : null;
        }

        if ($remarks === '') {
            return $refundSummary;
        }

        return $refundSummary . ' — ' . $remarks;
    }
}
