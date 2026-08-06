@extends('adminmodule::layouts.new-master')

@section('title', translate('Web_Bookings'))

@section('content')
    <style>
        .wb-table-card .card-body { padding: 1rem 1.25rem !important; }
        .wb-compact-table { font-size: 0.8125rem; margin-bottom: 0; }
        .wb-compact-table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.45rem 0.5rem;
            white-space: nowrap;
        }
        .wb-compact-table tbody td {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }
        .wb-compact-table .badge {
            font-size: 0.6875rem;
            padding: 0.2rem 0.45rem;
        }
        .wb-action-btns {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }
        .wb-action-btns .btn {
            font-size: 0.6875rem;
            line-height: 1.2;
            padding: 0.2rem 0.45rem;
        }
        .wb-status-badges {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }
    </style>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Web_Bookings') }}</h2>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            <form method="GET" action="{{ route('admin.booking.web-bookings.index') }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-8">
                                        <input type="text"
                                               name="search"
                                               class="form-control form-control-sm"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_name_phone_service_or_reference') }}">
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary btn-sm" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary btn-sm" href="{{ route('admin.booking.web-bookings.index') }}">
                                            {{ translate('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card wb-table-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle wb-compact-table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Reference') }}</th>
                                        <th>{{ translate('Lead_ID') }}</th>
                                        <th>{{ translate('Customer_Name') }}</th>
                                        <th>{{ translate('Phone_Number') }}</th>
                                        <th>{{ translate('Service') }}</th>
                                        <th>{{ translate('Area') }}</th>
                                        <th>{{ translate('Lead_Status') }}</th>
                                        <th>{{ translate('Date_Time') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($bookings as $key => $booking)
                                        @php
                                            $leadMeta = $booking->lead ? ($leadDisplayData[$booking->lead->id] ?? null) : null;
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $bookings->firstItem() + $key }}</td>
                                            <td class="text-nowrap">
                                                <a href="{{ route('admin.booking.web-bookings.show', $booking->id) }}" class="link-primary">
                                                    {{ $booking->reference_id }}
                                                </a>
                                            </td>
                                            <td class="text-nowrap">
                                                @if($booking->lead)
                                                    <a href="{{ route('admin.lead.show', $booking->lead->id) }}" class="link-primary fw-semibold">
                                                        #{{ $booking->lead->id }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $booking->name }}</td>
                                            <td class="text-nowrap">{{ $booking->phone }}</td>
                                            <td>{{ $booking->service_category ?: '—' }}</td>
                                            <td>{{ $booking->area ?: '—' }}</td>
                                            <td>
                                                @if($booking->lead && $leadMeta)
                                                    <div class="wb-status-badges">
                                                        <span class="badge" style="background-color: {{ $leadMeta['status_color'] }}; color: #fff;">
                                                            {{ $leadMeta['status_name'] }}
                                                        </span>
                                                        <span class="badge rounded-pill {{ $leadMeta['open_badge_class'] }}">
                                                            {{ $leadMeta['open_label'] }}
                                                        </span>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ $booking->created_at?->format('d M Y h:i a') }}</td>
                                            <td>
                                                <div class="wb-action-btns">
                                                    <a href="{{ route('admin.booking.web-bookings.show', $booking->id) }}" class="btn btn--secondary">
                                                        {{ translate('View') }}
                                                    </a>
                                                    @if($booking->lead)
                                                        <a href="{{ route('admin.lead.show', $booking->lead->id) }}" class="btn btn--primary">
                                                            {{ translate('View_Lead') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-3">{{ translate('No_data_available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                {!! $bookings->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
