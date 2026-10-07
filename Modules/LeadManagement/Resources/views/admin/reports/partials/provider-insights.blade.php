@php
    $a = $analytics ?? [];
    $summary = $a['summary'] ?? [];
    $tabCounts = $a['tab_counts'] ?? [];
    $activeTab = $providerStatusTab ?? 'overview';
    $statusTabs = [
        'overview' => ['label' => translate('Overview'), 'count' => $summary['total'] ?? 0, 'class' => ''],
        'completed' => ['label' => translate('completed'), 'count' => $tabCounts['completed'] ?? ($summary['completed'] ?? 0), 'class' => 'text-success'],
        'cancelled' => ['label' => translate('Cancelled'), 'count' => $tabCounts['cancelled'] ?? ($summary['cancelled'] ?? 0), 'class' => 'text-danger'],
        'hold' => ['label' => translate('Hold'), 'count' => $tabCounts['hold'] ?? ($summary['hold'] ?? 0), 'class' => 'text-info'],
        'pending' => ['label' => translate('Pending'), 'count' => $tabCounts['pending'] ?? ($summary['pending_action'] ?? 0), 'class' => 'text-warning'],
    ];
@endphp

@push('css_or_js')
    <style>
        .provider-lead-analytics .provider-report-chart-card {
            background: #fafbfc;
        }
        .provider-lead-analytics .provider-donut-chart .apexcharts-legend {
            padding-top: 4px !important;
            overflow-y: auto !important;
            overflow-x: hidden;
            align-content: flex-start;
        }
        .provider-lead-analytics .provider-donut-chart .apexcharts-legend.apexcharts-align-left {
            flex-direction: column !important;
            flex-wrap: nowrap !important;
            justify-content: flex-start !important;
            align-items: flex-start !important;
        }
        .provider-lead-analytics .provider-donut-chart .apexcharts-legend-text {
            font-size: 11px !important;
        }
        .provider-lead-analytics .section-title {
            font-size: 1rem;
            font-weight: 600;
        }
        .provider-lead-analytics .chart-empty-msg {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            color: #6c757d;
            font-size: 12px;
            text-align: center;
            padding: 1rem;
        }
        .provider-lead-analytics .chart-drilldown-view-btn {
            line-height: 1;
            vertical-align: middle;
            color: #4e73df;
            text-decoration: none;
        }
        .provider-lead-analytics .apexcharts-legend-series {
            display: inline-flex !important;
            align-items: center;
            gap: 2px;
        }
        .provider-status-tab-link .badge { font-size: 11px; }
    </style>
@endpush

