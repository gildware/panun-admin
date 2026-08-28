@php
    $visitId = (string) ($visit['id'] ?? '');
    $visitOtp = (string) ($visit['booking_otp'] ?? '');
    $visitPaid = (int) ($visit['is_paid'] ?? 0) === 1;
    $visitPayable = $visit['payable_amount'] ?? ($visit['total_booking_amount'] ?? 0);
    $visitExtrasTotal = $visit['extra_total'] ?? 0;
    $visitServiceman = trim((string) ($visit['serviceman_name'] ?? ''));
    $visitServicemanPhone = (string) ($visit['serviceman_phone'] ?? '');
    $visitRemarks = (string) ($visit['visit_remarks'] ?? '');
    $serviceLines = $visit['service_lines'] ?? [];
    $extraLines = $visit['extra_lines'] ?? [];
    $nextStatuses = $visit['next_statuses'] ?? [];
    $canCompleteVisit = ! empty($visit['can_complete']);
    $visitStatus = (string) ($visit['booking_status'] ?? '');
    $invoiceUrl = (string) ($visit['invoice_url'] ?? '');
    $statusUrl = (string) ($visit['status_url'] ?? '');
    $hasServiceLines = count($serviceLines) > 0;
    $hasExtraLines = count($extraLines) > 0;
    $hasNextStatuses = count($nextStatuses) > 0;
    $hasStatusUrl = $statusUrl !== '';
    $hasInvoiceUrl = $invoiceUrl !== '';
    $hasServiceman = $visitServiceman !== '';
    $hasRemarks = $visitRemarks !== '';
    $statusSelectId = 'repeat-visit-status-' . $visitId;
@endphp
<div class="repeat-visit-inline">
    <div class="repeat-visit-inline__meta">
        <div>
            <span class="text-muted d-block fz-11 text-uppercase">{{ translate('Booking_Otp') }}</span>
            <strong>{{ $visitOtp !== '' ? $visitOtp : '—' }}</strong>
        </div>
        <div>
            <span class="text-muted d-block fz-11 text-uppercase">{{ translate('Payment_Status') }}</span>
            <strong class="{{ $visitPaid ? 'text-success' : 'text-danger' }}">{{ $visitPaid ? translate('Paid') : translate('Unpaid') }}</strong>
        </div>
        <div>
            <span class="text-muted d-block fz-11 text-uppercase">{{ translate('Visit_total') }}</span>
            <strong>{{ with_currency_symbol($visitPayable) }}</strong>
        </div>
        <div>
            <span class="text-muted d-block fz-11 text-uppercase">{{ translate('Serviceman') }}</span>
            <strong>{{ $hasServiceman ? $visitServiceman : translate('Not_assigned') }}</strong>
            @if($hasServiceman && $visitServicemanPhone !== '')
                <div class="fz-12">{{ $visitServicemanPhone }}</div>
            @endif
        </div>
    </div>

    @if($hasServiceLines)
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>{{ translate('Service') }}</th>
                    <th>{{ translate('Qty') }}</th>
                    <th class="text-end">{{ translate('Price') }}</th>
                    <th class="text-end">{{ translate('Total') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($serviceLines as $line)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $line['name'] !== '' ? $line['name'] : '—' }}</div>
                            @if(($line['variant'] ?? '') !== '')
                                <div class="fz-12 text-muted">{{ $line['variant'] }}</div>
                            @endif
                        </td>
                        <td>{{ $line['qty'] }}</td>
                        <td class="text-end">{{ with_currency_symbol($line['price']) }}</td>
                        <td class="text-end">{{ with_currency_symbol($line['total']) }}</td>
                    </tr>
                @endforeach
                @if($hasExtraLines)
                    @foreach($extraLines as $extra)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $extra['title'] !== '' ? $extra['title'] : '—' }}</div>
                                <span class="badge badge-{{ ($extra['type'] ?? '') === 'spare_part' ? 'info' : 'primary' }}">
                                    {{ ($extra['type'] ?? '') === 'spare_part' ? translate('Spare_Part') : translate('Extra_Service') }}
                                </span>
                            </td>
                            <td>{{ $extra['qty'] }}</td>
                            <td class="text-end">{{ with_currency_symbol($extra['price']) }}</td>
                            <td class="text-end">{{ with_currency_symbol($extra['total']) }}</td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    @endif

    @if($hasRemarks)
        <p class="mb-3 fz-12"><span class="text-muted">{{ translate('Visit_remarks') }}:</span> {{ $visitRemarks }}</p>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-2">
        @include('bookingmodule::admin.booking.partials._repeat-visit-status-actions', ['visit' => $visit])
        @if($hasInvoiceUrl)
            <a href="{{ $invoiceUrl }}" target="_blank" class="btn btn-demo-outline btn-sm">
                <span class="material-icons fz-16">description</span>
                {{ translate('Invoice') }}
            </a>
        @endif
    </div>
</div>
