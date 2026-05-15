@php
    $a = $analytics ?? [];
    $summary = $a['summary'] ?? [];
@endphp

@push('css_or_js')
    <style>
        .customer-lead-analytics .customer-report-chart-card {
            background: #fafbfc;
        }
        .customer-lead-analytics .customer-donut-chart .apexcharts-legend {
            padding-top: 4px !important;
        }
        .customer-lead-analytics .customer-donut-chart .apexcharts-legend-text {
            font-size: 11px !important;
        }
        .customer-lead-analytics .section-title {
            font-size: 1rem;
            font-weight: 600;
        }
        .customer-lead-analytics .chart-empty-msg {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            color: #6c757d;
            font-size: 12px;
            text-align: center;
            padding: 1rem;
        }
    </style>
@endpush

<div class="customer-lead-analytics mb-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h4 class="mb-2 d-flex align-items-center gap-2">
                <span class="material-icons text-primary">insights</span>
                {{ translate('Business_Insights_Summary') }}
            </h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Customer_report_summary_help') }}</p>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                @foreach($a['insights'] ?? [] as $insight)
                    @php
                        $alertClass = match ($insight['type'] ?? 'info') {
                            'success' => 'alert-success',
                            'warning' => 'alert-warning',
                            'danger' => 'alert-danger',
                            default => 'alert-info',
                        };
                    @endphp
                    <li class="alert {{ $alertClass }} py-2 px-3 mb-0 fz-13">{{ $insight['text'] ?? '' }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-sm-6">
            <div class="card h-100 border-start border-4 border-success">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Booked') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['booked'] ?? 0 }}</h3>
                    <span class="fz-12">{{ translate('conversion') }}: {{ $summary['conversion_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card h-100 border-start border-4 border-danger">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Cancelled') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['cancelled'] ?? 0 }}</h3>
                    <span class="fz-12">{{ translate('cancellation_rate') }}: {{ $summary['cancel_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card h-100 border-start border-4 border-warning">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Pending') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['pending'] ?? 0 }}</h3>
                    <span class="fz-12">{{ translate('Needs_followup') }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Total_Leads_in_Range') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['total'] ?? 0 }}</h3>
                    <span class="fz-12">{{ translate('Customer_Lead_Reports') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1">{{ translate('Overview') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Customer_charts_overview_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-outcome-chart',
                    'title' => translate('Lead_Outcome'),
                    'subtitle' => translate('Booked_vs_cancelled_vs_pending'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-category-chart',
                    'title' => translate('By_Category'),
                    'subtitle' => translate('Service_category_share'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-zone-chart',
                    'title' => translate('By_Zone'),
                    'subtitle' => translate('Geographic_share'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1">{{ translate('When_Leads_Are_Received') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Lead_intake_by_hour_and_day') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-lead-day-chart',
                    'title' => translate('By_Day_of_Week'),
                    'colClass' => 'col-md-5',
                    'chartHeight' => 200,
                ])
                <div class="col-md-7">
                    <div class="card h-100 customer-report-chart-card border">
                        <div class="card-body p-3">
                            <h6 class="fw-semibold mb-0">{{ translate('By_Hour_of_Day') }}</h6>
                            <p class="text-muted fz-11 mb-2">{{ translate('Peak_hours_hint') }}</p>
                            <div id="customer-lead-hour-chart" class="customer-donut-chart" style="min-height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-success">{{ translate('Booked') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Booked_breakdown_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-booked-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-booked-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-booked-subcategory-chart',
                    'title' => translate('Sub_Category'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-booking-hour-chart',
                    'title' => translate('Booking_Time_of_Day'),
                    'subtitle' => translate('When_bookings_are_created'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                <div class="col-lg-8 col-md-6">
                    <div class="card h-100 customer-report-chart-card border">
                        <div class="card-body p-3">
                            <h6 class="fw-semibold mb-0">{{ translate('When_Bookings_Are_Created') }}</h6>
                            <p class="text-muted fz-11 mb-2">{{ translate('Bookings_over_selected_period') }}</p>
                            <div id="customer-booking-timeline-chart" style="min-height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-danger">{{ translate('Cancelled') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Cancelled_breakdown_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-cancelled-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-cancelled-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'customer-cancel-reason-chart',
                    'title' => translate('Cancellation_Reasons'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-2">{{ translate('Detailed_Breakdown') }}</h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Category_matrix_help') }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ translate('Category') }}</th>
                        <th class="text-end">{{ translate('Total') }}</th>
                        <th class="text-end">{{ translate('Booked') }}</th>
                        <th class="text-end">{{ translate('Cancelled') }}</th>
                        <th class="text-end">{{ translate('Pending') }}</th>
                        <th class="text-end">{{ translate('conversion') }} %</th>
                        <th class="text-end">{{ translate('Share') }} %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($a['category_wise'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-end">{{ $row['total'] }}</td>
                            <td class="text-end text-success">{{ $row['booked'] }}</td>
                            <td class="text-end text-danger">{{ $row['cancelled'] }}</td>
                            <td class="text-end text-warning">{{ $row['pending'] }}</td>
                            <td class="text-end">{{ $row['conversion_rate'] }}%</td>
                            <td class="text-end">{{ $row['share_percent'] }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">{{ translate('Data_not_available') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="text-muted fz-12 mb-3">{{ translate('Zone_matrix_help') }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ translate('Zone') }}</th>
                        <th class="text-end">{{ translate('Total') }}</th>
                        <th class="text-end">{{ translate('Booked') }}</th>
                        <th class="text-end">{{ translate('Cancelled') }}</th>
                        <th class="text-end">{{ translate('Pending') }}</th>
                        <th class="text-end">{{ translate('conversion') }} %</th>
                        <th class="text-end">{{ translate('Share') }} %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($a['zone_wise'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-end">{{ $row['total'] }}</td>
                            <td class="text-end text-success">{{ $row['booked'] }}</td>
                            <td class="text-end text-danger">{{ $row['cancelled'] }}</td>
                            <td class="text-end text-warning">{{ $row['pending'] }}</td>
                            <td class="text-end">{{ $row['conversion_rate'] }}%</td>
                            <td class="text-end">{{ $row['share_percent'] }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">{{ translate('Data_not_available') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