<div class="provider-lead-analytics mb-4">
    <ul class="nav nav--tabs mb-3 flex-wrap">
        @foreach($statusTabs as $tabKey => $tabMeta)
            <li class="nav-item">
                <a class="nav-link provider-status-tab-link {{ $activeTab === $tabKey ? 'active' : '' }} {{ $tabMeta['class'] }}"
                   href="{{ route('admin.lead.reports.inbound', array_merge($queryParams ?? ['inbound_report' => 'provider'], ['inbound_report' => 'provider', 'provider_status_tab' => $tabKey])) }}">
                    {{ $tabMeta['label'] }}
                    <span class="badge bg-light text-dark border ms-1">{{ $tabMeta['count'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if($activeTab === 'overview')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h4 class="mb-2 d-flex align-items-center gap-2">
                <span class="material-icons text-primary">insights</span>
                {{ translate('Business_Insights_Summary') }}
            </h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Provider_report_summary_help') }}</p>
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
                    <span class="fz-12">{{ translate('Hold') }}: {{ $summary['hold'] ?? 0 }} · {{ translate('Needs_action') }}: {{ $summary['pending_action'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Total_Leads_in_Range') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['total'] ?? 0 }}</h3>
                    <span class="fz-12">{{ translate('Provider_Lead_Reports') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1">{{ translate('Overview') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Provider_charts_overview_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-outcome-chart',
                    'title' => translate('Lead_Outcome'),
                    'subtitle' => translate('Completed_vs_cancelled_vs_pending'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-category-chart',
                    'title' => translate('By_Category'),
                    'subtitle' => translate('Service_category_share'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-zone-chart',
                    'title' => translate('By_Zone'),
                    'subtitle' => translate('Geographic_share'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1">{{ translate('When_Leads_Are_Received') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Provider_lead_intake_by_hour_and_day') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-lead-day-chart',
                    'title' => translate('By_Day_of_Week'),
                    'colClass' => 'col-md-5',
                    'chartHeight' => 200,
                ])
                <div class="col-md-7">
                    <div class="card h-100 provider-report-chart-card border">
                        <div class="card-body p-3">
                            <h6 class="fw-semibold mb-0">{{ translate('By_Hour_of_Day') }}</h6>
                            <p class="text-muted fz-11 mb-2">{{ translate('Provider_peak_hours_hint') }}</p>
                            <div id="provider-lead-hour-chart" class="provider-donut-chart" style="min-height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @elseif($activeTab === 'completed')
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-success">{{ translate('completed') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Completed_breakdown_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-completed-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-completed-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-completed-subcategory-chart',
                    'title' => translate('Sub_Category'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
            </div>
        </div>
    </div>
    @include('leadmanagement::admin.reports.partials.customer-leads-table', [
        'title' => translate('Completed_Leads'),
        'subtitle' => translate('Completed_leads_table_help'),
        'rows' => $a['leads_by_tab']['completed'] ?? [],
        'columns' => ['id', 'name', 'phone', 'category', 'subcategory', 'zone', 'handled_by', 'source', 'received_at', 'followups', 'first_contact'],
    ])

    @elseif($activeTab === 'cancelled')
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-danger">{{ translate('Cancelled') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Provider_cancelled_breakdown_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-cancelled-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-cancelled-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-cancel-reason-chart',
                    'title' => translate('Cancellation_Reasons'),
                    'colClass' => 'col-lg-6',
                    'chartHeight' => 300,
                ])
            </div>
        </div>
    </div>

    @php $cancelDeep = $a['cancelled_deep'] ?? []; @endphp
    <div class="card mb-3 border-0 shadow-sm border-start border-4 border-danger">
        <div class="card-body">
            <h4 class="mb-1 text-danger d-flex align-items-center gap-2">
                <span class="material-icons">analytics</span>
                {{ translate('Cancelled_Deep_Analysis') }}
            </h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Cancelled_deep_analysis_help') }}</p>
            @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
                'title' => translate('Category_x_Cancellation_Reason'),
                'subtitle' => translate('Category_reason_matrix_help'),
                'parentLabel' => translate('Category'),
                'childLabel' => translate('Cancellation_Reason'),
                'rows' => $cancelDeep['category_reason_matrix'] ?? [],
            ])
            @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
                'title' => translate('Category_x_Zone'),
                'subtitle' => translate('Open_category_zone_matrix_help'),
                'parentLabel' => translate('Category'),
                'childLabel' => translate('Zone'),
                'rows' => $cancelDeep['category_zone_matrix'] ?? [],
            ])
            @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
                'title' => translate('Reason_x_Zone'),
                'subtitle' => translate('Reason_zone_matrix_help'),
                'parentLabel' => translate('Cancellation_Reason'),
                'childLabel' => translate('Zone'),
                'rows' => $cancelDeep['reason_zone_matrix'] ?? [],
            ])
            @if(!empty($cancelDeep['remarks']))
                <div class="mt-4">
                    <h5 class="fz-14 mb-2">{{ translate('Cancellation_Remarks') }}</h5>
                    <p class="text-muted fz-12 mb-2">{{ translate('Cancellation_remarks_help') }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Category') }}</th>
                                <th>{{ translate('Zone') }}</th>
                                <th>{{ translate('Reason') }}</th>
                                <th>{{ translate('Remarks') }}</th>
                                <th class="text-end">{{ translate('Followups') }}</th>
                                <th class="text-end">{{ translate('Hours_to_first_followup') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($cancelDeep['remarks'] as $remark)
                                <tr>
                                    <td>{{ $remark['category'] ?? '—' }}</td>
                                    <td>{{ $remark['zone'] ?? '—' }}</td>
                                    <td>{{ $remark['reason'] ?? '—' }}</td>
                                    <td class="text-wrap" style="max-width: 280px;">{{ $remark['text'] ?? '' }}</td>
                                    <td class="text-end">{{ $remark['followup_count'] ?? 0 }}</td>
                                    <td class="text-end">{{ isset($remark['hours_to_first_followup']) ? $remark['hours_to_first_followup'].'h' : '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @include('leadmanagement::admin.reports.partials.customer-leads-table', [
        'title' => translate('All_Cancelled_Leads'),
        'subtitle' => translate('Cancelled_leads_table_help'),
        'rows' => $a['leads_by_tab']['cancelled'] ?? [],
        'columns' => ['id', 'name', 'phone', 'category', 'zone', 'reason', 'remarks', 'handled_by', 'source', 'received_at', 'followups', 'first_contact'],
    ])

    @elseif($activeTab === 'hold')
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-info">{{ translate('Hold') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Hold_leads_tab_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-hold-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-4',
                    'chartHeight' => 260,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-hold-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-4',
                    'chartHeight' => 260,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-hold-reason-chart',
                    'title' => translate('Hold_Reasons'),
                    'colClass' => 'col-lg-4',
                    'chartHeight' => 260,
                ])
            </div>
        </div>
    </div>

    @include('leadmanagement::admin.reports.partials.open-status-deep-insights', [
        'mode' => 'hold',
        'tone' => 'info',
        'title' => translate('Hold_Deep_Analysis'),
        'help' => translate('Hold_deep_analysis_help'),
        'deep' => $a['hold_deep'] ?? [],
    ])

    @include('leadmanagement::admin.reports.partials.customer-leads-table', [
        'title' => translate('All_Hold_Leads'),
        'subtitle' => translate('Hold_leads_full_table_help'),
        'rows' => $a['hold_deep']['rows'] ?? [],
        'columns' => ['id', 'name', 'phone', 'category', 'zone', 'hold_reason', 'status_remarks', 'handled_by', 'source', 'received_at', 'next_followup', 'followups', 'first_contact'],
    ])

    @elseif($activeTab === 'pending')
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1 text-warning">{{ translate('Pending') }}</p>
            <p class="text-muted fz-12 mb-3">{{ translate('Pending_leads_tab_help') }}</p>
            <div class="row g-3">
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-pending-category-chart',
                    'title' => translate('Category_Wise'),
                    'colClass' => 'col-lg-4',
                    'chartHeight' => 260,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-pending-zone-chart',
                    'title' => translate('Zone_Wise'),
                    'colClass' => 'col-lg-4',
                    'chartHeight' => 260,
                ])
                @include('leadmanagement::admin.reports.partials._donut-chart-card', [
                    'chartId' => 'provider-pending-reason-chart',
                    'title' => translate('Pending_Reasons'),
                    'colClass' => 'col-lg-4',
                    'chartHeight' => 260,
                ])
            </div>
        </div>
    </div>

    @include('leadmanagement::admin.reports.partials.open-status-deep-insights', [
        'mode' => 'pending',
        'tone' => 'warning',
        'title' => translate('Pending_Deep_Analysis'),
        'help' => translate('Pending_deep_analysis_help'),
        'deep' => $a['pending_deep'] ?? [],
    ])

    @include('leadmanagement::admin.reports.partials.customer-leads-table', [
        'title' => translate('All_Pending_Leads'),
        'subtitle' => translate('Pending_leads_full_table_help'),
        'rows' => $a['pending_deep']['rows'] ?? [],
        'columns' => ['id', 'name', 'phone', 'category', 'zone', 'pending_reason', 'status_remarks', 'handled_by', 'source', 'received_at', 'next_followup', 'followups', 'first_contact'],
    ])
    @endif

    @if($activeTab === 'overview')
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-2">{{ translate('Detailed_Breakdown') }}</h4>
            <p class="text-muted fz-12 mb-3">{{ translate('Provider_category_matrix_help') }}</p>
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
            <p class="text-muted fz-12 mb-3">{{ translate('Provider_zone_matrix_help') }}</p>
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
    @endif
</div>
