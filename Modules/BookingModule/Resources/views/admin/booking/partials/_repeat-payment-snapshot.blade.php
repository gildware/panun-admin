@php
    $isVisitPay = $booking instanceof \Modules\BookingModule\Entities\BookingRepeat;
    $viewAllHref = $viewAllHref ?? '';
    $viewAllModalId = $viewAllModalId ?? '';
    $hasViewAllModal = $viewAllModalId !== '';
    $hasViewAllHref = $viewAllHref !== '';
    if ($isVisitPay) {
        $visitPayTotal = $visitGrandTotal ?? $booking->total_booking_amount;
        $visitIsPaid = (int) ($booking->is_paid ?? 0) === 1;
        $snap = [
            'status_label' => $visitIsPaid ? translate('Paid') : translate('Unpaid'),
            'total' => $visitPayTotal,
            'amount_paid_display' => $visitIsPaid ? $visitPayTotal : 0,
            'due_balance' => $visitIsPaid ? 0 : $visitPayTotal,
            'amount_row_label' => translate('Amount_Paid'),
        ];
        $showRefundSnapshot = false;
        $hasPendingRefund = false;
        $parentMethod = $booking->booking->payment_method ?? $booking->payment_method ?? '';
        $paymentMethodLabel = ucwords(str_replace(['_', '-'], ' ', (string) $parentMethod));
        $amountRowLabel = $snap['amount_row_label'];
        $statusLabel = (string) $snap['status_label'];
    } else {
        $snap = booking_provider_api_payment_snapshot($booking);
        $showRefundSnapshot = array_key_exists('refundable_amount', $snap) && (float) ($snap['refundable_amount'] ?? 0) > 0.009;
        $hasPendingRefund = array_key_exists('pending_refund', $snap) && (float) ($snap['pending_refund'] ?? 0) > 0.009;
        $statusLabel = (string) ($snap['status_label'] ?? translate('Unpaid'));
        $paymentMethodLabel = ucwords(str_replace(['_', '-'], ' ', (string) ($booking->payment_method ?? '')));
        $amountRowLabel = $snap['amount_row_label'] ?? translate('Amount_Paid');
    }
    $statusKey = strtolower($statusLabel);
    $badgeClass = 'danger';
    if (str_contains($statusKey, 'partial')) {
        $badgeClass = 'info';
    } elseif (str_contains($statusKey, 'pending')) {
        $badgeClass = 'warning';
    } elseif (str_contains($statusKey, 'refund') || (str_contains($statusKey, 'paid') && ! str_contains($statusKey, 'unpaid'))) {
        $badgeClass = 'success';
    }
@endphp
<div class="party-card party-card--payment w-100">
    <div class="party-card__head">
        <span><span class="material-icons">account_balance_wallet</span> {{ translate('Payment_Snapshot') }}</span>
        @if($hasViewAllModal)
            <button type="button" class="party-card__head-link btn btn-link btn-sm p-0 border-0 fz-11" data-bs-toggle="modal" data-bs-target="#{{ $viewAllModalId }}">
                {{ translate('view_all') }}
            </button>
        @elseif($hasViewAllHref)
            <a href="{{ $viewAllHref }}" class="party-card__head-link btn btn-link btn-sm p-0 border-0 fz-11">
                {{ translate('view_all') }}
            </a>
        @endif
    </div>
    <div class="party-card__body party-card__body--stats">
        <div class="stat-kv">
            <div class="stat-kv__row">
                <span class="stat-kv__label">{{ translate('Payment_Status') }}</span>
                <span class="stat-kv__value">
                    <span class="badge badge-{{ $badgeClass }} mb-0 fz-12 flex-shrink-0">{{ $statusLabel }}</span>
                    @if($hasPendingRefund)
                        <span class="badge bg-warning text-dark mb-0 fz-12 flex-shrink-0">{{ translate('Pending_refund') }}</span>
                    @endif
                </span>
            </div>
            <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                <span class="title-color flex-shrink-0">{{ translate('Payment_Method') }}</span>
                <span class="c1 fw-semibold text-end text-break min-w-0">{{ $paymentMethodLabel }}</span>
            </div>
            @if($showRefundSnapshot)
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ translate('Amount_paid_by_customer') }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['customer_paid_total'] ?? 0) }}</span>
                </div>
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ translate('Refundable_amount') }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['refundable_amount'] ?? 0) }}</span>
                </div>
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ translate('Refunded_amount') }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['refunded_amount'] ?? 0) }}</span>
                </div>
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ translate('Refund_balance_remaining') }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['refundable_remaining'] ?? 0) }}</span>
                </div>
            @else
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ translate('Total_Amount') }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['total'] ?? 0) }}</span>
                </div>
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ $amountRowLabel }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['amount_paid_display'] ?? 0) }}</span>
                </div>
                @if($hasPendingRefund)
                    <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                        <span class="title-color flex-shrink-0">{{ translate('Pending_refund') }}</span>
                        <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['pending_refund'] ?? 0) }}</span>
                    </div>
                @endif
                <div class="booking-overview-kv-row d-flex justify-content-between align-items-baseline gap-2 mb-0">
                    <span class="title-color flex-shrink-0">{{ translate('Due_Balance') }}</span>
                    <span class="c1 fw-semibold text-end text-break min-w-0">{{ with_currency_symbol($snap['due_balance'] ?? 0) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
