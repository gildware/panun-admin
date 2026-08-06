@extends('adminmodule::layouts.new-master')

@section('title', translate('Web_Booking_Details'))

@section('content')
    @php
        $leadMeta = $booking->lead ? ($leadDisplayData[$booking->lead->id] ?? null) : null;
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title mb-0">{{ translate('Web_Booking_Details') }}</h2>
                        <div class="d-flex flex-wrap gap-2">
                            @if($booking->lead)
                                <a href="{{ route('admin.lead.show', $booking->lead->id) }}" class="btn btn--primary">
                                    {{ translate('View_Lead') }}
                                </a>
                            @endif
                            @can('booking_delete')
                                <button type="button"
                                        class="btn btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#wbDeleteModal"
                                        data-wb-delete-url="{{ route('admin.booking.web-bookings.delete', $booking->id) }}"
                                        data-wb-delete-label="{{ $booking->reference_id }} — {{ $booking->name }}"
                                        onclick="(function(btn){var f=document.getElementById('wbDeleteForm');var l=document.getElementById('wbDeleteModalItem');if(f){f.action=btn.getAttribute('data-wb-delete-url')||'#';}if(l){l.textContent=btn.getAttribute('data-wb-delete-label')||'';}})(this)">
                                    {{ translate('Delete') }}
                                </button>
                            @endcan
                            <a href="{{ route('admin.booking.web-bookings.index') }}" class="btn btn--secondary">
                                {{ translate('Back') }}
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Reference') }}</div>
                                    <div class="fw-semibold">{{ $booking->reference_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Lead_ID') }}</div>
                                    @if($booking->lead)
                                        <a href="{{ route('admin.lead.show', $booking->lead->id) }}" class="link-primary fw-semibold">
                                            #{{ $booking->lead->id }}
                                        </a>
                                    @else
                                        <div class="fw-semibold">—</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Lead_Status') }}</div>
                                    @if($booking->lead && $leadMeta)
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
                                    <div class="text-muted small">{{ translate('Customer_Name') }}</div>
                                    <div class="fw-semibold">{{ $booking->name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Phone_Number') }}</div>
                                    <div class="fw-semibold">{{ $booking->phone }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Service') }}</div>
                                    <div class="fw-semibold">{{ $booking->service_category ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Area') }}</div>
                                    <div class="fw-semibold">{{ $booking->area ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Preferred_date_time') }}</div>
                                    <div class="fw-semibold">{{ $booking->preferred_date ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ translate('Submitted_at') }}</div>
                                    <div class="fw-semibold">{{ $booking->created_at?->format('d M Y h:i a') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">{{ translate('Details') }}</div>
                                    <div class="fw-semibold" style="white-space: pre-wrap;">{{ $booking->details ?: '—' }}</div>
                                </div>
                                @if($booking->lead)
                                    <div class="col-12">
                                        <div class="text-muted small">{{ translate('Lead') }}</div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('admin.lead.show', $booking->lead->id) }}" class="link-primary fw-semibold">
                                                #{{ $booking->lead->id }} — {{ $booking->lead->name ?: '—' }}
                                            </a>
                                            @if($booking->lead->source)
                                                <span class="badge bg-light text-dark">{{ $booking->lead->source->name }}</span>
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

    @include('bookingmodule::admin.web-booking.partials._delete-modal')
@endsection
