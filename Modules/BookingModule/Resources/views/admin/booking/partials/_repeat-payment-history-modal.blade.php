@php
    $repeatPaymentHistoryModalId = $repeatPaymentHistoryModalId ?? 'bookingPaymentHistoryModal-' . $booking->id;
@endphp
<div class="modal fade" id="{{ $repeatPaymentHistoryModalId }}" tabindex="-1" aria-labelledby="{{ $repeatPaymentHistoryModalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $repeatPaymentHistoryModalId }}-label">{{ translate('Payment_and_refund_history') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body">
                @include('bookingmodule::admin.booking.partials._repeat-payment-history-tables', [
                    'booking' => $booking,
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>
