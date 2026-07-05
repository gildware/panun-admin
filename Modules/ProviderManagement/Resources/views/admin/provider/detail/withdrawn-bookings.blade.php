@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                @include('providermanagement::admin.provider.partials._provider-detail-tabs', ['webPage' => $webPage])
            </div>

            <div class="d-flex justify-content-end border-bottom mb-10">
                <div class="d-flex gap-2 fw-medium pe--4">
                    <span class="opacity-75">{{ translate('Total') }}:</span>
                    <span class="title-color">{{ $withdrawnCount ?? $bookings->total() }}</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                        <form action="{{ url()->current() }}?web_page=withdrawn_bookings" class="search-form search-form_style-two" method="POST">
                            @csrf
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon">
                                    <span class="material-icons">search</span>
                                </span>
                                <input type="search" class="theme-input-style search-form__input" value="{{ $search ?? '' }}" name="search" placeholder="{{ translate('search_here') }}">
                            </div>
                            <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{ translate('Booking_ID') }}</th>
                                <th>{{ translate('Booking_Status') }}</th>
                                <th>{{ translate('Customer_Info') }}</th>
                                <th>{{ translate('Reason') }}</th>
                                <th>{{ translate('Provider_withdrew_at') }}</th>
                                <th>{{ translate('Schedule_Date') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($bookings as $booking)
                                @php
                                    $reasonHistory = $booking->latestParentProviderCancellationStatusHistory
                                        ?? $booking->latestProviderRejectionHistory
                                        ?? $booking->latestPendingCancellationRequestHistory;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}">
                                            {{ $booking->readable_id }}
                                        </a>
                                    </td>
                                    <td>
                                        @include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])
                                    </td>
                                    <td>
                                        @if($booking->customer)
                                            <div>
                                                <a href="{{ route('admin.customer.detail', [$booking->customer->id, 'web_page' => 'overview']) }}">
                                                    {{ Str::limit($booking->customer->first_name, 30) }}
                                                </a>
                                            </div>
                                            {{ $booking->customer->phone ?? '' }}
                                        @else
                                            <span class="opacity-50">{{ translate('Customer_not_available') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $reasonHistory?->providerCancellationReason?->name ?? '—' }}</td>
                                    <td>
                                        @if($booking->provider_cancelled_at)
                                            {{ \Carbon\Carbon::parse($booking->provider_cancelled_at)->format('d-M-Y h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}</td>
                                    <td>
                                        <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}" class="btn btn--light-primary btn-sm">
                                            {{ translate('View_Details') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">{{ translate('No_data_found') }}</td>
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
@endsection
