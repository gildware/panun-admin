@php
    $__adminRefundChannelBreakdown = get_booking_customer_refund_channel_breakdown($booking);
    $__adminRefundWalletPaid = round((float) ($__adminRefundChannelBreakdown['wallet_paid'] ?? 0), 2);
    $__adminRefundDigitalPaid = round((float) ($__adminRefundChannelBreakdown['digital_paid'] ?? 0), 2);
    $__adminRefundShowChannelBreakdown = $__adminRefundWalletPaid > 0.009 || $__adminRefundDigitalPaid > 0.009;
    $__adminRefundDeliveredBreakdown = get_booking_customer_refund_delivered_breakdown($booking);
    $__adminRefundWalletRefunded = round((float) ($__adminRefundDeliveredBreakdown['wallet_refunded'] ?? 0), 2);
    $__adminRefundTransferRefunded = round((float) ($__adminRefundDeliveredBreakdown['transfer_refunded'] ?? 0), 2);
    $__adminRefundShowDeliveredBreakdown = ($__adminRefundDeliveredBreakdown['has_any'] ?? false)
        || $__adminRefundWalletRefunded > 0.009
        || $__adminRefundTransferRefunded > 0.009;
    $walletRefundLedgers = $walletRefundLedgers ?? (function_exists('booking_revertible_wallet_refund_ledgers')
        ? booking_revertible_wallet_refund_ledgers($booking)
        : collect());
    $showRefundProcessActions = $showRefundProcessActions ?? (round((float) $maxRefundAmount, 2) > 0);
    $canRevertWalletRefund = auth()->check() && auth()->user()->can('booking_can_manage_status');
