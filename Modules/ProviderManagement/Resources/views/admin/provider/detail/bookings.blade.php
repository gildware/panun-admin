@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@push('css_or_js')


@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'special_bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=special_bookings">{{ translate('Special_Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
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
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{ url()->current() }}?web_page={{ $webPage }}"
                                      class="search-form search-form_style-two"
                                      method="POST">
                                    @csrf
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{$search??''}}" name="search"
                                               placeholder="{{translate('search_here')}}">
                                    </div>
                                    <button type="submit" class="btn btn--primary">
                                        {{translate('search')}}
                                    </button>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
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
                                        <th>{{translate('Action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($bookings as $key=>$booking)
                                        @php
                                            $grandTotal = get_booking_revenue_reporting_amount($booking);
                                            $partsCharges = get_booking_revenue_reporting_spare_parts_amount($booking);
                                            $serviceCharges = round(max(0, $grandTotal - $partsCharges), 2);
                                            $commissionDetails = $booking->calculateCommissionDetails($booking, $booking->provider_id);
                                            $adminCommission = (float) ($commissionDetails['adminCommission'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}">
                                                    {{$booking->readable_id}}</a>
                                            </td>
                                            <td>
                                                @include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])
                                            </td>
                                            <td class="text-nowrap">
                                                @include('bookingmodule::admin.booking.partials._booking-list-tags-cell', ['booking' => $booking])
                                            </td>
                                            <td>
                                                @if(isset($booking->customer))
                                                    <div>
                                                        <a href="{{route('admin.customer.detail',[$booking->customer->id, 'web_page'=>'overview'])}}">
                                                            {{Str::limit($booking->customer->first_name, 30)}}
                                                        </a>
                                                    </div>
                                                    {{$booking->customer->phone??""}}
                                                @else
                                                    <span class="opacity-50">{{translate('Customer_not_available')}}</span>
                                                @endif
                                            </td>
                                            <td>{{ with_currency_symbol($serviceCharges) }}</td>
                                            <td>{{ with_currency_symbol($partsCharges) }}</td>
                                            <td>{{ with_currency_symbol($grandTotal) }}</td>
                                            <td>{{ with_currency_symbol($adminCommission) }}</td>
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
                                            <td>{{date('d-M-Y h:ia',strtotime($booking->service_schedule))}}</td>
                                            <td>{{date('d-M-Y h:ia',strtotime($booking->created_at))}}</td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}"
                                                       type="button"
                                                       class="table-actions_view btn btn--light-primary fw-medium text-capitalize fz-14">
                                                        <span class="material-icons">visibility</span>
                                                        {{translate('View_Details')}}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
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
