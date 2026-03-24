@extends('adminmodule::layouts.master')

@section('title', translate('Payment'))

@push('css_or_js')
    <style>
        .flow-card {
            border: 1px solid transparent;
        }
        .flow-card--company-in {
            border-color: rgba(13, 110, 253, .35);
            background: rgba(13, 110, 253, .06);
        }
        .flow-card--company-out {
            border-color: rgba(220, 53, 69, .35);
            background: rgba(220, 53, 69, .06);
        }
        .flow-card--provider-in {
            border-color: rgba(25, 135, 84, .35);
            background: rgba(25, 135, 84, .06);
        }
        .flow-pill {
            border-radius: 999px;
            font-size: .75rem;
            padding: .2rem .55rem;
            font-weight: 700;
            display: inline-block;
            border: 1px solid transparent;
        }
        .flow-pill--company-in {
            color: #0d6efd;
            background: rgba(13, 110, 253, .12);
            border-color: rgba(13, 110, 253, .35);
        }
        .flow-pill--company-out {
            color: #dc3545;
            background: rgba(220, 53, 69, .12);
            border-color: rgba(220, 53, 69, .35);
        }
        .flow-pill--provider-in {
            color: #198754;
            background: rgba(25, 135, 84, .12);
            border-color: rgba(25, 135, 84, .35);
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-4">
                @php
                    $customerDisplayName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    $customerDisplayName = $customerDisplayName !== '' ? $customerDisplayName : ($customer->email ?? translate('Customer'));
                    $customerStatus = (string) ($customer->manual_performance_status ?? 'active');
                    $customerStatusLabel = match($customerStatus) {
                        'blacklisted' => translate('Blacklisted'),
                        'suspended' => translate('Suspended'),
                        default => translate('Active'),
                    };
                    $customerStatusClass = match($customerStatus) {
                        'blacklisted' => 'bg-danger',
                        'suspended' => 'bg-warning text-dark',
                        default => 'bg-success',
                    };
                @endphp
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="page-title mb-2">{{ $customerDisplayName }}</h2>
                        <div>{{ translate('Joined_on') }} {{ date('d-M-y H:iA', strtotime($customer?->created_at)) }}</div>
                    </div>
                    <span class="badge {{ $customerStatusClass }}">{{ $customerStatusLabel }}</span>
                </div>
            </div>

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'payments'])

            <div class="card">
                <div class="card-body p-30">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <h2 class="mb-0">{{ translate('Payment') }}</h2>
                        <div class="text-muted fs-12">{{ translate('Booking_wise_customer_payment_breakdown') }}</div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="statistics-card statistics-card__style2 flow-card flow-card--company-in h-100">
                                <h3>{{ translate('Customer_paid_to_company') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($totals->customer_paid_to_company ?? 0)) }}</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="statistics-card statistics-card__style2 flow-card flow-card--company-out h-100">
                                <h3>{{ translate('Company_paid_to_customer') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($totals->company_paid_to_customer ?? 0)) }}</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="statistics-card statistics-card__style2 flow-card flow-card--provider-in h-100">
                                <h3>{{ translate('Customer_paid_to_provider') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($totals->customer_paid_to_provider ?? 0)) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-bordered">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Date') }}</th>
                                <th>{{ translate('Booking_Id') }}</th>
                                <th>{{ translate('Flow') }}</th>
                                <th>{{ translate('Channel') }}</th>
                                <th>{{ translate('Transaction_ID') }}</th>
                                <th class="text-end">{{ translate('Amount') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($paginatedTransactions as $row)
                                @php
                                    $flowLabel = match($row->flow) {
                                        'customer_paid_to_company' => translate('Customer_paid_to_company'),
                                        'company_paid_to_customer' => translate('Company_paid_to_customer'),
                                        'customer_paid_to_provider' => translate('Customer_paid_to_provider'),
                                        default => '-',
                                    };
                                    $flowPillClass = match($row->flow) {
                                        'customer_paid_to_company' => 'flow-pill--company-in',
                                        'company_paid_to_customer' => 'flow-pill--company-out',
                                        'customer_paid_to_provider' => 'flow-pill--provider-in',
                                        default => '',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $row->date ? \Illuminate\Support\Carbon::parse($row->date)->format('Y-m-d H:i') : '-' }}</td>
                                    <td>
                                        <a href="{{ route('admin.booking.details', [$row->booking_id]) }}" class="fw-semibold text-decoration-none">
                                            #{{ $row->booking_readable_id ?? $row->booking_id }}
                                        </a>
                                    </td>
                                    <td><span class="flow-pill {{ $flowPillClass }}">{{ $flowLabel }}</span></td>
                                    <td>{{ ucwords(str_replace('_', ' ', (string) $row->channel)) }}</td>
                                    <td>{{ $row->transaction_id ?: '-' }}</td>
                                    <td class="text-end">{{ with_currency_symbol((float) $row->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">{{ translate('No_payment_records_found') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $paginatedTransactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
