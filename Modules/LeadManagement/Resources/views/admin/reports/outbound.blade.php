@extends('adminmodule::layouts.new-master')

@section('title', translate('Outbound_Lead_Reports'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/apex/apexcharts.css') }}">
    <style>
        .main-content .apexcharts-legend.apexcharts-align-left {
            flex-direction: column !important;
            flex-wrap: nowrap !important;
            justify-content: flex-start !important;
            align-items: flex-start !important;
            overflow-y: auto !important;
            overflow-x: hidden;
            align-content: flex-start;
            max-height: 100%;
        }
        .report-filter-offcanvas { display: flex; flex-direction: column; }
        .report-filter-offcanvas .report-filter-form-flex { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .report-filter-offcanvas .report-filter-body { flex: 1; min-height: 0; }
        .report-filter-offcanvas .report-filter-footer { flex-shrink: 0; }
        .report-filter-btn-margin { margin-top: 0; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @php
                $filtersAppliedCount = 0;
                if (request()->filled('date_from')) {
                    $filtersAppliedCount++;
                }
                if (request()->filled('date_to')) {
                    $filtersAppliedCount++;
                }
                if (!empty($selectedHandledByIds ?? [])) {
                    $filtersAppliedCount++;
                }
                if (!empty($selectedContactedThroughs ?? [])) {
                    $filtersAppliedCount++;
                }
            @endphp

            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <h2 class="page-title mb-1">{{ translate('Outbound_Lead_Reports') }}</h2>
                <button type="button"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 position-relative report-filter-btn report-filter-btn-margin"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#reportFilterDrawer"
                        aria-controls="reportFilterDrawer">
                    <span class="material-icons">filter_list</span>
                    {{ translate('Filter') }}
                    @if($filtersAppliedCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="report-filter-count-badge">{{ $filtersAppliedCount }}</span>
                    @endif
                </button>
            </div>

            <div class="offcanvas offcanvas-end report-filter-offcanvas" tabindex="-1" id="reportFilterDrawer" aria-labelledby="reportFilterDrawerLabel" data-select-placeholder="{{ translate('All') }}" style="width: 560px; max-width: 95vw;">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="reportFilterDrawerLabel">{{ translate('Search_Data') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
                </div>
                <form action="{{ route('admin.lead.reports.outbound') }}" method="GET" id="report-filter-form" class="report-filter-form-flex">
                    <div class="offcanvas-body pt-3 overflow-auto flex-grow-1 report-filter-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label">{{ translate('From_Date') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div>
                                <label class="form-label">{{ translate('To_Date') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            <div>
                                <label class="form-label">{{ translate('Contacted_Through') }}</label>
                                <select name="contacted_throughs[]" class="js-select form-select" multiple>
                                    <option value="call" {{ in_array('call', $selectedContactedThroughs ?? [], false) ? 'selected' : '' }}>
                                        {{ translate('Call') }}
                                    </option>
                                    <option value="message" {{ in_array('message', $selectedContactedThroughs ?? [], false) ? 'selected' : '' }}>
                                        {{ translate('Message') }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">{{ translate('Handled_By') }}</label>
                                <select name="handled_by_ids[]" class="js-select form-select" multiple>
                                    @foreach($filterEmployees as $employee)
                                        @php
                                            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                            $label = $fullName ?: $employee->email;
                                        @endphp
                                        <option value="{{ $employee->id }}" {{ in_array($employee->id, $selectedHandledByIds ?? [], false) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-filter-footer border-top bg-body p-3 flex-shrink-0">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.lead.reports.outbound') }}" class="btn btn--secondary flex-grow-1">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('Filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="row gy-3 pt-2">
                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                        <div>
                            <h2 class="fz-26">{{ $totalOutbound ?? 0 }}</h2>
                            <span class="fz-12">{{ translate('Total_Outbound_Enquiries_in_Range') }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                        <div class="w-100">
                            <div class="fw-semibold mb-2">{{ translate('By_Channel') }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <tbody>
                                    @forelse(($outboundByChannel ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ $row['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-2">{{ translate('Data_not_available') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                        <div class="w-100">
                            <div class="fw-semibold mb-2">{{ translate('By_User') }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <tbody>
                                    @forelse(($outboundByUser ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ $row['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-2">{{ translate('Data_not_available') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="card flex-row gap-4 p-30 flex-wrap align-items-center h-100">
                        <img width="35" class="avatar" src="{{ asset('assets/admin-module/img/icons/total_expense.png') }}" alt="">
                        <div class="w-100">
                            <div class="fw-semibold mb-2">{{ translate('By_Status') }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <tbody>
                                    @forelse(($outboundByStatus ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ $row['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-2">{{ translate('Data_not_available') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h4 class="mb-3">{{ translate('Outbound_Reports') }}</h4>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ translate('Call_vs_Message') }}</div>
                                <div id="outbound-channel-chart" style="min-height: 260px;"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ translate('Status_wise') }}</div>
                                <div id="outbound-status-chart" style="min-height: 260px;"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ translate('Call_status_wise') }}</div>
                                <div id="outbound-call-status-chart" style="min-height: 260px;"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ translate('Message_status_wise') }}</div>
                                <div id="outbound-message-status-chart" style="min-height: 260px;"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <div class="fw-semibold mb-2">{{ translate('Users_wise_status') }}</div>
                                <div id="outbound-user-status-chart" style="min-height: 340px;"></div>
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
            const channelLabels = {!! json_encode(array_column($outboundByChannel ?? [], 'label')) !!};
            const channelValues = {!! json_encode(array_column($outboundByChannel ?? [], 'total')) !!};

            const statusLabels = {!! json_encode($outboundStatusLabels ?? []) !!};
            const statusValues = {!! json_encode(array_column($outboundByStatus ?? [], 'total')) !!};

            const callStatusValues = {!! json_encode($outboundCallStatusCounts ?? []) !!};
            const messageStatusValues = {!! json_encode($outboundMessageStatusCounts ?? []) !!};

            const userCategories = {!! json_encode($outboundUserCategories ?? []) !!};
            const userStatusSeries = {!! json_encode($outboundUserStatusSeries ?? []) !!};

            (function () {
                const el = document.querySelector('#outbound-channel-chart');
                if (!el) return;
                const options = {
                    series: channelValues,
                    chart: { type: 'donut', height: 260 },
                    labels: channelLabels.map(function (l, i) { return (l || '—') + ' (' + (channelValues[i] ?? 0) + ')'; }),
                    legend: { position: 'left', horizontalAlign: 'left', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#outbound-status-chart');
                if (!el) return;
                const labels = statusLabels.map(function (l, i) { return (l || '—') + ' (' + (statusValues[i] ?? 0) + ')'; });
                const options = {
                    series: statusValues,
                    chart: { type: 'pie', height: 260 },
                    labels: labels,
                    legend: { position: 'left', horizontalAlign: 'left', fontSize: '11px' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#outbound-call-status-chart');
                if (!el) return;
                const options = {
                    series: [{ name: "{{ translate('Calls') }}", data: callStatusValues }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: statusLabels, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#4E73DF'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#outbound-message-status-chart');
                if (!el) return;
                const options = {
                    series: [{ name: "{{ translate('Messages') }}", data: messageStatusValues }],
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: statusLabels, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    colors: ['#1CC88A'],
                    dataLabels: { enabled: true }
                };
                new ApexCharts(el, options).render();
            })();

            (function () {
                const el = document.querySelector('#outbound-user-status-chart');
                if (!el) return;
                const options = {
                    series: userStatusSeries,
                    chart: { type: 'bar', height: 340, stacked: true, toolbar: { show: true } },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                    xaxis: { categories: userCategories, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    legend: { position: 'left', horizontalAlign: 'left', fontSize: '11px' },
                    dataLabels: { enabled: false },
                    tooltip: { shared: true, intersect: false }
                };
                new ApexCharts(el, options).render();
            })();
        })();

        (function ($) {
            function cleanupReportFilterBackdrop() {
                document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop')
                    .forEach(function (el) { el.remove(); });
                document.body.classList.remove('modal-open', 'offcanvas-backdrop');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }

            function closeReportFilterDrawer() {
                var drawerEl = document.getElementById('reportFilterDrawer');
                if (!drawerEl) {
                    return;
                }
                $('#reportFilterDrawer .select2-hidden-accessible').each(function () {
                    var $el = $(this);
                    if ($el.data('select2')) {
                        $el.select2('close');
                    }
                });
                var bs = bootstrap.Offcanvas.getInstance(drawerEl);
                if (bs) {
                    bs.hide();
                } else {
                    cleanupReportFilterBackdrop();
                }
            }

            $(document).on('submit', '#report-filter-form', function () {
                closeReportFilterDrawer();
            });

            var reportFilterDrawerEl = document.getElementById('reportFilterDrawer');
            if (reportFilterDrawerEl) {
                reportFilterDrawerEl.addEventListener('shown.bs.offcanvas', function () {
                    if (typeof window.initAdminPageSelect2 === 'function') {
                        window.initAdminPageSelect2(this, { force: true, includeSingle: true });
                    }
                });
                reportFilterDrawerEl.addEventListener('hidden.bs.offcanvas', cleanupReportFilterBackdrop);
            }
        })(jQuery);
    </script>
@endpush
