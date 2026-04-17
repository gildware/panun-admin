@extends('adminmodule::layouts.master')

@section('title',translate('Booking_Report'))

@push('css_or_js')
    <style>
        .report-chart-title{
            font-weight: 800;
            color: #0b5ed7;
            letter-spacing: .2px;
        }
        .report-chart-title .report-chart-meta{
            font-weight: 700;
            color: #6c757d;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Booking_Reports')}}</h2>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3 fz-16">{{translate('Search_Data')}}</div>

                            <form action="{{route('admin.report.booking')}}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('zone')}}</label>
                                        <select class="js-select zone__select" name="zone_ids[]"
                                                id="zone_selector__select" multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($zones as $zone)
                                                <option
                                                    value="{{$zone['id']}}" {{array_key_exists('zone_ids', $queryParams) && in_array($zone['id'], $queryParams['zone_ids']) ? 'selected' : '' }}>{{$zone['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('category')}}</label>
                                        <select class="js-select category__select" name="category_ids[]"
                                                id="category_selector__select" multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($categories as $category)
                                                <option
                                                    value="{{$category['id']}}" {{array_key_exists('category_ids', $queryParams) && in_array($category['id'], $queryParams['category_ids']) ? 'selected' : '' }}>{{$category['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('sub_category')}}</label>
                                        <select class="js-select sub-category__select" name="sub_category_ids[]"
                                                id="sub_category_selector__select"
                                                multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($subCategories as $sub_category)
                                                <option
                                                    value="{{$sub_category['id']}}" {{array_key_exists('sub_category_ids', $queryParams) && in_array($sub_category['id'], $queryParams['sub_category_ids']) ? 'selected' : '' }}>{{$sub_category['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('service')}}</label>
                                        <select class="js-select service__select" name="service_ids[]"
                                                id="service_selector__select"
                                                multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($services as $service)
                                                <option
                                                    value="{{$service->id}}" {{array_key_exists('service_ids', $queryParams) && is_array($queryParams['service_ids']) && in_array($service->id, $queryParams['service_ids'], true) ? 'selected' : '' }}>{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('provider')}}</label>
                                        <select class="js-select provider__select" name="provider_ids[]"
                                                id="provider_selector__select" multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($providers as $provider)
                                                <option
                                                    value="{{$provider['id']}}" {{array_key_exists('provider_ids', $queryParams) && in_array($provider['id'], $queryParams['provider_ids']) ? 'selected' : '' }}>{{$provider['company_name']}}
                                                    ({{$provider['company_phone']}})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{ translate('Assignee') }}</label>
                                        <select class="js-select staff__select" name="staff_ids[]"
                                                id="staff_selector__select" multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            <option value="__unassigned__" {{ array_key_exists('staff_ids', $queryParams) && in_array('__unassigned__', $queryParams['staff_ids'] ?? [], true) ? 'selected' : '' }}>
                                                {{ translate('Unassigned') }}
                                            </option>
                                            @foreach($assignees as $u)
                                                <option value="{{ $u->id }}"
                                                    {{ array_key_exists('staff_ids', $queryParams) && in_array($u->id, $queryParams['staff_ids'] ?? [], true) ? 'selected' : '' }}>
                                                    {{ trim($u->first_name . ' ' . $u->last_name) }} ({{ $u->email ?? $u->phone }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('date_range')}}</label>
                                        <select class="js-select" id="date-range" name="date_range">
                                            <option value="0" disabled
                                                    selected>{{translate('Select Date Range')}}</option>
                                            <option
                                                value="all_time" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='all_time'?'selected':''}}>{{translate('All_Time')}}</option>
                                            <option
                                                value="this_week" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_week'?'selected':''}}>{{translate('This_Week')}}</option>
                                            <option
                                                value="last_week" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_week'?'selected':''}}>{{translate('Last_Week')}}</option>
                                            <option
                                                value="this_month" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_month'?'selected':''}}>{{translate('This_Month')}}</option>
                                            <option
                                                value="last_month" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_month'?'selected':''}}>{{translate('Last_Month')}}</option>
                                            <option
                                                value="last_15_days" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_15_days'?'selected':''}}>{{translate('Last_15_Days')}}</option>
                                            <option
                                                value="this_year" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year'?'selected':''}}>{{translate('This_Year')}}</option>
                                            <option
                                                value="last_year" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_year'?'selected':''}}>{{translate('Last_Year')}}</option>
                                            <option
                                                value="last_6_month" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_6_month'?'selected':''}}>{{translate('Last_6_Month')}}</option>
                                            <option
                                                value="this_year_1st_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_1st_quarter'?'selected':''}}>{{translate('This_Year_1st_Quarter')}}</option>
                                            <option
                                                value="this_year_2nd_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_2nd_quarter'?'selected':''}}>{{translate('This_Year_2nd_Quarter')}}</option>
                                            <option
                                                value="this_year_3rd_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_3rd_quarter'?'selected':''}}>{{translate('This_Year_3rd_Quarter')}}</option>
                                            <option
                                                value="this_year_4th_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_4th_quarter'?'selected':''}}>{{translate('this_year_4th_quarter')}}</option>
                                            <option
                                                value="custom_date" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='custom_date'?'selected':''}}>{{translate('Custom_Date')}}</option>
                                        </select>
                                    </div>
                                    <div
                                        class="col-lg-4 col-sm-6 {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='custom_date'?'':'d-none'}}"
                                        id="from-filter__div">
                                        <div class="form-floating mb-30">
                                            <input type="date" class="form-control" id="from" name="from"
                                                   value="{{array_key_exists('from', $queryParams)?$queryParams['from']:''}}">
                                            <label for="from">{{translate('From')}}</label>
                                        </div>
                                    </div>
                                    <div
                                        class="col-lg-4 col-sm-6 {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='custom_date'?'':'d-none'}}"
                                        id="to-filter__div">
                                        <div class="form-floating mb-30">
                                            <input type="date" class="form-control" id="to" name="to"
                                                   value="{{array_key_exists('to', $queryParams)?$queryParams['to']:''}}">
                                            <label for="to">{{translate('To')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                                        <a href="{{ route('admin.report.booking') }}"
                                           class="btn btn--secondary btn-sm">{{ translate('Clear_all_Filter') }}</a>
                                        <button type="submit"
                                                class="btn btn--primary btn-sm">{{translate('Submit')}}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Removed: Top widgets + Booking Statistics chart (per request) --}}

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <span>{{ translate('Booking_Status') }}</span>
                                            <span class="report-chart-meta">{{ translate('Total_Bookings') }}: {{ $bookings_count['total_bookings'] ?? 0 }}</span>
                                        </h5>
                                        <div id="apex_booking_status_donut"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">Status Vise earning</h5>
                                        <div id="apex_booking_earning_bar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="mb-3 fz-16 report-chart-title">{{ translate('Booking_cancellation_reasons') }}</div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">
                                            Canceled vs Special vs Disputed
                                            <span class="report-chart-meta">
                                                ({{ array_sum($cancel_bucket_chart['counts'] ?? []) }})
                                            </span>
                                        </h5>
                                        <div id="apex_cancel_bucket_donut"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">
                                            {{ translate('Cancellation_Reason') }} (before visit)
                                            <span class="report-chart-meta">({{ ($cancel_bucket_chart['counts'][0] ?? 0) }})</span>
                                        </h5>
                                        <div id="apex_cancel_reason_bar"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">
                                            {{ translate('service') }} (canceled before visit)
                                            <span class="report-chart-meta">({{ ($cancel_bucket_chart['counts'][0] ?? 0) }})</span>
                                        </h5>
                                        <div id="apex_cancel_service_bar"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">
                                            {{ translate('service') }} (special settlement / after visit)
                                            <span class="report-chart-meta">({{ ($cancel_bucket_chart['counts'][1] ?? 0) }})</span>
                                        </h5>
                                        <div id="apex_cancel_special_service_bar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="mb-3 fz-16 report-chart-title">
                                Disputed bookings
                                <span class="report-chart-meta">
                                    ({{ ($cancel_bucket_chart['counts'][2] ?? 0) + ($cancel_bucket_chart['counts'][3] ?? 0) }})
                                </span>
                            </div>
                            <div class="row g-3">
                                <div class="col-lg-12">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">{{ translate('service') }} (disputed)</h5>
                                        <div id="apex_disputed_service_bar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="mb-3 fz-16 report-chart-title">{{ translate('Booking_hold_reasons') }}</div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">
                                            {{ translate('Hold_reason') }}
                                            <span class="report-chart-meta">
                                                ({{ (collect($report_status_table ?? [])->firstWhere('key', 'on_hold')['count'] ?? 0) }})
                                            </span>
                                        </h5>
                                        <div id="apex_hold_reason_bar"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h5 class="fz-14 mb-3 report-chart-title">
                                            {{ translate('service') }} ({{ translate('Booking_status_tpl_on_hold') }})
                                            <span class="report-chart-meta">
                                                ({{ (collect($report_status_table ?? [])->firstWhere('key', 'on_hold')['count'] ?? 0) }})
                                            </span>
                                        </h5>
                                        <div id="apex_hold_service_bar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}"
                                      class="search-form search-form_style-two"
                                      method="GET">
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{request()->get('search')}}" name="search"
                                               placeholder="{{translate('search_by_Booking_ID')}}">
                                    </div>
                                    <button type="submit"
                                            class="btn btn--primary">{{translate('search')}}</button>
                                </form>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div>
                                        <select class="js-select booking-status__select" name="booking_status"
                                                id="booking-status">
                                            <option value="" selected disabled>{{translate('Booking_status')}}</option>
                                            <option value="all">{{translate('All')}}</option>
                                            @foreach(BOOKING_STATUSES as $booking_status)
                                                <option
                                                    value="{{$booking_status['key']}}" {{ $booking_status['key'] === request()->input('booking_status') ? 'selected' : '' }}>{{$booking_status['value']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @can('report_export')
                                        <div class="dropdown">
                                            <button type="button"
                                                    class="btn btn--secondary text-capitalize dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                <span
                                                    class="material-icons">file_download</span> {{translate('download')}}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                <li><a class="dropdown-item"
                                                       href="{{route('admin.report.booking.download').'?'.http_build_query(request()->all())}}">{{translate('Excel')}}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="text-nowrap">
                                    <tr>
                                        <th>{{translate('SL')}}</th>
                                        <th>{{translate('Booking_ID')}}</th>
                                        <th>{{translate('Booking_Status')}}</th>
                                        <th>{{translate('Status_Vise_earning')}}</th>
                                        <th>{{translate('Customer_Info')}}</th>
                                        <th>{{translate('Provider_Info')}}</th>
                                        <th>{{translate('Booking_Amount')}}</th>
                                        <th>{{translate('Service_Discount')}}</th>
                                        <th>{{translate('Coupon_Discount')}}</th>
                                        <th>{{translate('VAT_/_Tax')}}</th>
                                        <th>{{translate('Action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($filtered_bookings as $key=>$booking)
                                        <tr>
                                            <td>{{ $filtered_bookings->firstitem()+$key }}</td>
                                            <td>
                                                <a href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}">
                                                    {{$booking['readable_id']}}
                                                </a>
                                            </td>
                                            <td>
                                                @include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])
                                            </td>
                                            <td class="text-nowrap">
                                                @include('bookingmodule::admin.booking.partials._booking-list-tags-cell', ['booking' => $booking])
                                            </td>
                                            <td>
                                                @if(isset($booking->customer))
                                                    <div class="fw-medium">
                                                        <a href="{{route('admin.customer.detail',[$booking->customer->id, 'web_page'=>'overview'])}}">
                                                            {{$booking->customer->first_name . ' ' . $booking->customer->last_name}}
                                                        </a>
                                                    </div>
                                                    <a class="fz-12"
                                                       href="tel:{{$booking->customer->phone??''}}">{{$booking->customer->phone??''}}</a>
                                                @else
                                                    <div
                                                        class="fw-medium badge badge badge-danger radius-50">{{translate('Customer_not_available')}}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($booking->provider) && isset($booking->provider->owner))
                                                    <div class="fw-medium">
                                                        <a href="{{route('admin.provider.details',[$booking->provider->id, 'web_page'=>'overview'])}}">
                                                            {{$booking->provider->company_name}}
                                                        </a>
                                                    </div>
                                                    <a class="fz-12"
                                                       href="tel:{{$booking->provider->company_phone??''}}">{{$booking->provider->company_phone??''}}</a>
                                                @else
                                                    <div
                                                        class="fw-medium badge badge badge-danger radius-50">{{translate('Provider_not_available')}}</div>
                                                @endif
                                            </td>
                                            <td>{{with_currency_symbol($booking['total_booking_amount'])}}</td>
                                            <td>
                                                @if($booking['total_campaign_discount_amount'] > $booking['total_discount_amount'])
                                                    {{with_currency_symbol($booking['total_campaign_discount_amount'])}}
                                                    <label
                                                        class="fw-medium badge badge badge-info radius-50">{{translate('Campaign')}}</label>
                                                @else
                                                    {{with_currency_symbol($booking['total_discount_amount'])}}
                                                @endif
                                            </td>
                                            <td>{{with_currency_symbol($booking['total_coupon_discount_amount'])}}</td>
                                            <td>{{with_currency_symbol($booking['total_tax_amount'])}}</td>
                                            <td>
                                                @if($booking->is_repeated)
                                                    <a href="{{ route('admin.booking.repeat_details', [$booking->id, 'web_page' => 'details']) }}"
                                                       class="action-btn btn--light-primary" style="--size: 30px"><span
                                                            class="material-icons m-0">visibility</span>
                                                    </a>
                                                @else
                                                    <a href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}"
                                                       class="action-btn btn--light-primary" style="--size: 30px"><span
                                                            class="material-icons m-0">visibility</span>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-center" colspan="99">{{translate('Data_not_available')}}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end">
                                {!! $filtered_bookings->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>

    <script>
        "use strict";

        var bookingReportAllSubCategories = @json($allSubCategoriesForJs);
        var bookingReportAllServices = @json($allServicesForJs);
        var bookingReportSelectAllLabel = @json(translate('Select_All'));

        function bookingReportSelectedRealIds($select) {
            var vals = $select.val();
            if (!vals) {
                return [];
            }
            return vals.filter(function (v) {
                return v !== 'all';
            });
        }

        function rebuildBookingReportSubCategories() {
            var $cat = $('#category_selector__select');
            var $sub = $('#sub_category_selector__select');
            var catIds = bookingReportSelectedRealIds($cat);
            var prevSub = bookingReportSelectedRealIds($sub);
            var filtered = bookingReportAllSubCategories.filter(function (s) {
                return catIds.length > 0 && catIds.indexOf(s.parent_id) !== -1;
            });
            $sub.find('option').remove();
            $sub.append(new Option(bookingReportSelectAllLabel, 'all', false, false));
            filtered.forEach(function (s) {
                $sub.append(new Option(s.name, s.id, false, prevSub.indexOf(s.id) !== -1));
            });
            var nextSub = prevSub.filter(function (id) {
                return filtered.some(function (s) {
                    return s.id === id;
                });
            });
            $sub.val(nextSub.length ? nextSub : null);
            $sub.trigger('change');
        }

        function rebuildBookingReportServices() {
            var $sub = $('#sub_category_selector__select');
            var $svc = $('#service_selector__select');
            var subIds = bookingReportSelectedRealIds($sub);
            var prevSvc = bookingReportSelectedRealIds($svc);
            var filtered = bookingReportAllServices.filter(function (s) {
                return subIds.length > 0 && subIds.indexOf(s.sub_category_id) !== -1;
            });
            $svc.find('option').remove();
            $svc.append(new Option(bookingReportSelectAllLabel, 'all', false, false));
            filtered.forEach(function (s) {
                $svc.append(new Option(s.name, s.id, false, prevSvc.indexOf(s.id) !== -1));
            });
            var nextSvc = prevSvc.filter(function (id) {
                return filtered.some(function (s) {
                    return s.id === id;
                });
            });
            $svc.val(nextSvc.length ? nextSvc : null);
            $svc.trigger('change');
        }

        $('#zone_selector__select').on('change', function () {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

        $('#category_selector__select').on('change', function () {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
            rebuildBookingReportSubCategories();
        });

        $('#sub_category_selector__select').on('change', function () {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
            rebuildBookingReportServices();
        });

        $('#service_selector__select').on('change', function () {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

        $('#provider_selector__select').on('change', function () {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

        $(document).ready(function () {
            $('.zone__select').select2({
                placeholder: "{{translate('Select_zone')}}",
            });
            $('.provider__select').select2({
                placeholder: "{{translate('Select_provider')}}",
            });
            $('.staff__select').select2({
                placeholder: "{{translate('Assignee')}}",
            });
            $('.category__select').select2({
                placeholder: "{{translate('Select_category')}}",
            });
            $('.sub-category__select').select2({
                placeholder: "{{translate('Select_sub_category')}}",
            });
            $('.service__select').select2({
                placeholder: "{{translate('service')}}",
            });
            $('.booking-status__select').select2({
                placeholder: "{{translate('Booking_status')}}",
            });
        });

        $(document).ready(function () {
            $('#date-range').on('change', function () {
                if (this.value === 'custom_date') {
                    $('#from-filter__div').removeClass('d-none');
                    $('#to-filter__div').removeClass('d-none');
                }


                if (this.value !== 'custom_date') {
                    $('#from-filter__div').addClass('d-none');
                    $('#to-filter__div').addClass('d-none');
                }
            });
        });

        $(document).ready(function () {
            $('#booking-status').on('change', function () {
                location.href = "{{route('admin.report.booking')}}" + "?booking_status=" + this.value;
            });
        });

        var reportStatusChart = @json($report_status_chart);
        var reportChartPalette = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a'];

        if (document.querySelector('#apex_booking_status_donut')) {
            new ApexCharts(document.querySelector('#apex_booking_status_donut'), {
                chart: {type: 'donut', height: 320, toolbar: {show: false}},
                labels: reportStatusChart.labels,
                series: reportStatusChart.counts,
                colors: reportChartPalette,
                legend: {
                    position: 'bottom',
                    formatter: function (seriesName, opts) {
                        var val = (opts.w.globals.series[opts.seriesIndex] || 0);
                        return seriesName + ' (' + val + ')';
                    }
                },
                plotOptions: {pie: {donut: {size: '65%'}}},
                dataLabels: {enabled: true}
            }).render();
        }

        var earningChart = @json($earning_chart);
        if (document.querySelector('#apex_booking_earning_bar')) {
            new ApexCharts(document.querySelector('#apex_booking_earning_bar'), {
                chart: {type: 'bar', height: 360, stacked: true, toolbar: {show: false}},
                series: [
                    {name: 'Customer paid', data: earningChart.customer_paid},
                    {name: 'Company commission', data: earningChart.company_commission}
                ],
                plotOptions: {bar: {horizontal: false, columnWidth: '55%', borderRadius: 4}},
                xaxis: {
                    categories: earningChart.labels_short || [],
                    labels: {
                        rotate: 0,
                        rotateAlways: false,
                        trim: true,
                        hideOverlappingLabels: true,
                        style: {fontSize: '10px'},
                        formatter: function (val) {
                            if (!val) return val;
                            val = String(val);
                            return val.length > 10 ? (val.slice(0, 10) + '…') : val;
                        }
                    }
                },
                yaxis: {title: {text: @json(currency_symbol())}},
                dataLabels: {enabled: false},
                colors: ['#0b5ed7', '#00E396'],
                grid: {strokeDashArray: 4},
                tooltip: {
                    x: {
                        formatter: function (val, opts) {
                            var i = opts.dataPointIndex || 0;
                            if (earningChart.labels_full && earningChart.labels_full[i]) {
                                return earningChart.labels_full[i];
                            }
                            return val;
                        }
                    },
                    y: {
                        formatter: function (val) {
                            return @json(currency_symbol()) + (val || 0);
                        }
                    }
                },
                legend: {position: 'bottom'}
            }).render();
        }

        var cancelBucketChart = @json($cancel_bucket_chart);
        var cancelReasonChart = @json($cancel_reason_chart);
        var cancelServiceChart = @json($cancel_service_chart);
        var cancelSpecialServiceChart = @json($cancel_special_service_chart);
        var disputedServiceChart = @json($disputed_service_chart);
        var holdReasonChart = @json($hold_reason_chart);
        var holdServiceChart = @json($hold_service_chart);

        if (document.querySelector('#apex_cancel_bucket_donut')) {
            new ApexCharts(document.querySelector('#apex_cancel_bucket_donut'), {
                chart: {type: 'donut', height: 360, toolbar: {show: false}},
                labels: cancelBucketChart.labels,
                series: cancelBucketChart.counts,
                colors: ['#FF4560', '#FEB019', '#8B0000', '#FF8C00'],
                legend: {position: 'bottom'},
                plotOptions: {pie: {donut: {size: '65%'}}},
                dataLabels: {enabled: true},
                tooltip: {
                    y: {
                        formatter: function (val, opts) {
                            var i = opts.seriesIndex || 0;
                            var amt = (cancelBucketChart.amounts && cancelBucketChart.amounts[i]) ? cancelBucketChart.amounts[i] : 0;
                            return val + ' | ' + @json(currency_symbol()) + amt;
                        }
                    }
                }
            }).render();
        }

        if (document.querySelector('#apex_cancel_reason_bar')) {
            new ApexCharts(document.querySelector('#apex_cancel_reason_bar'), {
                chart: {type: 'bar', height: 360, toolbar: {show: false}},
                series: [{name: @json(translate('total')), data: cancelReasonChart.counts}],
                plotOptions: {bar: {horizontal: true, borderRadius: 4, barHeight: '70%'}},
                xaxis: {categories: cancelReasonChart.labels},
                dataLabels: {enabled: true},
                colors: ['#FF4560'],
                grid: {strokeDashArray: 4}
            }).render();
        }

        if (document.querySelector('#apex_cancel_service_bar')) {
            new ApexCharts(document.querySelector('#apex_cancel_service_bar'), {
                chart: {type: 'bar', height: 360, toolbar: {show: false}},
                series: [{name: @json(translate('total')), data: cancelServiceChart.counts}],
                plotOptions: {bar: {horizontal: true, borderRadius: 4, barHeight: '70%'}},
                xaxis: {categories: cancelServiceChart.labels},
                dataLabels: {enabled: true},
                colors: ['#FEB019'],
                grid: {strokeDashArray: 4}
            }).render();
        }

        if (document.querySelector('#apex_cancel_special_service_bar')) {
            new ApexCharts(document.querySelector('#apex_cancel_special_service_bar'), {
                chart: {type: 'bar', height: 360, toolbar: {show: false}},
                series: [{name: @json(translate('total')), data: cancelSpecialServiceChart.counts}],
                plotOptions: {bar: {horizontal: true, borderRadius: 4, barHeight: '70%'}},
                xaxis: {categories: cancelSpecialServiceChart.labels},
                dataLabels: {enabled: true},
                colors: ['#FF4560'],
                grid: {strokeDashArray: 4}
            }).render();
        }

        if (document.querySelector('#apex_disputed_service_bar')) {
            new ApexCharts(document.querySelector('#apex_disputed_service_bar'), {
                chart: {type: 'bar', height: 360, toolbar: {show: false}},
                series: [{name: @json(translate('total')), data: disputedServiceChart.counts}],
                plotOptions: {bar: {horizontal: true, borderRadius: 4, barHeight: '70%'}},
                xaxis: {categories: disputedServiceChart.labels},
                dataLabels: {enabled: true},
                colors: ['#775DD0'],
                grid: {strokeDashArray: 4}
            }).render();
        }

        if (document.querySelector('#apex_hold_reason_bar')) {
            new ApexCharts(document.querySelector('#apex_hold_reason_bar'), {
                chart: {type: 'bar', height: 360, toolbar: {show: false}},
                series: [{name: @json(translate('total')), data: holdReasonChart.counts}],
                plotOptions: {bar: {horizontal: true, borderRadius: 4, barHeight: '70%'}},
                xaxis: {categories: holdReasonChart.labels},
                dataLabels: {enabled: true},
                colors: ['#775DD0'],
                grid: {strokeDashArray: 4}
            }).render();
        }

        if (document.querySelector('#apex_hold_service_bar')) {
            new ApexCharts(document.querySelector('#apex_hold_service_bar'), {
                chart: {type: 'bar', height: 360, toolbar: {show: false}},
                series: [{name: @json(translate('total')), data: holdServiceChart.counts}],
                plotOptions: {bar: {horizontal: true, borderRadius: 4, barHeight: '70%'}},
                xaxis: {categories: holdServiceChart.labels},
                dataLabels: {enabled: true},
                colors: ['#00E396'],
                grid: {strokeDashArray: 4}
            }).render();
        }
    </script>
@endpush
