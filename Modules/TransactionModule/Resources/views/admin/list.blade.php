@extends('adminmodule::layouts.master')

@section('title', translate('transaction_list'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div
                        class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('transaction_list') }}</h2>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-success bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Total_In') }}</p>
                                    <h4 class="mb-0 text-success">{{ with_currency_symbol($data['totalIn'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Total_Out') }}</p>
                                    <h4 class="mb-0 text-danger">{{ with_currency_symbol($data['totalOut'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-primary bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Net') }}</p>
                                    <h4 class="mb-0 text-primary">{{ with_currency_symbol(round(($data['totalIn'] ?? 0) - ($data['totalOut'] ?? 0), 2)) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{ $trxType == 'all' ? 'active' : '' }}"
                                   href="{{ url()->current() }}?trx_type=all{{ $from_date ? '&from_date=' . urlencode($from_date) : '' }}{{ $to_date ? '&to_date=' . urlencode($to_date) : '' }}{{ $search !== '' ? '&search=' . urlencode($search) : '' }}">
                                    {{ translate('all') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $trxType == 'credit' ? 'active' : '' }}"
                                   href="{{ url()->current() }}?trx_type=credit{{ $from_date ? '&from_date=' . urlencode($from_date) : '' }}{{ $to_date ? '&to_date=' . urlencode($to_date) : '' }}{{ $search !== '' ? '&search=' . urlencode($search) : '' }}">
                                    {{ translate('In') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $trxType == 'debit' ? 'active' : '' }}"
                                   href="{{ url()->current() }}?trx_type=debit{{ $from_date ? '&from_date=' . urlencode($from_date) : '' }}{{ $to_date ? '&to_date=' . urlencode($to_date) : '' }}{{ $search !== '' ? '&search=' . urlencode($search) : '' }}">
                                    {{ translate('Out') }}
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{ translate('Total_Transactions') }}:</span>
                            <span class="title-color">{{ $transactions->total() }}</span>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all-tab-pane">
                            <div class="card">
                                <div class="card-body">
                                    <form method="get" action="{{ url()->current() }}" class="row g-3 mb-4">
                                        <input type="hidden" name="trx_type" value="{{ $trxType }}">
                                        <div class="col-auto">
                                            <label class="form-label">{{ translate('From_Date') }}</label>
                                            <input type="date" name="from_date" class="form-control" value="{{ $from_date }}">
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">{{ translate('To_Date') }}</label>
                                            <input type="date" name="to_date" class="form-control" value="{{ $to_date }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ translate('search') }}</label>
                                            <input type="search" class="form-control theme-input-style" name="search"
                                                   value="{{ $search }}"
                                                   placeholder="{{ translate('search_by_trx_id') }}, {{ translate('Booking_ID') }}…">
                                        </div>
                                        <div class="col-auto d-flex align-items-end">
                                            <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                                        </div>
                                    </form>

                                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                                        @can('transaction_export')
                                            <div class="d-flex flex-wrap align-items-center gap-3 ms-auto">
                                                <div class="dropdown">
                                                    <button type="button"
                                                            class="btn btn--secondary text-capitalize dropdown-toggle"
                                                            data-bs-toggle="dropdown">
                                                        <span class="material-icons">file_download</span> {{ translate('download') }}
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('admin.transaction.download') }}?{{ http_build_query(array_filter(['search' => $search, 'trx_type' => $trxType, 'from_date' => $from_date, 'to_date' => $to_date], fn ($v) => $v !== null && $v !== '')) }}">
                                                                {{ translate('excel') }}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        @endcan
                                    </div>

                                    <div class="table-responsive">
                                        <table id="example" class="table align-middle table-hover">
                                            <thead class="text-nowrap">
                                            <tr>
                                                <th>{{ translate('Sl') }}</th>
                                                <th>{{ translate('Date') }}</th>
                                                <th>{{ translate('Type') }}</th>
                                                <th>{{ translate('Description') }}</th>
                                                <th>{{ translate('Flow') }}</th>
                                                <th>{{ translate('Channel') }}</th>
                                                <th>{{ translate('Received_by') }}</th>
                                                <th>{{ translate('Booking_ID') }}</th>
                                                <th>{{ translate('Transaction_ID') }}</th>
                                                <th>{{ translate('Entry_by') }}</th>
                                                <th class="text-end">{{ translate('Amount') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($transactions as $key => $entry)
                                                <tr>
                                                    <td>{{ $key + $transactions->firstItem() }}</td>
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
                                                            @if($entry->payment_method === 'collect_from_provider')
                                                                {{ translate('Cash_collected_from_provider') }}
                                                            @elseif($entry->payment_method === 'advance_on_booking_create')
                                                                {{ translate('Advance_payment_on_booking_create') }}
                                                            @elseif($entry->booking)
                                                                {{ translate('Booking_payment') }}
                                                            @else
                                                                {{ translate('Payment_received') }}{{ $entry->payment_method ? ' (' . str_replace('_', ' ', $entry->payment_method) . ')' : '' }}
                                                            @endif
                                                        @else
                                                            @if($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
                                                                {{ translate('Refund') }}
                                                            @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_PROVIDER_PAYOUT)
                                                                {{ translate('Provider_payout') }}{{ $entry->provider?->company_name ? ' — ' . Str::limit($entry->provider->company_name, 40) : '' }}{{ $entry->reference_note ? ' — ' . Str::limit($entry->reference_note, 40) : '' }}
                                                            @else
                                                                {{ $entry->reason ? str_replace('_', ' ', $entry->reason) : translate('Out') }}
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                            @if($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_PROVIDER)
                                                                {{ translate('Customer_paid_to_provider') }}
                                                            @elseif($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_COMPANY)
                                                                {{ translate('Customer_paid_to_company') }}
                                                            @else
                                                                —
                                                            @endif
                                                        @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
                                                            {{ translate('Company_paid_to_customer') }}
                                                        @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_PROVIDER_PAYOUT)
                                                            {{ translate('Provider_payout') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td class="text-nowrap">
                                                        {{ $entry->payment_method ? str_replace('_', ' ', $entry->payment_method) : '—' }}
                                                    </td>
                                                    <td>
                                                        @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                            @if($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_PROVIDER)
                                                                {{ translate('Received_by_provider') }}
                                                            @elseif($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_COMPANY)
                                                                {{ translate('Received_by_company') }}
                                                            @else
                                                                —
                                                            @endif
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($entry->booking_id && $entry->relationLoaded('booking') && $entry->booking)
                                                            <a href="{{ route('admin.booking.details', [$entry->booking_id]) }}"
                                                               class="text-primary text-decoration-none">{{ $entry->booking->readable_id ?? $entry->booking_id }}</a>
                                                        @elseif($entry->booking_id)
                                                            {{ $entry->booking_id }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ $entry->transaction_id ?: '—' }}</td>
                                                    <td>
                                                        @if($entry->creator)
                                                            {{ trim($entry->creator->first_name . ' ' . $entry->creator->last_name) ?: $entry->creator->email }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td class="text-end fw-medium">
                                                        @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                            <span class="text-success">+ {{ with_currency_symbol($entry->amount) }}</span>
                                                        @else
                                                            <span class="text-danger">- {{ with_currency_symbol($entry->amount) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="text-center">
                                                    <td colspan="11" class="py-4 text-muted">{{ translate('No data available') }}</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        {!! $transactions->links() !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
