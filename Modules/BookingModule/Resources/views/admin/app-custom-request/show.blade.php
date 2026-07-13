@extends('adminmodule::layouts.new-master')

@section('title', translate('App_Custom_Request_Details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('App_Custom_Request_Details') }}</h2>
                        <a href="{{ route('admin.booking.app-custom-requests.index') }}" class="btn btn--secondary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Reference') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->reference_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Status') }}</div>
                                    <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', strtolower($customRequest->status)) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Customer_Name') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Phone_Number') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->phone }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Category') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->category_name ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Submitted_at') }}</div>
                                    <div class="fw-semibold">{{ $customRequest->created_at?->format('d M Y h:i a') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Description') }}</div>
                                    <div class="fw-semibold" style="white-space: pre-wrap;">{{ $customRequest->description ?: '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Lead') }}</div>
                                    @if($customRequest->lead)
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('admin.lead.show', $customRequest->lead->id) }}" class="link-primary fw-semibold">
                                                #{{ $customRequest->lead->id }} — {{ $customRequest->lead->name ?: '—' }}
                                            </a>
                                            @if($customRequest->lead->source)
                                                <span class="badge bg-light text-dark">{{ $customRequest->lead->source->name }}</span>
                                            @endif
                                            <a href="{{ route('admin.booking.create-from-lead', $customRequest->lead->id) }}" class="btn btn-sm btn--primary">
                                                {{ translate('Create_Booking') }}
                                            </a>
                                        </div>
                                    @else
                                        <div>—</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
