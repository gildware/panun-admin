@extends('adminmodule::layouts.new-master')

@section('title', translate('Workflow_Stuck_Items'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h2 class="h4 mb-1">{{ translate('Workflow_Stuck_Items') }}</h2>
                    <p class="text-muted small mb-0">{{ translate('Leads_and_bookings_with_incomplete_workflow_steps') }}</p>
                </div>
                <a href="{{ route('admin.process-guides.index') }}" class="btn btn--secondary btn-sm">
                    {{ translate('Process_Guides') }}
                </a>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="h6 mb-0">{{ translate('Stuck_Leads') }} ({{ count($stuckLeads) }})</h3>
                        </div>
                        <div class="card-body p-0">
                            @if(empty($stuckLeads))
                                <p class="text-muted small p-3 mb-0">{{ translate('No_stuck_leads_found') }}</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ translate('Lead') }}</th>
                                                <th>{{ translate('Next_step') }}</th>
                                                <th>{{ translate('Pending') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stuckLeads as $row)
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold small">{{ $row['name'] ?? '—' }}</div>
                                                        <div class="text-muted" style="font-size:11px;">{{ $row['phone'] ?? '' }}</div>
                                                    </td>
                                                    <td class="small">{{ $row['next_step']['label'] ?? '—' }}</td>
                                                    <td><span class="badge bg-warning text-dark">{{ $row['pending_count'] ?? 0 }}</span></td>
                                                    <td><a href="{{ $row['url'] }}" class="btn btn-sm btn--primary">{{ translate('Open') }}</a></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="h6 mb-0">{{ translate('Stuck_Bookings') }} ({{ count($stuckBookings) }})</h3>
                        </div>
                        <div class="card-body p-0">
                            @if(empty($stuckBookings))
                                <p class="text-muted small p-3 mb-0">{{ translate('No_stuck_bookings_found') }}</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ translate('Booking') }}</th>
                                                <th>{{ translate('Status') }}</th>
                                                <th>{{ translate('Next_step') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stuckBookings as $row)
                                                <tr>
                                                    <td class="fw-semibold small">#{{ $row['readable_id'] ?? $row['entity_id'] }}</td>
                                                    <td class="small">{{ $row['booking_status'] ?? '—' }}</td>
                                                    <td class="small">{{ $row['next_step']['label'] ?? '—' }}</td>
                                                    <td><a href="{{ $row['url'] }}" class="btn btn-sm btn--primary">{{ translate('Open') }}</a></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
