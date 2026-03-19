@extends('adminmodule::layouts.new-master')

@section('title', translate('Outbound_Enquiries'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Outbound_Enquiries') }}</h2>
                        <div>
                            <a href="{{ route('admin.lead.outbound-enquiry.create') }}" class="btn btn--primary">
                                <span class="material-icons">add</span>
                                {{ translate('Add_Outbound_Enquiry') }}
                            </a>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.lead.outbound-enquiry.index') }}">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-8">
                                        <input type="text"
                                               name="search"
                                               class="form-control"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_customer_phone_status') }}">
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary" href="{{ route('admin.lead.outbound-enquiry.index') }}">
                                            {{ translate('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Customer_Name') }}</th>
                                        <th>{{ translate('Phone_Number') }}</th>
                                        <th>{{ translate('Contacted_Through') }}</th>
                                        <th>{{ translate('Status') }}</th>
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
                                            <td>{{ $enquiry->customer_name }}</td>
                                            <td>{{ $enquiry->phone_number }}</td>
                                            <td class="text-capitalize">{{ $enquiry->contacted_through }}</td>
                                            <td>{{ $statusName }}</td>
                                            <td>
                                                {{ $enquiry->contacted_at ? $enquiry->contacted_at->format('d F Y h:i a') : '—' }}
                                            </td>
                                            <td>{{ $employeeName }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($enquiry->remarks ?? '—', 60) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                {{ translate('No_data_found') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                {!! $enquiries->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

