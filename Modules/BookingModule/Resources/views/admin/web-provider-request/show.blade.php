@extends('adminmodule::layouts.new-master')

@section('title', translate('Web_Provider_Request_Details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Web_Provider_Request_Details') }}</h2>
                        <a href="{{ route('admin.booking.web-provider-requests.index') }}" class="btn btn--secondary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Reference') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->reference_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Status') }}</div>
                                    <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', strtolower($providerRequest->status)) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Provider_Name') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Phone_Number') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->phone }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Service') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->service_category ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Area') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->area ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Experience') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->experience ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Submitted_at') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->created_at?->format('d M Y h:i a') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Details') }}</div>
                                    <div class="fw-semibold" style="white-space: pre-wrap;">{{ $providerRequest->details ?: '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Lead') }}</div>
                                    @if($providerRequest->lead)
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('admin.lead.show', $providerRequest->lead->id) }}" class="link-primary fw-semibold">
                                                #{{ $providerRequest->lead->id }} — {{ $providerRequest->lead->name ?: '—' }}
                                            </a>
                                            @if($providerRequest->lead->source)
                                                <span class="badge bg-light text-dark">{{ $providerRequest->lead->source->name }}</span>
                                            @endif
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
