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

                        @php
                            $overviewCanAddAddress = auth()->user()->can('customer_add');
                            $overviewCanEditAddress = auth()->user()->can('customer_add') || auth()->user()->can('customer_update');
                        @endphp
                        <div class="information-details-box customer-address">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-20">
                                <h3 class="fw-medium mb-0">{{ translate('Addresses') }}</h3>
                                @if($overviewCanAddAddress)
                                    <button type="button" class="btn btn--primary btn-sm" id="customer-overview-open-add-address">
                                        <span class="material-icons fz-18">add</span>
                                        {{ translate('Add_Address') }}
                                    </button>
                                @endif
                            </div>
                            @if($customer->addresses && $customer->addresses->count() > 0)
                                @foreach($customer->addresses as $address)
                                    <div class="d-flex justify-content-between gap-2 mb-20 align-items-start" data-customer-address-row="{{ $address->id }}">
                                        <div class="media gap-2 gap-xl-3 flex-grow-1">
                                            <span class="material-icons fz-30 c1 flex-shrink-0">home</span>
                                            <div class="media-body">
                                                <h4 class="fw-medium mb-1" data-role="address-label">{{ $address->address_label }}</h4>
                                                <div class="text-muted" data-role="address-text">{{ Str::limit($address->address, 200) }}</div>
                                            </div>
                                        </div>
                                        @if($overviewCanEditAddress)
                                            <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0 customer-overview-edit-address"
                                                    data-address-id="{{ $address->id }}">
                                                {{ translate('Edit') }}
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted mb-0">{{ translate('no_data_found') }}</p>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($overviewCanAddAddress || $overviewCanEditAddress)
    <div class="modal fade" id="customerOverviewAddressModal" tabindex="-1" aria-labelledby="customerOverviewAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header px-4">
                    <h5 class="modal-title" id="customerOverviewAddressModalLabel">{{ translate('Add_Customer_Address') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pt-3">
                    <div id="customer-overview-address-alert" class="alert alert-danger d-none" role="alert"></div>
                    <form id="customer-overview-address-form">
                        @csrf
                        <input type="hidden" id="customer-overview-address-edit-id" value="">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Address') }}</label>
                            <textarea class="form-control" name="address" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Address_Label') }}</label>
                            <input type="text" class="form-control" name="address_label" placeholder="{{ translate('Home/Office/Others') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Landmark') }} ({{ translate('Optional') }})</label>
                            <input type="text" class="form-control" name="landmark">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ translate('lat') }} ({{ translate('Optional') }})</label>
                                <input type="text" class="form-control" name="lat" placeholder="{{ translate('lat') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ translate('long') }} ({{ translate('Optional') }})</label>
                                <input type="text" class="form-control" name="lon" placeholder="{{ translate('long') }}">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="button" class="btn btn--primary" id="customer-overview-save-address">{{ translate('Save_changes') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
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

    @if($overviewCanAddAddress || $overviewCanEditAddress)
    <script>
        "use strict";
        (function ($) {
            const customerId = @json($customer->id);
            const $modal = $('#customerOverviewAddressModal');
            const $form = $('#customer-overview-address-form');
            const $editId = $('#customer-overview-address-edit-id');
            const $title = $('#customerOverviewAddressModalLabel');
            const $alert = $('#customer-overview-address-alert');

            function overviewAddrShowError(msg) {
                if (Array.isArray(msg)) {
                    $alert.html(msg.map(function (m) { return $('<div/>').text(m).html(); }).join('<br>'));
                } else {
                    $alert.text(msg);
                }
                $alert.removeClass('d-none');
            }

            function overviewAddrHideError() {
                $alert.addClass('d-none').empty();
            }

            $modal.on('hidden.bs.modal', function () {
                if ($form[0]) {
                    $form[0].reset();
                }
                $editId.val('');
                $title.text(@json(translate('Add_Customer_Address')));
                overviewAddrHideError();
            });

            $('#customer-overview-open-add-address').on('click', function () {
                $editId.val('');
                $title.text(@json(translate('Add_Customer_Address')));
                if ($form[0]) {
                    $form[0].reset();
                }
                overviewAddrHideError();
                $modal.modal('show');
            });

            $(document).on('click', '.customer-overview-edit-address', function () {
                const addressId = $(this).data('address-id');
                if (!addressId) {
                    return;
                }
                let showUrl = @json(route('admin.customer.address-quick-show', ['id' => '__CID__', 'addressId' => '__AID__']));
                showUrl = showUrl.replace('__CID__', encodeURIComponent(customerId)).replace('__AID__', encodeURIComponent(addressId));
                overviewAddrHideError();
                $.get(showUrl, function (addr) {
                    $editId.val(addr.id);
                    $title.text(@json(translate('Edit_Address')));
                    $form.find('[name="address"]').val(addr.address || '');
                    $form.find('[name="address_label"]').val(addr.address_label || '');
                    $form.find('[name="landmark"]').val(addr.landmark || '');
                    $form.find('[name="lat"]').val(addr.lat || '');
                    $form.find('[name="lon"]').val(addr.lon || '');
                    $modal.modal('show');
                }).fail(function (xhr) {
                    overviewAddrShowError(xhr.status === 404 ? @json(translate('not_found')) : @json(translate('Something_went_wrong')));
                });
            });

            $('#customer-overview-save-address').on('click', function () {
                const editVal = $editId.val();
                let url;
                let data;
                if (editVal) {
                    url = @json(route('admin.customer.address-quick-update', ['id' => '__CID__', 'addressId' => '__AID__']));
                    url = url.replace('__CID__', encodeURIComponent(customerId)).replace('__AID__', encodeURIComponent(editVal));
                    data = $form.serialize() + '&_method=PUT';
                } else {
                    url = @json(route('admin.customer.address-quick-store', ['id' => '__CID__']));
                    url = url.replace('__CID__', encodeURIComponent(customerId));
                    data = $form.serialize();
                }
                overviewAddrHideError();
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function () {
                        $modal.modal('hide');
                        window.location.reload();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var msgs = [];
                            Object.values(xhr.responseJSON.errors).forEach(function (errs) {
                                msgs = msgs.concat(errs);
                            });
                            overviewAddrShowError(msgs);
                        } else {
                            overviewAddrShowError(@json(translate('Something_went_wrong')));
                        }
                    }
                });
            });
        })(jQuery);
    </script>
    @endif
@endpush
