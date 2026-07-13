@extends('adminmodule::layouts.new-master')

@section('title', translate('Web_Bookings'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Web_Bookings') }}</h2>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.booking.web-bookings.index') }}">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-8">
                                        <input type="text"
                                               name="search"
                                               class="form-control"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_name_phone_service_or_reference') }}">
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary" href="{{ route('admin.booking.web-bookings.index') }}">
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
                                        <th>{{ translate('Reference') }}</th>
                                        <th>{{ translate('Customer_Name') }}</th>
                                        <th>{{ translate('Phone_Number') }}</th>
                                        <th>{{ translate('Service') }}</th>
                                        <th>{{ translate('Area') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Lead') }}</th>
                                        <th>{{ translate('Date_Time') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($bookings as $key => $booking)
                                        <tr>
                                            <td>{{ $bookings->firstItem() + $key }}</td>
                                            <td>
                                                <a href="{{ route('admin.booking.web-bookings.show', $booking->id) }}" class="link-primary">
                                                    {{ $booking->reference_id }}
                                                </a>
                                            </td>
                                            <td>{{ $booking->name }}</td>
                                            <td>{{ $booking->phone }}</td>
                                            <td>{{ $booking->service_category ?: '—' }}</td>
                                            <td>{{ $booking->area ?: '—' }}</td>
                                            <td>
                                                <span class="badge bg-info text-capitalize">
                                                    {{ str_replace('_', ' ', strtolower($booking->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($booking->lead)
                                                    <a href="{{ route('admin.lead.show', $booking->lead->id) }}?in_modal=1" class="link-primary">
                                                        #{{ $booking->lead->id }} — {{ $booking->lead->name ?: '—' }}
                                                    </a>
                                                    @if($booking->lead->source)
                                                        <div class="text-muted small">{{ $booking->lead->source->name }}</div>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $booking->created_at?->format('d M Y h:i a') }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ route('admin.booking.web-bookings.show', $booking->id) }}" class="btn btn-sm btn--secondary">
                                                        {{ translate('View') }}
                                                    </a>
                                                    @if($booking->lead)
                                                        <a href="{{ route('admin.booking.create-from-lead', $booking->lead->id) }}" class="btn btn-sm btn--primary">
                                                            {{ translate('Create_Booking') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">{{ translate('No_data_available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                {!! $bookings->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
