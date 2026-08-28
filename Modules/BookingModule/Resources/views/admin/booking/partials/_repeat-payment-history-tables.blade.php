@php
    $installmentPayload = booking_installment_payments_payload($booking);
    $installmentRows = [];
    foreach ($installmentPayload['rows'] ?? [] as $row) {
        $dateRaw = (string) ($row['date'] ?? '');
        $installmentRows[] = [
            'serial' => $row['serial'] ?? 0,
            'date_label' => $dateRaw !== '' ? \Carbon\Carbon::parse($dateRaw)->format('d M Y, H:i:s') : '—',
            'received_by_label' => $row['received_by_label'] ?? '—',
            'amount' => $row['amount'] ?? 0,
            'payment_method_label' => $row['payment_method_label'] ?? '—',
            'transaction_id' => ! empty($row['transaction_id']) ? $row['transaction_id'] : '—',
            'due_after_payment' => $row['due_after_payment'] ?? 0,
        ];
    }
    $hasInstallments = count($installmentRows) > 0;
    $refundLedgerRows = \Modules\TransactionModule\Entities\LedgerTransaction::query()
        ->where('booking_id', $booking->id)
        ->where('reason', \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
        ->where('type', \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();
    $refundDisplayRows = [];
    foreach ($refundLedgerRows as $idx => $lt) {
        $note = trim((string) ($lt->reference_note ?? ''));
        $refundDisplayRows[] = [
            'serial' => $idx + 1,
            'date_label' => $lt->created_at ? $lt->created_at->format('d M Y, H:i:s') : '—',
            'amount' => (float) ($lt->amount ?? 0),
            'transaction_id' => $lt->transaction_id ? (string) $lt->transaction_id : '—',
            'note' => $note !== '' ? \Illuminate\Support\Str::limit($note, 120) : '—',
        ];
    }
    $hasRefundLedger = count($refundDisplayRows) > 0;
    $snap = booking_provider_api_payment_snapshot($booking);
    $showRefundSnapshot = array_key_exists('refundable_amount', $snap) && (float) ($snap['refundable_amount'] ?? 0) > 0.009;
    $showRefundHistoryBlock = $hasRefundLedger || $showRefundSnapshot;
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
            </tr>
        </thead>
        <tbody>
            @if($hasInstallments)
                @foreach($installmentRows as $pp)
                    <tr>
                        <td>{{ $pp['serial'] }}</td>
                        <td class="text-nowrap">{{ $pp['date_label'] }}</td>
                        <td>{{ $pp['received_by_label'] }}</td>
                        <td class="text-end fw-medium">{{ with_currency_symbol($pp['amount']) }}</td>
                        <td class="text-break">{{ $pp['payment_method_label'] }}</td>
                        <td class="text-break">{{ $pp['transaction_id'] }}</td>
                        <td class="text-end">{{ with_currency_symbol($pp['due_after_payment']) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">{{ translate('No data available') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

@if($showRefundHistoryBlock)
    <p class="text-uppercase text-muted fz-11 mb-2 fw-semibold mt-4">{{ translate('Refunds_to_customer') }}</p>
    @if($hasRefundLedger)
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
                    @foreach($refundDisplayRows as $rr)
                        <tr>
                            <td>{{ $rr['serial'] }}</td>
                            <td class="text-nowrap">{{ $rr['date_label'] }}</td>
                            <td class="text-end fw-medium text-danger">-{{ with_currency_symbol($rr['amount']) }}</td>
                            <td class="text-break">{{ $rr['transaction_id'] }}</td>
                            <td class="text-break">{{ $rr['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted fz-12 mb-0">{{ translate('No_refund_transactions_recorded_yet') }}</p>
    @endif
@endif
