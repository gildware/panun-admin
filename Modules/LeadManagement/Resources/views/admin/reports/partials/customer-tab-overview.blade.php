@php $summary = $a['summary'] ?? []; @endphp

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100 border-start border-4 border-primary">
            <div class="card-body py-3">
                <span class="fz-12 text-muted">{{ translate('Total_Leads_in_Range') }}</span>
                <h3 class="mb-0 mt-1">{{ $summary['total'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
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
                <span class="fz-12 text-muted">{{ translate('Pending') }} / {{ translate('Hold') }}</span>
                <h3 class="mb-0 mt-1">{{ ($summary['pending'] ?? 0) + ($summary['hold'] ?? 0) }}</h3>
                <span class="fz-12">{{ translate('Pending') }}: {{ $summary['pending'] ?? 0 }} · {{ translate('Hold') }}: {{ $summary['hold'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <p class="section-title mb-1">{{ translate('Overview') }}</p>
        <p class="text-muted fz-12 mb-3">{{ translate('Customer_charts_overview_help') }}</p>
        @include('leadmanagement::admin.reports.partials._customer-tab-charts-row', [
            'charts' => [
                    [
                        'chartId' => 'customer-outcome-chart',
                        'title' => translate('Lead_Outcome'),
                        'subtitle' => translate('Booked_vs_cancelled_vs_pending'),
                    ],
                    [
                        'chartId' => 'customer-category-chart',
                        'title' => translate('Category_Wise'),
                        'subtitle' => translate('Service_category_share'),
                    ],
                    [
                        'chartId' => 'customer-zone-chart',
                        'title' => translate('Zone_Wise'),
                        'subtitle' => translate('Geographic_share'),
                    ],
                ],
        ])
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
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">{{ translate('Data_not_available') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