@endphp
<div class="card booking-refund-actions-card mb-0 h-100">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-2 mb-3">
            <h4 class="mb-0">{{ $showRefundProcessActions ? translate('Process_refund') : translate('Wallet_refunds') }}</h4>
        </div>
        @if($showRefundProcessActions)
            <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 mb-3">
                <span class="material-icons fz-18 flex-shrink-0">info</span>
                <span class="fz-12">{{ translate('Refund_of') }} <strong>{{ with_currency_symbol($maxRefundAmount) }}</strong> {{ translate('is_pending') }}</span>
            </div>
        @endif
        <div class="booking-refund-actions-card__body py-2 px-3">
            @if($showRefundProcessActions && $__adminRefundShowChannelBreakdown)
                <div class="border-bottom pb-2 mb-2">
                    <p class="text-uppercase text-muted fz-11 mb-2 fw-semibold">{{ translate('Customer_paid') }}</p>
                    @if($__adminRefundWalletPaid > 0.009)
                        <div class="d-flex justify-content-between align-items-baseline gap-2 fz-12 mb-1">
                            <span class="title-color">{{ translate('Paid_via_wallet') }}</span>
                            <strong class="text-break">{{ with_currency_symbol($__adminRefundWalletPaid) }}</strong>
                        </div>
                    @endif
                    @if($__adminRefundDigitalPaid > 0.009)
                        <div class="d-flex justify-content-between align-items-baseline gap-2 fz-12">
                            <span class="title-color">{{ translate('Paid_via_digital') }}</span>
                            <strong class="text-break">{{ with_currency_symbol($__adminRefundDigitalPaid) }}</strong>
                        </div>
                    @endif
                </div>
            @endif
            @if($__adminRefundShowDeliveredBreakdown || $walletRefundLedgers->isNotEmpty())
                <div class="border-bottom pb-2 mb-2">
                    <p class="text-uppercase text-muted fz-11 mb-2 fw-semibold">{{ translate('Refunds_already_processed') }}</p>
                    @if($walletRefundLedgers->isNotEmpty())
                        @foreach($walletRefundLedgers as $walletRefundLedger)
                            <div class="d-flex justify-content-between align-items-center gap-2 fz-12 mb-1">
                                <span class="title-color">
                                    {{ translate('Refunded_to_wallet') }}
                                    <span class="text-muted">{{ $walletRefundLedger->created_at ? $walletRefundLedger->created_at->format('d M Y, H:i') : '' }}</span>
                                </span>
                                <span class="d-flex align-items-center gap-2">
                                    <strong class="text-break text-success">-{{ with_currency_symbol((float) $walletRefundLedger->amount) }}</strong>
                                    @if($canRevertWalletRefund)
                                        <button type="button"
                                            class="btn btn-outline-danger btn-sm py-0 px-2 fz-11"
                                            data-bs-toggle="modal"
                                            data-bs-target="#revertWalletRefundConfirm-{{ $booking->id }}"
                                            data-ledger-id="{{ $walletRefundLedger->id }}"
                                            data-amount-line="{{ e(translate('Refunded_to_wallet')) }}: {{ e(with_currency_symbol((float) $walletRefundLedger->amount)) }}">{{ translate('Revert_wallet_refund') }}</button>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                        <p class="text-muted fz-11 mb-1">{{ translate('Revert_wallet_refund_once_hint') }}</p>
                    @elseif($__adminRefundWalletRefunded > 0.009)
                        <div class="d-flex justify-content-between align-items-baseline gap-2 fz-12 mb-1">
                            <span class="title-color">{{ translate('Refunded_to_wallet') }}</span>
                            <strong class="text-break text-success">-{{ with_currency_symbol($__adminRefundWalletRefunded) }}</strong>
                        </div>
                    @endif
                    @if($__adminRefundTransferRefunded > 0.009)
                        <div class="d-flex justify-content-between align-items-baseline gap-2 fz-12">
                            <span class="title-color">{{ translate('Refunded_via_transfer') }}</span>
                            <strong class="text-break text-success">-{{ with_currency_symbol($__adminRefundTransferRefunded) }}</strong>
                        </div>
                    @endif
                </div>
            @endif
            @if($showRefundProcessActions)
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-end align-items-baseline gap-2">
                        <span class="text-muted text-break fz-12">{{ translate('Remaining_refundable') }}: <strong>{{ with_currency_symbol($maxRefundAmount) }}</strong></span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn--primary booking-refund-action-btn w-100 text-nowrap px-2" data-bs-toggle="modal" data-bs-target="#refundWalletModal-{{ $booking->id }}">{{ translate('Refund_to_wallet') }}</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn--danger booking-refund-action-btn w-100 text-nowrap px-2" data-bs-toggle="modal" data-bs-target="#refundTransferModal-{{ $booking->id }}">{{ translate('Transfer_to_customer') }}</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@if($showRefundProcessActions)
<div class="modal fade" id="refundWalletModal-{{ $booking->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.booking.refund_to_wallet', $booking->id) }}" class="refund-form" data-max-amount="{{ $maxRefundAmount }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Refund_to_wallet') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Refund amount') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('Max') }}: {{ with_currency_symbol($maxRefundAmount) }})</small></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $maxRefundAmount }}" name="amount" class="form-control refund-amount" required value="{{ $maxRefundAmount }}" placeholder="{{ translate('Max') }} {{ with_currency_symbol($maxRefundAmount) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Reference_Note') }} <span class="text-muted small">({{ translate('Optional') }})</span></label>
                        <textarea name="reference_note" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Optional_note') }}"></textarea>
                    </div>
                    <p class="small text-muted mb-0">{{ translate('Refund_to_wallet_modal_hint') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Refund_to_wallet') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="refundTransferModal-{{ $booking->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.booking.refund', $booking->id) }}" class="refund-form" data-max-amount="{{ $maxRefundAmount }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Transfer_to_customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Refund amount') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('Max') }}: {{ with_currency_symbol($maxRefundAmount) }})</small></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $maxRefundAmount }}" name="amount" class="form-control refund-amount" required value="{{ $maxRefundAmount }}" placeholder="{{ translate('Max') }} {{ with_currency_symbol($maxRefundAmount) }}">
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
                    <p class="small text-muted mb-0">{{ translate('Transfer_to_customer_modal_hint') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--danger">{{ translate('Transfer_to_customer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
