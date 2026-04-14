@php
    /** @var \Modules\BookingModule\Entities\Booking $booking */
    $compRows = $booking->compensations ?? collect();
@endphp

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
            <h4 class="mb-0">{{ translate('Compensation') }}</h4>
            @can('booking_view')
                <button type="button" class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#compensationModal-{{ $booking->id }}">
                    {{ translate('Add_Compensation') }}
                </button>
            @endcan
        </div>

        @if($compRows->isEmpty())
            <div class="text-muted">{{ translate('No_compensation_recorded') }}</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Transaction_ID') }}</th>
                        <th class="text-end">{{ translate('Amount') }}</th>
                        <th>{{ translate('Recorded_by') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($compRows as $row)
                        @php
                            $flowKey = ($row->from_party ?? '') . '_to_' . ($row->to_party ?? '');
                            $typeLabel = match($flowKey) {
                                'company_to_customer' => translate('Company_to_customer_compensation'),
                                'company_to_provider' => translate('Company_to_provider_compensation'),
                                'provider_to_customer' => translate('Provider_to_customer_compensation'),
                                default => ucwords(str_replace('_', ' ', $flowKey)),
                            };
                            $by = $row->creator
                                ? (trim(($row->creator->first_name ?? '').' '.($row->creator->last_name ?? '')) ?: ($row->creator->email ?? '—'))
                                : '—';
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ $row->created_at ? $row->created_at->format('Y-m-d H:i') : ($row->date?->format('Y-m-d') ?? '—') }}</td>
                            <td>{{ $typeLabel }}</td>
                            <td class="text-break">{{ $row->transaction_id ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ with_currency_symbol((float) ($row->amount ?? 0)) }}</td>
                            <td>{{ $by }}</td>
                        </tr>
                        @if(!empty($row->reference_note))
                            <tr>
                                <td colspan="5" class="text-muted small">
                                    <strong>{{ translate('Reference_Note') }}:</strong>
                                    {{ $row->reference_note }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="compensationModal-{{ $booking->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.booking.compensation', $booking->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Add_Compensation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Compensation_type') }} <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="company_to_customer">{{ translate('Company_to_customer_compensation') }}</option>
                            <option value="company_to_provider">{{ translate('Company_to_provider_compensation') }}</option>
                            <option value="provider_to_customer">{{ translate('Provider_to_customer_compensation') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
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
                    <p class="small text-muted mb-0">{{ translate('Compensation_ledger_hint') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

