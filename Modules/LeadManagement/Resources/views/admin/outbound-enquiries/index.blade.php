@extends('adminmodule::layouts.new-master')

@section('title', translate('Outbound_Enquiries'))

@section('content')
    <style>
        .oe-table-card .card-body { padding: 1rem 1.25rem !important; }
        .oe-compact-table { font-size: 0.8125rem; margin-bottom: 0; }
        .oe-compact-table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.45rem 0.5rem;
            white-space: nowrap;
        }
        .oe-compact-table tbody td {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }
        .oe-compact-table .badge {
            font-size: 0.6875rem;
            padding: 0.2rem 0.45rem;
        }
        .oe-customer-cell {
            line-height: 1.25;
        }
        .oe-customer-cell .oe-phone {
            font-size: 0.75rem;
            color: var(--bs-secondary-color, #6c757d);
        }
    </style>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Outbound_Enquiries') }}</h2>
                        <div>
                            <a href="{{ route('admin.lead.outbound-enquiry.create') }}" class="btn btn--primary btn-sm">
                                <span class="material-icons" style="font-size:16px;">add</span>
                                {{ translate('Add_Outbound_Enquiry') }}
                            </a>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            <form method="GET" action="{{ route('admin.lead.outbound-enquiry.index') }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-8">
                                        <input type="text"
                                               name="search"
                                               class="form-control form-control-sm"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_customer_phone_status') }}">
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary btn-sm" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary btn-sm" href="{{ route('admin.lead.outbound-enquiry.index') }}">
                                            {{ translate('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card oe-table-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle oe-compact-table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Customer_Name') }}</th>
                                        <th>{{ translate('Contacted_Through') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Link_Lead') }}</th>
                                        <th>{{ translate('Booking_ID') }}</th>
                                        <th>{{ translate('Date_Time') }}</th>
                                        <th>{{ translate('Handled_By') }}</th>
                                        <th>{{ translate('Remarks') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($enquiries as $key => $enquiry)
                                        @php
                                            $employee = $enquiry->handledBy ?: $enquiry->createdBy;
                                            $employeeName = $employee ? (trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: $employee->email) : '—';
                                            $statusName = $enquiry->statusConfig?->name ?? $enquiry->status ?? '—';
                                        @endphp
                                        <tr>
                                            <td>{{ $enquiries->firstItem() + $key }}</td>
                                            <td class="oe-customer-cell">
                                                <div>{{ $enquiry->customer_name }}</div>
                                                <div class="oe-phone">{{ $enquiry->phone_number }}</div>
                                                @if($enquiry->isFromFutureCustomerLead())
                                                    <span class="badge rounded-pill bg-info text-capitalize mt-1">{{ translate('Future_Customer') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-capitalize">{{ $enquiry->contacted_through }}</td>
                                            <td>{{ $statusName }}</td>
                                            <td>
                                                @if($enquiry->relatedLead)
                                                    <a href="{{ route('admin.lead.show', $enquiry->relatedLead->id) }}" class="link-primary">
                                                        #{{ $enquiry->relatedLead->id }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($enquiry->booking)
                                                    <a href="{{ route('admin.booking.details', $enquiry->booking->id) }}" class="link-primary">
                                                        {{ $enquiry->booking->readable_id ?: $enquiry->booking->id }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                {{ $enquiry->contacted_at ? $enquiry->contacted_at->format('d M Y, h:i A') : '—' }}
                                            </td>
                                            <td>{{ $employeeName }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($enquiry->remarks ?? '—', 40) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-3">
                                                {{ translate('No_data_found') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                {!! $enquiries->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
