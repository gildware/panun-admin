@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@push('css_or_js')
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-list-compact.css') }}?v={{ filemtime(public_path('assets/admin-module/css/booking-list-compact.css')) }}">
    <style>
        .provider-bookings-table-wrap .table.provider-bookings-table {
            font-size: 0.8125rem;
            margin-bottom: 0;
        }
        .provider-bookings-table-wrap .table.provider-bookings-table > :not(caption) > * > * {
            padding: 0.35rem 0.45rem;
            vertical-align: middle;
        }
        .provider-bookings-table-wrap .table.provider-bookings-table thead th {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            color: rgba(33, 37, 41, 0.72);
            background: var(--bs-gray-100, #f8f9fa);
            border-bottom-width: 1px;
        }
        .provider-bookings-table-wrap .provider-bookings-table tbody td {
            white-space: nowrap;
        }
        .provider-bookings-table-wrap .provider-bookings-id {
            font-weight: 600;
            text-decoration: none;
        }
        .provider-bookings-table-wrap .provider-bookings-customer {
            display: flex;
            flex-direction: column;
            gap: 0.05rem;
            line-height: 1.2;
            min-width: 0;
        }
        .provider-bookings-table-wrap .provider-bookings-customer a {
            font-weight: 600;
            text-decoration: none;
        }
        .provider-bookings-table-wrap .provider-bookings-customer__phone {
            font-size: 0.72rem;
            color: rgba(33, 37, 41, 0.62);
        }
        .provider-bookings-table-wrap .provider-bookings-date {
            display: flex;
            flex-direction: column;
            gap: 0.05rem;
            line-height: 1.2;
            font-size: 0.75rem;
        }
        .provider-bookings-table-wrap .provider-bookings-date__time {
            color: rgba(33, 37, 41, 0.55);
        }
        .provider-bookings-table-wrap .provider-bookings-amount {
            font-variant-numeric: tabular-nums;
        }
        .provider-bookings-table-wrap .booking-status-badge {
            font-size: 0.6875rem !important;
            padding: 0.2rem 0.45rem !important;
        }
        .provider-bookings-table-wrap .badge.radius-50 {
            font-size: 0.6875rem;
            padding: 0.15rem 0.45rem;
            gap: 0.25rem;
        }
        .provider-bookings-table-wrap .badge.radius-50 .dot {
            block-size: 0.3125rem;
            inline-size: 0.3125rem;
        }
        .provider-bookings-table-wrap .provider-bookings-actions .action-btn {
            --size: 26px;
        }
    </style>
@endpush

@section('content')
    @php
        $search = $search ?? '';
        $bookingStatus = $bookingStatus ?? 'all';
        $paymentStatus = $paymentStatus ?? 'all';
        $startDate = $startDate ?? '';
        $endDate = $endDate ?? '';
        $filterCounter = (int) ($filterCounter ?? 0);
        $providerBookingStatusOptions = [
            'all' => translate('All'),
            'pending' => translate('Pending'),
            'accepted' => translate('Accepted'),
            'pending_cancellation' => translate('Pending_cancellation'),
            'ongoing' => translate('Ongoing'),
            'on_hold' => translate('On_hold'),
            'completed' => translate('Completed'),
            'canceled' => translate('Canceled'),
        ];
    @endphp
    <div class="filter-aside filter-aside--booking-compact">
        <div class="filter-aside__header d-flex justify-content-between align-items-center">
            <h3 class="filter-aside__title mb-0">{{ translate('Filter_your_Booking') }}</h3>
            <button type="button" class="btn-close p-2 btn-close-white"></button>
        </div>
        <form action="{{ url()->current() }}" method="GET" id="provider-bookings-filter-form" class="filter-aside__form">
            <input type="hidden" name="web_page" value="{{ $webPage }}">
            @if($search !== '')
                <input type="hidden" name="search" value="{{ $search }}">
            @endif
            <div class="filter-aside__body d-flex flex-column">
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label" for="provider-bookings-status">{{ translate('Booking_Status') }}</label>
                    <div class="filter-aside__field">
                        <select class="theme-input-style w-100" name="booking_status" id="provider-bookings-status">
                            @foreach($providerBookingStatusOptions as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" {{ $bookingStatus === $statusKey ? 'selected' : '' }}>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label" for="provider-bookings-payment-status">{{ translate('Payment_Status') }}</label>
                    <div class="filter-aside__field">
                        <select class="theme-input-style w-100" name="payment_status" id="provider-bookings-payment-status">
                            <option value="all" {{ $paymentStatus === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                            <option value="paid" {{ $paymentStatus === 'paid' ? 'selected' : '' }}>{{ translate('Paid') }}</option>
                            <option value="unpaid" {{ $paymentStatus === 'unpaid' ? 'selected' : '' }}>{{ translate('Unpaid') }}</option>
                        </select>
                    </div>
                </div>
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Booked_Date_Range') }}</label>
                    <div class="filter-aside__date-row">
                        <div class="filter-aside__field">
                            <label class="filter-aside__field-label" for="provider-bookings-start-date">{{ translate('Start_Date') }}</label>
                            <input type="date" id="provider-bookings-start-date" class="form-control filter-aside__date-input"
                                   name="start_date" value="{{ $startDate }}">
                        </div>
                        <div class="filter-aside__field">
                            <label class="filter-aside__field-label" for="provider-bookings-end-date">{{ translate('End_Date') }}</label>
                            <input type="date" id="provider-bookings-end-date" class="form-control filter-aside__date-input"
                                   name="end_date" value="{{ $endDate }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-aside__bottom_btns">
                <a class="btn btn--secondary text-capitalize" href="{{ url()->current() }}?web_page={{ $webPage }}">
                    {{ translate('Clear_all_Filter') }}
                </a>
                <button class="btn btn--primary text-capitalize" type="submit">{{ translate('Filter') }}</button>
            </div>
        </form>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                @include('providermanagement::admin.provider.partials._provider-detail-tabs', ['webPage' => $webPage])
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="boookings-tab-pane">
                    <div class="d-flex justify-content-end border-bottom mb-10">
                        <div class="d-flex gap-2 fw-medium pe--4">
                            <span class="opacity-75">{{translate('Total_Bookings')}}:</span>
                            <span class="title-color">{{$bookings->total()}}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between align-items-center">
                                <form action="{{ url()->current() }}"
                                      class="search-form search-form_style-two"
                                      method="GET">
                                    <input type="hidden" name="web_page" value="{{ $webPage }}">
                                    @if($bookingStatus !== 'all')
                                        <input type="hidden" name="booking_status" value="{{ $bookingStatus }}">
                                    @endif
                                    @if($paymentStatus !== 'all')
                                        <input type="hidden" name="payment_status" value="{{ $paymentStatus }}">
                                    @endif
                                    @if($startDate !== '')
                                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                                    @endif
                                    @if($endDate !== '')
                                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                                    @endif
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{ $search ?? '' }}" name="search"
                                               placeholder="{{ translate('search_here') }}">
                                    </div>
                                    <button type="submit" class="btn btn--primary">
                                        {{ translate('search') }}
                                    </button>
                                </form>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <button type="button" class="btn text-capitalize filter-btn border px-3">
                                        <span class="material-icons">filter_list</span> {{ translate('Filter') }}
                                        @if($filterCounter > 0)
                                            <span class="count">{{ $filterCounter }}</span>
                                        @endif
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive provider-bookings-table-wrap">
                                <table id="example" class="table table-sm align-middle provider-bookings-table">
                                    <thead>
                                    <tr>
                                        <th>{{translate('Booking_ID')}}</th>
                                        <th>{{translate('Booking_Status')}}</th>
                                        <th>{{translate('Tag')}}</th>
                                        <th>{{translate('Customer_Info')}}</th>
                                        <th>{{translate('Service_Charges')}}</th>
                                        <th>{{translate('Parts_Charges')}}</th>
                                        <th>{{translate('Total_Booking_Amount')}}</th>
                                        <th>{{translate('Admin_Commission')}}</th>
                                        <th>{{translate('Payment_Status')}}</th>
                                        <th>{{translate('Schedule_Date')}}</th>
                                        <th>{{translate('Booking_Date')}}</th>
                                        <th class="text-center">{{translate('Action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($bookings as $key=>$booking)
                                        @php
                                            $grandTotal = get_booking_revenue_reporting_amount($booking);
                                            $partsCharges = get_booking_revenue_reporting_spare_parts_amount($booking);
                                            $serviceCharges = round(max(0, $grandTotal - $partsCharges), 2);
                                            $commissionDetails = $booking->calculateCommissionDetails($booking, $booking->provider_id);
                                            $adminCommission = (float) ($commissionDetails['adminCommission'] ?? 0);
                                            $scheduleAt = $booking->service_schedule ? strtotime($booking->service_schedule) : null;
                                            $bookedAt = $booking->created_at ? strtotime($booking->created_at) : null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a class="provider-bookings-id" href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}">
                                                    {{$booking->readable_id}}</a>
                                            </td>
                                            <td>
                                                @include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])
                                            </td>
                                            <td>
                                                @include('bookingmodule::admin.booking.partials._booking-list-tags-cell', ['booking' => $booking])
                                            </td>
                                            <td>
                                                @if(isset($booking->customer))
                                                    <div class="provider-bookings-customer">
                                                        <a href="{{route('admin.customer.detail',[$booking->customer->id, 'web_page'=>'overview'])}}">
                                                            {{Str::limit($booking->customer->first_name, 24)}}
                                                        </a>
                                                        @if(!empty($booking->customer->phone))
                                                            <span class="provider-bookings-customer__phone">{{ $booking->customer->phone }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="opacity-50">{{translate('Customer_not_available')}}</span>
                                                @endif
                                            </td>
                                            <td class="provider-bookings-amount">{{ with_currency_symbol($serviceCharges) }}</td>
                                            <td class="provider-bookings-amount">{{ with_currency_symbol($partsCharges) }}</td>
                                            <td class="provider-bookings-amount">{{ with_currency_symbol($grandTotal) }}</td>
                                            <td class="provider-bookings-amount">{{ with_currency_symbol($adminCommission) }}</td>
                                            @php
                                                $__provListPaidDisplay = (bool) $booking->is_paid;
                                                if (!$__provListPaidDisplay) {
                                                    $cap = get_booking_payable_total_for_partial_dues($booking);
                                                    $pt = (float) ($booking->booking_partial_payments ?? collect())->sum('paid_amount');
                                                    $st = (string) ($booking->booking_status ?? '');
                                                    $out = (string) ($booking->settlement_outcome ?? '');
                                                    if ($st === 'canceled' && (
                                                        !empty($booking->after_visit_cancel)
                                                        || $out === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
                                                    )) {
                                                        $__provListPaidDisplay = $cap > 0 && $pt + 0.005 >= $cap;
                                                    } elseif ($st === 'completed' && $out === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT) {
                                                        $__provListPaidDisplay = $cap > 0 && $pt + 0.005 >= $cap;
                                                    }
                                                }
                                            @endphp
                                            <td>
                                                <span class="badge badge badge-{{ $__provListPaidDisplay ? 'success' : 'danger' }} radius-50">
                                                    <span class="dot"></span>
                                                    {{ $__provListPaidDisplay ? translate('paid') : translate('unpaid') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($scheduleAt)
                                                    <div class="provider-bookings-date">
                                                        <span>{{ date('d-M-Y', $scheduleAt) }}</span>
                                                        <span class="provider-bookings-date__time">{{ date('h:ia', $scheduleAt) }}</span>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($bookedAt)
                                                    <div class="provider-bookings-date">
                                                        <span>{{ date('d-M-Y', $bookedAt) }}</span>
                                                        <span class="provider-bookings-date__time">{{ date('h:ia', $bookedAt) }}</span>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                <div class="provider-bookings-actions d-flex justify-content-center">
                                                    <a href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}"
                                                       class="action-btn btn--light-primary"
                                                       title="{{ translate('View_Details') }}">
                                                        <span class="material-icons">visibility</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
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
