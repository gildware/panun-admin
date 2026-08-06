@extends('adminmodule::layouts.new-master')

@section('title', translate('Web_Provider_Request_Details'))

@section('content')
    @php
        $leadMeta = $providerRequest->lead ? ($leadDisplayData[$providerRequest->lead->id] ?? null) : null;
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title mb-0">{{ translate('Web_Provider_Request_Details') }}</h2>
                        <div class="d-flex flex-wrap gap-2">
                            @if($providerRequest->lead)
                                <a href="{{ route('admin.lead.show', $providerRequest->lead->id) }}" class="btn btn--primary">
                                    {{ translate('View_Lead') }}
                                </a>
                            @endif
                            @can('booking_delete')
                                <button type="button"
                                        class="btn btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#wprDeleteModal"
                                        data-wpr-delete-url="{{ route('admin.booking.web-provider-requests.destroy', $providerRequest->id) }}"
                                        data-wpr-delete-label="{{ $providerRequest->reference_id }} — {{ $providerRequest->name }}">
                                    {{ translate('Delete') }}
                                </button>
                            @endcan
                            <a href="{{ route('admin.booking.web-provider-requests.index') }}" class="btn btn--secondary">
                                {{ translate('Back') }}
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Reference') }}</div>
                                    <div class="fw-semibold">{{ $providerRequest->reference_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Lead_ID') }}</div>
                                    @if($providerRequest->lead)
                                        <a href="{{ route('admin.lead.show', $providerRequest->lead->id) }}" class="link-primary fw-semibold">
                                            #{{ $providerRequest->lead->id }}
                                        </a>
                                    @else
                                        <div class="fw-semibold">—</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Lead_Status') }}</div>
                                    @if($providerRequest->lead && $leadMeta)
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge" style="background-color: {{ $leadMeta['status_color'] }}; color: #fff;">
                                                {{ $leadMeta['status_name'] }}
                                            </span>
                                            <span class="badge rounded-pill {{ $leadMeta['open_badge_class'] }}">
                                                {{ $leadMeta['open_label'] }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="fw-semibold">—</div>
                                    @endif
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
                                @if($providerRequest->lead)
                                    <div class="col-12">
                                        <div class="text-muted small">{{ translate('Lead') }}</div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('admin.lead.show', $providerRequest->lead->id) }}" class="link-primary fw-semibold">
                                                #{{ $providerRequest->lead->id }} — {{ $providerRequest->lead->name ?: '—' }}
                                            </a>
                                            @if($providerRequest->lead->source)
                                                <span class="badge bg-light text-dark">{{ $providerRequest->lead->source->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('bookingmodule::admin.web-provider-request.partials._delete-modal')
@endsection
