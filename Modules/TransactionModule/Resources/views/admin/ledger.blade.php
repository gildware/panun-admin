@extends('adminmodule::layouts.master')

@section('title', translate('Ledger'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Ledger') }}</h2>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-success bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Total_In') }}</p>
                                    <h4 class="mb-0 text-success">{{ with_currency_symbol($totalIn) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Total_Out') }}</p>
                                    <h4 class="mb-0 text-danger">{{ with_currency_symbol($totalOut) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-primary bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Net') }}</p>
                                    <h4 class="mb-0 text-primary">{{ with_currency_symbol(round($totalIn - $totalOut, 2)) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="get" action="{{ route('admin.ledger.index') }}" class="row g-3 mb-4">
                                <div class="col-auto">
                                    <label class="form-label">{{ translate('From_Date') }}</label>
                                    <input type="date" name="from_date" class="form-control" value="{{ $from_date }}">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">{{ translate('To_Date') }}</label>
                                    <input type="date" name="to_date" class="form-control" value="{{ $to_date }}">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">{{ translate('Type') }}</label>
                                    <select name="type" class="form-select">
                                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                                        <option value="IN" {{ $type === 'IN' ? 'selected' : '' }}>{{ translate('In') }}</option>
                                        <option value="OUT" {{ $type === 'OUT' ? 'selected' : '' }}>{{ translate('Out') }}</option>
                                    </select>
                                </div>
                                <div class="col-auto d-flex align-items-end">
                                    <button type="submit" class="btn btn--primary">{{ translate('Filter') }}</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table align-middle table-hover">
                                    <thead class="text-nowrap">
                                    <tr>
                                        <th>{{ translate('Sl') }}</th>
                                        <th>{{ translate('Date') }}</th>
                                        <th>{{ translate('Type') }}</th>
                                        <th>{{ translate('Description') }}</th>
                                        <th>{{ translate('payment_method') }}</th>
                                        <th>{{ translate('Booking_ID') }}</th>
                                        <th>{{ translate('Transaction_ID') }}</th>
                                        <th>{{ translate('Entry_by') }}</th>
                                        <th class="text-end">{{ translate('Amount') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($entries as $key => $entry)
                                        <tr>
                                            <td>{{ $key + $entries->firstItem() }}</td>
                                            <td>{{ $entry->created_at ? $entry->created_at->format('jS F Y : g i A') : '—' }}</td>
                                            <td>
                                                @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                    <span class="badge bg-success">{{ translate('In') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ translate('Out') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                    @if($entry->booking)
                                                        {{ translate('Booking_payment') }}
                                                    @elseif($entry->payment_method === 'collect_from_provider')
                                                        {{ translate('Cash_collected_from_provider') }}
                                                    @elseif($entry->payment_method === 'advance_on_booking_create')
                                                        {{ translate('Advance_payment_on_booking_create') }}
                                                    @else
                                                        {{ translate('Payment_received') }} {{ $entry->payment_method ? '('.str_replace('_', ' ', $entry->payment_method).')' : '' }}
                                                    @endif
                                                @else
                                                    @if($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
                                                        {{ translate('Refund') }}{{ $entry->reference_note ? ' — ' . \Illuminate\Support\Str::limit($entry->reference_note, 60) : '' }}
                                                    @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_PROVIDER_PAYOUT)
                                                        {{ translate('Provider_payout') }}{{ $entry->reference_note ? ' - '.Str::limit($entry->reference_note, 40) : '' }}
                                                    @else
                                                        {{ $entry->reason ? str_replace('_', ' ', $entry->reason) : translate('Out') }}
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ $entry->formatPaymentMethodForDisplay() }}</td>
                                            <td>
                                                @if($entry->booking_id && $entry->relationLoaded('booking') && $entry->booking)
                                                    <a href="{{ route('admin.booking.details', [$entry->booking_id]) }}" class="text-primary text-decoration-none">{{ $entry->booking->readable_id ?? $entry->booking_id }}</a>
                                                @elseif($entry->booking_id)
                                                    {{ $entry->booking_id }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $entry->transaction_id ?: '—' }}</td>
                                            <td>{{ $entry->resolvedEntryByLabel() }}</td>
                                            <td class="text-end fw-medium">
                                                @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                    <span class="text-success">+ {{ with_currency_symbol($entry->amount) }}</span>
                                                @else
                                                    <span class="text-danger">- {{ with_currency_symbol($entry->amount) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">{{ translate('No data available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                {!! $entries->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
