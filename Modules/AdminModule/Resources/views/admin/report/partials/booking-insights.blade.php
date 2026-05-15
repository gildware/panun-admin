@php
    $a = $analytics ?? [];
    $summary = $a['summary'] ?? [];
@endphp

@push('css_or_js')
    <style>
        .booking-report-analytics .booking-report-chart-card {
            background: #fafbfc;
        }
        .booking-report-analytics .booking-donut-chart .apexcharts-legend {
            padding-top: 4px !important;
        }
        .booking-report-analytics .booking-donut-chart .apexcharts-legend-text {
            font-size: 11px !important;
        }
        .booking-report-analytics .section-title {
            font-size: 1rem;
            font-weight: 600;
        }
        .booking-report-analytics .chart-empty-msg {
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

<div class="booking-report-analytics mb-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h4 class="mb-2 d-flex align-items-center gap-2">
                <span class="material-icons text-primary">insights</span>
                {{ translate('Business_Insights_Summary') }}
            </h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_report_summary_help') }}</p>
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
                    <span class="fz-12 text-muted">{{ translate('completed') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['completed'] ?? 0 }}</h3>
                    <span class="fz-12">{{ translate('completion_rate') }}: {{ $summary['completion_rate'] ?? 0 }}%</span>
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
                    <span class="fz-12">{{ translate('Booking_Reports') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1">{{ translate('Overview') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_charts_overview_help') }}</p>
            <div class="row g-3">
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-outcome-chart',
                    'title' => translate('Lead_Outcome'),
                    'subtitle' => translate('Booking_completed_vs_cancelled_vs_pending'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-category-chart',
                    'title' => translate('By_Category'),
                    'subtitle' => translate('Service_category_share'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-zone-chart',
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
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_created_by_hour_and_day') }}</p>
            <div class="row g-3">
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-day-chart',
                    'title' => translate('By_Day_of_Week'),
                    'colClass' => 'col-md-5',
                    'chartHeight' => 200,
                ])
                <div class="col-md-7">
                    <div class="card h-100 booking-report-chart-card border">
                        <div class="card-body p-3">
                            <h6 class="fw-semibold mb-0">{{ translate('By_Hour_of_Day') }}</h6>
                            <p class="text-muted fz-11 mb-2">{{ translate('Booking_peak_hours_hint') }}</p>
                            <div id="booking-hour-chart" class="booking-donut-chart" style="min-height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-success">{{ translate('completed') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_completed_breakdown_help') }}</p>
            <div class="row g-3">
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-completed-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-completed-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-completed-subcategory-chart',
                    'title' => translate('Sub_Category'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-danger">{{ translate('Cancelled') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_cancelled_breakdown_help') }}</p>
            <div class="row g-3">
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-cancelled-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
                @include('adminmodule::admin.report.partials._donut-chart-card', [
                    'chartId' => 'booking-cancelled-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-4 col-md-6',
                ])
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-2">{{ translate('Detailed_Breakdown') }}</h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_category_matrix_help') }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ translate('Category') }}</th>
                        <th class="text-end">{{ translate('Total') }}</th>
                        <th class="text-end">{{ translate('completed') }}</th>
                        <th class="text-end">{{ translate('Cancelled') }}</th>
                        <th class="text-end">{{ translate('Pending') }}</th>
                        <th class="text-end">{{ translate('completion_rate') }} %</th>
                        <th class="text-end">{{ translate('Share') }} %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($a['category_wise'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-end">{{ $row['total'] }}</td>
                            <td class="text-end text-success">{{ $row['completed'] }}</td>
                            <td class="text-end text-danger">{{ $row['cancelled'] }}</td>
                            <td class="text-end text-warning">{{ $row['pending'] }}</td>
                            <td class="text-end">{{ $row['completion_rate'] }}%</td>
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
            <p class="text-muted fz-12 mb-3">{{ translate('Booking_zone_matrix_help') }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ translate('Zone') }}</th>
                        <th class="text-end">{{ translate('Total') }}</th>
                        <th class="text-end">{{ translate('completed') }}</th>
                        <th class="text-end">{{ translate('Cancelled') }}</th>
                        <th class="text-end">{{ translate('Pending') }}</th>
                        <th class="text-end">{{ translate('completion_rate') }} %</th>
                        <th class="text-end">{{ translate('Share') }} %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($a['zone_wise'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-end">{{ $row['total'] }}</td>
                            <td class="text-end text-success">{{ $row['completed'] }}</td>
                            <td class="text-end text-danger">{{ $row['cancelled'] }}</td>
                            <td class="text-end text-warning">{{ $row['pending'] }}</td>
                            <td class="text-end">{{ $row['completion_rate'] }}%</td>
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
