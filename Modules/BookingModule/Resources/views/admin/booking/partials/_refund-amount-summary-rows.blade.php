@php
    /** @var \Modules\BookingModule\Entities\Booking $booking */
    /** @var string $variant details|invoice */
    /** @var string|null $summaryRowClass optional CSS classes for details-variant rows (e.g. booking summary tint) */
    $variant = $variant ?? 'details';
    $summaryRowClass = $summaryRowClass ?? '';
    $__refundDisp = get_booking_refund_display_totals($booking);
@endphp
@if ($__refundDisp['show'])
    @if ($variant === 'invoice')
        <tr>
            <td colspan="3"></td>
            <td class="fw-700">{{ translate('Refundable_amount') }}</td>
            <td class="fw-700 text-right">{{ with_currency_symbol($__refundDisp['refundable_remaining']) }}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td class="fw-700">{{ translate('Refunded_amount') }}</td>
            <td class="fw-700 text-right">{{ with_currency_symbol($__refundDisp['refunded_total']) }}</td>
        </tr>
    @else
        <tr @if($summaryRowClass !== '') class="{{ $summaryRowClass }}" @endif>
            <td>{{ translate('Refundable_amount') }}</td>
            <td class="text--end pe--4">{{ with_currency_symbol($__refundDisp['refundable_remaining']) }}</td>
        </tr>
        <tr @if($summaryRowClass !== '') class="{{ $summaryRowClass }}" @endif>
            <td>{{ translate('Refunded_amount') }}</td>
            <td class="text--end pe--4">{{ with_currency_symbol($__refundDisp['refunded_total']) }}</td>
        </tr>
    @endif
@endif
