@extends('adminmodule::layouts.new-master')

@section('title', translate('User_Report'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/apex/apexcharts.css') }}">
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <h2 class="page-title mb-1">{{ translate('User_Report') }}</h2>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 fz-16">{{ translate('Search_Data') }}</div>
                    <form action="{{ route('admin.lead.reports.user') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('From_Date') }}</label>
                                <input type="date" name="date_from" class="form-control h-45" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('To_Date') }}</label>
                                <input type="date" name="date_to" class="form-control h-45" value="{{ $dateTo }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('User') }}</label>
                                <select name="user_id" class="js-select form-select">
                                    @foreach($filterEmployees as $employee)
                                        @php
                                            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                            $label = $fullName ?: $employee->email;
                                            $selected = ((string)($selectedUserId ?? request()->input('user_id', auth()->id()))) === (string)$employee->id;
                                        @endphp
                                        <option value="{{ $employee->id }}" {{ $selected ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-sm-6 d-flex gap-2">
                                <button type="submit" class="btn btn--primary mt-4 flex-grow-1">{{ translate('Filter') }}</button>
                                <a href="{{ route('admin.lead.reports.user', ['user_id' => auth()->id()]) }}" class="btn btn--secondary mt-4 flex-grow-1">{{ translate('Reset') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row gy-3 pt-2">
                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                        <div>
                            <h2 class="fz-26">{{ $userLeadsTotal ?? 0 }}</h2>
                            <span class="fz-12">{{ translate('Leads_Handled') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/commission_earning.png') }}" alt="">
                        <div>
                            <h2 class="fz-26">{{ $userCanceledTotal ?? 0 }}</h2>
                            <span class="fz-12">{{ translate('Cancelled_Leads') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/net_profit.png') }}" alt="">
                        <div>
                            <h2 class="fz-26">{{ $userBookingsCount ?? 0 }}</h2>
                            <span class="fz-12">{{ translate('Bookings') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                        <div>
                            <h2 class="fz-26">{{ $userOutboundTotal ?? 0 }}</h2>
                            <span class="fz-12">{{ translate('Outbound_Enquiries') }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h4 class="mb-0">{{ translate('User_Report') }}: {{ $selectedUserName ?? '' }}</h4>
                                <span class="text-muted">{{ translate('Date_Range') }}: {{ $dateFrom ?? '' }} - {{ $dateTo ?? '' }}</span>
                            </div>

                            <div class="row gy-3">
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Leads_Volume_Over_Time') }}</div>
                                        <div id="user-leads-volume-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Lead_Type_Distribution') }}</div>
                                        <div id="user-lead-type-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Lead_Status_Open_vs_Closed') }}</div>
                                        <div id="user-open-closed-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Provider_Status_Summary') }}</div>
                                        <div id="user-provider-status-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Customer_Status_Summary') }}</div>
                                        <div id="user-customer-status-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Outbound_By_Channel') }}</div>
                                        <div id="user-outbound-channel-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2">{{ translate('Outbound_By_Status') }}</div>
                                        <div id="user-outbound-status-chart" style="min-height: 260px;"></div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-semibold mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <span>{{ translate('Pending_Followups_Over_Time') }}</span>
                                            <span class="text-muted">{{ $userPendingFollowupsTotal ?? 0 }} {{ translate('pending') }}</span>
                                        </div>
                                        <div id="user-followup-chart" style="min-height: 320px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
    <script>
        "use strict";

        $(document).ready(function () {
            $('.js-select').select2({
                width: '100%',
                placeholder: "{{ translate('All') }}",
                allowClear: true
            });
        });

        (function () {
            (function () {
                const el = document.querySelector('#user-leads-volume-chart');
                if (!el) return;
                const options = {
                    series: [{
                        name: "{{ translate('Leads') }}",
                        data: {!! json_encode($userLeadsPerDay ?? []) !!}
                    }],
                    chart: { height: 290, type: 'line', toolbar: { show: true } },
                    colors: ['#6F8AED'],
                    dataLabels: { enabled: true },
                    stroke: { curve: 'smooth' },
                    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: true } }, borderColor: '#CAD2FF', strokeDashArray: 5 },
                    markers: { size: 1 },
                    xaxis: { categories: {!! json_encode($userLeadsTimeline ?? []) !!} },
                    legend: { position: 'top', horizontalAlign: 'center' }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-lead-type-chart');
                if (!el) return;
                const labels = {!! json_encode($userLeadsByTypeLabels ?? []) !!}.map(function (l, i) {
                    const v = ({!! json_encode($userLeadsByTypeValues ?? []) !!})[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const options = {
                    series: {!! json_encode($userLeadsByTypeValues ?? []) !!},
                    chart: { type: 'donut', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-provider-status-chart');
                if (!el) return;
                const labels = {!! json_encode($userProviderStatusLabels ?? []) !!}.map(function (l, i) {
                    const v = ({!! json_encode($userProviderStatusValues ?? []) !!})[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const options = {
                    series: {!! json_encode($userProviderStatusValues ?? []) !!},
                    chart: { type: 'pie', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-open-closed-chart');
                if (!el) return;
                const values = {!! json_encode($userOpenClosedValues ?? [0, 0]) !!};
                const labelsRaw = {!! json_encode($userOpenClosedLabels ?? ['Open', 'Closed']) !!};
                const labels = labelsRaw.map(function (l, i) {
                    return (l || '—') + ' (' + (values[i] ?? 0) + ')';
                });
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 280 },
                    labels: labels,
                    colors: ['#e74a3b', '#1cc88a'],
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-customer-status-chart');
                if (!el) return;
                const labels = {!! json_encode($userCustomerStatusLabels ?? []) !!}.map(function (l, i) {
                    const v = ({!! json_encode($userCustomerStatusValues ?? []) !!})[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const options = {
                    series: {!! json_encode($userCustomerStatusValues ?? []) !!},
                    chart: { type: 'pie', height: 280 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-outbound-channel-chart');
                if (!el) return;
                const labels = {!! json_encode(array_column($userOutboundByChannel ?? [], 'label')) !!}.map(function (l, i) {
                    const v = {!! json_encode(array_column($userOutboundByChannel ?? [], 'total')) !!}[i] ?? 0;
                    return (l || '—') + ' (' + v + ')';
                });
                const values = {!! json_encode(array_column($userOutboundByChannel ?? [], 'total')) !!};
                const options = {
                    series: values,
                    chart: { type: 'donut', height: 260 },
                    labels: labels,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-outbound-status-chart');
                if (!el) return;
                const categories = {!! json_encode($userOutboundStatusLabels ?? []) !!};
                const seriesValues = {!! json_encode($userOutboundStatusValues ?? []) !!};
                const options = {
                    series: [{ name: "{{ translate('Outbound') }}", data: seriesValues }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: categories, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#4E73DF'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#user-followup-chart');
                if (!el) return;
                const options = {
                    series: [{
                        name: "{{ translate('Pending_Followups') }}",
                        data: {!! json_encode($userFollowupsPerDay ?? []) !!}
                    }],
                    chart: { height: 320, type: 'line', toolbar: { show: true } },
                    colors: ['#36B9CC'],
                    stroke: { curve: 'smooth' },
                    dataLabels: { enabled: false },
                    markers: { size: 2 },
                    xaxis: { categories: {!! json_encode($userFollowupTimeline ?? []) !!} },
                    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: true } }, borderColor: '#E6E6E6' }
                };
                new ApexCharts(el, options).render();
            })();
        })();
    </script>
@endpush
