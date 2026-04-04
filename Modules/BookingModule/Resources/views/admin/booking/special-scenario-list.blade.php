@extends('adminmodule::layouts.master')

@section('title', translate('Special_scenario_bookings'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h2 class="page-title mb-0">{{ translate('Special_scenario_bookings') }}</h2>
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{ translate('Total_Request') }}:</span>
                            <span class="title-color">{{ $bookings->total() }}</span>
                        </div>
                    </div>

                    <div class="mt-30 mb-30">
                        <ul class="nav nav--tabs nav--tabs__style2 nav--tabs__booking-tally flex-wrap gap-2">
                            <li class="nav-item">
                                <a class="nav-link {{ $scenario === 'all' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list.special_scenarios', array_merge($queryParams, ['scenario' => 'all'])) }}">
                                    {{ translate('Bfs_scenario_tab_all') }}
                                    <span class="count">{{ $scenarioCounts['all'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $scenario === 'loss_making' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list.special_scenarios', array_merge($queryParams, ['scenario' => 'loss_making'])) }}">
                                    {{ translate('Bfs_scenario_tab_loss_making') }}
                                    <span class="count">{{ $scenarioCounts['loss_making'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $scenario === 'cancelled_after_visit' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list.special_scenarios', array_merge($queryParams, ['scenario' => 'cancelled_after_visit'])) }}">
                                    {{ translate('Bfs_scenario_tab_cancelled_after_visit') }}
                                    <span class="count">{{ $scenarioCounts['cancelled_after_visit'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $scenario === 'little_or_no_service' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list.special_scenarios', array_merge($queryParams, ['scenario' => 'little_or_no_service'])) }}">
                                    {{ translate('Bfs_scenario_tab_little_or_no_service') }}
                                    <span class="count">{{ $scenarioCounts['little_or_no_service'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $scenario === 'custom_commission' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list.special_scenarios', array_merge($queryParams, ['scenario' => 'custom_commission'])) }}">
                                    {{ translate('Bfs_scenario_tab_custom_commission') }}
                                    <span class="count">{{ $scenarioCounts['custom_commission'] ?? 0 }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.booking.list.special_scenarios') }}" method="get" class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                                <input type="hidden" name="scenario" value="{{ $scenario }}">
                                <div class="input-group search-form__input_group" style="max-width: 420px;">
                                    <span class="search-form__icon input-group-text border-end-0 bg-white">
                                        <span class="material-icons">search</span>
                                    </span>
                                    <input type="search" class="form-control border-start-0" name="search"
                                           value="{{ $queryParams['search'] ?? '' }}"
                                           placeholder="{{ translate('search_here') }}">
                                </div>
                                <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                            </form>

                            <div class="table-responsive">
                                <table class="table align-middle tr-hover">
                                    <thead class="text-nowrap">
                                        <tr>
                                            <th>{{ translate('SL') }}</th>
                                            <th>{{ translate('Booking_ID') }}</th>
                                            <th>{{ translate('Booking_Status') }}</th>
                                            <th>{{ translate('Customer_Info') }}</th>
                                            <th>{{ translate('Provider_Info') }}</th>
                                            <th>{{ translate('Schedule_Date') }}</th>
                                            <th>{{ translate('Total_Amount') }}</th>
                                            <th>{{ translate('Payment_Status') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($bookings as $key => $booking)
                                            <tr>
                                                <td>{{ $key + $bookings->firstItem() }}</td>
                                                <td>
                                                    <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}">
                                                        {{ $booking->readable_id }}
                                                    </a>
                                                    @include('bookingmodule::admin.booking.partials._booking-settlement-list-badge', ['booking' => $booking])
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $booking->booking_status === 'ongoing' ? 'warning' : ($booking->booking_status === 'completed' ? 'success' : ($booking->booking_status === 'canceled' ? 'danger' : 'secondary')) }}">
                                                        {{ ucwords(str_replace('_', ' ', $booking->booking_status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        @if ($booking->customer)
                                                            <a href="{{ route('admin.customer.detail', [$booking->customer->id, 'web_page' => 'overview']) }}">
                                                                {{ \Illuminate\Support\Str::limit(trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? '')), 30) }}
                                                            </a>
                                                        @else
                                                            <span>{{ \Illuminate\Support\Str::limit($booking->service_address->contact_person_name ?? '—', 30) }}</span>
                                                        @endif
                                                    </div>
                                                    <span class="text-muted small">{{ $booking->customer?->phone ?? $booking->service_address?->contact_person_number }}</span>
                                                </td>
                                                <td>
                                                    @if($booking->provider)
                                                        <a href="{{ route('admin.provider.details', [$booking->provider_id, 'web_page' => 'overview']) }}">{{ $booking->provider->company_name }}</a>
                                                        <div class="text-muted small">{{ $booking->provider->company_phone }}</div>
                                                    @else
                                                        <span class="badge badge-danger radius-50">{{ translate('unassigned') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $booking->service_schedule ? date('d-M-Y h:ia', strtotime($booking->service_schedule)) : '—' }}</td>
                                                <td>{{ with_currency_symbol(get_booking_total_amount($booking)) }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $booking->is_paid ? 'success' : 'danger' }} radius-50">
                                                        {{ $booking->is_paid ? translate('paid') : translate('unpaid') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}"
                                                       class="action-btn btn--light-primary fw-medium text-capitalize fz-14" style="--size: 30px">
                                                        <span class="material-icons">visibility</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">{{ translate('no data available') }}</td>
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
