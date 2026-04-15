@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/swiper/swiper-bundle.min.css') }}">
    <style>
        .booking-details-overview-row {
            align-items: stretch;
            /* Taller base so Payment / Revenue / left cards show full content without tight scroll areas */
            --booking-overview-small-card-h: 9.5rem;
            --booking-overview-column-gap: 1rem;
            /* Left: Customer + tall Provider (2× small + gap); same total as former 3 small cards + 2 gaps */
            --booking-overview-left-stack-h: calc(3 * var(--booking-overview-small-card-h) + 2 * var(--booking-overview-column-gap));
            --booking-overview-provider-card-h: calc(2 * var(--booking-overview-small-card-h) + var(--booking-overview-column-gap));
            --booking-overview-mid-card-h: calc((var(--booking-overview-left-stack-h) - var(--booking-overview-column-gap)) / 2);
            /* Shift a bit from Payment → Revenue (heights still sum to stack − gap) */
            --booking-overview-mid-split-shift: 1rem;
            --booking-overview-mid-payment-h: calc(var(--booking-overview-mid-card-h) - var(--booking-overview-mid-split-shift));
            --booking-overview-mid-revenue-h: calc(var(--booking-overview-mid-card-h) + var(--booking-overview-mid-split-shift));
            /* Right: Booking dates + Booking Information + Service location (2 gaps between three cards) */
            --booking-overview-right-dates-shift: 2rem;
            --booking-overview-right-dates-h: calc(var(--booking-overview-small-card-h) + 2rem - var(--booking-overview-right-dates-shift));
            --booking-overview-right-service-loc-h: var(--booking-overview-small-card-h);
            --booking-overview-right-info-h: calc(var(--booking-overview-left-stack-h) - 2 * var(--booking-overview-column-gap) - var(--booking-overview-right-dates-h) - var(--booking-overview-right-service-loc-h));
            min-height: var(--booking-overview-left-stack-h);
        }
        .booking-details-overview-row > [class*="col-"] {
            min-height: 0;
            display: flex;
            flex-direction: column;
            align-self: stretch;
        }
        .booking-details-overview-row .booking-overview-min-h-0 { min-height: 0; }
        .booking-details-overview-row .booking-overview-column-inner {
            flex: 0 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: var(--booking-overview-column-gap);
            min-height: var(--booking-overview-left-stack-h);
        }
        .booking-details-overview-row .booking-overview-left-card {
            flex: 0 0 var(--booking-overview-small-card-h);
            min-height: var(--booking-overview-small-card-h);
            max-height: var(--booking-overview-small-card-h);
            height: var(--booking-overview-small-card-h);
        }
        .booking-details-overview-row .booking-overview-left-card--provider {
            flex: 0 0 var(--booking-overview-provider-card-h);
            min-height: var(--booking-overview-provider-card-h);
            max-height: var(--booking-overview-provider-card-h);
            height: var(--booking-overview-provider-card-h);
        }
        .booking-details-overview-row .booking-overview-mid-card--payment {
            flex: 0 0 var(--booking-overview-mid-payment-h);
            min-height: var(--booking-overview-mid-payment-h);
            max-height: var(--booking-overview-mid-payment-h);
            height: var(--booking-overview-mid-payment-h);
        }
        .booking-details-overview-row .booking-overview-mid-card--revenue {
            flex: 0 0 var(--booking-overview-mid-revenue-h);
            min-height: var(--booking-overview-mid-revenue-h);
            max-height: var(--booking-overview-mid-revenue-h);
            height: var(--booking-overview-mid-revenue-h);
        }
        .booking-details-overview-row .booking-overview-booking-dates-card {
            flex: 0 0 var(--booking-overview-right-dates-h);
            min-height: var(--booking-overview-right-dates-h);
            max-height: var(--booking-overview-right-dates-h);
            height: var(--booking-overview-right-dates-h);
        }
        .booking-details-overview-row .booking-overview-booking-info-card {
            flex: 0 0 var(--booking-overview-right-info-h);
            min-height: var(--booking-overview-right-info-h);
            max-height: var(--booking-overview-right-info-h);
            height: var(--booking-overview-right-info-h);
        }
        .booking-details-overview-row .booking-overview-right-service-loc-card {
            flex: 0 0 var(--booking-overview-right-service-loc-h);
            min-height: var(--booking-overview-right-service-loc-h);
            max-height: var(--booking-overview-right-service-loc-h);
            height: var(--booking-overview-right-service-loc-h);
        }

        /* Booking status overview: pill buttons — light fill + border, solid + white text on hover */
        #booking-status-overview-actions .booking-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 0.4rem 1rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            border-radius: 999px;
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        #booking-status-overview-actions .booking-status-pill:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        #booking-status-overview-actions .booking-status-pill:focus-visible {
            outline: 2px solid var(--bs-primary, #0d6efd);
            outline-offset: 2px;
        }
        #booking-status-overview-actions .booking-status-pill--primary {
            color: var(--bs-primary, #0d6efd);
            border-color: var(--bs-primary, #0d6efd);
            background-color: color-mix(in srgb, var(--bs-primary, #0d6efd) 14%, transparent);
        }
        #booking-status-overview-actions .booking-status-pill--primary:hover:not(:disabled) {
            color: #fff !important;
            background-color: var(--bs-primary, #0d6efd);
            border-color: var(--bs-primary, #0d6efd);
        }
        #booking-status-overview-actions .booking-status-pill--info {
            color: var(--bs-info, #0dcaf0);
            border-color: var(--bs-info, #0dcaf0);
            background-color: color-mix(in srgb, var(--bs-info, #0dcaf0) 18%, transparent);
        }
        #booking-status-overview-actions .booking-status-pill--info:hover:not(:disabled) {
            color: #052c33 !important;
            background-color: var(--bs-info, #0dcaf0);
            border-color: var(--bs-info, #0dcaf0);
        }
        #booking-status-overview-actions .booking-status-pill--success {
            color: var(--bs-success, #198754);
            border-color: var(--bs-success, #198754);
            background-color: color-mix(in srgb, var(--bs-success, #198754) 14%, transparent);
        }
        #booking-status-overview-actions .booking-status-pill--success:hover:not(:disabled) {
            color: #fff !important;
            background-color: var(--bs-success, #198754);
            border-color: var(--bs-success, #198754);
        }
        #booking-status-overview-actions .booking-status-pill--danger {
            color: var(--bs-danger, #dc3545);
            border-color: var(--bs-danger, #dc3545);
            background-color: color-mix(in srgb, var(--bs-danger, #dc3545) 14%, transparent);
        }
        #booking-status-overview-actions .booking-status-pill--danger:hover:not(:disabled) {
            color: #fff !important;
            background-color: var(--bs-danger, #c82333);
            border-color: var(--bs-danger, #c82333);
        }
        #booking-status-overview-actions .booking-status-pill--warning {
            color: #856404;
            border-color: #ffc107;
            background-color: color-mix(in srgb, #ffc107 22%, transparent);
        }
        #booking-status-overview-actions .booking-status-pill--warning:hover:not(:disabled) {
            color: #fff !important;
            background-color: #d39e00;
            border-color: #d39e00;
        }
        #booking-status-overview-actions .booking-status-pill--secondary {
            color: var(--bs-secondary, #6c757d);
            border-color: var(--bs-secondary, #6c757d);
            background-color: color-mix(in srgb, var(--bs-secondary, #6c757d) 14%, transparent);
        }
        #booking-status-overview-actions .booking-status-pill--secondary:hover:not(:disabled) {
            color: #fff !important;
            background-color: var(--bs-secondary, #5c636a);
            border-color: var(--bs-secondary, #5c636a);
        }
        @supports not (background-color: color-mix(in srgb, red 50%, transparent)) {
            #booking-status-overview-actions .booking-status-pill--success {
                background-color: rgba(25, 135, 84, 0.12);
            }
            #booking-status-overview-actions .booking-status-pill--danger {
                background-color: rgba(220, 53, 69, 0.12);
            }
            #booking-status-overview-actions .booking-status-pill--warning {
                background-color: rgba(255, 193, 7, 0.22);
            }
            #booking-status-overview-actions .booking-status-pill--secondary {
                background-color: rgba(108, 117, 125, 0.14);
            }
            #booking-status-overview-actions .booking-status-pill--primary {
                background-color: rgba(13, 110, 253, 0.12);
            }
            #booking-status-overview-actions .booking-status-pill--info {
                background-color: rgba(13, 202, 240, 0.18);
            }
        }

        /* Service location card: travel note as compact single-line pill */
        .booking-overview-service-loc-note-pill {
            display: block;
            max-width: 100%;
            padding: 0.15rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            border-radius: 999px;
            border: 1px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-sizing: border-box;
        }
        .booking-overview-service-loc-note-pill--at-provider-site {
            background-color: rgba(13, 110, 253, 0.12);
            color: #084298;
            border-color: rgba(13, 110, 253, 0.28);
        }
        .booking-overview-service-loc-note-pill--at-customer-site {
            background-color: rgba(25, 135, 84, 0.12);
            color: #0f5132;
            border-color: rgba(25, 135, 84, 0.28);
        }

        /* Overview cards: alternating row backgrounds for key/value readability */
        .booking-overview-kv-rows {
            display: flex;
            flex-direction: column;
            gap: 0;
            border-radius: 6px;
            overflow: hidden;
        }
        .booking-overview-kv-rows > .booking-overview-kv-row {
            padding: 0.4rem 0.55rem;
        }
        .booking-overview-kv-rows > .booking-overview-kv-row:nth-child(odd) {
            background-color: transparent;
        }
        .booking-overview-kv-rows > .booking-overview-kv-row:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.055);
        }

        /* Top header action row (with Invoice): cancellation / hold / reopen reason summary — not repeated in the Booking Status card below */
        .booking-status-detail-box {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.75);
        }
        .booking-status-detail-box .booking-status-detail-box__head {
            padding: 0.35rem 0.55rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            background-color: rgba(0, 0, 0, 0.04);
        }

        /* Booking summary: colored row bands (no legend text — colors only) */
        .booking-summary-breakdown tbody tr td {
            vertical-align: middle;
            border-bottom-color: rgba(0, 0, 0, 0.06);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--charge td:first-child {
            border-left: 3px solid #0d6efd;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--charge td {
            background-color: rgba(13, 110, 253, 0.07);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--subtotal td:first-child {
            border-left: 3px solid #084298;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--subtotal td {
            background-color: rgba(8, 66, 152, 0.1);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--discount td:first-child {
            border-left: 3px solid #c2255c;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--discount td {
            background-color: rgba(194, 37, 92, 0.08);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--tax td:first-child {
            border-left: 3px solid #6f42c1;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--tax td {
            background-color: rgba(111, 66, 193, 0.08);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--grand td:first-child {
            border-left: 3px solid #052c65;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--grand td {
            background-color: rgba(5, 44, 101, 0.12);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--payment-in td:first-child {
            border-left: 3px solid #198754;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--payment-in td {
            background-color: rgba(25, 135, 84, 0.09);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--due td:first-child {
            border-left: 3px solid #e36402;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--due td {
            background-color: rgba(227, 100, 2, 0.1);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--refund-out td:first-child {
            border-left: 3px solid #b02a37;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--refund-out td {
            background-color: rgba(176, 42, 55, 0.08);
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--refund-info td:first-child {
            border-left: 3px solid #495057;
        }
        .booking-summary-breakdown tbody tr.booking-summary-row--refund-info td {
            background-color: rgba(73, 80, 87, 0.07);
        }
    </style>
@endpush

@section('content')
    @php
        $allowDeleteAdminBookingPartialPayments = $allowDeleteAdminBookingPartialPayments ?? false;
        $bookingDetail = $booking->detail ?? collect();
        $totalPaidFromPartials = (float) ($booking->booking_partial_payments ?? collect())->sum('paid_amount');
        $bookingTotalForPayment = get_booking_payable_total_for_partial_dues($booking);
        if (!isset($remainingDueForAddPayment)) {
            $remainingDueForAddPayment = round(max(0, $bookingTotalForPayment - $totalPaidFromPartials), 2);
        }
        $paymentFullyCovered = $booking->booking_partial_payments->isEmpty()
            ? (bool) $booking->is_paid
            : (round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2));
        $displayPaidAmount = $booking->booking_partial_payments->isNotEmpty()
            ? $totalPaidFromPartials
            : (($paymentFullyCovered && (bool) $booking->is_paid) ? $bookingTotalForPayment : 0);
        $__bfsVisitRetainedCanceled = (string) ($booking->booking_status ?? '') === 'canceled'
            && (
                !empty($booking->after_visit_cancel)
                || (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            );
        $__bfsDecidedChargesPaidDisplayCap = $__bfsVisitRetainedCanceled
            || (
                (string) ($booking->booking_status ?? '') === 'completed'
                && (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT
            );
        if ($__bfsDecidedChargesPaidDisplayCap && round($bookingTotalForPayment, 2) > 0
            && round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2)) {
            $displayPaidAmount = round((float) $bookingTotalForPayment, 2);
        }
        $showAsAmountPaidLabel = $booking->booking_status == 'completed' || $paymentFullyCovered;
        $advanceOffline = ($booking->booking_partial_payments ?? collect())->where('paid_with', 'offline')->first();
        $bookingHasTax = (float)($booking->total_tax_amount ?? 0) > 0;
        $bookingNotEditable = in_array($booking->booking_status ?? '', ['completed', 'canceled', 'refunded']);
        $serviceAtProviderPlace = (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0);
        $__bfsDecidedChargesSnapshot = ! empty($booking->settlement_snapshot)
            && is_array($booking->settlement_snapshot)
            && ! empty($booking->settlement_snapshot['decided_visit_charges_mode']);
        $__bfsSplitBookingSummaryCancel = (string) ($booking->booking_status ?? '') === 'canceled'
            && $__bfsDecidedChargesSnapshot
            && (
                ! empty($booking->after_visit_cancel)
                || (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            );
        $__bfsSplitBookingSummaryComplete = (string) ($booking->booking_status ?? '') === 'completed'
            && $__bfsDecidedChargesSnapshot
            && (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
        $__bfsSplitBookingSummary = $__bfsSplitBookingSummaryCancel || $__bfsSplitBookingSummaryComplete;
        $__bfsScaledLive = null;
        $bfsAddPayLossCaps = null;
        if ((int) ($booking->is_repeated ?? 0) === 0
            && trim((string) ($booking->settlement_outcome ?? '')) === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $__bfsScaledLive = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class)->buildPreview($booking);
            if ($__bfsScaledLive !== null) {
                $bfsAddPayLossCaps = [
                    'provider' => max(0.0, round((float) ($__bfsScaledLive['scaled_loss_provider_share'] ?? 0), 2)),
                    'company' => max(0.0, round((float) ($__bfsScaledLive['scaled_loss_company_share'] ?? 0), 2)),
                ];
            }
        }
        $__bfsScaledPaymentCard = $__bfsScaledLive !== null
            && ! in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true);
        $paymentDetailsTotalAmount = $__bfsScaledPaymentCard
            ? round((float) get_booking_total_amount($booking), 2)
            : (float) $bookingTotalForPayment;
        $paymentDetailsAmountPaid = $__bfsScaledPaymentCard
            ? round((float) get_booking_total_paid($booking), 2)
            : (float) $displayPaidAmount;
        $adminAddPaymentRemainingCapacity = get_booking_admin_add_payment_remaining_amount($booking);
        $canAdminRecordFurtherCustomerPayment = ! in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)
            && round($adminAddPaymentRemainingCapacity, 2) > 0.009;
        $bookingStatusForAddPayment = (string) ($booking->booking_status ?? '');
        $addPaymentAllowedByStatus = $bookingStatusForAddPayment === 'ongoing'
            || ($bookingStatusForAddPayment === 'on_hold' && booking_on_hold_is_after_visit_from_ongoing($booking));
        // Loss-making (scaled) bookings: allow recording additional payments even after completion
        // (up to invoice recovery cap), so don't require ongoing/on-hold here.
        $bfsShowRecordPaymentButton = $__bfsScaledLive !== null
            && (int) ($booking->is_repeated ?? 0) === 0
            && $canAdminRecordFurtherCustomerPayment;
        $showAddPaymentOnBookingDetails = $addPaymentAllowedByStatus
            && $canAdminRecordFurtherCustomerPayment
            && (
                (! $bookingNotEditable && ! $paymentFullyCovered)
                || ($bookingNotEditable && $__bfsScaledLive !== null)
                || ($__bfsScaledLive !== null && $paymentFullyCovered)
            );
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="page-title mb-0">{{ translate('Booking_Details') }} </h2>
                    @if(!empty($booking->lead_id))
                        <span class="badge bg-info">
                            {{ translate('Lead_ID') }}:
                            <a href="{{ route('admin.lead.show', $booking->lead_id) }}" class="text-white text-decoration-underline">#{{ $booking->lead_id }}</a>
                        </span>
                    @endif
                </div>
                @can('booking_delete')
                    <button type="button"
                            class="action-btn btn--danger rounded-circle"
                            style="--size: 34px"
                            data-bs-toggle="modal"
                            data-bs-target="#bookingDeleteModal--{{ $booking['id'] }}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                @endcan
            </div>

            @php
                $__overviewMaxBa = (float) (business_config('max_booking_amount', 'booking_setup')->live_values ?? 0);
                $__overviewStatusCashBlock = $booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && (float) $booking->total_booking_amount >= $__overviewMaxBa;
                $__overviewSt = $booking->booking_status ?? '';
                $__cancelHist = $booking->latestParentCancellationStatusHistory;
                $__holdHist = $booking->latestParentHoldStatusHistory;
                $__reopenEv = $booking->reopenFromCompletedDisplayEvent();
                $__respLabels = ['customer' => translate('Customer'), 'provider' => translate('Provider'), 'staff' => translate('Staff'), 'no_one' => translate('No_one')];
                $__overviewShowReopenInCard = $booking->adminEligibleForReopenFromCompleted();
                $__overviewBadge = 'info';
                if ($__overviewSt === 'ongoing') {
                    $__overviewBadge = 'warning';
                } elseif ($__overviewSt === 'on_hold') {
                    $__overviewBadge = 'secondary';
                } elseif ($__overviewSt === 'completed') {
                    $__overviewBadge = 'success';
                } elseif ($__overviewSt === 'canceled' || $__overviewSt === 'cancelled') {
                    $__overviewBadge = 'danger';
                } elseif ($__overviewSt === 'refunded') {
                    $__overviewBadge = 'success';
                }
                $__adminNextStatuses = booking_admin_allowed_next_statuses_for_booking($booking, $__overviewSt);
                $__headerRefundRemaining = isset($maxRefundAmount) ? round((float) $maxRefundAmount, 2) : 0.0;
                $__showHeaderPendingRefundBadge = in_array($__overviewSt, ['canceled', 'cancelled'], true)
                    && $__headerRefundRemaining > 0.009;
                $__headerStatusNorm = strtolower((string) ($booking->booking_status ?? ''));
                $__headerMainBadgeClass = match ($__headerStatusNorm) {
                    'ongoing' => 'warning',
                    'on_hold' => 'secondary',
                    'completed' => 'success',
                    'canceled', 'cancelled' => 'danger',
                    'refunded' => 'success',
                    default => 'info',
                };
                $__showHeaderBookingCancelledWithRefunded = $__headerStatusNorm === 'refunded';
                $__bookingStatusDisplayLabel = booking_admin_booking_status_display_label($booking);
            @endphp

            <div class="pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h3 class="c1 fw-bold">{{ translate('Booking') }} # {{ $booking['readable_id'] }}</h3>
                        @if($__showHeaderBookingCancelledWithRefunded)
                            <span class="badge bg-danger">{{ translate('Booking_cancelled') }}</span>
                        @endif
                        <span class="badge badge-{{ $__headerMainBadgeClass }}">
                            {{ $__bookingStatusDisplayLabel }}
                        </span>
                        @if($__showHeaderPendingRefundBadge)
                            <span class="badge bg-warning text-dark"
                                title="{{ translate('Remaining_refundable') }}: {{ with_currency_symbol($__headerRefundRemaining) }}">{{ translate('Pending_refund') }}</span>
                        @endif
                        @if($booking->booking_status === 'completed'
                            && (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS)
                            @if($booking->isScaledSettlementLossRecovered())
                                <span class="badge bg-success">{{ translate('Bfs_badge_loss_recovered_booking') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ translate('Bfs_badge_loss_making_booking') }}</span>
                            @endif
                        @endif
                        @if(in_array($__headerStatusNorm, ['completed', 'canceled', 'cancelled', 'refunded'], true)
                            && (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT)
                            <span class="badge bg-success"
                                title="{{ translate('Bfs_label_complete_visit_only') }}">{{ translate('Bfs_list_badge_little_or_no_service') }}</span>
                        @endif
                        @if(!empty($booking->after_visit_cancel))
                            <span class="badge bg-dark">{{ translate('Bfs_badge_after_visit_cancel') }}</span>
                        @endif
                        @if($booking->isOpenReopenTicket())
                            <span class="badge bg-warning text-dark">{{ translate('Reopened') }}</span>
                        @elseif($booking->isReopenedTagged() && (empty($booking->reopen_disputed_snapshot) || !is_array($booking->reopen_disputed_snapshot)))
                            <span class="badge bg-success">{{ translate('Resolved') }}</span>
                        @endif
                    </div>
                    <p class="opacity-75 fz-12">{{ translate('Booking_Placed') }}
                        : {{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</p>
                </div>
                <div class="d-flex flex-wrap flex-xxl-nowrap gap-3 flex-column flex-xxl-row ms-auto align-items-end align-items-xxl-center">
                    @php
                        $bookingFeedbackSvc = app(\Modules\ProviderManagement\Services\BookingAdminFeedbackService::class);
                        $terminalBooking = $bookingFeedbackSvc->isTerminalBooking($booking);
                        $needProviderAdminFb = $terminalBooking && !empty($booking->provider_id) && !$bookingFeedbackSvc->providerFeedbackResolved($booking);
                        $needCustomerAdminFb = $terminalBooking && !empty($booking->customer_id) && !$bookingFeedbackSvc->customerFeedbackResolved($booking);
                    @endphp
                    @if($needProviderAdminFb || $needCustomerAdminFb)
                        <div class="alert alert-warning mb-0 w-100" role="alert" style="max-width: 640px;">
                            <div class="fw-semibold mb-2">{{ translate('Internal_booking_feedback') }}</div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($needProviderAdminFb)
                                    <button type="button" class="btn btn-sm btn--primary open-feedback-manual">
                                        <span class="material-icons">engineering</span>{{ translate('Provider_feedback') }}
                                    </button>
                                @endif
                                @if($needCustomerAdminFb)
                                    <button type="button" class="btn btn-sm btn--primary open-customer-feedback-manual">
                                        <span class="material-icons">person</span>{{ translate('Customer_feedback') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="d-flex flex-wrap gap-3 justify-content-end">
                        @php
                            $maxBookingAmount = business_config('max_booking_amount', 'booking_setup')->live_values;
                        @endphp
                        @if (
                            $booking['payment_method'] == 'cash_after_service' &&
                                $booking->is_verified == '0' &&
                                $booking->total_booking_amount >= $maxBookingAmount)
                            @can('booking_can_approve_or_deny')
                                <span class="btn btn--primary verify-booking-request" data-id="{{ $booking->id }}"
                                    data-bs-toggle="modal" data-bs-target="#exampleModal--{{ $booking->id }}">
                                    <span class="material-icons">done</span>
                                    {{ translate('verify booking request') }}
                                </span>
                            @endcan

                            <div class="modal fade" id="exampleModal--{{ $booking->id }}" tabindex="-1"
                                aria-labelledby="exampleModalLabel--{{ $booking->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body p-4 py-5">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                            <div class="text-center mb-4 pb-3">
                                                <div class="text-center">
                                                    <img class="mb-4"
                                                        src="{{ asset('/assets/admin-module/img/booking-req-status.png') }}"
                                                        alt="">
                                                </div>
                                                <h3 class="mb-1 fw-medium">
                                                    {{ translate('Verify the booking request status?') }}</h3>
                                                <p class="fs-12 fw-medium text-muted">
                                                    {{ translate('Need verification for max booking amount') }}</p>
                                            </div>
                                            <form method="post"
                                                action="{{ route('admin.booking.verification-status', [$booking->id]) }}">
                                                @csrf
                                                <div class="c1-light-bg p-4 rounded">
                                                    <h5 class="mb-3">{{ translate('Request Status') }}</h5>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input approve-request check-approve-status booking-verification-status"
                                                                checked type="radio" name="status" id="inlineRadio1"
                                                                value="approve">
                                                            <label class="form-check-label"
                                                                for="inlineRadio1">{{ translate('Approve the Request') }}</label>
                                                        </div>
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input deny-request booking-verification-status" type="radio"
                                                                name="status" id="inlineRadio2" value="deny">
                                                            <label class="form-check-label"
                                                                for="inlineRadio2">{{ translate('Deny the Request') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 cancellation-note" style="display: none;">
                                                        <textarea class="form-control h-69px" placeholder="{{ translate('Cancellation Note ...') }}" name="booking_deny_note"
                                                            id="add-your-note"></textarea>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center mt-4">
                                                    <button type="submit"
                                                        class="btn btn--primary">{{ translate('submit') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (
                            $booking['payment_method'] == 'cash_after_service' &&
                                $booking->is_verified == '2' &&
                                $booking->total_booking_amount >= $maxBookingAmount)
                            @can('booking_can_manage_status')
                                <span class="btn btn--primary change-booking-request" data-id="{{ $booking->id }}"
                                    data-bs-toggle="modal" data-bs-target="#exampleModals--{{ $booking->id }}">
                                    <span class="material-icons">done</span>{{ translate('Change Request Status') }}
                                </span>
                            @endcan

                            <div class="modal fade" id="exampleModals--{{ $booking->id }}" tabindex="-1"
                                aria-labelledby="exampleModalLabels--{{ $booking->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body pt-5 p-md-5">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                            <div class="text-center mb-4 pb-3">
                                                <img class="mb-4"
                                                    src="{{ asset('/assets/admin-module/img/booking-req-status.png') }}"
                                                    alt="">
                                                <h3 class="mb-1 fw-medium">
                                                    {{ translate('Verify the booking request status?') }}</h3>
                                                <p class="text-start fs-12 fw-medium text-muted">
                                                    {{ translate('Need verification for max booking amount') }}</p>
                                            </div>
                                            <form method="post"
                                                action="{{ route('admin.booking.verification-status', [$booking->id]) }}">
                                                @csrf

                                                <div class="c1-light-bg p-4 rounded">
                                                    <h5 class="mb-3">{{ translate('Request Status') }}</h5>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input approve-request" checked
                                                                type="radio" name="status" id="inlineRadio1"
                                                                value="approve">
                                                            <label class="form-check-label"
                                                                for="inlineRadio1">{{ translate('Approve the Request') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center mt-4">
                                                    <button type="submit"
                                                        class="btn btn--primary">{{ translate('submit') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(in_array($__overviewSt, ['canceled', 'cancelled', 'refunded'], true) && $__cancelHist && ($__cancelHist->cancellationReason || filled($__cancelHist->status_change_remarks)))
                            <div class="booking-status-detail-box fz-12 w-100 align-self-stretch" style="max-width: min(100%, 36rem);">
                                <div class="booking-status-detail-box__head">
                                    <span class="title-color fw-semibold text-uppercase fz-11">{{ translate('Booking_cancellation_reasons') }}</span>
                                </div>
                                <div class="booking-overview-kv-rows">
                                    @if($__cancelHist->cancellationReason)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Reason') }}:</span>
                                            <span class="fz-12 text-break text-end fw-semibold min-w-0">{{ $__cancelHist->cancellationReason->name }}</span>
                                        </div>
                                        @if($__cancelHist->cancellationReason->description)
                                            <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                                <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Description') }}:</span>
                                                <span class="fz-12 text-muted text-break text-end min-w-0">{{ $__cancelHist->cancellationReason->description }}</span>
                                            </div>
                                        @endif
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Responsible') }}:</span>
                                            <span class="fz-12 text-break text-end min-w-0">{{ $__respLabels[$__cancelHist->cancellationReason->responsible] ?? $__cancelHist->cancellationReason->responsible }}</span>
                                        </div>
                                    @endif
                                    @if(filled($__cancelHist->status_change_remarks))
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Status_change_remarks') }}:</span>
                                            <div class="fz-12 text-break text-end min-w-0">{!! nl2br(e($__cancelHist->status_change_remarks)) !!}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif(in_array($__overviewSt, ['on_hold', 'ongoing'], true) && $__holdHist && ($__holdHist->holdReopenReason || filled($__holdHist->status_change_remarks)))
                            <div class="booking-status-detail-box fz-12 w-100 align-self-stretch" style="max-width: min(100%, 36rem);">
                                <div class="booking-status-detail-box__head">
                                    <span class="title-color fw-semibold text-uppercase fz-11">{{ $__overviewSt === 'on_hold' ? translate('Booking_hold_reasons') : translate('Last_on_hold_reason') }}</span>
                                </div>
                                <div class="booking-overview-kv-rows">
                                    @if($__holdHist->holdReopenReason)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Reason') }}:</span>
                                            <span class="fz-12 text-break text-end fw-semibold min-w-0">{{ $__holdHist->holdReopenReason->name }}</span>
                                        </div>
                                        @if($__holdHist->holdReopenReason->description)
                                            <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                                <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Description') }}:</span>
                                                <span class="fz-12 text-muted text-break text-end min-w-0">{{ $__holdHist->holdReopenReason->description }}</span>
                                            </div>
                                        @endif
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Responsible') }}:</span>
                                            <span class="fz-12 text-break text-end min-w-0">{{ $__respLabels[$__holdHist->holdReopenReason->responsible] ?? $__holdHist->holdReopenReason->responsible }}</span>
                                        </div>
                                    @endif
                                    @if($__holdHist->changed_by)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Put_on_hold_by') }}:</span>
                                            <span class="fz-12 text-break text-end min-w-0">
                                                @if($__holdHist->user)
                                                    {{ trim(($__holdHist->user->first_name ?? '') . ' ' . ($__holdHist->user->last_name ?? '')) }}
                                                    @if(filled($__holdHist->user->email ?? null) || filled($__holdHist->user->phone ?? null))
                                                        — {{ $__holdHist->user->email ?? $__holdHist->user->phone }}
                                                    @endif
                                                @else
                                                    #{{ $__holdHist->changed_by }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                    @if(filled($__holdHist->status_change_remarks))
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Status_change_remarks') }}:</span>
                                            <div class="fz-12 text-break text-end min-w-0">{!! nl2br(e($__holdHist->status_change_remarks)) !!}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif($__reopenEv && ($__reopenEv->holdReopenReason || filled($__reopenEv->complaint_notes)))
                            <div class="booking-status-detail-box fz-12 w-100 align-self-stretch" style="max-width: min(100%, 36rem);">
                                <div class="booking-status-detail-box__head">
                                    <span class="title-color fw-semibold text-uppercase fz-11">{{ translate('Booking_reopen_from_completed_reasons') }}</span>
                                </div>
                                <div class="booking-overview-kv-rows">
                                    @if($__reopenEv->holdReopenReason)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Reason') }}:</span>
                                            <span class="fz-12 text-break text-end fw-semibold min-w-0">{{ $__reopenEv->holdReopenReason->name }}</span>
                                        </div>
                                        @if($__reopenEv->holdReopenReason->description)
                                            <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                                <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Description') }}:</span>
                                                <span class="fz-12 text-muted text-break text-end min-w-0">{{ $__reopenEv->holdReopenReason->description }}</span>
                                            </div>
                                        @endif
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Responsible') }}:</span>
                                            <span class="fz-12 text-break text-end min-w-0">{{ $__respLabels[$__reopenEv->holdReopenReason->responsible] ?? $__reopenEv->holdReopenReason->responsible }}</span>
                                        </div>
                                    @endif
                                    @if(filled($__reopenEv->complaint_notes))
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Complaint_or_notes') }}:</span>
                                            <div class="fz-12 text-break text-end min-w-0">{!! nl2br(e($__reopenEv->complaint_notes)) !!}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <a href="{{ route('admin.booking.invoice', [$booking->id]) }}" class="btn btn-primary"
                            target="_blank">
                            <span class="material-icons">description</span>{{ translate('Invoice') }}
                        </a>
                        @can('booking_can_manage_status')
                            @if($booking->adminEligibleForReopenFromCompleted())
                                <button type="button" class="btn btn--secondary" data-bs-toggle="modal"
                                    data-bs-target="#bookingReopenModal--{{ $booking->id }}">
                                    <span class="material-icons">restore</span>{{ translate('Reopen_or_complaint') }}
                                </button>
                            @endif
                            @if((int)($booking->is_repeated ?? 0) === 0
                                && $booking->isOpenReopenTicket()
                                && in_array((string) ($booking->booking_status ?? ''), ['ongoing', 'on_hold'], true)
                            )
                                <button type="button" class="btn btn--danger" data-bs-toggle="modal"
                                    data-bs-target="#reopenDisputeModal--{{ $booking->id }}">
                                    <span class="material-icons">gavel</span>{{ translate('Dispute_and_close') }}
                                </button>
                            @endif
                            @if($booking->canMarkReopenResolved())
                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#reopenResolveModal--{{ $booking->id }}">
                                    <span class="material-icons">check_circle</span>{{ translate('Mark_reopen_resolved') }}
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            @if($booking->reopenEvents->isNotEmpty() || !empty($booking->originated_from_booking_id) || $booking->spawnedFollowupBookings->isNotEmpty())
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-soft-warning border-warning">
                        <h5 class="mb-0">{{ translate('Reopen_and_complaint_history') }}</h5>
                    </div>
                    <div class="card-body">
                        @if(!empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot))
                            @php
                                $snap = $booking->reopen_disputed_snapshot;
                            @endphp
                            @php
                                $__snapPoolOwesCo = (float) ($snap['provider_owes_company'] ?? 0);
                                $__snapFinAdmin = (float) ($snap['final_admin_commission'] ?? 0);
                                $__snapProviderRemitTotal = isset($snap['provider_total_remittance_to_company'])
                                    ? (float) $snap['provider_total_remittance_to_company']
                                    : round($__snapPoolOwesCo + $__snapFinAdmin, 2);
                                $__snapCompanyPaysPr = (float) ($snap['company_owes_provider'] ?? 0);
                            @endphp
                            <div class="alert alert-secondary py-2 mb-3 small">
                                <div class="fw-semibold mb-1">{{ translate('Reopen_disputed_settlement_snapshot') }}</div>
                                <div class="row g-2">
                                    <div class="col-md-6">{{ translate('Refund_paid_from_company_pool') }}: <strong>{{ with_currency_symbol((float) ($snap['refund_company_amount'] ?? 0)) }}</strong></div>
                                    <div class="col-md-6">{{ translate('Refund_paid_from_provider_pool') }}: <strong>{{ with_currency_symbol((float) ($snap['refund_provider_amount'] ?? 0)) }}</strong></div>
                                    <div class="col-md-6">{{ translate('Provider_owes_company_refund_above_pool') }}: <strong>{{ with_currency_symbol($__snapPoolOwesCo) }}</strong></div>
                                    <div class="col-md-6">{{ translate('Company_owes_provider_refund_above_pool') }}: <strong>{{ with_currency_symbol($__snapCompanyPaysPr) }}</strong></div>
                                    <div class="col-md-4">{{ translate('Final_amount_retained_from_customer_after_refunds') }}: <strong>{{ with_currency_symbol((float) ($snap['retained_from_customer'] ?? $snap['final_net_to_customer'] ?? 0)) }}</strong></div>
                                    <div class="col-md-4">{{ translate('Final_admin_commission_net_basis') }}: <strong>{{ with_currency_symbol($__snapFinAdmin) }}</strong></div>
                                    <div class="col-md-4">{{ translate('Final_provider_earning_net_basis') }}: <strong>{{ with_currency_symbol((float) ($snap['final_provider_earning'] ?? 0)) }}</strong></div>
                                    <div class="col-12 border-top pt-2 mt-1">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <span>{{ translate('Disputed_total_provider_pays_company') }} <span class="text-muted">({{ translate('Disputed_provider_pays_company_formula_hint') }})</span>:</span>
                                            <strong>{{ with_currency_symbol($__snapProviderRemitTotal) }}</strong>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-between gap-2 mt-1">
                                            <span>{{ translate('Disputed_total_company_pays_provider') }}:</span>
                                            <strong>{{ with_currency_symbol($__snapCompanyPaysPr) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($booking->reopen_resolved_at)
                            <p class="alert alert-success py-2 mb-3">
                                <span class="fw-semibold">{{ translate('Resolved') }}:</span>
                                {{ $booking->reopen_resolved_at->format('d-M-Y H:i') }}
                                @if($booking->reopenCaseResolvedByUser)
                                    — {{ $booking->reopenCaseResolvedByUser->first_name }} {{ $booking->reopenCaseResolvedByUser->last_name }}
                                @endif
                                @if(!empty($booking->reopen_resolve_remarks))
                                    <span class="d-block mt-2 small fw-normal text-dark">
                                        <span class="fw-semibold">{{ translate('Reopen_resolve_remarks') }}:</span><br>
                                        {!! nl2br(e($booking->reopen_resolve_remarks)) !!}
                                    </span>
                                @endif
                            </p>
                        @endif
                        @if(!empty($booking->originated_from_booking_id) && $booking->originatedFromBooking)
                            <p class="mb-2">
                                <span class="fw-semibold">{{ translate('Originated_from_booking') }}:</span>
                                <a href="{{ route('admin.booking.details', [$booking->originated_from_booking_id, 'web_page' => 'details']) }}">
                                    #{{ $booking->originatedFromBooking->readable_id ?? $booking->originated_from_booking_id }}
                                </a>
                            </p>
                        @endif
                        @if($booking->spawnedFollowupBookings->isNotEmpty())
                            <p class="fw-semibold mb-2">{{ translate('Follow_up_bookings') }}:</p>
                            <ul class="mb-3">
                                @foreach($booking->spawnedFollowupBookings as $child)
                                    <li>
                                        <a href="{{ route('admin.booking.details', [$child->id, 'web_page' => 'details']) }}">
                                            #{{ $child->readable_id }}</a>
                                        — <span class="text-capitalize">{{ $child->booking_status }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if($booking->reopenEvents->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ translate('When') }}</th>
                                            <th>{{ translate('By') }}</th>
                                            <th>{{ translate('Resolution') }}</th>
                                            <th>{{ translate('Notes') }}</th>
                                            <th>{{ translate('Linked_booking') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->reopenEvents as $ev)
                                            <tr>
                                                <td>{{ $ev->created_at?->format('d-M-Y H:i') }}</td>
                                                <td>{{ $ev->actor?->first_name }} {{ $ev->actor?->last_name }}</td>
                                                <td class="text-capitalize">{{ str_replace('_', ' ', $ev->resolution) }}</td>
                                                <td class="small">{{ \Illuminate\Support\Str::limit($ev->complaint_notes ?? '', 120) }}</td>
                                                <td>
                                                    @if($ev->child_booking_id)
                                                        <a href="{{ route('admin.booking.details', [$ev->child_booking_id, 'web_page' => 'details']) }}">{{ translate('Open') }}</a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @include('bookingmodule::admin.booking.partials._reopen-from-completed-modal')
            @include('bookingmodule::admin.booking.partials._reopen-resolve-modal', [
                'modalId' => 'reopenResolveModal--' . $booking->id,
                'formId' => 'reopenResolveForm--' . $booking->id,
                'formAction' => route('admin.booking.reopen-resolve', $booking->id),
            ])
            @if((int)($booking->is_repeated ?? 0) === 0 && booking_admin_can_dispute_and_close($booking))
                @include('bookingmodule::admin.booking.partials._reopen-dispute-modal')
            @endif
            @php
                $__reopenErrResolve = $errors->has('reopen_resolve_remarks');
                $__reopenErrResolveComplete = $errors->has('reopen_resolve_complete_remarks');
                $__reopenErrDispute = $errors->has('reopen_dispute_remarks')
                    || $errors->has('refund_company_amount')
                    || $errors->has('refund_provider_amount')
                    || $errors->has('refund_company_transaction_id')
                    || $errors->has('refund_provider_transaction_id')
                    || $errors->has('final_net_to_customer')
                    || $errors->has('final_admin_commission')
                    || $errors->has('final_provider_earning');
            @endphp
            @if($__reopenErrResolve || $__reopenErrResolveComplete || $__reopenErrDispute)
                @push('script')
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            if (window.bootstrap && bootstrap.Modal) {
                                @if($__reopenErrDispute)
                                    var d = document.getElementById('reopenDisputeModal--{{ $booking->id }}');
                                    if (d) bootstrap.Modal.getOrCreateInstance(d).show();
                                @elseif($__reopenErrResolveComplete)
                                    var c = document.getElementById('reopenResolveCompleteModal--{{ $booking->id }}');
                                    if (c) bootstrap.Modal.getOrCreateInstance(c).show();
                                @elseif($__reopenErrResolve)
                                    var el = document.getElementById('reopenResolveModal--{{ $booking->id }}');
                                    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
                                @endif
                            }
                        });
                    </script>
                @endpush
            @endif

            @can('booking_delete')
                <div class="modal fade" id="bookingDeleteModal--{{ $booking['id'] }}" tabindex="-1"
                     aria-labelledby="bookingDeleteModalLabel--{{ $booking['id'] }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body pt-5 p-md-5">
                                <button type="button" class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>

                                <div class="d-flex justify-content-center mb-4">
                                    <img width="75" height="75"
                                         src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                                         class="rounded-circle" alt="">
                                </div>

                                <h3 class="text-center mb-2 fw-medium">
                                    {{ translate('Are_you_sure_you_want_to_delete_this_booking?') }}
                                </h3>
                                <p class="text-center fs-12 fw-medium text-muted">
                                    {{ translate('This_action_will_permanently_remove_the_booking_and_its_related_data.') }}
                                </p>

                                <form method="POST"
                                      action="{{ route('admin.booking.delete', [$booking->id]) }}">
                                    @csrf
                                    @method('DELETE')

                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="btn btn-danger">
                                            {{ translate('Delete') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            @can('booking_edit')
            @if(!$bookingNotEditable)
            <div class="modal fade" id="bookingInfoModal--{{ $booking->id }}" tabindex="-1" aria-labelledby="bookingInfoModalLabel--{{ $booking->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('admin.booking.info-update', [$booking->id]) }}" method="POST" id="booking-info-form--{{ $booking->id }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="bookingInfoModalLabel--{{ $booking->id }}">{{ translate('Update_Booking_Information') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Assignee') }}</label>
                                    <select name="assignee_id" class="form-control js-select">
                                        <option value="">{{ translate('Unassigned') }}</option>
                                        @foreach($assignees ?? [] as $a)
                                            <option value="{{ $a->id }}" {{ (old('assignee_id', $booking->assignee_id) == $a->id) ? 'selected' : '' }}>
                                                {{ $a->first_name }} {{ $a->last_name }} ({{ $a->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }}) — {{ $a->email ?? $a->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Source') }}</label>
                                    <select name="booking_source" class="form-control">
                                        <option value="whatsapp" {{ (old('booking_source', $booking->booking_source ?? 'whatsapp') == 'whatsapp') ? 'selected' : '' }}>{{ translate('Whatsapp') }}</option>
                                        <option value="call" {{ (old('booking_source', $booking->booking_source) == 'call') ? 'selected' : '' }}>{{ translate('Call') }}</option>
                                        <option value="social_media" {{ (old('booking_source', $booking->booking_source) == 'social_media') ? 'selected' : '' }}>{{ translate('Social_Media') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Service_Additional_Details') }}</label>
                                    <textarea name="service_description" class="form-control" rows="3" maxlength="2000" placeholder="{{ translate('Optional_additional_notes_about_the_service') }}">{{ old('service_description', $booking->service_description) }}</textarea>
                                    <small class="text-muted">{{ translate('Max_2000_characters') }}</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addExtraServiceModal--{{ $booking->id }}" tabindex="-1" aria-labelledby="addExtraServiceModalLabel--{{ $booking->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('admin.booking.extra-service.store', [$booking->id]) }}" method="POST" id="extra-service-form--{{ $booking->id }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addExtraServiceModalLabel--{{ $booking->id }}">{{ translate('Add_Extra_Service') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="255" placeholder="{{ translate('Title') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Details_of_Service') }}</label>
                                    <textarea name="details" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Details_of_Service') }}">{{ old('details') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control" required>
                                        <option value="service" {{ old('type', 'service') == 'service' ? 'selected' : '' }}>{{ translate('Service') }}</option>
                                        <option value="spare_part" {{ old('type') == 'spare_part' ? 'selected' : '' }}>{{ translate('Spare_Part') }}</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ translate('Qty') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" required min="1" step="1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="price" class="form-control extra-price-input" value="{{ old('price', 0) }}" required min="0" step="0.01">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ translate('Discount') }}</label>
                                        <input type="number" name="discount" class="form-control extra-discount-input" value="{{ old('discount', 0) }}" min="0" step="0.01">
                                    </div>
                                </div>
                                <p class="mb-0 small text-muted">{{ translate('Total') }} = ({{ translate('Qty') }} × {{ translate('Price') }}) − {{ translate('Discount') }}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Add') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endcan

            @include('bookingmodule::admin.booking.partials.details._special-financial-settlement-banner', [
                'booking' => $booking,
                'bfsIncludeSettlementModal' => (int) ($booking->is_repeated ?? 0) === 0
                    && (($booking->booking_status ?? '') === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking))
                    && ! $bookingNotEditable,
                // Loss-making (scaled) bookings: show Record payment button when add-payment modal is available.
                'bfsAddPaymentModalOnPage' => $bfsShowRecordPaymentButton,
            ])

            @if((int) ($booking->is_repeated ?? 0) === 0 && $booking->isLossMakingFinancialSettlement())
                @php
                    $svc = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
                    $preview = $svc->buildPreview($booking);
                    $lossCapPr = round(max(0.0, (float) ($preview['scaled_loss_provider_share'] ?? 0)), 2);
                    $lossCapCo = round(max(0.0, (float) ($preview['scaled_loss_company_share'] ?? 0)), 2);
                    $lossRemain = round($lossCapPr + $lossCapCo, 2);
                @endphp
                <div class="modal fade" id="lossWriteoffModal-{{ $booking->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="{{ route('admin.booking.loss_writeoff', $booking->id) }}">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ translate('Settle_remaining_amount_as_discount') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-light border small">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">{{ translate('Due_Balance') }} ({{ translate('Invoice') }})</span>
                                            <strong>{{ with_currency_symbol($adminAddPaymentRemainingCapacity) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted">{{ translate('Bfs_preview_scaled_loss_amount') }}</span>
                                            <strong>{{ with_currency_symbol($lossRemain) }}</strong>
                                        </div>
                                        @if(!empty($preview['scaled_loss_writeoff_amount']))
                                            <div class="d-flex justify-content-between mt-1">
                                                <span class="text-muted">{{ translate('Settlement_amount') }}</span>
                                                <strong>{{ with_currency_symbol((float) $preview['scaled_loss_writeoff_amount']) }}</strong>
                                            </div>
                                        @endif
                                        <div class="text-muted mt-2">
                                            {{ translate('This will not record a customer payment. It writes off the remaining loss so the booking shows as recovered for reporting.') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                    <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @push('script')
                    <script>
                        // No client-side split needed for write-off modal.
                    </script>
                @endpush
            @endif

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
                            href="{{ url()->current() }}?web_page=details">{{ translate('details') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'history' ? 'active' : '' }}"
                            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'history']) }}">{{ translate('History') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'followups' ? 'active' : '' }}"
                            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'followups']) }}">{{ translate('Followups') }}</a>
                    </li>
                </ul>
                @php
                    $max_booking_amount = business_config('max_booking_amount', 'booking_setup')->live_values ?? 0;
                @endphp

                @if ($booking->is_verified == 2 && $booking->payment_method == 'cash_after_service' && $max_booking_amount <= $booking->total_booking_amount)
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span class="text-danger"># {{ translate('Note: ') }}</span>
                        <span>{{ $booking?->bookingDeniedNote?->value }}</span>
                    </div>
                @endif

                @if ($booking->is_verified == 0 && $booking->payment_method == 'cash_after_service' && $max_booking_amount <= $booking->total_booking_amount)
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span class="text-danger"># {{ translate('Note: ') }}</span>
                        <span>
                            {{ translate('You have to verify the booking because of maximum amount exceed') }}
                        </span>
                        <span>{{ $booking?->bookingDeniedNote?->value }}</span>
                    </div>
                @endif

                @if ($booking->booking_offline_payments->isNotEmpty() && $booking->payment_method == 'offline_payment' && $booking?->booking_offline_payments?->first()?->payment_status != 'approved')
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        @if($booking?->booking_offline_payments?->first()?->payment_status == 'pending')
                            <span>
                                <span class="text-danger fw-semibold"> # {{ translate('Note: ') }} </span>
                                {{ translate('Please Check & Verify the payment information weather it is correct or not before confirm the booking. ') }}
                            </span>
                        @endif
                            @if($booking?->booking_offline_payments?->first()?->payment_status == 'denied')
                                <span>
                                <span class="text-danger fw-semibold"> # {{ translate('Denied Note: ') }} </span>
                                {{ $booking?->booking_offline_payments?->first()?->denied_note }}
                            </span>
                            @endif

                    </div>
                @endif

            </div>

            <div class="row mb-3 g-3 align-items-stretch">
                <div class="col-xl-4 col-md-6 d-flex">
                    <div class="card h-100 w-100">
                        <div class="card-body py-3 px-3 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2 flex-shrink-0 flex-wrap">
                                <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                    <span class="material-icons title-color fz-16">event</span>
                                    {{ translate('Next_Follow_up_Date') }}
                                </h6>
                                <span class="fz-12 fw-semibold text-uppercase c1 flex-shrink-0">{{ translate('Provider') }}</span>
                            </div>
                            <div class="booking-overview-kv-rows flex-grow-1 fz-12">
                                @if($booking->provider)
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                        <span class="title-color flex-shrink-0">{{ translate('company_name') }}:</span>
                                        <span class="text-break text-end fw-semibold">{{ $booking->provider->company_name ?? '—' }}</span>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                        <span class="title-color flex-shrink-0">{{ translate('Phone') }}:</span>
                                        <span class="text-break text-end">
                                            <a href="tel:{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '' }}" class="text-muted">{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '—' }}</a>
                                        </span>
                                    </div>
                                @endif
                                <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                    <span class="title-color flex-shrink-0">{{ translate('Date_&_Time') }}:</span>
                                    <span class="text-break text-end fw-semibold">
                                        @if($nextFollowupProvider ?? null)
                                            {{ $nextFollowupProvider->date->format('d-M-Y h:ia') }}
                                            @if($nextFollowupProvider->reason)
                                                <span class="text-muted fw-normal"> ({{ Str::limit($nextFollowupProvider->reason, 60) }})</span>
                                            @endif
                                        @else
                                            <span class="text-muted fw-normal">—</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 d-flex">
                    <div class="card h-100 w-100">
                        <div class="card-body py-3 px-3 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2 flex-shrink-0 flex-wrap">
                                <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                    <span class="material-icons title-color fz-16">event</span>
                                    {{ translate('Next_Follow_up_Date') }}
                                </h6>
                                <span class="fz-12 fw-semibold text-uppercase c1 flex-shrink-0">{{ translate('Customer') }}</span>
                            </div>
                            <div class="booking-overview-kv-rows flex-grow-1 fz-12">
                                @if(($customerName ?? '') || ($customerPhone ?? ''))
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                        <span class="title-color flex-shrink-0">{{ translate('Name') }}:</span>
                                        <span class="text-break text-end fw-semibold">{{ ($customerName ?? '') ?: '—' }}</span>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                        <span class="title-color flex-shrink-0">{{ translate('Phone') }}:</span>
                                        <span class="text-break text-end">
                                            @if($customerPhone ?? null)
                                                <a href="tel:{{ $customerPhone }}" class="text-muted">{{ $customerPhone }}</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                    <span class="title-color flex-shrink-0">{{ translate('Date_&_Time') }}:</span>
                                    <span class="text-break text-end fw-semibold">
                                        @if($nextFollowupCustomer ?? null)
                                            {{ $nextFollowupCustomer->date->format('d-M-Y h:ia') }}
                                            @if($nextFollowupCustomer->reason)
                                                <span class="text-muted fw-normal"> ({{ Str::limit($nextFollowupCustomer->reason, 60) }})</span>
                                            @endif
                                        @else
                                            <span class="text-muted fw-normal">—</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 d-flex">
                    <div class="card h-100 w-100">
                        <div class="card-body py-3 px-3 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2 flex-shrink-0 flex-wrap">
                                <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                    <span class="material-icons title-color fz-16">flag</span>
                                    {{ translate('Booking_Status') }}
                                </h6>
                                <div class="d-flex flex-column align-items-end gap-1 text-end flex-shrink-0">
                                    <div class="d-flex flex-wrap gap-1 justify-content-end align-items-center">
                                        @if($__showHeaderBookingCancelledWithRefunded)
                                            <span class="badge bg-danger fz-12">{{ translate('Booking_cancelled') }}</span>
                                        @endif
                                        <span class="badge badge-{{ $__overviewBadge }} text-capitalize px-2 py-1 fz-12" id="booking-status-overview-badge">
                                            {{ $__bookingStatusDisplayLabel }}
                                        </span>
                                        @if($__showHeaderPendingRefundBadge)
                                            <span class="badge bg-warning text-dark fz-12"
                                                title="{{ translate('Remaining_refundable') }}: {{ with_currency_symbol($__headerRefundRemaining) }}">{{ translate('Pending_refund') }}</span>
                                        @endif
                                    </div>
                                    @if($booking->isOpenReopenTicket())
                                        <span class="badge bg-warning text-dark fz-12">{{ translate('Reopened') }}</span>
                                    @elseif($booking->isReopenedTagged() && (empty($booking->reopen_disputed_snapshot) || !is_array($booking->reopen_disputed_snapshot)))
                                        <span class="badge bg-success fz-12">{{ translate('Resolved') }}</span>
                                    @endif
                                </div>
                            </div>
                            @can('booking_can_manage_status')
                                @if(!$bookingNotEditable)
                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center min-h-0 w-100">
                                    <div class="d-flex flex-wrap gap-2 justify-content-center w-100" id="booking-status-overview-actions">
                                        @forelse ($__adminNextStatuses as $__nextSt)
                                            @php
                                                $__cashBlockTargets = ['pending', 'ongoing', 'completed'];
                                                $__btnDisabled = $__overviewStatusCashBlock && in_array($__nextSt, $__cashBlockTargets, true);
                                                if ($__nextSt === 'completed' && ! booking_can_be_completed($booking)) {
                                                    $__btnDisabled = true;
                                                }
                                                $__pillClass = match ($__nextSt) {
                                                    'accepted' => 'booking-status-pill--success',
                                                    'pending' => 'booking-status-pill--secondary',
                                                    'ongoing' => 'booking-status-pill--primary',
                                                    'on_hold' => 'booking-status-pill--warning',
                                                    'completed' => 'booking-status-pill--success',
                                                    'canceled', 'cancelled' => 'booking-status-pill--danger',
                                                    default => 'booking-status-pill--secondary',
                                                };
                                                $__pillLabel = match ($__nextSt) {
                                                    'accepted' => translate('Accept_Booking'),
                                                    'pending' => translate('Mark_as_Pending'),
                                                    'ongoing' => translate('Mark_as_Ongoing'),
                                                    'on_hold' => $__overviewSt === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold'),
                                                    'completed' => translate('Complete_Booking'),
                                                    'canceled', 'cancelled' => translate('Cancel_Booking'),
                                                    default => ucwords(str_replace('_', ' ', $__nextSt)),
                                                };
                                            @endphp
                                            <button type="button" class="booking-status-pill {{ $__pillClass }} booking-status-overview-btn" data-status="{{ $__nextSt }}"
                                                @if($__btnDisabled) disabled title="{{ translate('Not available for this booking') }}" @endif>{{ $__pillLabel }}</button>
                                        @empty
                                            <p class="fz-12 text-muted mb-0 w-100 text-center">{{ translate('No_status_changes_available') }}</p>
                                        @endforelse
                                        @if((int)($booking->is_repeated ?? 0) === 0 && ($__overviewSt === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking)))
                                            <button type="button" class="booking-status-pill booking-status-pill--info" data-bs-toggle="modal"
                                                data-bs-target="#bookingFinancialSettlementModal">
                                                {{ translate('Configure_special_scenarios') }}
                                            </button>
                                        @endif
                                        @if((int)($booking->is_repeated ?? 0) === 0 && ($__overviewSt === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking)))
                                            <button type="button" class="booking-status-pill booking-status-pill--danger" data-bs-toggle="modal"
                                                data-bs-target="#reopenDisputeModal--{{ $booking->id }}">
                                                {{ translate('Dispute_and_close') }}
                                            </button>
                                        @endif
                                        @if((int)($booking->is_repeated ?? 0) === 0 && $booking->isOpenReopenTicket())
                                            <button type="button" class="booking-status-pill booking-status-pill--success" data-bs-toggle="modal"
                                                data-bs-target="#reopenResolveCompleteModal--{{ $booking->id }}">
                                                {{ translate('Resolve_booking') }}
                                            </button>
                                            <button type="button" class="booking-status-pill booking-status-pill--danger" data-bs-toggle="modal"
                                                data-bs-target="#reopenDisputeModal--{{ $booking->id }}">
                                                {{ translate('Dispute_and_close') }}
                                            </button>
                                        @endif
                                    </div>
                                    </div>
                                @elseif($__overviewShowReopenInCard)
                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center min-h-0 w-100">
                                    <div class="d-flex flex-wrap gap-2 justify-content-center w-100" id="booking-status-overview-actions">
                                        <button type="button" class="booking-status-pill booking-status-pill--secondary" data-bs-toggle="modal"
                                            data-bs-target="#bookingReopenModal--{{ $booking->id }}">
                                            {{ translate('Reopen_Booking') }}
                                        </button>
                                        @if($booking->isOpenReopenTicket())
                                            <button type="button" class="booking-status-pill booking-status-pill--danger" data-bs-toggle="modal"
                                                data-bs-target="#reopenDisputeModal--{{ $booking->id }}">
                                                {{ translate('Dispute_and_close') }}
                                            </button>
                                        @endif
                                        @if($booking->canMarkReopenResolved())
                                            <button type="button" class="booking-status-pill booking-status-pill--success" data-bs-toggle="modal"
                                                data-bs-target="#reopenResolveModal--{{ $booking->id }}">
                                                {{ translate('Mark_as_Resolved') }}
                                            </button>
                                        @endif
                                    </div>
                                    </div>
                                @else
                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center text-center px-1 min-h-0 w-100">
                                        <p class="fz-12 text-muted mb-0">{{ translate('Status is fixed for this booking.') }}</p>
                                    </div>
                                @endif
                            @else
                                <div class="flex-grow-1 d-flex align-items-center justify-content-center text-center px-1 min-h-0 w-100">
                                    <p class="fz-12 text-muted mb-0">{{ translate('You do not have permission to change booking status.') }}</p>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            @php
                $revenueSettlement = get_booking_received_and_settlement($booking);
                $dueBalanceDisplay = round(max(0, (float) $bookingTotalForPayment - (float) $displayPaidAmount), 2);
                if ($dueBalanceDisplay > 0 && in_array($booking->booking_status, ['pending', 'accepted', 'ongoing'], true) && $booking->payment_method != 'cash_after_service' && (float) ($booking->additional_charge ?? 0) > 0) {
                    $dueBalanceDisplay = round($dueBalanceDisplay + (float) $booking->additional_charge, 2);
                }
                if ($__bfsScaledPaymentCard) {
                    $dueBalanceDisplay = round(max(0.0, (float) get_booking_total_amount($booking) - (float) get_booking_total_paid($booking)), 2);
                }
                $__bfsPaymentCardVisitRetainedCanceled = (string) ($booking->booking_status ?? '') === 'canceled'
                    && (
                        !empty($booking->after_visit_cancel)
                        || (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
                    );
                $__paymentRefundBreakdown = get_booking_refund_display_totals($booking);
                $__paymentRefundMaxEligible = round((float) ($__paymentRefundBreakdown['max_eligible'] ?? 0), 2);
                $__paymentRefundRemaining = isset($maxRefundAmount) ? round((float) $maxRefundAmount, 2) : round((float) ($__paymentRefundBreakdown['refundable_remaining'] ?? 0), 2);
                $__paymentRefundRefunded = round((float) ($__paymentRefundBreakdown['refunded_total'] ?? 0), 2);
                $__showPaymentRefundRows = $__paymentRefundMaxEligible > 0.009;
                $__paymentAmountPaidByCustomer = round((float) get_booking_total_paid($booking), 2);
                if ($__bfsPaymentCardVisitRetainedCanceled) {
                    $payableCap = round((float) get_booking_payable_total_for_partial_dues($booking), 2);
                    $paidPartials = round((float) ($booking->booking_partial_payments ?? collect())->sum('paid_amount'), 2);
                    if ($payableCap <= 0) {
                        $adminPaymentStatusLabel = translate('Unpaid');
                        $adminPaymentStatusBadgeClass = 'danger';
                    } elseif ($paidPartials + 0.005 >= $payableCap || $paymentFullyCovered) {
                        $adminPaymentStatusLabel = translate('Paid');
                        $adminPaymentStatusBadgeClass = 'success';
                    } elseif ($paidPartials > 0) {
                        $adminPaymentStatusLabel = translate('Partially paid');
                        $adminPaymentStatusBadgeClass = 'info';
                    } else {
                        $adminPaymentStatusLabel = translate('Unpaid');
                        $adminPaymentStatusBadgeClass = 'danger';
                    }
                } elseif ($__showPaymentRefundRows) {
                    if ($__paymentRefundRemaining > 0.009 && in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled'], true)) {
                        $adminPaymentStatusLabel = translate('Pending_refund');
                        $adminPaymentStatusBadgeClass = 'warning';
                    } else {
                        $adminPaymentStatusLabel = translate('Refunded');
                        $adminPaymentStatusBadgeClass = 'success';
                    }
                } elseif (in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
                    if ($paymentFullyCovered) {
                        $adminPaymentStatusLabel = translate('Paid');
                        $adminPaymentStatusBadgeClass = 'success';
                    } elseif ($booking->booking_partial_payments->isNotEmpty() && $totalPaidFromPartials > 0) {
                        $adminPaymentStatusLabel = translate('Partially paid');
                        $adminPaymentStatusBadgeClass = 'info';
                    } else {
                        $adminPaymentStatusLabel = translate('Unpaid');
                        $adminPaymentStatusBadgeClass = 'danger';
                    }
                } elseif ($paymentFullyCovered) {
                    $adminPaymentStatusLabel = translate('Paid');
                    $adminPaymentStatusBadgeClass = 'success';
                } elseif ($booking->booking_partial_payments->isNotEmpty()) {
                    $adminPaymentStatusLabel = translate('Partially paid');
                    $adminPaymentStatusBadgeClass = 'info';
                } else {
                    $adminPaymentStatusLabel = translate('Unpaid');
                    $adminPaymentStatusBadgeClass = 'danger';
                }
                if ($__bfsScaledPaymentCard) {
                    $grandInv = round((float) get_booking_total_amount($booking), 2);
                    $paidAct = round((float) get_booking_total_paid($booking), 2);
                    if ($paidAct + 0.005 < $grandInv) {
                        $adminPaymentStatusLabel = translate('Partially paid');
                        $adminPaymentStatusBadgeClass = 'info';
                    }
                }
            @endphp
            <div class="row g-3 mb-3 align-items-stretch booking-details-overview-row">
                <div class="col-xl-4 col-md-6 d-flex flex-column booking-overview-min-h-0">
                    <div class="booking-overview-column-inner">
                        <div class="card mb-0 d-flex flex-column overflow-hidden booking-overview-left-card">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between gap-1 border-bottom pb-2 mb-2 flex-shrink-0">
                                    <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                        <span class="material-icons title-color fz-16">person</span>
                                        {{ translate('Customer_Information') }}
                                    </h6>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        @can('whatsapp_chat_view')
                                            @if (!empty($customerPhone))
                                                <button type="button"
                                                        class="btn btn-link p-0 border-0 d-inline-flex align-items-center wa-open-admin-chat"
                                                        data-phone="{{ e($customerPhone) }}"
                                                        data-prepare-url="{{ route('admin.whatsapp.conversations.prepare-open') }}"
                                                        title="{{ translate('WhatsApp') }} — {{ translate('chat_with_Customer') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-grow-1 booking-overview-min-h-0 overflow-y-auto">
                                    @if (!$booking?->is_guest && $booking?->customer)
                                        <img width="42" height="42" class="rounded-circle border border-white flex-shrink-0 object-fit-cover align-self-start" src="{{ $booking?->customer?->profile_image_full_path }}" alt="">
                                    @else
                                        <img width="42" height="42" class="rounded-circle border border-white flex-shrink-0 object-fit-cover align-self-start" src="{{ asset('assets/provider-module/img/user2x.png') }}" alt="">
                                    @endif
                                    <div class="min-w-0 flex-grow-1 small">
                                        @if (!$booking?->is_guest && $booking?->customer)
                                            <a href="{{ route('admin.customer.detail', [$booking?->customer?->id, 'web_page' => 'overview']) }}" class="c1 d-block text-break fw-semibold fz-12">{{ Str::limit($customerName ?? '', 48) }}</a>
                                        @else
                                            <span class="d-block text-break fw-semibold fz-12">{{ Str::limit($customerName ?? '', 48) }}</span>
                                        @endif
                                        @if ($customerPhone ?? null)
                                            <a href="tel:{{ $customerPhone }}" class="d-block text-break fz-12 text-muted">{{ $customerPhone }}</a>
                                        @endif
                                        @if(!empty($booking?->service_address?->address))
                                            <span class="d-block text-break fz-12 text-muted mt-1" title="{{ $booking?->service_address?->address }}"><span class="material-icons fz-12 align-middle">map</span> {{ Str::limit($booking?->service_address?->address, 180) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-0 d-flex flex-column overflow-hidden booking-overview-left-card--provider">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between gap-1 border-bottom pb-2 mb-2 flex-shrink-0">
                                    <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                        <span class="material-icons title-color fz-16">person</span>
                                        {{ translate('Provider_Information') }}
                                    </h6>
                                    @if (isset($booking->provider))
                                        @php
                                            $providerWaPhone = trim((string) ($booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? ''));
                                        @endphp
                                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                            @can('whatsapp_chat_view')
                                                @if ($providerWaPhone !== '')
                                                    <button type="button"
                                                            class="btn btn-link p-0 border-0 d-inline-flex align-items-center wa-open-admin-chat"
                                                            data-phone="{{ e($providerWaPhone) }}"
                                                            data-prepare-url="{{ route('admin.whatsapp.conversations.prepare-open') }}"
                                                            title="{{ translate('WhatsApp') }} — {{ translate('chat_with_Provider') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                    </button>
                                                @endif
                                            @endcan
                                            @if (booking_admin_can_reassign_provider($booking) && in_array($booking->booking_status, ['accepted', 'pending', 'on_hold']))
                                                @can('booking_can_manage_status')
                                                    <span class="cursor-pointer d-inline-flex align-items-center" role="button" tabindex="0" data-bs-target="#providerModal" data-bs-toggle="modal" title="{{ translate('change_Provider') }}">
                                                        <span class="material-symbols-outlined fz-18">manage_history</span>
                                                    </span>
                                                @endcan
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-y-auto gap-2">
                                    @if (isset($booking->provider))
                                        <div class="d-flex align-items-center gap-2">
                                            <img width="42" height="42" class="rounded-circle border border-white flex-shrink-0 object-fit-cover align-self-start" src="{{ $booking?->provider?->logo_full_path }}" alt="">
                                            <div class="min-w-0 flex-grow-1 small">
                                                <a href="{{ route('admin.provider.details', [$booking?->provider?->id, 'web_page' => 'overview']) }}" class="c1 d-block text-break fw-semibold fz-12">{{ Str::limit($booking->provider->company_name ?? '', 48) }}</a>
                                                <a href="tel:{{ $booking->provider->contact_person_phone ?? '' }}" class="d-block text-break fz-12 text-muted">{{ $booking->provider->contact_person_phone ?? '' }}</a>
                                                <span class="d-block text-break fz-12 text-muted mt-1" title="{{ $booking->provider->company_address }}"><span class="material-icons fz-12 align-middle">map</span> {{ Str::limit($booking->provider->company_address ?? '', 180) }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center justify-content-between gap-2 py-1">
                                            <span class="text-muted small mb-0">{{ translate('No Provider Information') }}</span>
                                            @if($booking->is_verified != 2)
                                                <button type="button" class="btn btn-sm btn--primary py-0 px-2 fz-11 flex-shrink-0" data-bs-target="#providerModal" data-bs-toggle="modal">{{ translate('assign provider') }}</button>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="border-top pt-2 mt-1">
                                        <div class="d-flex align-items-center justify-content-between gap-1 pb-2 mb-0 flex-shrink-0">
                                            <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                                <span class="material-icons title-color fz-16">engineering</span>
                                                {{ translate('Serviceman_Information') }}
                                            </h6>
                                            @if (isset($booking->serviceman))
                                                <div class="btn-group">
                                                    @if (in_array($booking->booking_status, ['ongoing', 'accepted']))
                                                        <div class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <span class="material-symbols-outlined fz-18">more_vert</span>
                                                        </div>
                                                        <ul class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                            <li>
                                                                <div class="d-flex align-items-center gap-2 cursor-pointer provider-chat">
                                                                    <span class="material-symbols-outlined">chat</span>
                                                                    {{ translate('chat_with_Serviceman') }}
                                                                    <form action="{{ route('admin.chat.create-channel') }}" method="post" id="chatForm-serviceman-overview-{{ $booking->id }}">
                                                                        @csrf
                                                                        <input type="hidden" name="serviceman_id" value="{{ $booking?->serviceman?->user?->id }}">
                                                                        <input type="hidden" name="type" value="booking">
                                                                        <input type="hidden" name="user_type" value="provider-serviceman">
                                                                    </form>
                                                                </div>
                                                            </li>
                                                            @can('booking_can_manage_status')
                                                                <li>
                                                                    <div class="d-flex align-items-center gap-2" data-bs-target="#servicemanModal" data-bs-toggle="modal">
                                                                        <span class="material-symbols-outlined">manage_history</span>
                                                                        {{ translate('change serviceman') }}
                                                                    </div>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @if (isset($booking->serviceman))
                                            <div class="d-flex align-items-center gap-2">
                                                <img width="42" height="42" class="rounded-circle border border-white flex-shrink-0 object-fit-cover align-self-start" src="{{ $booking?->serviceman?->user?->profile_image_full_path }}" alt="{{ translate('serviceman') }}">
                                                <div class="min-w-0 flex-grow-1 small">
                                                    <span class="c1 d-block text-break fw-semibold fz-12">{{ Str::limit($booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->first_name . ' ' . $booking->serviceman->user->last_name : '', 48) }}</span>
                                                    <a href="tel:{{ $booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone : '' }}" class="d-block text-break fz-12 text-muted">{{ $booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone : '' }}</a>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex flex-column gap-2 align-items-center py-2">
                                                <span class="material-icons text-muted fs-2">account_circle</span>
                                                <p class="text-muted text-center fw-medium mb-2 fz-12">{{ translate('No Serviceman Information') }}</p>
                                            </div>
                                            <div class="text-center pb-1">
                                                <button type="button" class="btn btn--primary" data-bs-target="#servicemanModal" data-bs-toggle="modal"
                                                    @if($booking['booking_status'] == 'completed' || $booking['booking_status'] == 'canceled' || !isset($booking->provider)) disabled @endif>
                                                    {{ translate('assign Serviceman') }}
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 d-flex flex-column booking-overview-min-h-0">
                    <div class="booking-overview-column-inner">
                        <div class="card mb-0 w-100 d-flex flex-column overflow-hidden booking-overview-mid-card--payment">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between gap-1 border-bottom pb-2 mb-2 flex-shrink-0">
                                    <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                        <span class="material-icons title-color fz-16">payments</span>
                                        {{ translate('Payment_Details') }}
                                    </h6>
                                    <button type="button" class="btn btn-link btn-sm p-0 lh-1 text-decoration-none text-nowrap fz-12" data-bs-toggle="modal" data-bs-target="#bookingPaymentHistoryModal-{{ $booking->id }}">
                                        {{ translate('view_all') }}
                                    </button>
                                </div>
                                <div class="booking-overview-kv-rows flex-grow-1 booking-overview-min-h-0 overflow-y-auto pb-1 fz-12">
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-center gap-2 mb-0">
                                        <span class="title-color flex-shrink-0">{{ translate('Payment_Status') }}</span>
                                        <span class="badge badge-{{ $adminPaymentStatusBadgeClass }} mb-0 fz-12 flex-shrink-0" id="payment_status__span">{{ $adminPaymentStatusLabel }}</span>
                                    </div>
                                    @if ($__showPaymentRefundRows)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Amount_paid_by_customer') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($__paymentAmountPaidByCustomer) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Refundable_amount') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($__paymentRefundMaxEligible) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Refunded_amount') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($__paymentRefundRefunded) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Refund_balance_remaining') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($__paymentRefundRemaining) }}</span>
                                        </div>
                                    @else
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Total_Amount') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($paymentDetailsTotalAmount) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ $showAsAmountPaidLabel ? translate('Amount_Paid') : translate('Advance_Paid') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($paymentDetailsAmountPaid) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Due_Balance') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($dueBalanceDisplay) }}</span>
                                        </div>
                                    @endif
                                    @if ($__bfsScaledLive !== null && ! $__showPaymentRefundRows)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Bfs_bad_debt_balance_not_due') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol((float) ($__bfsScaledLive['scaled_bad_debt_balance_not_due'] ?? 0)) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Loss_to_company') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol((float) ($__bfsScaledLive['scaled_loss_company_share'] ?? 0)) }}</span>
                                        </div>
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Loss_to_provider') }}</span>
                                            <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol((float) ($__bfsScaledLive['scaled_loss_provider_share'] ?? 0)) }}</span>
                                        </div>
                                    @endif
                                    @if($booking->payment_method == 'offline_payment' && $booking->booking_offline_payments->isNotEmpty())
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Request Verify Status') }}</span>
                                            <span class="text-end min-w-0">
                                            @if($booking->booking_offline_payments?->first()?->payment_status == 'pending')
                                                <span class="text-info text-capitalize fw-bold">{{ translate('Pending') }}</span>
                                            @endif
                                            @if($booking->booking_offline_payments?->first()?->payment_status == 'denied')
                                                <span class="text-danger text-capitalize fw-bold">{{ translate('Denied') }}</span>
                                            @endif
                                            @if($booking->booking_offline_payments?->first()?->payment_status == 'approved')
                                                <span class="text-primary text-capitalize fw-bold">{{ translate('Approved') }}</span>
                                            @endif
                                            </span>
                                        </div>
                                    @endif
                                    @if ($booking->is_verified == '0' && $booking->payment_method == 'cash_after_service' && $booking->total_booking_amount >= $maxBookingAmount)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Request Verify Status:') }}</span>
                                            <span class="c1 text-capitalize text-end min-w-0">{{ translate('Pending') }}</span>
                                        </div>
                                    @elseif($booking->is_verified == '2' &&  $booking->payment_method == 'cash_after_service' && $booking->total_booking_amount >= $maxBookingAmount)
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                            <span class="title-color flex-shrink-0">{{ translate('Request Verify Status:') }}</span>
                                            <span class="text-danger text-capitalize text-end min-w-0" id="booking_status__span">{{ translate('Denied') }}</span>
                                        </div>
                                    @endif
                                </div>
                                @can('booking_can_manage_status')
                                    @if($showAddPaymentOnBookingDetails)
                                        <div class="flex-shrink-0 pt-2 border-top mt-2">
                                            <button type="button" class="btn btn--primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal-{{ $booking->id }}">{{ translate('Add payment') }}</button>
                                        </div>
                                    @endif
                                @endcan
                            </div>
                        </div>
                        <div class="modal fade" id="bookingPaymentHistoryModal-{{ $booking->id }}" tabindex="-1" aria-labelledby="bookingPaymentHistoryModalLabel-{{ $booking->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="bookingPaymentHistoryModalLabel-{{ $booking->id }}">{{ translate('Payment_and_refund_history') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                    </div>
                                    <div class="modal-body">
                                        @php
                                            $installmentPaymentsOrdered = $booking->booking_partial_payments
                                                ->sortBy([
                                                    ['created_at', 'asc'],
                                                    ['id', 'asc'],
                                                ])
                                                ->values()
                                                ->filter(function ($pp) {
                                                    return round((float) ($pp->paid_amount ?? 0), 2) != 0.0;
                                                })
                                                ->values();
                                            $installmentPayableCap = $__bfsScaledLive !== null
                                                ? round((float) get_booking_total_amount($booking), 2)
                                                : get_booking_payable_total_for_partial_dues($booking);
                                            $installmentRunningPaid = 0.0;
                                            $refundLedgerRowsForPaymentModal = \Modules\TransactionModule\Entities\LedgerTransaction::query()
                                                ->where('booking_id', $booking->id)
                                                ->where('reason', \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
                                                ->where('type', \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT)
                                                ->orderBy('created_at')
                                                ->orderBy('id')
                                                ->with('creator')
                                                ->get();
                                            $showInstallmentPaymentDeleteColumn = $allowDeleteAdminBookingPartialPayments
                                                && auth()->check()
                                                && auth()->user()->can('booking_can_manage_status');
                                        @endphp
                                        <p class="text-uppercase text-muted fz-11 mb-2 fw-semibold">{{ translate('Installment_payments') }}</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle fz-12 mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-nowrap">#</th>
                                                        <th class="text-nowrap">{{ translate('Date_time_added') }}</th>
                                                        <th class="text-nowrap">{{ translate('Received by') }}</th>
                                                        <th class="text-nowrap">{{ translate('Amount') }}</th>
                                                        <th class="text-nowrap">{{ translate('Payment_Method') }}</th>
                                                        <th class="text-nowrap">{{ translate('transaction_id') }}</th>
                                                        <th class="text-nowrap">{{ translate('Due_after_this_payment') }}</th>
                                                        @if($showInstallmentPaymentDeleteColumn)
                                                            <th class="text-nowrap text-end">{{ translate('Action') }}</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($installmentPaymentsOrdered as $idx => $pp)
                                                        @php
                                                            $installmentRunningPaid += (float) $pp->paid_amount;
                                                            $dueAfterThisInstallment = round(max(0, $installmentPayableCap - $installmentRunningPaid), 2);
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $idx + 1 }}</td>
                                                            <td class="text-nowrap">{{ $pp->created_at ? $pp->created_at->format('d M Y, H:i:s') : '—' }}</td>
                                                            <td>
                                                                @if(($pp->received_by ?? '') === 'company')
                                                                    {{ translate('Company') }}
                                                                @elseif(($pp->received_by ?? '') === 'provider')
                                                                    {{ translate('Provider') }}
                                                                @else
                                                                    {{ $pp->received_by ? ucfirst((string) $pp->received_by) : '—' }}
                                                                @endif
                                                            </td>
                                                            <td class="text-end fw-medium">{{ with_currency_symbol($pp->paid_amount) }}</td>
                                                            <td class="text-break">{{ $pp->paymentMethodLabelForAdmin($booking) }}</td>
                                                            <td class="text-break">{{ $pp->transaction_id ?: '—' }}</td>
                                                            <td class="text-end">{{ with_currency_symbol($dueAfterThisInstallment) }}</td>
                                                            @if($showInstallmentPaymentDeleteColumn)
                                                                <td class="text-end text-nowrap">
                                                                    @if(($pp->paid_with ?? '') === 'admin_entry')
                                                                        <button type="button"
                                                                            class="btn btn-outline-danger btn-sm py-0 px-2 fz-11"
                                                                            title="{{ translate('Delete_payment_entry') }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteAdminPartialPaymentConfirm-{{ $booking->id }}"
                                                                            data-partial-payment-id="{{ $pp->id }}"
                                                                            data-amount-line="{{ e(translate('Amount')) }}: {{ e(with_currency_symbol($pp->paid_amount)) }}">{{ translate('Delete') }}</button>
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="{{ $showInstallmentPaymentDeleteColumn ? 8 : 7 }}" class="text-center text-muted py-3">{{ translate('No data available') }}</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        @if($refundLedgerRowsForPaymentModal->isNotEmpty() || (!empty($__showPaymentRefundRows) && $__showPaymentRefundRows))
                                            <p class="text-uppercase text-muted fz-11 mb-2 fw-semibold mt-4">{{ translate('Refunds_to_customer') }}</p>
                                            @if($refundLedgerRowsForPaymentModal->isNotEmpty())
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle fz-12 mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="text-nowrap">#</th>
                                                                <th class="text-nowrap">{{ translate('Date_time_added') }}</th>
                                                                <th class="text-nowrap">{{ translate('Amount') }}</th>
                                                                <th class="text-nowrap">{{ translate('transaction_id') }}</th>
                                                                <th class="text-nowrap">{{ translate('Reference_Note') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($refundLedgerRowsForPaymentModal as $idx => $lt)
                                                                <tr>
                                                                    <td>{{ $idx + 1 }}</td>
                                                                    <td class="text-nowrap">{{ $lt->created_at ? $lt->created_at->format('d M Y, H:i:s') : '—' }}</td>
                                                                    <td class="text-end fw-medium text-danger">-{{ with_currency_symbol((float) ($lt->amount ?? 0)) }}</td>
                                                                    <td class="text-break">{{ $lt->transaction_id ? $lt->transaction_id : '—' }}</td>
                                                                    <td class="text-break">{{ filled($lt->reference_note) ? Str::limit((string) $lt->reference_note, 120) : '—' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted fz-12 mb-0">{{ translate('No_refund_transactions_recorded_yet') }}</p>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($showInstallmentPaymentDeleteColumn)
                            <div class="modal fade" id="deleteAdminPartialPaymentConfirm-{{ $booking->id }}" tabindex="-1" aria-labelledby="deleteAdminPartialPaymentConfirmLabel-{{ $booking->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header border-bottom-0 pb-0">
                                            <h5 class="modal-title d-flex align-items-center gap-2 text-danger fz-16 mb-0" id="deleteAdminPartialPaymentConfirmLabel-{{ $booking->id }}">
                                                <span class="material-symbols-outlined" aria-hidden="true">warning</span>
                                                {{ translate('Delete_payment_entry') }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                        </div>
                                        <div class="modal-body pt-2">
                                            <div class="alert alert-warning mb-3 fz-12" role="alert">
                                                {{ translate('Delete_payment_entry_confirm') }}
                                            </div>
                                            <p class="text-muted fz-12 mb-0 fw-medium" id="deletePartialPaymentSummaryLine-{{ $booking->id }}"></p>
                                        </div>
                                        <div class="modal-footer border-top-0 pt-0">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                            <form method="post" action="{{ route('admin.booking.partial_payment.delete', $booking->id) }}" class="d-inline" id="deleteAdminPartialPaymentForm-{{ $booking->id }}">
                                                @csrf
                                                <input type="hidden" name="partial_payment_id" id="deletePartialPaymentIdField-{{ $booking->id }}" value="">
                                                <button type="submit" class="btn btn-danger">{{ translate('Yes, Delete') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                (function () {
                                    var confirmEl = document.getElementById('deleteAdminPartialPaymentConfirm-{{ $booking->id }}');
                                    if (!confirmEl) return;
                                    confirmEl.addEventListener('show.bs.modal', function (event) {
                                        var trigger = event.relatedTarget;
                                        var idField = document.getElementById('deletePartialPaymentIdField-{{ $booking->id }}');
                                        var summary = document.getElementById('deletePartialPaymentSummaryLine-{{ $booking->id }}');
                                        if (!trigger || !idField) return;
                                        var pid = trigger.getAttribute('data-partial-payment-id') || '';
                                        var amountLine = trigger.getAttribute('data-amount-line') || '';
                                        idField.value = pid;
                                        if (summary) {
                                            summary.textContent = amountLine;
                                        }
                                    });
                                })();
                            </script>
                        @endif
                        <div class="card border-primary mb-0 w-100 d-flex flex-column overflow-hidden booking-overview-mid-card--revenue">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between gap-1 border-bottom pb-2 mb-2 flex-shrink-0">
                                    <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                        <span class="material-icons title-color fz-16">account_balance</span>
                                        {{ translate('Revenue_&_Settlement') }}
                                    </h6>
                                </div>
                                <div class="d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-y-auto pb-1 fz-12 gap-2">
                                    @if(booking_should_show_admin_revenue_settlement_breakdown($booking))
                                    <div class="booking-overview-kv-rows flex-shrink-0">
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-center">
                                        <span class="title-color">
                                            @if ($__bfsScaledLive !== null && (float) ($revenueSettlement['company_share'] ?? 0) < -0.009)
                                                {{ translate('Company_loss') }} ({{ translate('Net') }})
                                            @else
                                                {{ translate('Company_share') }} ({{ translate('Commission') }})
                                            @endif
                                        </span>
                                        <strong @class(['text-primary' => (float) ($revenueSettlement['company_share'] ?? 0) >= -0.009, 'text-danger' => (float) ($revenueSettlement['company_share'] ?? 0) < -0.009])>{{ with_currency_symbol($revenueSettlement['company_share']) }}</strong>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-center">
                                        <span class="title-color">
                                            @if ($__bfsScaledLive !== null && (float) ($revenueSettlement['provider_share'] ?? 0) < -0.009)
                                                {{ translate('Provider_loss') }} ({{ translate('Net') }})
                                            @else
                                                {{ translate('Provider_share') }}
                                            @endif
                                        </span>
                                        <strong @class(['text-danger' => (float) ($revenueSettlement['provider_share'] ?? 0) < -0.009])>{{ with_currency_symbol($revenueSettlement['provider_share']) }}</strong>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline text-muted">
                                        <span>{{ translate('Received_by_company') }}:</span>
                                        <span class="text-end text-break min-w-0">{{ with_currency_symbol($revenueSettlement['amount_received_by_company']) }}</span>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline text-muted">
                                        <span>{{ translate('Received_by_provider') }}:</span>
                                        <span class="text-end text-break min-w-0">{{ with_currency_symbol($revenueSettlement['amount_received_by_provider']) }}</span>
                                    </div>
                                    </div>
                                    @if(!empty($revenueSettlement['net_revenue_zeroed_after_refund']))
                                        <div class="alert alert-secondary mb-0 py-2 px-2 fz-12">
                                            {{ translate('Net_settlement_zero_after_full_refund_hint') }}
                                        </div>
                                        @if((float) ($revenueSettlement['pay_to_provider'] ?? 0) > 0.009)
                                            <div class="alert alert-info mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center mt-2">
                                                <span>{{ translate('Pay_to_provider') }} <span class="text-muted">({{ translate('Reopen_disputed_settlement_snapshot') }})</span>:</span>
                                                <strong>{{ with_currency_symbol($revenueSettlement['pay_to_provider']) }}</strong>
                                            </div>
                                        @endif
                                        @if((float) ($revenueSettlement['provider_owes_company'] ?? 0) > 0.009)
                                            <div class="alert alert-warning mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center mt-2">
                                                <span>{{ translate('Provider_owes_you') }} <span class="text-muted">({{ translate('Reopen_disputed_settlement_snapshot') }})</span>:</span>
                                                <strong>{{ with_currency_symbol($revenueSettlement['provider_owes_company']) }}</strong>
                                            </div>
                                        @endif
                                    @elseif($revenueSettlement['pay_to_provider'] > 0)
                                        <div class="alert alert-info mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center">
                                            <span>{{ translate('Pay_to_provider') }}:</span>
                                            <strong>{{ with_currency_symbol($revenueSettlement['pay_to_provider']) }}</strong>
                                        </div>
                                    @elseif($revenueSettlement['provider_owes_company'] > 0)
                                        <div class="alert alert-warning mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center">
                                            <span>{{ translate('Provider_owes_you') }}:</span>
                                            <strong>{{ with_currency_symbol($revenueSettlement['provider_owes_company']) }}</strong>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary mb-0 py-2 px-2 fz-12">
                                            {{ $revenueSettlement['total_paid'] >= $bookingTotalForPayment ? translate('Settled') : translate('Unpaid_or_partially_paid') }}
                                        </div>
                                    @endif
                                    @else
                                        <div class="alert alert-light border mb-0 fz-12 text-muted">
                                            {{ translate('No_revenue_cancelled_before_service') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @can('booking_can_manage_status')
                        @if($showAddPaymentOnBookingDetails || $bfsShowRecordPaymentButton)
                            <div class="modal fade" id="addPaymentModal-{{ $booking->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="{{ route('admin.booking.add-payment', $booking->id) }}" class="add-payment-form" data-due-amount="{{ $adminAddPaymentRemainingCapacity }}" data-loss-allocation="{{ $booking->isLossMakingFinancialSettlement() ? '1' : '0' }}" @if($bfsAddPayLossCaps !== null) data-loss-cap-provider="{{ $bfsAddPayLossCaps['provider'] }}" data-loss-cap-company="{{ $bfsAddPayLossCaps['company'] }}" @endif novalidate>
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ translate('Add payment') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-danger d-none add-payment-modal-errors mb-3" role="alert"></div>
                                                @if($__bfsScaledLive !== null && $paymentFullyCovered && round($adminAddPaymentRemainingCapacity, 2) > 0.009)
                                                    <div class="alert alert-info fz-12 mb-3 py-2">
                                                        {{ translate('Bfs_add_payment_invoice_recovery_hint') }}
                                                    </div>
                                                @endif
                                                <div class="mb-3">
                                                    <label class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('Due amount') }}: {{ with_currency_symbol($adminAddPaymentRemainingCapacity) }})</small></label>
                                                    <input type="number" step="0.01" min="0.01" max="{{ $adminAddPaymentRemainingCapacity }}" name="amount" class="form-control add-payment-amount" required placeholder="{{ translate('Max') }} {{ with_currency_symbol($adminAddPaymentRemainingCapacity) }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label d-block">{{ translate('Received by') }} <span class="text-danger">*</span></label>
                                                    <div class="d-flex flex-wrap gap-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="received_by" id="addPaymentReceivedProvider--{{ $booking->id }}" value="provider" checked>
                                                            <label class="form-check-label" for="addPaymentReceivedProvider--{{ $booking->id }}">{{ translate('Provider') }}</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="received_by" id="addPaymentReceivedCompany--{{ $booking->id }}" value="company">
                                                            <label class="form-check-label" for="addPaymentReceivedCompany--{{ $booking->id }}">{{ translate('Company') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3 add-payment-company-inflow-wrap d-none">
                                                    <div class="row g-2">
                                                        @include('bookingmodule::admin.booking.partials._admin-company-inflow-payment-method', [
                                                            'instanceId' => 'addpay-' . $booking->id,
                                                            'advancePaymentMethodGroups' => $advancePaymentMethodGroups ?? [],
                                                            'advancePmDisabled' => false,
                                                            'advancePmSelected' => '',
                                                        ])
                                                    </div>
                                                    <div class="mb-0 mt-2">
                                                        <label class="form-label">{{ translate('Reference_Note') }} <span class="text-muted small">({{ translate('Optional') }})</span></label>
                                                        <textarea name="company_inflow_note" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Optional_note') }}"></textarea>
                                                        <small class="text-muted d-block mt-1 fz-11">{{ translate('Reference_note_can_fill_transaction_field') }}</small>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ translate('Date') }}</label>
                                                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                                                </div>
                                                @if($booking->isLossMakingFinancialSettlement())
                                                    <div class="mb-0 add-payment-loss-allocation-wrap border rounded p-3 bg-light">
                                                        <div class="fw-semibold fz-12 mb-2">{{ translate('Bfs_loss_recovery_split_title') }}</div>
                                                        <p class="small text-muted mb-2">{{ translate('Bfs_loss_recovery_split_intro') }}</p>
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label mb-1">{{ translate('Bfs_add_payment_split_provider') }} <span class="text-danger">*</span></label>
                                                                <input type="text" inputmode="decimal" autocomplete="off" name="split_amount_provider"
                                                                    class="form-control add-payment-split-provider"
                                                                    value="{{ old('split_amount_provider', '0') }}">
                                                                @if($bfsAddPayLossCaps !== null)
                                                                    <div class="small text-muted mt-1 mb-0">{{ translate('Bfs_add_payment_split_max_to_provider_loss') }} {{ with_currency_symbol($bfsAddPayLossCaps['provider']) }}</div>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label mb-1">{{ translate('Bfs_add_payment_split_company') }} <span class="text-danger">*</span></label>
                                                                <input type="text" inputmode="decimal" autocomplete="off" name="split_amount_company"
                                                                    class="form-control add-payment-split-company"
                                                                    value="{{ old('split_amount_company', '0') }}">
                                                                @if($bfsAddPayLossCaps !== null)
                                                                    <div class="small text-muted mt-1 mb-0">{{ translate('Bfs_add_payment_split_max_to_company_loss') }} {{ with_currency_symbol($bfsAddPayLossCaps['company']) }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <p class="small text-muted mt-2 mb-0">{{ translate('Bfs_add_payment_split_sum_hint') }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endcan
                </div>
                <div class="col-xl-4 col-md-12 d-flex flex-column booking-overview-min-h-0">
                    <div class="booking-overview-column-inner">
                        <div class="card mb-0 w-100 d-flex flex-column overflow-hidden booking-overview-booking-dates-card">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between gap-1 border-bottom pb-2 mb-2 flex-shrink-0">
                                    <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                        <span class="material-icons title-color fz-16">event</span>
                                        {{ translate('Booking_Dates') }}
                                    </h6>
                                </div>
                                <div class="booking-overview-kv-rows flex-grow-1 booking-overview-min-h-0 overflow-y-auto pb-1 fz-12">
                                    @php
                                        $serviceScheduleLocalValue = \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d\TH:i');
                                        $scheduleHistoriesCount = (int) ($booking?->schedule_histories?->count() ?? 0);
                                    @endphp
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2 mb-0 flex-wrap">
                                        <span class="title-color flex-shrink-0">{{ translate('Schedule_Date') }}</span>
                                        <div class="min-w-0 text-end flex-grow-1" style="max-width: min(100%, 22rem);">
                                            @can('booking_can_manage_status')
                                                @if(!$bookingNotEditable && !in_array($booking->booking_status, ['ongoing', 'completed']))
                                                    <div id="booking-schedule-view-mode">
                                                        <div class="d-flex align-items-center gap-1 justify-content-end flex-wrap">
                                                            <span class="fw-semibold text-break" id="booking-overview-service-schedule">
                                                                {{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}
                                                                @if($scheduleHistoriesCount > 1)
                                                                    <span class="small text-muted ms-1">({{ translate('Edited') }})</span>
                                                                @endif
                                                            </span>
                                                            <button type="button" class="btn btn-link p-0 lh-1 border-0 align-baseline text-decoration-none" id="booking-schedule-edit-toggle" title="{{ translate('Edit') }}" aria-label="{{ translate('Edit') }}">
                                                                <span class="material-icons title-color" style="font-size: 14px;">edit</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div id="booking-schedule-edit-mode" class="d-none">
                                                        <input type="datetime-local" class="form-control form-control-sm"
                                                               name="service_schedule"
                                                               value="{{ $serviceScheduleLocalValue }}"
                                                               id="service_schedule"
                                                               data-original="{{ $serviceScheduleLocalValue }}"
                                                               min="{{ date('Y-m-d\TH:i') }}"
                                                               onchange="service_schedule_update()">
                                                    </div>
                                                @else
                                                    <span class="fw-semibold text-end text-break d-inline-block" id="booking-overview-service-schedule">
                                                        {{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}
                                                        @if($scheduleHistoriesCount > 1)
                                                            <span class="small text-muted ms-1">({{ translate('Edited') }})</span>
                                                        @endif
                                                    </span>
                                                @endif
                                            @else
                                                <span class="fw-semibold text-end text-break d-inline-block" id="booking-overview-service-schedule">
                                                    {{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}
                                                    @if($scheduleHistoriesCount > 1)
                                                        <span class="small text-muted ms-1">({{ translate('Edited') }})</span>
                                                    @endif
                                                </span>
                                            @endcan
                                        </div>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                        <span class="title-color flex-shrink-0">{{ translate('Booking_Placed_On') }}</span>
                                        <span class="fw-semibold text-end text-break min-w-0">{{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</span>
                                    </div>
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                                        <span class="title-color flex-shrink-0">{{ translate('Booking_Otp') }}</span>
                                        <span class="c1 fw-semibold text-capitalize text-end text-break min-w-0" id="booking-overview-booking-otp">{{ $booking?->booking_otp !== null && $booking?->booking_otp !== '' ? $booking->booking_otp : '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card w-100 d-flex flex-column booking-overview-booking-info-card overflow-hidden">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center gap-2 border-bottom pb-2 mb-2 flex-shrink-0">
                                <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                    <span class="material-icons title-color fz-16">info</span>
                                    {{ translate('Booking_Information') }}
                                </h6>
                                @can('booking_edit')
                                    @if(!$bookingNotEditable)
                                        <button type="button" class="btn btn-sm btn--primary" data-bs-toggle="modal"
                                                data-bs-target="#bookingInfoModal--{{ $booking->id }}">
                                            <span class="material-symbols-outlined" style="font-size: 18px;">edit_square</span>
                                            {{ translate('Update') }}
                                        </button>
                                    @endif
                                @endcan
                            </div>
                            <div class="booking-overview-kv-rows flex-grow-1 booking-overview-min-h-0 overflow-y-auto pb-1 fz-12">
                                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                    <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Assignee') }}:</span>
                                    <span id="booking-assignee-display" class="fz-12 text-break text-end min-w-0">
                                        @if($booking->assignee_id && $booking->assignee)
                                            {{ $booking->assignee->first_name }} {{ $booking->assignee->last_name }}
                                            ({{ $booking->assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }})
                                            — {{ $booking->assignee->email ?? $booking->assignee->phone }}
                                        @else
                                            <span class="text-muted">{{ translate('Unassigned') }}</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                    <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Source') }}:</span>
                                    <span id="booking-source-display" class="fz-12 text-break text-end min-w-0">
                                        @switch(strtolower((string)($booking->booking_source ?? 'app')))
                                            @case('app'){{ translate('App') }}@break
                                            @case('call'){{ translate('Call') }}@break
                                            @case('whatsapp'){{ translate('Whatsapp') }}@break
                                            @case('social_media'){{ translate('Social_Media') }}@break
                                            @default{{ ucfirst(strtolower((string)($booking->booking_source ?? 'app'))) }}
                                        @endswitch
                                    </span>
                                </div>
                                <div class="booking-overview-kv-row d-flex justify-content-between align-items-start gap-2">
                                    <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Service_Additional_Details') }}:</span>
                                    <span id="booking-service-description-display" class="fz-12 text-break text-end min-w-0">
                                        {{ $booking->service_description ?: translate('Not_specified') }}
                                    </span>
                                </div>
                            </div>
                            </div>
                        </div>
                        <div class="card mb-0 w-100 d-flex flex-column overflow-hidden booking-overview-right-service-loc-card">
                            <div class="card-body py-3 px-3 d-flex flex-column flex-grow-1 booking-overview-min-h-0 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between gap-1 border-bottom pb-2 mb-2 flex-shrink-0">
                                    <h6 class="c1 mb-0 d-flex align-items-center gap-1 fz-12 text-uppercase">
                                        <span class="material-icons title-color fz-16">map</span>
                                        {{ translate('Service_location') }}
                                    </h6>
                                    @if($serviceAtProviderPlace == 1)
                                        @if($booking->provider_id)
                                            @php
                                                $serviceLocationStack = getProviderSettings(providerId: $booking->provider_id, key: 'service_location', type: 'provider_config');
                                            @endphp
                                            @if(in_array('customer', $serviceLocationStack) && in_array('provider', $serviceLocationStack))
                                                @can('booking_edit')
                                                    @if(!$bookingNotEditable)
                                                        <div data-bs-toggle="modal" data-bs-target="#serviceLocationModal--{{ $booking['id'] }}" class="cursor-pointer" data-toggle="tooltip" data-placement="top">
                                                            <span class="material-symbols-outlined fz-18">edit_square</span>
                                                        </div>
                                                    @endif
                                                @endcan
                                            @endif
                                        @else
                                            @can('booking_edit')
                                                @if(!$bookingNotEditable)
                                                    <div data-bs-toggle="modal" data-bs-target="#serviceLocationModal--{{ $booking['id'] }}" class="cursor-pointer" data-toggle="tooltip" data-placement="top">
                                                        <span class="material-symbols-outlined fz-18">edit_square</span>
                                                    </div>
                                                @endif
                                            @endcan
                                        @endif
                                    @endif
                                </div>
                                <div class="flex-grow-1 booking-overview-min-h-0 overflow-y-auto d-flex flex-column">
                                    <div class="booking-overview-kv-rows pb-1">
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                        <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Zone') }}:</span>
                                        <span class="fz-12 text-break text-end">
                                            @if($booking?->zone?->name)
                                                {{ $booking->zone->name }}@if($booking->zone?->parentZone?->name) ({{ $booking->zone->parentZone->name }})@endif
                                            @else
                                                {{ translate('not_available') }}
                                            @endif
                                        </span>
                                    </div>
                                @if($booking->service_location == 'provider')
                                    @if($booking->provider_id != null)
                                        @if($booking->provider)
                                            <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                                <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Service Location') }}:</span>
                                                <span class="mb-0 fz-12 text-break text-end" title="{{ $booking?->provider?->company_address }}">{{ Str::limit($booking?->provider?->company_address ?? translate('not_available'), 280) }}</span>
                                            </div>
                                        @else
                                            <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                                <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Service Location') }}:</span>
                                                <span class="mb-0 fz-12 text-end">{{ translate('Provider Unavailable') }}</span>
                                            </div>
                                        @endif
                                    @else
                                        <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                            <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Service Location') }}:</span>
                                            <span class="mb-0 fz-12 text-break text-end">{{ translate('The Service Location will be available after this booking accepts or assign to a provider') }}</span>
                                        </div>
                                    @endif
                                @else
                                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2">
                                        <span class="title-color fz-12 fw-semibold flex-shrink-0">{{ translate('Service Location') }}:</span>
                                        <span class="mb-0 fz-12 text-break text-end" title="{{ $booking?->service_address?->address }}">{{ Str::limit($booking?->service_address?->address ?? translate('not_available'), 280) }}</span>
                                    </div>
                                @endif
                                    </div>
                                @if($booking->service_location == 'provider')
                                    <div class="d-flex justify-content-center mt-2 px-1 flex-shrink-0 w-100 min-w-0">
                                        <span class="booking-overview-service-loc-note-pill booking-overview-service-loc-note-pill--at-provider-site" title="{{ translate('Customer has to go to the Provider Location to receive the service') }}">{{ translate('Customer has to go to the Provider Location to receive the service') }}</span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-center mt-2 px-1 flex-shrink-0 w-100 min-w-0">
                                        <span class="booking-overview-service-loc-note-pill booking-overview-service-loc-note-pill--at-customer-site" title="{{ translate('Provider has to go to the Customer Location to provide the service') }}">{{ translate('Provider has to go to the Customer Location to provide the service') }}</span>
                                    </div>
                                @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3 align-items-start">
                <div class="col-12">
                    @if ($__bfsSplitBookingSummary)
                        @php
                            $__snap = $booking->settlement_snapshot;
                            $__visitAmt = round((float) ($__snap['visit_charges_paid'] ?? 0), 2);
                            $__closingAmt = round((float) ($__snap['closing_amount_paid'] ?? 0), 2);
                            $__coVisit = round((float) ($__snap['company_amount_from_visit'] ?? 0), 2);
                            $__prVisit = round((float) ($__snap['provider_amount_from_visit'] ?? 0), 2);
                            $__coClose = round((float) ($__snap['company_amount_from_closing'] ?? 0), 2);
                            $__prClose = round((float) ($__snap['provider_amount_from_closing'] ?? 0), 2);
                            $__retainedTotal = round((float) ($__snap['retained_on_cancel'] ?? $__snap['booking_total'] ?? 0), 2);
                        @endphp
                        <div class="card border-primary mb-3">
                            <div class="card-body pb-5">
                                <h3 class="mb-3 fz-18">{{ $__bfsSplitBookingSummaryComplete ? translate('Bfs_after_complete_settlement_card_title') : translate('Bfs_after_cancel_settlement_card_title') }}</h3>
                                <div class="row justify-content-end">
                                    <div class="col-sm-10 col-md-6 col-xl-5">
                                        <div class="table-responsive">
                                            <table class="table table-md title-color align-right w-100 booking-summary-breakdown mb-4">
                                                <tbody>
                                                    <tr class="booking-summary-row--charge">
                                                        <td class="text-capitalize">{{ translate('Bfs_preview_visiting_charges') }}</td>
                                                        <td class="text--end pe--4">{{ with_currency_symbol($__visitAmt) }}</td>
                                                    </tr>
                                                    <tr class="booking-summary-row--subtotal">
                                                        <td>{{ translate('Bfs_company_commission_visit_line') }}</td>
                                                        <td class="text--end pe--4">{{ with_currency_symbol($__coVisit) }}</td>
                                                    </tr>
                                                    <tr class="booking-summary-row--subtotal">
                                                        <td>{{ translate('Bfs_provider_share_visit_line') }}</td>
                                                        <td class="text--end pe--4">{{ with_currency_symbol($__prVisit) }}</td>
                                                    </tr>
                                                    @if ($__closingAmt > 0.005 || $__coClose > 0.005 || $__prClose > 0.005)
                                                        <tr class="border-top booking-summary-row--charge">
                                                            <td class="text-capitalize">{{ translate('Bfs_preview_closing_amount') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($__closingAmt) }}</td>
                                                        </tr>
                                                        <tr class="booking-summary-row--subtotal">
                                                            <td>{{ translate('Bfs_company_commission_closing_line') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($__coClose) }}</td>
                                                        </tr>
                                                        <tr class="booking-summary-row--subtotal">
                                                            <td>{{ translate('Bfs_provider_share_closing_line') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($__prClose) }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr class="booking-summary-row--grand border-top">
                                                        <td><strong>{{ $__bfsSplitBookingSummaryComplete ? translate('Bfs_decided_charges_grand_total') : translate('Bfs_preview_total_retained') }}</strong></td>
                                                        <td class="text--end pe--4"><strong>{{ with_currency_symbol($__retainedTotal) }}</strong></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <h4 class="h6 text-uppercase text-muted mb-2 fz-12">{{ $__bfsSplitBookingSummaryComplete ? translate('Bfs_after_complete_payments_section_title') : translate('Bfs_after_cancel_payments_section_title') }}</h4>
                                <div class="row justify-content-end">
                                    <div class="col-sm-10 col-md-6 col-xl-5">
                                        <div class="table-responsive">
                                            <table class="table table-md title-color align-right w-100 booking-summary-breakdown">
                                                <tbody>
                                                    @if ($booking->booking_partial_payments->isNotEmpty())
                                                        @php
                                                            $__sumPaidProvider2 = round((float) ($revenueSettlement['amount_received_by_provider'] ?? 0), 2);
                                                            $__sumPaidCompany2 = round((float) ($revenueSettlement['amount_received_by_company'] ?? 0), 2);
                                                            $__sumPaidTotal2 = round((float) ($revenueSettlement['total_paid'] ?? 0), 2);
                                                        @endphp
                                                        @if ($__sumPaidProvider2 > 0)
                                                            <tr class="booking-summary-row--payment-in">
                                                                <td>{{ translate('Paid_to_service_provider') }}</td>
                                                                <td class="text--end pe--4">{{ with_currency_symbol($__sumPaidProvider2) }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($__sumPaidCompany2 > 0)
                                                            <tr class="booking-summary-row--payment-in">
                                                                <td>{{ translate('Paid_to_company') }}</td>
                                                                <td class="text--end pe--4">{{ with_currency_symbol($__sumPaidCompany2) }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($__sumPaidTotal2 > 0)
                                                            <tr class="booking-summary-row--payment-in">
                                                                <td><strong>{{ translate('Total_paid') }}</strong></td>
                                                                <td class="text--end pe--4"><strong>{{ with_currency_symbol($__sumPaidTotal2) }}</strong></td>
                                                            </tr>
                                                        @endif
                                                    @endif
                                                    @include('bookingmodule::admin.booking.partials._refund-amount-summary-rows', ['booking' => $booking, 'variant' => 'details', 'summaryRowClass' => 'booking-summary-row--refund-info'])
                                                    @php
                                                        $dueAmountAfterCancel = get_booking_invoice_due_amount($booking);
                                                    @endphp
                                                    @if ($dueAmountAfterCancel > 0)
                                                        <tr class="booking-summary-row--due">
                                                            <td>{{ translate('Due_Amount') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($dueAmountAfterCancel) }}</td>
                                                        </tr>
                                                    @endif
                                                    @if ($booking->payment_method != 'cash_after_service' && $booking->additional_charge < 0)
                                                        <tr class="booking-summary-row--refund-out">
                                                            <td>{{ translate('Refund') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol(abs($booking->additional_charge)) }}</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="card mb-3">
                        <div class="card-body pb-5">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                                <h3 class="mb-0">{{ $__bfsSplitBookingSummary ? ($__bfsSplitBookingSummaryComplete ? translate('Booking_Summary_before_complete') : translate('Booking_Summary_before_cancel')) : translate('Booking_Summary') }}</h3>
                                @if (in_array($booking['booking_status'], ['pending', 'accepted', 'ongoing', 'on_hold']))
                                    @can('booking_edit')
                                        <button type="button" class="btn btn--primary btn-sm flex-shrink-0" data-bs-toggle="modal"
                                            data-bs-target="#serviceUpdateModal--{{ $booking['id'] }}" data-toggle="tooltip"
                                            title="{{ translate('Add or remove services') }}">
                                            <span class="material-symbols-outlined">edit</span>{{ translate('Edit Services') }}
                                        </button>
                                    @endcan
                                @endif
                            </div>
                            @if ($__bfsSplitBookingSummary)
                                <p class="fz-12 text-muted mb-2">{{ $__bfsSplitBookingSummaryComplete ? translate('Bfs_booking_summary_before_complete_hint') : translate('Bfs_booking_summary_before_cancel_hint') }}</p>
                            @endif

                            <div class="table-responsive border-bottom">
                                <table class="table text-nowrap align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-lg-3">{{ translate('Service') }}</th>
                                            <th>{{ translate('Price') }}</th>
                                            <th>{{ translate('Qty') }}</th>
                                            <th>{{ translate('Discount') }}</th>
                                            @if($bookingHasTax)
                                            <th>{{ company_default_tax_label() }}</th>
                                            @endif
                                            <th class="text--end">{{ translate('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $subTotal = 0;
                                            $extraServicesTotal = 0;
                                            $extraServicesServiceTotal = 0;
                                            $extraServicesSpareTotal = 0;
                                            $extraGrossService = 0.0;
                                            $extraGrossSpare = 0.0;
                                            $extraSpareDiscountSum = 0.0;
                                        @endphp
                                        @foreach ($bookingDetail as $detail)
                                            @php
                                                $detailLineTotal = round(($detail->service_cost * $detail->quantity) - ($detail->discount_amount ?? 0) - ($detail->campaign_discount_amount ?? 0) + ($detail->tax_amount ?? 0), 2);
                                            @endphp
                                            <tr>
                                                <td class="text-wrap ps-lg-3">
                                                    @if (isset($detail->service))
                                                        <div class="d-flex flex-column">
                                                            <a href="{{ route('admin.service.detail', [$detail->service->id]) }}"
                                                                class="fw-bold">{{ Str::limit($detail->service->name, 30) }}</a>
                                                            <div class="text-capitalize">
                                                                @if(isset($detail->variation) && $detail->variation)
                                                                    {{ Str::limit($detail->variation->variant ?? $detail->variant_key, 50) }}
                                                                @else
                                                                    {{ Str::limit($detail->variant_key ?? '', 50) }}
                                                                @endif
                                                            </div>
                                                            <span class="badge badge-primary mt-1" style="width: fit-content;">{{ translate('Service') }}</span>
                                                            @if ($detail->overall_coupon_discount_amount > 0)
                                                                <small
                                                                    class="fz-10 text-capitalize">{{ translate('coupon_discount') }}
                                                                    :
                                                                    -{{ with_currency_symbol($detail->overall_coupon_discount_amount) }}</small>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span
                                                            class="badge badge-pill badge-danger">{{ translate('Service_unavailable') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ with_currency_symbol($detail->service_cost) }}</td>
                                                <td>
                                                    <span>{{ $detail->quantity }}</span>
                                                </td>
                                                <td>
                                                    @if ($detail?->discount_amount > 0)
                                                        {{ with_currency_symbol($detail->discount_amount) }}
                                                    @elseif($detail?->campaign_discount_amount > 0)
                                                        {{ with_currency_symbol($detail->campaign_discount_amount) }}
                                                        <br><span
                                                            class="fz-12 text-capitalize">{{ translate('campaign') }}</span>
                                                    @else
                                                        {{ with_currency_symbol(0) }}
                                                    @endif

                                                </td>
                                                @if($bookingHasTax)
                                                <td>{{ with_currency_symbol($detail->tax_amount) }}</td>
                                                @endif
                                                <td class="text--end">{{ with_currency_symbol($detailLineTotal) }}</td>
                                            </tr>
                                            @php
                                                $subTotal += $detail->service_cost * $detail->quantity;
                                            @endphp
                                        @endforeach
                                        @foreach ($booking->extra_services ?? [] as $extra)
                                            <tr class="table-light">
                                                <td class="text-wrap ps-lg-3">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ Str::limit($extra->title, 40) }}</span>
                                                        @if($extra->details)
                                                            <small class="text-muted">{{ Str::limit($extra->details, 60) }}</small>
                                                        @endif
                                                        <span class="badge badge-{{ $extra->type === 'spare_part' ? 'info' : 'primary' }} mt-1" style="width: fit-content;">
                                                            {{ $extra->type === 'spare_part' ? translate('Spare_Part') : translate('Service') }}
                                                        </span>
                                                        @can('booking_edit')
                                                        @if(!$bookingNotEditable)
                                                        <form method="post" action="{{ route('admin.booking.extra-service.destroy', [$booking->id, $extra->id]) }}" class="d-inline mt-1" onsubmit="return confirm('{{ translate('Remove_this_item') }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">{{ translate('Remove') }}</button>
                                                        </form>
                                                        @endif
                                                        @endcan
                                                    </div>
                                                </td>
                                                <td>{{ with_currency_symbol($extra->price) }}</td>
                                                <td>{{ $extra->quantity }}</td>
                                                <td>{{ with_currency_symbol($extra->discount) }}</td>
                                                @if($bookingHasTax)
                                                <td>—</td>
                                                @endif
                                                <td class="text--end">{{ with_currency_symbol($extra->total) }}</td>
                                            </tr>
                                            @php
                                                $extraLineGross = (float) $extra->price * (int) $extra->quantity;
                                                $extraServicesTotal += $extra->total;
                                                if ($extra->type === \Modules\BookingModule\Entities\BookingExtraService::TYPE_SPARE_PART) {
                                                    $extraServicesSpareTotal += $extra->total;
                                                    $extraGrossSpare += $extraLineGross;
                                                    $extraSpareDiscountSum += (float) ($extra->discount ?? 0);
                                                } else {
                                                    $extraServicesServiceTotal += $extra->total;
                                                    $extraGrossService += $extraLineGross;
                                                }
                                            @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @can('booking_edit')
                            @if(!$bookingNotEditable)
                            <div class="mt-3 mb-3">
                                <button type="button" class="btn btn-sm btn--primary" data-bs-toggle="modal" data-bs-target="#addExtraServiceModal--{{ $booking->id }}">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                                    {{ translate('Add_Extra_Service') }}
                                </button>
                            </div>
                            @endif
                            @endcan
                            @php
                                $grandTotalCalculated = $__bfsSplitBookingSummary
                                    ? round(get_booking_total_amount($booking), 2)
                                    : round(get_booking_payable_total_for_partial_dues($booking), 2);
                                $acDisplayRows = $additionalChargesDisplayRows ?? enrich_booking_additional_charges_breakdown_for_display($booking);
                                $displayBookingServiceDiscount = round((float) ($booking->total_discount_amount ?? 0) + get_booking_extra_service_line_discount_total($booking) + $extraSpareDiscountSum, 2);
                                $catalogGrossSubtotal = round((float) $subTotal, 2);
                                $additionalChargesTotal = round((float) ($booking->extra_fee ?? 0), 2);
                                $summaryGrossTotal = round($catalogGrossSubtotal + $extraGrossService + $extraGrossSpare + $additionalChargesTotal, 2);
                                $couponDiscountAmount = round((float) ($booking->total_coupon_discount_amount ?? 0), 2);
                                $campaignDiscountAmount = round((float) ($booking->total_campaign_discount_amount ?? 0), 2);
                                $referralDiscountAmount = round((float) ($booking->total_referral_discount_amount ?? 0), 2);
                            @endphp
                            <div class="row justify-content-end mt-3">
                                <div class="col-sm-10 col-md-6 col-xl-5">
                                    <div class="table-responsive">
                                        <table class="table table-md title-color align-right w-100 booking-summary-breakdown">
                                            <tbody>
                                                <tr class="booking-summary-row--charge">
                                                    <td class="text-capitalize">{{ translate('service_amount') }}@if($bookingHasTax) <small
                                                            class="fz-12">{{ booking_tax_excluded_bracket_hint() }}</small>@endif</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($catalogGrossSubtotal) }}</td>
                                                </tr>
                                                @if($extraGrossService > 0)
                                                <tr class="booking-summary-row--charge">
                                                    <td class="text-capitalize">{{ translate('Extra_Services') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol(round($extraGrossService, 2)) }}</td>
                                                </tr>
                                                @endif
                                                @if($extraGrossSpare > 0)
                                                <tr class="booking-summary-row--charge">
                                                    <td class="text-capitalize">{{ translate('Spare_Parts') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol(round($extraGrossSpare, 2)) }}</td>
                                                </tr>
                                                @endif
                                                @if ($booking->extra_fee > 0)
                                                    @if(count($acDisplayRows))
                                                        @foreach($acDisplayRows as $acRow)
                                                            @if((float)($acRow['amount'] ?? 0) > 0)
                                                            @php
                                                                $acLineAmount = (float) ($acRow['amount'] ?? 0);
                                                                $acLineAmountInput = number_format($acLineAmount, 2, '.', '');
                                                                $acLineCustomizable = ! empty($acRow['customizable']);
                                                            @endphp
                                                            <tr class="booking-summary-row--charge">
                                                                <td class="text-capitalize">
                                                                    {{ $acRow['name'] ?? translate('Additional_charges') }}
                                                                </td>
                                                                <td class="text--end pe--4">
                                                                    <div class="ac-charge-line-wrap text-end ms-auto" style="max-width: 14rem;">
                                                                        <div class="ac-charge-line-view d-inline-flex align-items-center gap-1 justify-content-end flex-wrap">
                                                                            <span class="ac-charge-line-amount">{{ with_currency_symbol($acLineAmount) }}</span>
                                                                            @can('booking_edit')
                                                                                @if(!$bookingNotEditable && $acLineCustomizable)
                                                                                    <button type="button" class="btn btn-link p-0 border-0 lh-1 text-decoration-none ac-charge-line-edit-btn" title="{{ translate('Edit') }}" aria-label="{{ translate('Edit') }}">
                                                                                        <span class="material-icons title-color" style="font-size: 14px;">edit</span>
                                                                                    </button>
                                                                                @endif
                                                                            @endcan
                                                                        </div>
                                                                        @can('booking_edit')
                                                                            @if(!$bookingNotEditable && $acLineCustomizable)
                                                                                <div class="ac-charge-line-edit d-none mt-1">
                                                                                    <form method="post" action="{{ route('admin.booking.additional-charges.update', $booking->id) }}" class="d-flex flex-column align-items-end gap-1">
                                                                                        @csrf
                                                                                        @method('PUT')
                                                                                        <input type="number" name="ac_line_amount[{{ $acRow['id'] }}]" value="{{ $acLineAmountInput }}" min="0" step="0.01" class="form-control form-control-sm text-end ac-charge-line-input" style="max-width: 7.5rem">
                                                                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                                                                            <button type="submit" class="btn btn-sm btn--primary">{{ translate('Save') }}</button>
                                                                                            <button type="button" class="btn btn-sm btn-outline-secondary ac-charge-line-cancel-btn">{{ translate('cancel') }}</button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            @endif
                                                                        @endcan
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <tr class="booking-summary-row--charge">
                                                            <td class="text-capitalize">{{ translate('Additional_charges') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($booking->extra_fee) }}</td>
                                                        </tr>
                                                    @endif
                                                @endif
                                                <tr class="border-top booking-summary-row--subtotal">
                                                    <td class="text-capitalize fw-semibold">{{ translate('total') }}</td>
                                                    <td class="text--end pe--4 fw-semibold">{{ with_currency_symbol($summaryGrossTotal) }}</td>
                                                </tr>
                                                @if($displayBookingServiceDiscount > 0)
                                                <tr class="booking-summary-row--discount">
                                                    <td class="text-capitalize">{{ translate('service_discount') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($displayBookingServiceDiscount) }}</td>
                                                </tr>
                                                @endif
                                                @if($couponDiscountAmount > 0)
                                                <tr class="booking-summary-row--discount">
                                                    <td class="text-capitalize">{{ translate('coupon_discount') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($couponDiscountAmount) }}</td>
                                                </tr>
                                                @endif
                                                @if($campaignDiscountAmount > 0)
                                                <tr class="booking-summary-row--discount">
                                                    <td class="text-capitalize">{{ translate('campaign_discount') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($campaignDiscountAmount) }}</td>
                                                </tr>
                                                @endif
                                                @if($referralDiscountAmount > 0)
                                                <tr class="booking-summary-row--discount">
                                                    <td class="text-capitalize">{{ translate('Referral Discount') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($referralDiscountAmount) }}</td>
                                                </tr>
                                                @endif
                                                @if($bookingHasTax)
                                                <tr class="booking-summary-row--tax">
                                                    <td>{{ company_default_tax_label() }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($booking->total_tax_amount) }}</td>
                                                </tr>
                                                @endif
                                                <tr class="booking-summary-row--grand">
                                                    <td><strong>{{ translate('Grand_Total') }}</strong></td>
                                                    <td class="text--end pe--4">
                                                        <strong>{{ with_currency_symbol($grandTotalCalculated) }}</strong>
                                                    </td>
                                                </tr>

                                                @if (! $__bfsSplitBookingSummary)
                                                @if ($booking->booking_partial_payments->isNotEmpty())
                                                    @php
                                                        $__sumPaidProvider = round((float) ($revenueSettlement['amount_received_by_provider'] ?? 0), 2);
                                                        $__sumPaidCompany = round((float) ($revenueSettlement['amount_received_by_company'] ?? 0), 2);
                                                        $__sumPaidTotal = round((float) ($revenueSettlement['total_paid'] ?? 0), 2);
                                                    @endphp
                                                    @if ($__sumPaidProvider > 0)
                                                        <tr class="booking-summary-row--payment-in">
                                                            <td>{{ translate('Paid_to_service_provider') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($__sumPaidProvider) }}</td>
                                                        </tr>
                                                    @endif
                                                    @if ($__sumPaidCompany > 0)
                                                        <tr class="booking-summary-row--payment-in">
                                                            <td>{{ translate('Paid_to_company') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($__sumPaidCompany) }}</td>
                                                        </tr>
                                                    @endif
                                                    @if ($__sumPaidTotal > 0)
                                                        <tr class="booking-summary-row--payment-in">
                                                            <td><strong>{{ translate('Total_paid') }}</strong></td>
                                                            <td class="text--end pe--4"><strong>{{ with_currency_symbol($__sumPaidTotal) }}</strong></td>
                                                        </tr>
                                                    @endif
                                                @endif

                                                @include('bookingmodule::admin.booking.partials._refund-amount-summary-rows', ['booking' => $booking, 'variant' => 'details', 'summaryRowClass' => 'booking-summary-row--refund-info'])

                                                @php
                                                $dueAmount = get_booking_invoice_due_amount($booking);
                                                @endphp

                                                @if ($dueAmount > 0)
                                                    <tr class="booking-summary-row--due">
                                                        <td>{{ translate('Due_Amount') }}</td>
                                                        <td class="text--end pe--4">
                                                            {{ with_currency_symbol($dueAmount) }}</td>
                                                    </tr>
                                                @endif

                                                @if ($booking->payment_method != 'cash_after_service' && $booking->additional_charge < 0)
                                                    <tr class="booking-summary-row--refund-out">
                                                        <td>{{ translate('Refund') }}</td>
                                                        <td class="text--end pe--4">
                                                            {{ with_currency_symbol(abs($booking->additional_charge)) }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row gy-3 align-items-start">
                <div class="col-lg-8 col-xl-7 d-flex flex-column gap-3 align-items-stretch">
                    @can('booking_can_manage_status')
                        @if(!$bookingNotEditable)
                            <div class="d-none" aria-hidden="true">
                                @php
                                    $__statusSelectNext = booking_admin_allowed_next_statuses_for_booking($booking);
                                    $__statusCashBlock = $booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && $booking->total_booking_amount >= $maxBookingAmount;
                                @endphp
                                <select class="js-select without-search" id="booking_status" data-current="{{ $booking->booking_status }}" data-can-complete="{{ booking_can_be_completed($booking) ? '1' : '0' }}">
                                    <option value="0" disabled selected>{{ translate('Booking_Status') }}: {{ ucwords(str_replace('_', ' ', $booking->booking_status)) }}</option>
                                    @foreach ($__statusSelectNext as $__selSt)
                                        @php
                                            $__optDisabled = $__statusCashBlock && in_array($__selSt, ['pending', 'ongoing', 'completed'], true);
                                            if ($__selSt === 'completed' && ! booking_can_be_completed($booking)) {
                                                $__optDisabled = true;
                                            }
                                            $__optLabel = match ($__selSt) {
                                                'accepted' => translate('Accept_Booking'),
                                                'pending' => translate('Mark_as_Pending'),
                                                'ongoing' => translate('Mark_as_Ongoing'),
                                                'on_hold' => ($booking->booking_status ?? '') === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold'),
                                                'completed' => translate('Complete_Booking'),
                                                'canceled', 'cancelled' => translate('Cancel_Booking'),
                                                default => ucwords(str_replace('_', ' ', $__selSt)),
                                            };
                                        @endphp
                                        <option value="{{ $__selSt }}" @if($__optDisabled) disabled @endif>{{ $__optLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endcan

                    @can('booking_can_manage_status')
                                @if(in_array($booking->booking_status, ['canceled', 'cancelled', 'refunded'], true) && isset($maxRefundAmount) && $maxRefundAmount > 0)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 form-control py-2 px-3 w-100">
                                        <span class="title-color flex-shrink-0">{{ translate('Refund') }}</span>
                                        <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto min-w-0">
                                            <span class="text-muted text-break">{{ translate('Remaining_refundable') }}: <strong>{{ with_currency_symbol($maxRefundAmount) }}</strong></span>
                                            <button type="button" class="btn btn--danger btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#refundModal-{{ $booking->id }}">{{ translate('Refund customer') }}</button>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="refundModal-{{ $booking->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post" action="{{ route('admin.booking.refund', $booking->id) }}" class="refund-form" data-max-amount="{{ $maxRefundAmount }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ translate('Refund customer') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Refund amount') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('Max') }}: {{ with_currency_symbol($maxRefundAmount) }})</small></label>
                                                            <input type="number" step="0.01" min="0.01" max="{{ $maxRefundAmount }}" name="amount" class="form-control refund-amount" required placeholder="{{ translate('Max') }} {{ with_currency_symbol($maxRefundAmount) }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Transaction_ID') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="transaction_id" class="form-control" maxlength="100" required placeholder="{{ translate('Gateway or manual reference') }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Reference_Note') }} <span class="text-muted small">({{ translate('Optional') }})</span></label>
                                                            <textarea name="reference_note" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Optional_note') }}"></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Date') }}</label>
                                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                                                        </div>
                                                        <p class="small text-muted">{{ translate('Refund_modal_ledger_hint') }}</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                        <button type="submit" class="btn btn--danger">{{ translate('Refund') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                        </div>
                                    </div>
                                @endif
                            @endcan

                            @if($booking->payment_method == 'offline_payment')
                                <div class="mt-3 border border-color-primary">
                                    <div class="card text-center">
                                        <div class="card-header">
                                            <h5 class="font-weight-bold">{{ translate('Verification of Offline Payment') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            @if($booking->booking_offline_payments->isNotEmpty())
                                                <div class="d-flex gap-1 flex-column">
                                                    @php
                                                        $offlinePaymentNote = '';
                                                    @endphp
                                                    @foreach ($booking?->booking_offline_payments?->first()?->customer_information ?? [] as $key => $item)
                                                        <div class="d-flex gap-2">
                                                            @if ($key != 'payment_note' )
                                                                <span class="w-100px d-flex justify-content-start">{{ translate($key) }}</span>
                                                                <span>: {{ translate($item) }}</span>
                                                            @endif
                                                        </div>
                                                        @php
                                                            $offlinePaymentNote = ($key == 'payment_note') ? $item : $offlinePaymentNote;
                                                        @endphp
                                                    @endforeach
                                                </div>
                                                @if($offlinePaymentNote != '')
                                                    <div class="badge-warning px-3 py-3 rounded title-color mt-3">
                                                        <span>
                                                            <span class="fw-semibold"> # {{ translate('Payment Note') }}:  </span>
                                                            {{ $offlinePaymentNote }}
                                                        </span>
                                                    </div>
                                               @endif

                                                @if($booking->booking_offline_payments?->first()?->payment_status == 'pending')
                                                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                                                        <button class="btn badge-danger flex-grow-1 py-3" data-bs-toggle="modal"
                                                                data-bs-target="#deniedModal-{{$booking->id}}">{{ translate('deny') }}</button>
                                                        <button class="btn badge-info flex-grow-1 py-3 offline-payment">{{ translate('approve') }}</button>
                                                    </div>
                                                @elseif($booking->booking_offline_payments?->first()?->payment_status == 'denied')
                                                    @if($booking['booking_status'] != 'canceled')
                                                        <div class="d-flex flex-column gap-2 mt-4">
                                                            <button class="btn badge-info w-100 py-3 switch-to-cash-after-service">{{ translate('Switch to Cash after Service') }}</button>
                                                        </div>
                                                    @endif
                                                @endif

                                            @else
                                                <img src="{{ asset('assets/admin-module/img/offline-payment.png') }}" alt="Payment Icon" class="mb-3">
                                                <p class="text-muted">{{ translate('Customer did not submit any payment information yet') }}</p>
                                                @if($booking['booking_status'] != 'canceled')
                                                    <div class="d-flex flex-column gap-2 mt-4">
                                                        <button class="btn badge-info w-100 py-3 switch-to-cash-after-service">{{ translate('Switch to Cash after Service') }}</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(($booking->booking_status ?? '') === 'completed' && ! $booking->blocksAdminCommissionOverrideAndCompensation())
                                @include('bookingmodule::admin.booking.partials.details._compensation-box', ['booking' => $booking])
                            @endif

                            @if ($booking->evidence_photos)
                                <div class="mt-3 c1-light-bg radius-10 py-3 px-3">
                                    <h4 class="mb-2 h6">{{ translate('uploaded_Images') }}</h4>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach ($booking->evidence_photos_full_path ?? [] as $key => $img)
                                            <img width="100" class="max-height-100" src="{{ $img }}" alt="{{ translate('evidence-photo') }}">
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(! $booking->blocksAdminCommissionOverrideAndCompensation())
                                @include('bookingmodule::admin.booking.partials.details._booking-commission-override', [
                                    'booking' => $booking,
                                    'bfsDefaultCustomAdminCommission' => $bfsDefaultCustomAdminCommission ?? 0,
                                ])
                            @endif
                </div>
            </div>
        </div>
    </div>

    @include('bookingmodule::admin.booking.partials.details._update-customer-address-modal')
    @if($booking->service_address_id)
        @include('bookingmodule::admin.booking.partials.details._service-address-modal')
    @endif

    @include('bookingmodule::admin.booking.partials.details._service-location-modal')


    @include('bookingmodule::admin.booking.partials.details._service-modal')

    @include('bookingmodule::admin.booking.partials._booking-status-reason-modal')
    @can('booking_can_manage_status')
        @if((int)($booking->is_repeated ?? 0) === 0 && ! $bookingNotEditable
            && (($booking->booking_status ?? '') === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking)))
            @include('bookingmodule::admin.booking.partials._financial-settlement-modal', [
                'booking' => $booking,
                'financialSettlementOutcomes' => $financialSettlementOutcomes ?? [],
                'defaultVisitFeeCompanyPercent' => $defaultVisitFeeCompanyPercent ?? 20,
                'bookingCancellationReasons' => $bookingCancellationReasons ?? collect(),
                'bfsAllowCollectPayment' => (string) ($booking->booking_status ?? '') === 'ongoing'
                    || (
                        strtolower((string) ($booking->booking_status ?? '')) === 'on_hold'
                        && booking_on_hold_is_after_visit_from_ongoing($booking)
                    ),
                'advancePaymentMethodGroups' => $advancePaymentMethodGroups ?? [],
                'bfsAddPayLossCaps' => $bfsAddPayLossCaps,
            ])
        @endif
    @endcan

    <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data" id="modal-data-info">
                @include('bookingmodule::admin.booking.partials.details.provider-info-modal-data')
            </div>
        </div>
    </div>
    <div class="modal fade" id="servicemanModal" tabindex="-1" aria-labelledby="servicemanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data1" id="modal-data-info1">
                @include('bookingmodule::admin.booking.partials.details.serviceman-info-modal-data')
            </div>
        </div>
    </div>

    @include('providermanagement::admin.partials.provider-performance-feedback-modal')
    @include('providermanagement::admin.partials.customer-performance-feedback-modal')

    <div class="modal fade" id="deniedModal-{{$booking->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body pt-5 p-md-5">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="d-flex justify-content-center mb-4">
                        <img width="75" height="75" src="{{asset('assets/admin-module/img/icons/info-round.svg')}}" class="rounded-circle" alt="">
                    </div>

                    <h3 class="text-start mb-1 fw-medium text-center">{{translate('Are you sure you want to deny?')}}</h3>
                    <p class="text-start fs-12 fw-medium text-muted text-center">{{translate('Please insert the deny note for this payment request')}}</p>
                    <form method="post" action="{{route('admin.booking.offline-payment.verify',['booking_id' => $booking->id, 'payment_status' => 'denied'])}}">
                        @csrf
                        <div class="form-floating">
                            <textarea class="form-control h-69px" placeholder="{{translate('Type here your note')}}" name="note" id="add-your-note" maxlength="255" required></textarea>
                            <label for="add-your-note" class="d-flex align-items-center gap-1">{{translate('Deny Note')}}</label>
                            <div class="d-flex justify-content-center mt-3 gap-3">
                                <button type="button" class="btn btn--secondary min-w-92px px-2" data-bs-dismiss="modal" aria-label="Close">{{translate('Not Now')}}</button>
                                <button type="submit" class="btn btn-primary min-w-92px">{{translate('Submit')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        var pkAdminAdvanceMethodConfig = @json(\Modules\BookingModule\Services\AdminCompanyInflowPaymentService::fieldConfigMapFromGroups($advancePaymentMethodGroups ?? []));

        function pkApmDynInitialVal(f, $form, useInitial) {
            if (!useInitial) {
                return '';
            }
            if (f.input_name === 'advance_transaction_id') {
                var $el = $form.find('input[name="advance_transaction_id"]');
                return $el.length ? String($el.val() || '') : '';
            }
            var m = String(f.input_name || '').match(/^advance_method_fields\[([^\]]+)\]$/);
            if (m) {
                var $el2 = $form.find('[name="advance_method_fields[' + m[1] + ']"]');
                return $el2.length ? String($el2.val() || '') : '';
            }
            return '';
        }

        function pkApmRenderDynamic($scope, selectedKey, $form, opts) {
            opts = opts || {};
            var useInitial = !!opts.useInitial;
            var $box = $scope.find('.pk-apm-dynamic-fields');
            $box.empty();
            if (!selectedKey || !pkAdminAdvanceMethodConfig[selectedKey]) {
                return;
            }
            var cfg = pkAdminAdvanceMethodConfig[selectedKey];
            var fields = cfg.fields || [];
            fields.forEach(function (f) {
                var fid = 'pkdyn-' + String(f.name || '').replace(/[^a-zA-Z0-9_-]/g, '_') + '-' + String($scope.data('pk-apm-instance') || 'x');
                var val = pkApmDynInitialVal(f, $form, useInitial);
                var $col = $('<div class="col-md-6"></div>');
                var $grp = $('<div class="mb-0"></div>');
                var req = !!f.required;
                var $label = $('<label class="form-label" for="' + fid + '"></label>');
                $label.text(f.label || '');
                if (req) {
                    $label.append(' <span class="text-danger">*</span>');
                }
                var $input = $('<input type="text" class="form-control" autocomplete="off">')
                    .attr('id', fid)
                    .attr('name', f.input_name || '')
                    .attr('placeholder', f.placeholder || '')
                    .val(val);
                if (req) {
                    $input.attr('required', 'required');
                }
                $grp.append($label).append($input);
                $col.append($grp);
                $box.append($col);
            });
        }

        function pkApmTier2Visibility($scope) {
            var t1 = $scope.find('.pk-apm-tier1:checked').val();
            $scope.find('.pk-apm-tier2-digital-wrap').toggleClass('d-none', t1 !== 'digital');
            $scope.find('.pk-apm-tier2-offline-wrap').toggleClass('d-none', t1 !== 'offline');
        }

        function pkApmUpdateHidden($scope) {
            var t1 = $scope.find('.pk-apm-tier1:checked').val();
            var $h = $scope.find('.pk-apm-hidden');
            var v = '';
            if (t1 === 'cas') {
                v = 'cash_after_service';
            } else if (t1 === 'digital') {
                v = ($scope.find('.pk-apm-tier2-digital:checked').val() || '').trim();
            } else if (t1 === 'offline') {
                v = ($scope.find('.pk-apm-tier2-offline:checked').val() || '').trim();
            }
            $h.val(v);
        }

        function pkApmSync($scope, $form, opts) {
            opts = opts || {};
            var hydrate = !!opts.hydrateFromInitial;
            pkApmUpdateHidden($scope);
            var v = ($scope.find('.pk-apm-hidden').val() || '').trim();
            pkApmRenderDynamic($scope, v, $form, { useInitial: hydrate });
        }

        function pkApmInitScope($scope, $form) {
            $scope.find('.pk-apm-tier1').off('change.pkApm').on('change.pkApm', function (e, payload) {
                var hydrate = payload && payload.hydrateFromInitial;
                if (!hydrate) {
                    $scope.find('.pk-apm-tier2-digital').prop('checked', false);
                    $scope.find('.pk-apm-tier2-offline').prop('checked', false);
                }
                var t1 = $scope.find('.pk-apm-tier1:checked').val();
                pkApmTier2Visibility($scope);
                if (!hydrate && t1 === 'digital' && $scope.find('.pk-apm-tier2-digital').length === 1) {
                    $scope.find('.pk-apm-tier2-digital').first().prop('checked', true);
                }
                if (!hydrate && t1 === 'offline' && $scope.find('.pk-apm-tier2-offline').length === 1) {
                    $scope.find('.pk-apm-tier2-offline').first().prop('checked', true);
                }
                pkApmSync($scope, $form, { hydrateFromInitial: !!hydrate });
            });
            $scope.find('.pk-apm-tier2-digital, .pk-apm-tier2-offline').off('change.pkApm').on('change.pkApm', function (e, payload) {
                pkApmSync($scope, $form, { hydrateFromInitial: !!(payload && payload.hydrateFromInitial) });
            });
        }

        function pkApmBindForm($form) {
            $form.find('.pk-apm-scope').each(function () {
                var $scope = $(this);
                pkApmInitScope($scope, $form);
                pkApmTier2Visibility($scope);
                pkApmSync($scope, $form, {});
            });
        }

        /** Escape double quotes for jQuery attribute selectors [name="..."] */
        function pkApmEscapeSelectorAttr(s) {
            return String(s || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        }

        /**
         * Copy Reference note into empty transaction / offline required fields (matches server validateAdvanceFollowUp).
         */
        function pkApmBackfillCompanyInflowNoteIntoAdvanceFields($form) {
            var note = ($form.find('textarea[name="company_inflow_note"]').val() || '').trim();
            if (!note) {
                return;
            }
            var $cw = $form.find('.add-payment-company-inflow-wrap');
            var hv = ($cw.find('.pk-apm-hidden').first().val() || '').trim();
            if (!hv || !pkAdminAdvanceMethodConfig[hv]) {
                return;
            }
            var $tidInDyn = $cw.find('.pk-apm-dynamic-fields').find('input[name="advance_transaction_id"]');
            if ($tidInDyn.length && !($tidInDyn.val() || '').trim()) {
                $tidInDyn.val(note);
            }
            if (hv.indexOf('offline:') !== 0) {
                return;
            }
            var cfg = pkAdminAdvanceMethodConfig[hv];
            var fields = cfg.fields || [];
            var requiredFields = [];
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].required && fields[i].input_name) {
                    requiredFields.push(fields[i]);
                }
            }
            var txnLike = function (name) {
                return /transaction|trx|utr|reference|ref_no|ref_id|payment_id|receipt|voucher|txn|utr_no|upi/i.test(String(name || ''));
            };
            var filled = false;
            for (var j = 0; j < requiredFields.length; j++) {
                var f = requiredFields[j];
                if (!txnLike(f.name)) {
                    continue;
                }
                var $inp = $cw.find('.pk-apm-dynamic-fields').find('[name="' + pkApmEscapeSelectorAttr(f.input_name) + '"]');
                if ($inp.length && !($inp.val() || '').trim()) {
                    $inp.val(note);
                    filled = true;
                    break;
                }
            }
            if (!filled && requiredFields.length === 1) {
                var f1 = requiredFields[0];
                var $in1 = $cw.find('.pk-apm-dynamic-fields').find('[name="' + pkApmEscapeSelectorAttr(f1.input_name) + '"]');
                if ($in1.length && !($in1.val() || '').trim()) {
                    $in1.val(note);
                }
            }
        }

        $(document).on('click', '.wa-open-admin-chat', function (e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.data('wa-opening')) {
                return;
            }
            var phone = $btn.data('phone');
            var url = $btn.data('prepare-url');
            if (!phone || !url) {
                return;
            }
            $btn.data('wa-opening', true);
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    phone: String(phone),
                },
                success: function (res) {
                    if (res && res.redirect_url) {
                        window.location.href = res.redirect_url;
                        return;
                    }
                    $btn.data('wa-opening', false);
                    toastr.error('{{ translate('Something went wrong') }}');
                },
                error: function (xhr) {
                    $btn.data('wa-opening', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : '{{ translate('Something went wrong') }}';
                    toastr.error(msg);
                },
            });
        });

        $('.switcher_input').on('click', function() {
            let paymentStatus = $(this).is(':checked') === true ? 1 : 0;
            payment_status_change(paymentStatus)
        })

        // Provider performance feedback must be submitted before reassigning/changing provider.
        let pendingReassignProviderId = null;
        let pendingPostFeedbackAction = null; // 'reload' | 'reassign'

        const bookingContextId = @json($booking->id);
        const bookingCurrentProviderId = @json($booking->provider_id);
        const bookingCustomerId = @json($booking->customer_id);

        const skipBookingFeedbackUrl = @json(route('admin.provider.booking-admin-feedback.skip'));

        function postBookingAdminFeedbackSkip(side, done) {
            $.ajax({
                url: skipBookingFeedbackUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    booking_id: bookingContextId,
                    side: side
                },
                success: function () {
                    if (typeof done === 'function') {
                        done();
                    } else {
                        location.reload();
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Something went wrong') }}');
                }
            });
        }

        $('#customerPerformanceFeedbackSkip').on('click', function () {
            postBookingAdminFeedbackSkip('customer', function () {
                const modalEl = document.getElementById('customerPerformanceFeedbackModal');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                location.reload();
            });
        });

        function openCustomerPerformanceFeedbackModal(customerId, actionType = 'completed') {
            if (!customerId) {
                toastr.error('{{ translate('Customer not found for feedback.') }}');
                return;
            }
            $('#customerPerformanceContextBookingId').val(bookingContextId);
            $('#customerPerformanceCustomerId').val(customerId);
            $('#customerPerformanceActionType').val(actionType === 'canceled' ? 'cancelled' : actionType);
            $('#customerPerformanceNotes').val('');
            $('#customerPerformanceFeedbackForm input[type="radio"]').prop('checked', false);
            $('#customerPerformanceFeedbackForm input[type="checkbox"]').prop('checked', false);

            document.querySelectorAll('.modal.show').forEach((m) => {
                if (m?.id !== 'customerPerformanceFeedbackModal') {
                    bootstrap.Modal.getInstance(m)?.hide();
                }
            });
            $('.modal-backdrop').remove();

            const modalEl = document.getElementById('customerPerformanceFeedbackModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function openProviderPerformanceFeedbackModal(evaluatedProviderId, actionType = 'completed') {
            $('#providerPerformanceContextBookingId').val(bookingContextId);
            $('#providerPerformanceProviderId').val(evaluatedProviderId);
            $('#providerPerformanceActionType').val(actionType === 'canceled' ? 'cancelled' : actionType);
            $('#providerPerformanceNotes').val('');
            $('#providerPerformanceFeedbackForm input[type="radio"]').prop('checked', false);
            $('#providerPerformanceFeedbackForm input[type="checkbox"]').prop('checked', false);

            // Prevent multiple Bootstrap modal backdrops from blocking clicks
            // (e.g. provider reassign modal still open when opening feedback).
            document.querySelectorAll('.modal.show').forEach((m) => {
                if (m?.id !== 'providerPerformanceFeedbackModal') {
                    bootstrap.Modal.getInstance(m)?.hide();
                }
            });
            $('.modal-backdrop').remove();

            const modalEl = document.getElementById('providerPerformanceFeedbackModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function reassignProviderAfterFeedback(providerId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/provider/reassign-provider') }}' + '/' + bookingId;
            const sortOption = document.querySelector('input[name="sort"]:checked')?.value ?? 'default';
            const searchTerm = $('.search-form-input').val() ?? '';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    sort_by: sortOption,
                    booking_id: bookingId,
                    search: searchTerm,
                    provider_id: providerId
                },
                beforeSend: function () {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function (response) {
                    if (response?.view) {
                        $('.modal-content-data').html(response.view);
                    }
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function () {
                        location.reload();
                    }, 600);
                },
                error: function (xhr) {
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Failed to load') }}');
                }
            });
        }

        $('#providerPerformanceFeedbackForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const route = $form.data('feedback-route');

            // Some actions may open the modal without setting pendingPostFeedbackAction.
            // Default to 'reassign' if a provider id is queued, otherwise just reload.
            if (!pendingPostFeedbackAction) {
                pendingPostFeedbackAction = pendingReassignProviderId ? 'reassign' : 'reload';
            }

            $.ajax({
                url: route,
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                beforeSend: function () {
                    $('#providerPerformanceFeedbackSubmit').prop('disabled', true);
                },
                success: function () {
                    $('#providerPerformanceFeedbackSubmit').prop('disabled', false);
                    const modalEl = document.getElementById('providerPerformanceFeedbackModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();

                    if (pendingPostFeedbackAction === 'reassign') {
                        const providerId = pendingReassignProviderId;
                        pendingReassignProviderId = null;
                        pendingPostFeedbackAction = null;
                        if (providerId) {
                            if (typeof window.updateProvider === 'function') {
                                window.updateProvider(providerId);
                            } else {
                                reassignProviderAfterFeedback(providerId);
                            }
                            return;
                        }
                    }

                    pendingReassignProviderId = null;
                    pendingPostFeedbackAction = null;
                    location.reload();
                },
                error: function (xhr) {
                    $('#providerPerformanceFeedbackSubmit').prop('disabled', false);
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Failed to store feedback') }}');
                }
            });
        });

        $(document).on('click', '.reassign-provider', function() {
            let newProviderId = $(this).data('provider-reassign');
            pendingReassignProviderId = newProviderId;
            pendingPostFeedbackAction = 'reassign';

            // Evaluate the currently assigned provider (if present); otherwise evaluate the provider being assigned.
            const evaluatedProviderId = bookingCurrentProviderId ?? newProviderId;
            if (!evaluatedProviderId) {
                toastr.error('{{ translate('Provider not found for feedback.') }}');
                return;
            }

            openProviderPerformanceFeedbackModal(evaluatedProviderId, 'provider_changed');
        })

        $('.open-feedback-manual').on('click', function() {
            if (!bookingCurrentProviderId) {
                toastr.error('{{ translate('Provider not found for feedback.') }}');
                return;
            }
            pendingPostFeedbackAction = 'reload';
            const st = @json($booking->booking_status);
            const at = st === 'canceled' ? 'canceled' : 'completed';
            openProviderPerformanceFeedbackModal(bookingCurrentProviderId, at);
        });

        $('.open-customer-feedback-manual').on('click', function() {
            pendingPostFeedbackAction = 'reload';
            const st = @json($booking->booking_status);
            const at = st === 'canceled' ? 'canceled' : 'completed';
            openCustomerPerformanceFeedbackModal(bookingCustomerId, at);
        });

        $('#customerPerformanceFeedbackForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const type = $form.find('input[name="incident_type"]:checked').val();
            if (!type) {
                toastr.error('{{ translate('Please select a feedback type.') }}');
                return;
            }
            const route = $form.data('feedback-route');
            pendingPostFeedbackAction = 'reload';

            $.ajax({
                url: route,
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                beforeSend: function () {
                    $('#customerPerformanceFeedbackSubmit').prop('disabled', true);
                },
                success: function () {
                    $('#customerPerformanceFeedbackSubmit').prop('disabled', false);
                    const modalEl = document.getElementById('customerPerformanceFeedbackModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    location.reload();
                },
                error: function (xhr) {
                    $('#customerPerformanceFeedbackSubmit').prop('disabled', false);
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Failed to store feedback') }}');
                }
            });
        });

        $('.reassign-serviceman').on('click', function() {
            let id = $(this).data('serviceman-reassign');
            updateServiceman(id)
        })

        $('.offline-payment').on('click', function() {
            let route = '{{ route('admin.booking.offline-payment.verify', ['booking_id' => $booking->id]) }}'+ '&payment_status=' + 'approved';
            route_alert_reload(route, '{{ translate('Want to verify the payment') }}', true);
        })

        @if ($booking->booking_status == 'pending')
            $(document).ready(function() {
                selectElementVisibility('serviceman_assign', false);
                selectElementVisibility('payment_status', false);
            });
        @endif

        $("#booking_status").change(function() {
            var $select = $("#booking_status");
            var booking_status = $select.val();
            var previous_status = $select.data('current');
            if (booking_status && booking_status !== '0') {
                if (booking_status === 'completed' && $select.data('can-complete') === '0') {
                    toastr.error('{{ translate('Booking cannot be completed until full payment is received.') }}', { CloseButton: true, ProgressBar: true });
                    $select.val(previous_status).trigger('change');
                    if ($select.next(".select2-container").length) {
                        $select.next(".select2-container").find(".select2-selection__rendered").text($select.find("option:selected").text());
                    }
                    return;
                }
                if (typeof bookingAdminStatusNeedsReason === 'function' && bookingAdminStatusNeedsReason(booking_status, previous_status)) {
                    $select.val(previous_status);
                    if ($select.next(".select2-container").length) {
                        $select.next(".select2-container").find(".select2-selection__rendered").text($select.find("option:selected").text());
                    }
                    if (typeof bookingAdminOpenStatusReasonModal === 'function') {
                        bookingAdminOpenStatusReasonModal(booking_status, previous_status);
                    }
                    return;
                }
                var route = '{{ route('admin.booking.status_update', [$booking->id]) }}' + '?booking_status=' + booking_status;
                var message = booking_status === 'canceled'
                    ? '{{ translate('Please contact the customer before proceeding with the cancellation process.') }}'
                    : '{{ translate('want_to_update_status') }}';
                update_booking_details(route, message, 'booking_status', booking_status, previous_status);
            } else {
                toastr.error('{{ translate('choose_proper_status') }}');
            }
        });

        $(document).on('click', '.booking-status-overview-btn:not(:disabled)', function() {
            var status = $(this).data('status');
            var $select = $('#booking_status');
            if (!$select.length) {
                return;
            }
            var previous_status = $select.data('current');
            if (String(status) === String(previous_status)) {
                return;
            }
            if (status === 'completed' && $select.data('can-complete') === '0') {
                toastr.error('{{ translate('Booking cannot be completed until full payment is received.') }}', { CloseButton: true, ProgressBar: true });
                return;
            }
            $select.val(status);
            if ($select.val() !== String(status)) {
                toastr.error('{{ translate('Something went wrong. Please try again.') }}', { CloseButton: true, ProgressBar: true });
                return;
            }
            if ($select.next('.select2-container').length) {
                $select.next('.select2-container').find('.select2-selection__rendered').text($select.find('option:selected').text());
            }
            $select.trigger('change');
        });

        $('#booking-schedule-edit-toggle').on('click', function () {
            $('#booking-schedule-view-mode').addClass('d-none');
            $('#booking-schedule-edit-mode').removeClass('d-none');
            setTimeout(function () {
                $('#service_schedule').trigger('focus');
            }, 0);
        });

        $(document).on('click', '.ac-charge-line-edit-btn', function () {
            $('.ac-charge-line-wrap').each(function () {
                var $w = $(this);
                $w.find('.ac-charge-line-edit').addClass('d-none');
                $w.find('.ac-charge-line-view').removeClass('d-none');
                var $inp = $w.find('.ac-charge-line-input');
                if ($inp.length && $inp.data('original') !== undefined) {
                    $inp.val($inp.data('original'));
                }
            });
            var $wrap = $(this).closest('.ac-charge-line-wrap');
            var $input = $wrap.find('.ac-charge-line-input');
            $input.data('original', $input.val());
            $wrap.find('.ac-charge-line-view').addClass('d-none');
            $wrap.find('.ac-charge-line-edit').removeClass('d-none');
            $input.trigger('focus');
        });
        $(document).on('click', '.ac-charge-line-cancel-btn', function () {
            var $wrap = $(this).closest('.ac-charge-line-wrap');
            var $input = $wrap.find('.ac-charge-line-input');
            if ($input.length && $input.data('original') !== undefined) {
                $input.val($input.data('original'));
            }
            $wrap.find('.ac-charge-line-edit').addClass('d-none');
            $wrap.find('.ac-charge-line-view').removeClass('d-none');
        });

        function bookingScheduleExitEditMode() {
            var $in = $('#service_schedule');
            if (!$in.length) {
                return;
            }
            $in.val($in.data('original'));
            $('#booking-schedule-edit-mode').addClass('d-none');
            $('#booking-schedule-view-mode').removeClass('d-none');
        }

        $("#serviceman_assign").change(function() {
            var serviceman_id = $("#serviceman_assign option:selected").val();
            if (serviceman_id !== 'no_serviceman') {
                var route = '{{ route('admin.booking.serviceman_update', [$booking->id]) }}' + '?serviceman_id=' +
                    serviceman_id;

                update_booking_details(route, '{{ translate('want_to_assign_the_serviceman') }}?',
                    'serviceman_assign', serviceman_id);
            } else {
                toastr.error('{{ translate('choose_proper_serviceman') }}');
            }
        });

        function payment_status_change(payment_status) {
            var route = '{{ route('admin.booking.payment_update', [$booking->id]) }}' + '?payment_status=' +
                payment_status;
            update_booking_details(route, '{{ translate('want_to_update_status') }}', 'payment_status', payment_status);
        }

        function service_schedule_update() {
            var $input = $("#service_schedule");
            if (!$input.length) {
                return;
            }
            var service_schedule = $input.val();
            var original = $input.data('original');

            if (!service_schedule) {
                $input.val(original);
                return;
            }

            // Normalize formats (replace space with 'T' for parsing)
            var newDate = new Date(service_schedule);
            var originalDate = new Date(String(original).replace(" ", "T"));
            var now = new Date();

            // Compare with current time
            if (newDate < now) {
                toastr.error("Reschedule cannot be earlier than the current time");
                $input.val(original);
                return;
            }

            // Compare with original schedule
            if (newDate < originalDate) {
                toastr.error("Reschedule cannot be earlier than the original schedule");
                $input.val(original);
                return;
            }

            var route = '{{ route('admin.booking.schedule_update', [$booking->id]) }}' + '?service_schedule=' + service_schedule;

            update_booking_details(route, '{{ translate('want_to_update_the_booking_schedule') }}', 'service_schedule', service_schedule);
        }

        $(".switch-to-cash-after-service").on('click', function() {
            var payment_method = 'cash_after_service';
            var route = '{{ route('admin.booking.switch-payment-method', [$booking->id]) }}' + '?payment_method=' + payment_method;
            update_booking_details(route, '{{ translate('want_to_switch_payment_method_to_cash_after_service') }}', 'payment_method', payment_method);
        });

        function toggleAddPaymentTransactionField($form) {
            var receivedBy = $form.find('input[name="received_by"]:checked').val();
            var $cw = $form.find('.add-payment-company-inflow-wrap');
            if (receivedBy === 'company') {
                $cw.removeClass('d-none');
                $cw.find('input, textarea, select').prop('disabled', false);
                pkApmBindForm($form);
                var $sc = $cw.find('.pk-apm-scope').first();
                if ($sc.length) {
                    pkApmSync($sc, $form, {});
                }
            } else {
                $cw.addClass('d-none');
                $cw.find('input, textarea, select').prop('disabled', true);
            }
        }

        $(document).on('change', '.add-payment-form input[name="received_by"]', function() {
            toggleAddPaymentTransactionField($(this).closest('.add-payment-form'));
        });

        $(document).on('shown.bs.modal', '[id^="addPaymentModal-"]', function() {
            var $form = $(this).find('.add-payment-form');
            if ($form.length) {
                toggleAddPaymentTransactionField($form);
                applyLossSplitAutofill($form, null);
            }
        });

        $(document).on('shown.bs.modal', '#bookingFinancialSettlementModal', function() {
            var $bf = $('#bfs-add-payment-form');
            if ($bf.length) {
                toggleAddPaymentTransactionField($bf);
            }
        });

        $(document).on('hidden.bs.modal', '[id^="addPaymentModal-"]', function() {
            var $form = $(this).find('.add-payment-form');
            $form.find('.add-payment-modal-errors').addClass('d-none').empty();
            $form.find('button[type="submit"]').prop('disabled', false);
        });

        function apRound2(x) {
            return Math.round((parseFloat(x) || 0) * 100) / 100;
        }

        function apParseUserNumber(str) {
            if (str === undefined || str === null) return NaN;
            var t = String(str).trim().replace(/,/g, '');
            if (t === '' || t === '.' || t === '-' || t.endsWith('.')) return NaN;
            var n = parseFloat(t);
            return isNaN(n) ? NaN : n;
        }

        function apReadCap($form, attrName) {
            var v = $form.attr(attrName);
            if (v === undefined || v === null || v === '') return null;
            var n = parseFloat(String(v));
            return isNaN(n) ? null : apRound2(Math.max(0, n));
        }

        function apClamp(n, min, max) {
            var x = apRound2(n);
            if (x < min) x = min;
            if (max !== null && x > max) x = max;
            return apRound2(x);
        }

        function applyLossSplitAutofill($form, edited) {
            if (!$form || !$form.length) return;
            if ($form.attr('data-loss-allocation') !== '1') return;
            var $sp = $form.find('[name="split_amount_provider"]');
            var $sc = $form.find('[name="split_amount_company"]');
            if (!$sp.length || !$sc.length) return;

            var amount = parseFloat($form.find('.add-payment-amount').val());
            if (isNaN(amount) || amount <= 0) {
                return;
            }
            amount = apRound2(amount);

            var capPv = apReadCap($form, 'data-loss-cap-provider');
            var capCv = apReadCap($form, 'data-loss-cap-company');

            var curSp = apParseUserNumber($sp.val());
            var curSc = apParseUserNumber($sc.val());
            if (isNaN(curSp)) curSp = 0;
            if (isNaN(curSc)) curSc = 0;

            // If no explicit edit target, auto-fill by remaining-loss ratio (caps).
            if (edited === null) {
                // Only auto-fill when user hasn't edited split for this amount.
                if ($form.data('lossSplitUserEdited') === true) {
                    return;
                }
                var denom = (capPv !== null ? capPv : 0) + (capCv !== null ? capCv : 0);
                var ratioP = denom > 0.0001 ? ((capPv !== null ? capPv : 0) / denom) : 0.5;
                var sp0 = apRound2(amount * ratioP);
                var sc0 = apRound2(amount - sp0);
                if (capPv !== null) sp0 = apClamp(sp0, 0, capPv);
                if (capCv !== null) sc0 = apClamp(sc0, 0, capCv);
                // Rebalance if clamped.
                if (apRound2(sp0 + sc0) !== amount) {
                    if (capPv !== null && sp0 >= capPv - 0.0001) {
                        sc0 = apClamp(amount - sp0, 0, capCv);
                    } else {
                        sp0 = apClamp(amount - sc0, 0, capPv);
                    }
                }
                $sp.val(String(sp0));
                $sc.val(String(sc0));
                return;
            }

            if (edited === 'provider') {
                $form.data('lossSplitUserEdited', true);
                var rawSp = apParseUserNumber($sp.val());
                if (isNaN(rawSp)) return;
                var sp1 = apClamp(rawSp, 0, capPv);
                var sc1 = apClamp(amount - sp1, 0, capCv);
                // If company side clamped, push back into provider within its cap.
                if (apRound2(sp1 + sc1) !== amount) {
                    sp1 = apClamp(amount - sc1, 0, capPv);
                }
                // Don't rewrite the field the user is typing unless we must clamp.
                if (document.activeElement !== $sp.get(0) && Math.abs(sp1 - rawSp) > 0.0001) {
                    $sp.val(String(sp1));
                }
                $sc.val(String(sc1));
            } else if (edited === 'company') {
                $form.data('lossSplitUserEdited', true);
                var rawSc = apParseUserNumber($sc.val());
                if (isNaN(rawSc)) return;
                var sc2 = apClamp(rawSc, 0, capCv);
                var sp2 = apClamp(amount - sc2, 0, capPv);
                if (apRound2(sp2 + sc2) !== amount) {
                    sc2 = apClamp(amount - sp2, 0, capCv);
                }
                $sp.val(String(sp2));
                if (document.activeElement !== $sc.get(0) && Math.abs(sc2 - rawSc) > 0.0001) {
                    $sc.val(String(sc2));
                }
            }
        }

        // Auto-fill split once amount is entered (loss-making mode).
        $(document).on('input', '.add-payment-form .add-payment-amount', function () {
            var $form = $(this).closest('.add-payment-form');
            // New amount entered: allow auto-fill again until user edits split.
            $form.data('lossSplitUserEdited', false);
            applyLossSplitAutofill($form, null);
        });

        // Keep provider/company split linked: changing one updates the other to (amount - edited).
        $(document).on('input', '.add-payment-form [name="split_amount_provider"]', function () {
            var $form = $(this).closest('.add-payment-form');
            applyLossSplitAutofill($form, 'provider');
        });
        $(document).on('input', '.add-payment-form [name="split_amount_company"]', function () {
            var $form = $(this).closest('.add-payment-form');
            applyLossSplitAutofill($form, 'company');
        });

        // Normalize on blur (no typing interference).
        $(document).on('blur', '.add-payment-form [name="split_amount_provider"], .add-payment-form [name="split_amount_company"]', function () {
            var $form = $(this).closest('.add-payment-form');
            if ($form.attr('data-loss-allocation') !== '1') return;
            var n = apParseUserNumber($(this).val());
            if (isNaN(n)) {
                $(this).val('0');
            } else {
                $(this).val(String(apRound2(Math.max(0, n)).toFixed(2)));
            }
            applyLossSplitAutofill($form, $(this).attr('name') === 'split_amount_provider' ? 'provider' : 'company');
        });

        $(document).on('submit', '.add-payment-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $modal = $form.closest('.modal');
            var $errBox = $form.find('.add-payment-modal-errors');
            $errBox.addClass('d-none').empty();

            if ($form.hasClass('bfs-add-payment-form')) {
                var $scaledCap = $form.find('input[name="bfs_scaled_loss_cap"]');
                if ($scaledCap.length && String($scaledCap.val()) === '1') {
                    $form.find('input[name="scaled_customer_paid_amount"]').val($('#bfs-scaled-customer-paid').val() || '0');
                    var scaledOutcome = ($form.attr('data-bfs-outcome-scaled') || '').trim();
                    if (scaledOutcome) {
                        $form.find('input[name="bfs_settlement_outcome"]').val(scaledOutcome);
                    }
                    $form.find('input[name="bfs_decided_charges_cap"]').val('0');
                } else {
                    $form.find('input[name="bfs_scaled_loss_cap"]').val('0');
                    $('#bfs-cap-visit-charges').val($('#bfs-visit-charges-paid').val() || '0');
                    $('#bfs-cap-closing').val($('#bfs-closing-amount').val() || '');
                    if (typeof bfsSelectedOutcome === 'function') {
                        $('#bfs-cap-settlement-outcome').val(bfsSelectedOutcome());
                    }
                    $form.find('input[name="bfs_decided_charges_cap"]').val('1');
                }
            }

            var dueAmount = parseFloat($form.attr('data-due-amount')) || 0;
            var amount = parseFloat($form.find('.add-payment-amount').val()) || 0;
            if (!amount || amount < 0.01) {
                $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Amount') }}: {{ translate('This field is required.') }}</li></ul>');
                return false;
            }
            if (dueAmount > 0 && amount > dueAmount) {
                $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Amount cannot exceed the due amount. Due amount') }}: ' + dueAmount.toFixed(2) + '</li></ul>');
                return false;
            }

            if ($form.attr('data-loss-allocation') === '1') {
                var sp = parseFloat($form.find('[name="split_amount_provider"]').val()) || 0;
                var sc = parseFloat($form.find('[name="split_amount_company"]').val()) || 0;
                if (Math.abs(sp + sc - amount) > 0.02) {
                    $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Bfs_add_payment_split_must_equal_total') }}</li></ul>');
                    return false;
                }
                if (sp < 0.01 && sc < 0.01) {
                    $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Bfs_add_payment_split_need_positive_segment') }}</li></ul>');
                    return false;
                }
                var maxPS = $form.attr('data-loss-cap-provider');
                var maxCS = $form.attr('data-loss-cap-company');
                if (maxPS !== undefined && maxPS !== '' && maxCS !== undefined && maxCS !== '') {
                    var capPv = parseFloat(maxPS);
                    var capCv = parseFloat(maxCS);
                    if (!isNaN(capPv) && !isNaN(capCv) && amount > capPv + capCv + 0.02) {
                        $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Bfs_add_payment_split_exceeds_sum_caps_js') }}</li></ul>');
                        return false;
                    }
                    if (!isNaN(capPv) && sp > capPv + 0.02) {
                        $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Bfs_add_payment_split_exceeds_provider_loss_js') }}</li></ul>');
                        return false;
                    }
                    if (!isNaN(capCv) && sc > capCv + 0.02) {
                        $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Bfs_add_payment_split_exceeds_company_loss_js') }}</li></ul>');
                        return false;
                    }
                }
            }

            var receivedBy = $form.find('input[name="received_by"]:checked').val();
            var $cwWrap = $form.find('.add-payment-company-inflow-wrap');
            if (receivedBy === 'company') {
                $cwWrap.find('input, textarea, select').prop('disabled', false);
                $cwWrap.find('.pk-apm-hidden, .pk-apm-tier1, .pk-apm-tier2-digital, .pk-apm-tier2-offline').prop('disabled', false);
                var $sc = $cwWrap.find('.pk-apm-scope').first();
                if ($sc.length) {
                    pkApmUpdateHidden($sc);
                }
                pkApmBackfillCompanyInflowNoteIntoAdvanceFields($form);
                var hv = ($cwWrap.find('.pk-apm-hidden').first().val() || '').trim();
                if (!hv) {
                    $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>{{ translate('Advance_payment_method_is_required_when_advance_amount_is_set') }}</li></ul>');
                    return false;
                }
                var missingLabels = [];
                $cwWrap.find('.pk-apm-dynamic-fields').find('input[required], textarea[required], select[required]').filter(':enabled').filter(':visible').each(function () {
                    var $inp = $(this);
                    if (($inp.val() || '').trim()) {
                        return;
                    }
                    var fid = $inp.attr('id');
                    var labelText = '';
                    if (fid) {
                        var $lbl = $cwWrap.find('label[for="' + pkApmEscapeSelectorAttr(fid) + '"]');
                        labelText = ($lbl.text() || '').replace(/\s+/g, ' ').replace(/\s*\*+\s*$/,'').trim();
                    }
                    missingLabels.push(labelText || '{{ translate('Transaction_Reference_ID') }}');
                });
                if (missingLabels.length) {
                    var esc = function(t) { return $('<div/>').text(t).html(); };
                    var reqMsg = '{{ translate('This field is required.') }}';
                    var listHtml = missingLabels.map(function (t) {
                        return '<li>' + esc(t) + ': ' + esc(reqMsg) + '</li>';
                    }).join('');
                    $errBox.removeClass('d-none').html('<ul class="mb-0 ps-3">' + listHtml + '</ul>');
                    return false;
                }
            } else {
                $cwWrap.find('input, textarea, select').prop('disabled', true);
            }

            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            }).done(function(res) {
                if ($form.hasClass('bfs-add-payment-form')) {
                    $btn.prop('disabled', false);
                    $form.find('.add-payment-amount').val('');
                    $form.find('.pk-apm-dynamic-fields').empty();
                    $form.find('.pk-apm-hidden').val('');
                    $form.find('textarea[name="company_inflow_note"]').val('');
                    $form.find('.pk-apm-tier1').prop('checked', false);
                    $form.find('.pk-apm-tier2-digital, .pk-apm-tier2-offline').prop('checked', false);
                    var $dateIn = $form.find('input[name="date"]');
                    if ($dateIn.length) {
                        $dateIn.val($form.attr('data-default-date') || new Date().toISOString().slice(0, 10));
                    }
                    $form.find('input[name="received_by"][value="provider"]').prop('checked', true);
                    $form.find('input[name="received_by"][value="company"]').prop('checked', false);
                    if ($form.find('[name="split_amount_provider"]').length) {
                        $form.find('[name="split_amount_provider"]').val('0');
                        $form.find('[name="split_amount_company"]').val('0');
                    }
                    if (typeof toggleAddPaymentTransactionField === 'function') {
                        toggleAddPaymentTransactionField($form);
                    }
                    if (res && res.message && typeof toastr !== 'undefined') {
                        toastr.success(res.message);
                    }
                    if (res && res.booking_partial_payment_id) {
                        window.bfsModalSessionPartialPaymentIds = window.bfsModalSessionPartialPaymentIds || [];
                        window.bfsModalSessionPartialPaymentIds.push(String(res.booking_partial_payment_id));
                    }
                    if (typeof window.bfsRunPreviewAfterEmbeddedPayment === 'function') {
                        window.bfsRunPreviewAfterEmbeddedPayment();
                    }
                    return;
                }
                var modalEl = $modal[0];
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) {
                        inst.hide();
                    }
                }
                if (res && res.message && typeof toastr !== 'undefined') {
                    toastr.success(res.message);
                }
                location.reload();
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                var msgs = [];
                var payload = xhr.responseJSON;
                if (payload && payload.errors) {
                    if (Array.isArray(payload.errors)) {
                        payload.errors.forEach(function(er) {
                            if (er && er.message) {
                                msgs.push(er.message);
                            } else if (typeof er === 'string') {
                                msgs.push(er);
                            }
                        });
                    } else if (typeof payload.errors === 'object') {
                        Object.keys(payload.errors).forEach(function(k) {
                            var v = payload.errors[k];
                            if (Array.isArray(v)) {
                                v.forEach(function(m) {
                                    msgs.push(m);
                                });
                            } else if (v) {
                                msgs.push(String(v));
                            }
                        });
                    }
                }
                if (msgs.length === 0 && payload && payload.message) {
                    msgs.push(payload.message);
                }
                if (msgs.length === 0) {
                    msgs.push('{{ translate('Something went wrong. Please try again.') }}');
                }
                var esc = function(t) {
                    return $('<div/>').text(t).html();
                };
                var html = '<ul class="mb-0 ps-3">' + msgs.map(function(m) {
                    return '<li>' + esc(m) + '</li>';
                }).join('') + '</ul>';
                $errBox.removeClass('d-none').html(html);
            });

            return false;
        });

        $(document).on('submit', '.refund-form', function(e) {
            var $form = $(this);
            var maxAmount = parseFloat($form.data('max-amount')) || 0;
            var amount = parseFloat($form.find('.refund-amount').val()) || 0;
            if (maxAmount > 0 && amount > maxAmount) {
                e.preventDefault();
                toastr.error('{{ translate('Refund amount cannot exceed amount paid by customer. Max') }}: ' + maxAmount.toFixed(2));
                return false;
            }
        });

        function update_booking_details(route, message, componentId, updatedValue, revertValue) {
            var swalOpts = {
                title: "{{ translate('are_you_sure') }}?",
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: '{{ translate('Cancel') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            };
            if (componentId === 'service_schedule') {
                swalOpts.onClose = function () {
                    bookingScheduleExitEditMode();
                };
            }
            Swal.fire(swalOpts).then((result) => {
                var confirmed = result.value === true || result.isConfirmed === true;
                if (confirmed) {
                    var ajaxOpts = {
                        dataType: 'json',
                        beforeSend: function() {},
                        success: function(data) {
                            if (componentId === 'booking_status') {
                                $("#booking_status").data('current', updatedValue);
                            }
                            update_component(componentId, updatedValue);
                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });

                            if (componentId === 'booking_status' && (updatedValue === 'completed' || updatedValue === 'canceled' || updatedValue === 'cancelled')) {
                                if (bookingCurrentProviderId) {
                                    pendingPostFeedbackAction = 'reload';
                                    openProviderPerformanceFeedbackModal(bookingCurrentProviderId, updatedValue);
                                    return;
                                }
                            }

                            if (componentId === 'booking_status' || componentId === 'payment_status' ||
                                componentId === 'service_schedule' || componentId === 'serviceman_assign' || componentId === 'payment_method' ) {
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Something went wrong. Please try again.') }}';
                            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                                var errs = xhr.responseJSON.errors;
                                if (typeof errs === 'object' && !Array.isArray(errs)) {
                                    var first = Object.values(errs)[0];
                                    if (Array.isArray(first) && first[0]) {
                                        msg = first[0];
                                    }
                                }
                            }
                            if (componentId === 'booking_status' && revertValue !== undefined) {
                                $("#booking_status").val(revertValue).trigger('change');
                                $("#booking_status").data('current', revertValue);
                                if ($("#booking_status").next(".select2-container").length) {
                                    $("#booking_status").next(".select2-container").find(".select2-selection__rendered").text($("#booking_status option:selected").text());
                                }
                            }
                            toastr.error(msg, { CloseButton: true, ProgressBar: true });
                        },
                        complete: function() {},
                    };
                    if (componentId === 'booking_status') {
                        ajaxOpts.url = '{{ route('admin.booking.status_update', [$booking->id]) }}';
                        ajaxOpts.method = 'POST';
                        ajaxOpts.data = {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            booking_status: updatedValue
                        };
                        ajaxOpts.headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
                        $.ajax(ajaxOpts);
                    } else {
                        ajaxOpts.url = route;
                        ajaxOpts.method = 'GET';
                        ajaxOpts.data = {};
                        $.ajax(ajaxOpts);
                    }
                }
            })
        }

        function update_component(componentId, updatedValue) {

            if (componentId === 'booking_status') {
                $("#booking_status__span").html(updatedValue);

                selectElementVisibility('serviceman_assign', true);
                selectElementVisibility('payment_status', true);

            } else if (componentId === 'payment_status') {
                $("#payment_status__span").html(updatedValue);
                if (updatedValue === 'paid') {
                    $("#payment_status__span").addClass('text-success').removeClass('text-danger');
                } else if (updatedValue === 'unpaid') {
                    $("#payment_status__span").addClass('text-danger').removeClass('text-success');
                }

            }
        }

        function selectElementVisibility(componentId, visibility) {
            if (visibility === true) {
                $('#' + componentId).next(".select2-container").show();
            } else if (visibility === false) {
                $('#' + componentId).next(".select2-container").hide();
            } else {}
        }
    </script>

    <script>
        const serviceUpdateModalSelector = "#serviceUpdateModal--{{ $booking['id'] }}";
        window.bookingEditCurrencyDecimals = parseInt('{{ (int) (business_config('currency_decimal_point', 'business_information')->live_values ?? 2) }}', 10) || 2;
        window.bookingEditDefaultTaxPercent = parseFloat(@json((float) company_default_tax_percentage())) || 0;

        function bookingEditRecalcRowTotal($row) {
            if (!$row || !$row.length) {
                return;
            }
            const dec = window.bookingEditCurrencyDecimals ?? 2;
            let taxPct = parseFloat($row.attr('data-tax-percent'));
            if (isNaN(taxPct)) {
                taxPct = window.bookingEditDefaultTaxPercent ?? 0;
            }
            const unitRaw = String($row.find('.row-unit-price').val() ?? '').replace(',', '.');
            const unit = parseFloat(unitRaw);
            const unitNum = isNaN(unit) ? 0 : unit;
            let qty = parseInt($row.find('.row-qty').val(), 10);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }
            const discRaw = String($row.find('.row-discount').val() ?? '').replace(',', '.');
            const disc = parseFloat(discRaw);
            const discNum = isNaN(disc) ? 0 : Math.max(0, disc);
            const subtotal = Math.round(unitNum * qty * 100) / 100;
            const maxDisc = Math.min(discNum, subtotal);
            const taxable = Math.round(Math.max(0, subtotal - maxDisc) * 100) / 100;
            const tax = Math.round(taxable * taxPct / 100 * 100) / 100;
            const total = Math.round((taxable + tax) * 100) / 100;
            $row.find('.row-total-cost').first().text(total.toFixed(dec));
        }

        function bookingEditSelect2ModalParent() {
            return $(serviceUpdateModalSelector);
        }

        function bookingEditDestroySelect2($select) {
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
        }

        function bookingEditInitSelect2($select) {
            $select.select2({ dropdownParent: bookingEditSelect2ModalParent() });
        }

        function bookingEditLoadSubcategories(categoryId, selectedSubId) {
            const $modal = $(serviceUpdateModalSelector);
            const providerId = $modal.data('booking-provider-id');
            if (!categoryId) {
                return;
            }
            let url = '{{ route('admin.booking.service.ajax-get-subcategories') }}?category_id=' + encodeURIComponent(categoryId);
            if (providerId) {
                url += '&provider_id=' + encodeURIComponent(providerId);
            }
            $.get(url, function(response) {
                let o = '<option value="" disabled>{{ translate('Select_Sub_Category') }}</option>';
                (response.content || []).forEach(function(sc) {
                    const sel = selectedSubId && String(sc.id) === String(selectedSubId) ? ' selected' : '';
                    o += '<option value="' + sc.id + '"' + sel + '>' + sc.name + '</option>';
                });
                const $sub = $('#sub_category_selector__select');
                bookingEditDestroySelect2($sub);
                $sub.html(o);
                bookingEditInitSelect2($sub);
            }).fail(function() {
                toastr.error('{{ translate('Failed to load') }}');
            });
        }

        $(document).ready(function() {
            bookingEditInitSelect2($('#category_selector__select'));
            bookingEditInitSelect2($('#sub_category_selector__select'));
            bookingEditInitSelect2($('#service_selector__select'));
            bookingEditInitSelect2($('#service_variation_selector__select'));
        });

        $(serviceUpdateModalSelector).on('shown.bs.modal', function() {
            const catId = $('#category_selector__select').val();
            const selectedSub = @json($subCategory?->id);
            if (catId) {
                bookingEditLoadSubcategories(catId, selectedSub);
            }
            $('#service-edit-tbody tr').each(function() {
                bookingEditRecalcRowTotal($(this));
            });
        });

        $(document).on('input change', '#service-edit-tbody .row-unit-price, #service-edit-tbody .row-qty, #service-edit-tbody .row-discount, #service-edit-tbody .row-discount-bearer', function() {
            bookingEditRecalcRowTotal($(this).closest('tr'));
        });

        $('#category_selector__select').on('change', function() {
            const catId = $(this).val();
            bookingEditLoadSubcategories(catId, null);
            const $svc = $('#service_selector__select');
            bookingEditDestroySelect2($svc);
            $svc.html('<option value="" selected disabled>{{ translate('Select Service') }}</option>');
            bookingEditInitSelect2($svc);
            $('#service_variation_selector__select').html(
                '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');
        });

        $('#sub_category_selector__select').on('change', function() {
            const subId = $(this).val();
            if (!subId) {
                return;
            }
            $.get('{{ route('admin.booking.service.ajax-get-services') }}', { sub_category_id: subId }, function(response) {
                let o = '<option value="" selected disabled>{{ translate('Select Service') }}</option>';
                (response.content || []).forEach(function(s) {
                    o += '<option value="' + s.id + '">' + s.name + '</option>';
                });
                const $svc = $('#service_selector__select');
                bookingEditDestroySelect2($svc);
                $svc.html(o);
                bookingEditInitSelect2($svc);
                $('#service_variation_selector__select').html(
                    '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');
            }).fail(function() {
                toastr.error('{{ translate('Failed to load') }}');
            });
        });

        $("#service_selector__select").on('change', function() {
            $("#service_variation_selector__select").html(
                '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');

            const serviceId = this.value;
            const route = '{{ route('admin.booking.service.ajax-get-variant') }}' + '?service_id=' + serviceId +
                '&zone_id=' + "{{ $booking->zone_id }}";

            $.get({
                url: route,
                dataType: 'json',
                data: {},
                beforeSend: function() {
                    $('.preloader').show();
                },
                success: function(response) {
                    var selectString =
                        '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>';
                    response.content.forEach((item) => {
                        selectString +=
                            `<option value="${item.variant_key}">${item.variant}</option>`;
                    });
                    $("#service_variation_selector__select").html(selectString)
                },
                complete: function() {
                    $('.preloader').hide();
                },
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}')
                }
            });
        });

        $(document).on('change', '#serviceUpdateModal--{{ $booking["id"] }} .row-service-select', function() {
            const $row = $(this).closest('tr');
            const $variantSelect = $row.find('.row-variant-select');
            const serviceId = $(this).val();
            const zoneId = $(this).data('zone-id') || '{{ $booking->zone_id }}';
            if (!serviceId) {
                $variantSelect.html('<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');
                return;
            }
            const route = '{{ route('admin.booking.service.ajax-get-variant') }}' + '?service_id=' + serviceId + '&zone_id=' + zoneId;
            $.get({
                url: route,
                dataType: 'json',
                success: function(response) {
                    if (typeof response.service_tax_percent !== 'undefined' && response.service_tax_percent !== null) {
                        $row.attr('data-tax-percent', response.service_tax_percent);
                    }
                    let options = '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>';
                    (response.content || []).forEach(function(item) {
                        options += '<option value="' + item.variant_key + '">' + (item.variant || item.variant_key) + '</option>';
                    });
                    $variantSelect.html(options);
                    bookingEditRecalcRowTotal($row);
                },
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        });

        $("#serviceUpdateModal--{{ $booking['id'] }}").on('hidden.bs.modal', function() {
            $('#service_selector__select').prop('selectedIndex', 0);
            $("#service_variation_selector__select").html(
                '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');
            $("#service_quantity").val('');
        });

        $("#add-service").on('click', function() {
            const service_id = $("[name='service_id']").val();
            const variant_key = $("[name='variant_key']").val();
            const quantity = parseInt($("[name='service_quantity']").val());
            const zone_id = '{{ $booking->zone_id }}';


            if (service_id === '' || service_id === null) {
                toastr.error('{{ translate('Select a service') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            } else if (variant_key === '' || variant_key === null) {
                toastr.error('{{ translate('Select a variation') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            } else if (quantity < 1) {
                toastr.error('{{ translate('Quantity must not be empty') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            let $matchRow = null;
            $('#service-edit-tbody tr').each(function() {
                const $tr = $(this);
                const sid = $tr.find('select[name="service_ids[]"], input[name="service_ids[]"]').first().val();
                const vk = $tr.find('select[name="variant_keys[]"], input[name="variant_keys[]"]').first().val();
                if (String(sid) === String(service_id) && String(vk) === String(variant_key)) {
                    $matchRow = $tr;
                    return false;
                }
            });

            if ($matchRow && $matchRow.length) {
                const $q = $matchRow.find('.row-qty');
                const oldQty = parseInt($q.val(), 10) || 1;
                $q.val(oldQty + quantity);
                bookingEditRecalcRowTotal($matchRow);
                toastr.success('{{ translate('Added successfully') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            let query_string = 'service_id=' + encodeURIComponent(service_id) + '&variant_key=' + encodeURIComponent(variant_key) + '&quantity=' +
                quantity + '&zone_id=' + encodeURIComponent(zone_id) + '&booking_id=' + encodeURIComponent('{{ $booking->id }}');
            $.ajax({
                type: 'GET',
                url: "{{ route('admin.booking.service.ajax-get-service-info') }}" + '?' + query_string,
                data: {},
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('.preloader').show();
                },
                success: function(response) {
                    $("#service-edit-tbody").append(response.view);
                    const $last = $('#service-edit-tbody tr').last();
                    bookingEditRecalcRowTotal($last);
                    toastr.success('{{ translate('Added successfully') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                complete: function() {
                    $('.preloader').hide();
                },
            });
        })

        $(".remove-service-row").on('click', function() {
            let row = $(this).data('row');
            removeServiceRow(row)
        })

        function removeServiceRow(row) {
            const row_count = $('#service-edit-tbody tr').length;
            if (row_count <= 1) {
                toastr.error('{{ translate('Can not remove the only service') }}');
                return;
            }

            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: '{{ translate('want to remove the service from the booking') }}',
                type: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $(`#${row}`).remove();
                }
            })
        }
    </script>


    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ business_config('google_map', 'third_party')?->live_values['map_api_key_client'] }}&libraries=places&v=3.45.8">
    </script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this);
        });

        // for update customer location from service address modal
        $(document).ready(function() {
            function initAutocomplete() {
                let myLatLng = {
                    lat: {{ $customerAddress?->lat ?? 23.811842872190343 }},
                    lng: {{ $customerAddress?->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                    center: myLatLng,
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                let marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('latitude').value = coordinates['lat'];
                    document.getElementById('longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address').value = results[1]
                                    .formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];

                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];

                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('latitude').value = this.position.lat();
                            document.getElementById('longitude').value = this.position
                                .lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            initAutocomplete();
        });

        // for update service location from update customer address modal
        $(document).ready(function() {
            function addressMap() {
                let myLatLng = {
                    lat: {{ $booking->service_address?->lat ?? 23.811842872190343 }},
                    lng: {{ $booking->service_address?->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("address_location_map_canvas"), {
                    center: myLatLng,
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                let marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('address_latitude').value = coordinates['lat'];
                    document.getElementById('address_longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address_address').value = results[1].formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("address_pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];

                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];

                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('address_latitude').value = this.position.lat();
                            document.getElementById('address_longitude').value = this.position.lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            addressMap();
        });


        $('.__right-eye').on('click', function() {
            if ($(this).hasClass('active')) {
                $(this).removeClass('active')
                $(this).find('i').removeClass('tio-invisible')
                $(this).find('i').addClass('tio-hidden-outlined')
                $(this).siblings('input').attr('type', 'password')
            } else {
                $(this).addClass('active')
                $(this).siblings('input').attr('type', 'text')


                $(this).find('i').addClass('tio-invisible')
                $(this).find('i').removeClass('tio-hidden-outlined')
            }
        })
    </script>

    <script>
        $(document).ready(function() {

            $(document).on('click', '.sort-by-class', function() {
                console.log('hi')
                const route = '{{ url('admin/provider/available-provider') }}'
                var sortOption = document.querySelector('input[name="sort"]:checked').value;
                var bookingId = "{{ $booking->id }}"

                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        sort_by: sortOption,
                        booking_id: bookingId
                    },
                    beforeSend: function() {

                    },
                    success: function(response) {
                        $('.modal-content-data').html(response.view);
                    },
                    complete: function() {},
                    error: function() {
                        toastr.error('{{ translate('Failed to load') }}')
                    }
                });
            })
        });

        $(document).ready(function() {
            $(document).on('keyup', '.search-form-input', function() {
                const route = '{{ url('admin/provider/available-provider') }}';
                let sortOption = document.querySelector('input[name="sort"]:checked').value;
                let bookingId = "{{ $booking->id }}";
                let searchTerm = $('.search-form-input').val();

                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        sort_by: sortOption,
                        booking_id: bookingId,
                        search: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        $('.modal-content-data').html(response.view);


                        var cursorPosition = searchTerm.lastIndexOf(searchTerm.charAt(searchTerm
                            .length - 1)) + 1;
                        $('.search-form-input').focus().get(0).setSelectionRange(cursorPosition,
                            cursorPosition);
                    },
                    complete: function() {},
                    error: function() {
                        toastr.error('{{ translate('Failed to load') }}');
                    }
                });
            });
        });

        function updateProvider(providerId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/provider/reassign-provider') }}' + '/' + bookingId;
            const sortOption = document.querySelector('input[name="sort"]:checked').value;
            const searchTerm = $('.search-form-input').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    sort_by: sortOption,
                    booking_id: bookingId,
                    search: searchTerm,
                    provider_id: providerId
                },
                beforeSend: function() {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
                complete: function() {},
                error: function(xhr) {
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Failed to load') }}');
                }
            });
        }

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).on('keyup', '.search-form-input1', function() {
                const route = '{{ url('admin/booking/serviceman-update', $booking->id) }}';
                let searchTerm = $('.search-form-input1').val();

                $.ajax({
                    url: route,
                    type: 'PUT',
                    dataType: 'json',
                    data: {
                        booking_id: "{{ $booking->id }}",
                        search: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        $('.modal-content-data1').html(response.view);
                    },
                    complete: function() {},
                    error: function(xhr) {
                        if (xhr.status === 419) {
                            toastr.error('{{ translate('Session expired, please refresh the page.') }}');
                        } else {
                            toastr.error('{{ translate('Failed to load') }}');
                        }
                    }
                });
            });
        });


        function updateServiceman(servicemanId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/booking/serviceman-update') }}' + '/' + bookingId;
            const searchTerm = $('.search-form-input1').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    booking_id: bookingId,
                    search: searchTerm,
                    serviceman_id: servicemanId
                },
                beforeSend: function() {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
                complete: function() {},
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        $(document).ready(function() {
            // Hide all cancellation notes initially
            $('.cancellation-note').hide();

            // When a radio button changes in the modal
            $('.booking-verification-status').change(function() {
                const $modal = $(this).closest('.modal');
                const $cancellationNote = $modal.find('.cancellation-note textarea');

                if ($(this).hasClass('deny-request') && $(this).is(':checked')) {
                    $modal.find('.cancellation-note').show();
                    $cancellationNote.prop('required', true);
                } else if ($(this).hasClass('approve-request') && $(this).is(':checked')) {
                    $modal.find('.cancellation-note').hide();
                    $cancellationNote.prop('required', false);
                }
            });
        });

        $(document).on('click', '.customer-chat, .provider-chat', function(e) {
            e.preventDefault();
            $(this).find('form').trigger('submit');
        });


        // for update service location from update customer address modal
        $(document).ready(function() {
            function addressMap() {
                let myLatLng = {
                    lat: {{ $booking->service_address?->lat ?? 23.811842872190343 }},
                    lng: {{ $booking->service_address?->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("address_location_map_canvas"), {
                    center: myLatLng,
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                let marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('address_latitude').value = coordinates['lat'];
                    document.getElementById('address_longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address_address').value = results[1].formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("address_pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];

                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];

                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('address_latitude').value = this.position.lat();
                            document.getElementById('address_longitude').value = this.position.lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            addressMap();
        });

        $(document).ready(function() {
            // Get booking ID dynamically
            var bookingId = "{{ $booking['id'] }}";

            function toggleServiceLocation() {
                if ($('#customer_location').is(':checked')) {
                    $('.customer-details').show();
                    $('.provider-details').hide();
                } else {
                    $('.customer-details').hide();
                    $('.provider-details').show();
                }
            }

            // Run toggle function on radio button change
            $('input[name="service_location"]').on('change', function() {
                toggleServiceLocation();
            });

            // Run toggle function when the modal is opened
            $('#serviceLocationModal--' + bookingId).on('shown.bs.modal', function () {
                toggleServiceLocation();
            });

            // When the address modal opens, hide the first modal
            $('#customerAddressModal--' + bookingId).on('show.bs.modal', function () {
                $('#serviceLocationModal--' + bookingId).modal('hide'); // Hide the first modal
            });

            // When the address modal closes, reopen the service location modal and update the address
            $('#customerAddressModal--' + bookingId).on('hidden.bs.modal', function () {
                $('#serviceLocationModal--' + bookingId).modal('show'); // Show the first modal again
            });
        });

        $(document).ready(function () {
            $("#customerAddressModalSubmit").on("submit", function (e) {
                e.preventDefault(); // Prevent form submission

                var bookingId = "{{ $booking['id'] }}";

                let customerAddressModal = $("#customerAddressModal--" + bookingId);
                let serviceLocationModal = $("#serviceLocationModal--" + bookingId);

                let addressLabel = customerAddressModal.find("input[name='address_label']").val();
                let address = customerAddressModal.find("textarea[name='address']").val();
                let landmark = customerAddressModal.find("input[name='landmark']").val();
                let latitude = customerAddressModal.find("input[name='latitude']").val();
                let longitude = customerAddressModal.find("input[name='longitude']").val();

                serviceLocationModal.find("input[name='address_label']").val(addressLabel);
                serviceLocationModal.find("input[name='address']").val(address);
                serviceLocationModal.find("input[name='landmark']").val(landmark);
                serviceLocationModal.find("input[name='latitude']").val(latitude);
                serviceLocationModal.find("input[name='longitude']").val(longitude);

                $('#customer_service_location').removeClass('text-danger');
                $('#customer_service_location').text(address);
                $('.customer-address-update-btn').removeAttr('disabled'); // Update the customer service location update button

               // Close the customerAddressModal
                customerAddressModal.modal("hide");

                // Open the serviceLocationModal to show updated data
                serviceLocationModal.modal("show");
            });
        });

        $(".customer-address-reset-btn").on("click", function (e) {
            e.preventDefault(); // prevent default behavior

            // Reset the form (visible inputs)
            $("#customerAddressModalSubmit")[0].reset();

            // Restore hidden inputs to original values from server
            $("input[name='contact_person_name']").val("{{ $booking->service_address->contact_person_name ?? '' }}");
            $("input[name='contact_person_number']").val("{{ $booking->service_address->contact_person_number ?? '' }}");
            $("input[name='address_label']").val("{{ $booking->service_address->address_label ?? '' }}");
            $("textarea[name='address']").val({!! json_encode($booking->service_address->address ?? '') !!});
            $("input[name='landmark']").val("{{ $booking->service_address->landmark ?? '' }}");
            $("input[name='latitude']").val("{{ $booking->service_address->lat ?? '' }}");
            $("input[name='longitude']").val("{{ $booking->service_address->lon ?? '' }}");

            // Update the UI
            let name = {!! json_encode($customerName ?? '') !!};
            let phone = {!! json_encode($customerPhone ?? '') !!};
            let customerAddress = "{{ $booking?->service_address?->address }}";

            $('.updated_customer_name').text(name); // Update the customer name
            $('#updated_customer_phone').text(phone); // Update the customer phone

            if (customerAddress) {
                $('#customer_service_location').text(customerAddress);
                $('#customer_service_location').removeClass('text-danger');
                $('.customer-address-update-btn').removeAttr('disabled');
            } else {
                $('#customer_service_location').text("No address found");
                $('#customer_service_location').addClass('text-danger');
                $('.customer-address-update-btn').attr('disabled', true);
            }
        });


    </script>
    <script>
        $(document).ready(function() {
            $('.without-search').select2({
                minimumResultsForSearch: Infinity
            });
        });

    </script>
@endpush
