@extends('adminmodule::layouts.master')

@section('title',translate('Overview'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-4">
                @php
                    $customerDisplayName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    $customerDisplayName = $customerDisplayName !== '' ? $customerDisplayName : ($customer->email ?? translate('Customer'));
                    $customerStatus = (string) ($customer->manual_performance_status ?? 'active');
                    $customerStatusLabel = match($customerStatus) {
                        'blacklisted' => translate('Blacklisted'),
                        'suspended' => translate('Suspended'),
                        default => translate('Active'),
                    };
                    $customerStatusClass = match($customerStatus) {
                        'blacklisted' => 'bg-danger',
                        'suspended' => 'bg-warning text-dark',
                        default => 'bg-success',
                    };
                @endphp
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="page-title mb-2">{{ $customerDisplayName }}</h2>
                        <div>{{translate('Joined_on')}} {{date('d-M-y H:iA', strtotime($customer?->created_at))}}</div>
                    </div>
                    <span class="badge {{ $customerStatusClass }}">{{ $customerStatusLabel }}</span>
                </div>
            </div>

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'overview'])

            <div class="card">
                <div class="card-body p-30">
                    <div class="row customer-overview-top g-3 align-items-stretch mb-30">
                        <div class="col-12 col-lg-8 d-flex min-h-0">
                            <div class="customer-overview-stat-grid w-100 h-100 flex-grow-1">
                                <div class="statistics-card statistics-card__style2 statistics-card__pending-withdraw customer-overview-stat-tile">
                                    <h2>{{$customer->bookings_count}}</h2>
                                    <h3>{{translate('Total_Booking_Placed')}}</h3>
                                </div>
                                <div class="statistics-card statistics-card__style2 statistics-card__already-withdraw customer-overview-stat-tile">
                                    <h2>{{with_currency_symbol($totalBookingAmount)}}</h2>
                                    <h3>{{translate('Total_Booking_Amount')}}</h3>
                                </div>
                                <div class="statistics-card statistics-card__style2 statistics-card__total-earning customer-overview-stat-tile">
                                    <h2>{{with_currency_symbol($customer['wallet_balance'])}}</h2>
                                    <h3>{{translate('Wallet Balance')}}</h3>
                                </div>
                                <div class="statistics-card statistics-card__style2 statistics-card__withdrawable-amount customer-overview-stat-tile">
                                    <h2>{{$customer['loyalty_point']}}</h2>
                                    <h3>{{translate('Loyalty Point')}}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4 d-flex min-h-0">
                            <div class="statistics-card statistics-card__order-overview customer-overview-chart-card w-100 h-100 d-flex flex-column min-h-0">
                                <h3 class="mb-2 flex-shrink-0">{{ translate('Booking_Overview') }} ({{ (int) $customer->bookings_count }})</h3>
                                <div id="apex-pie-chart" class="customer-overview-chart-host flex-grow-1 d-flex justify-content-center align-items-center min-h-0 w-100"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h2>{{translate('Personal_Details')}}</h2>
                    </div>

                    <div>
                        <div class="information-details-box media flex-column flex-sm-row gap-20 mb-3">
                            <img class="avatar-img radius-5"
                                 src="{{$customer->profile_image_full_path}}" alt="{{translate('image')}}">
                            <div class="media-body d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h2 class="information-details-box__title">{{Str::limit($customer->first_name, 30)}}</h2>

                                    <ul class="contact-list">
                                        <li>
                                            <span class="material-symbols-outlined">phone_iphone</span>
                                            <a href="tel:{{$customer->phone}}">{{$customer->phone}}</a>
                                        </li>
                                        <li>
                                            <span class="material-symbols-outlined">mail</span>
                                            <a href="mailto:{{$customer->email}}">{{$customer->email}}</a>
                                        </li>
                                    </ul>
                                </div>
                                @can('customer_update')
                                    <a href="{{route('admin.customer.edit',[$customer->id])}}" class="btn btn--primary">
                                        <span class="material-icons">border_color</span>
                                        {{translate('Edit')}}
                                    </a>
                                @endcan
                            </div>
                        </div>

                        @if($customer->addresses && $customer->addresses->count() > 0)
                            <div class="information-details-box customer-address">
                                <h3 class="fw-medium mb-20">{{ translate('Addresses') }}</h3>
                                @foreach($customer->addresses as $key=>$address)
                                    <div class="d-flex justify-content-between gap-2 mb-20">
                                        <div class="media gap-2 gap-xl-3">
                                            <span class="material-icons fz-30 c1">home</span>
                                            <div class="media-body">
                                                <h4 class="fw-medium mb-1">{{$address->address_label}}</h4>
                                                <div class="text-muted">{{ Str::limit($address->address, 100) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header px-4">
                    <h5 class="modal-title" id="exampleModalLabel">{{translate('Add_Customer_Address')}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-0 pt-4 mt-2 px-4">
                    <div class="form-floating mb-30">
                        <input type="text" class="form-control" id="street" name="street"
                               placeholder="{{translate('Street')}}" value="{{old('street')}}" required>
                        <label>{{translate('Street')}}</label>
                    </div>
                    <div class="form-floating mb-30">
                        <input type="text" class="form-control" id="city" name="city"
                               placeholder="{{translate('City')}}" value="{{old('city')}}" required>
                        <label>{{translate('City')}}</label>
                    </div>
                    <div class="form-floating mb-30">
                        <input type="text" class="form-control" id="country" name="country"
                               placeholder="{{translate('Country')}}" value="{{old('country')}}" required>
                        <label>{{translate('Country')}}</label>
                    </div>
                    <div class="form-floating mb-30">
                        <input type="text" class="form-control" id="zip_code" name="zip_code"
                               placeholder="{{translate('Zip_Code')}}" value="{{old('zip_code')}}" required>
                        <label>{{translate('Zip_Code')}}</label>
                    </div>
                    <div class="form-floating mb-30">
                        <textarea type="text" class="form-control" id="address" name="address"
                                  placeholder="{{translate('Address')}}" value="{{old('address')}}" required></textarea>
                        <label>{{translate('Address')}}</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn--secondary"
                            data-bs-dismiss="modal">{{translate('Close')}}</button>
                    <button type="button" class="btn btn--primary">{{translate('Save_changes')}}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_or_js')
    <style>
        .customer-overview-top {
            --customer-overview-stat-gap: 0.5rem;
        }

        .customer-overview-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-rows: repeat(2, minmax(0, 1fr));
            gap: var(--customer-overview-stat-gap);
            min-height: 11rem;
        }

        @media (min-width: 992px) {
            .customer-overview-stat-grid {
                min-height: 100%;
            }
        }

        .customer-overview-stat-tile {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            min-width: 0;
            min-height: 0;
        }

        .customer-overview-stat-tile h2 {
            margin-block-end: 0.5rem;
        }

        .customer-overview-chart-host {
            min-height: 200px;
        }

        @media (min-width: 992px) {
            .customer-overview-chart-host {
                min-height: 0;
            }
        }
    </style>
@endpush

@push('script')
    @php
        $bookingOverviewStatuses = ['pending', 'accepted', 'ongoing', 'completed', 'canceled'];
        $bookingOverviewChartLabels = [];
        foreach ($bookingOverviewStatuses as $idx => $statusKey) {
            $bookingOverviewChartLabels[] = translate($statusKey) . ' (' . (int) ($total[$idx] ?? 0) . ')';
        }
    @endphp

    <script src="{{asset('assets/admin-module/plugins/apex/apexcharts.min.js')}}"></script>

    <script>
        "use strict"
        var options = {
            labels: @json($bookingOverviewChartLabels),
            series: {{ json_encode($total) }},
            chart: {
                width: '100%',
                height: 200,
                type: 'donut',
                toolbar: { show: false },
            },
            dataLabels: {
                enabled: false
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: true,
                            name: { show: false },
                            value: { show: false },
                            total: {
                                show: true,
                                showAlways: true,
                                label: @json(translate('Bookings')),
                                fontSize: '11px',
                                fontWeight: 500,
                                formatter: function () {
                                    return String({{ (int) $customer->bookings_count }});
                                },
                            },
                        },
                    },
                },
            },
            responsive: [
                {
                    breakpoint: 991,
                    options: {
                        chart: { height: 280 },
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center',
                            offsetY: 4,
                        },
                    },
                },
            ],
            legend: {
                position: 'right',
                offsetY: 0,
                fontSize: '10px',
                itemMargin: {
                    horizontal: 4,
                    vertical: 2,
                },
            },
        };

        var chart = new ApexCharts(document.querySelector("#apex-pie-chart"), options);
        chart.render();
    </script>
@endpush
