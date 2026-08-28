@php
    $includeSnapshotInBoard = ! empty($includeSnapshotInBoard);
@endphp

@if($includeSnapshotInBoard)
    <div class="mb-3">
        @include('bookingmodule::admin.booking.partials._repeat-payment-snapshot', [
            'booking' => $booking,
            'viewAllHref' => '',
            'viewAllModalId' => '',
        ])
    </div>
@endif

<section class="summary-panel booking-summary-panel">
    <div class="summary-panel__head">
        <h2 class="summary-panel__title">
            <span class="material-icons">receipt_long</span>
            {{ translate('Payment_and_refund_history') }}
        </h2>
    </div>
    <div class="summary-panel__body p-3">
        @include('bookingmodule::admin.booking.partials._repeat-payment-history-tables', [
            'booking' => $booking,
        ])
    </div>
</section>
