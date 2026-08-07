@php
    $leadAnalytics = $leadAnalytics ?? [];
    $typeBreakdown = $leadAnalytics['type_breakdown'] ?? [];
    $customer = $leadAnalytics['customer'] ?? [];
    $provider = $leadAnalytics['provider'] ?? [];
    $futureCustomerReasons = $leadAnalytics['future_customer_reasons'] ?? [];
    $invalidReasons = $leadAnalytics['invalid_reasons'] ?? [];
    $outbound = $leadAnalytics['outbound'] ?? [];
    $sources = $leadAnalytics['sources']['rows'] ?? [];
    $leadPeriodLabel = $leadAnalytics['period_label'] ?? ($periodLabel ?? ($dateLabel ?? ''));
@endphp

@if($leadPeriodLabel !== '')
    <div class="compact-banner">
        @include('adminmodule::partials._material-icon', ['name' => 'date_range', 'class' => 'mso'])
        <div>{{ translate('Progress_lead_analytics_period') ?? 'Lead breakdown for' }}: <strong>{{ $leadPeriodLabel }}</strong></div>
    </div>
@endif

<div class="booking-status-section">
    @include('adminmodule::partials._employee-progress-section-label', [
        'label' => translate('Progress_leads_handled') ?? translate('Leads_added'),
        'helpKey' => 'leads_handled_section',
    ])
    @include('adminmodule::partials._employee-progress-lead-metric-grid', [
        'rows' => collect($typeBreakdown)->map(function ($row) {
            $row['help_key'] = 'lead_type_'.($row['key'] ?? '');

            return $row;
        })->all(),
        'gridClass' => 'lead-metric-grid lead-metric-grid--handled',
    ])
</div>

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => translate('Customer').' '.translate('Leads'),
    'helpKey' => 'customer_leads_section',
])
@include('adminmodule::partials._employee-progress-lead-metric-grid', [
    'rows' => collect($customer['outcome_rows'] ?? [])->map(function ($row) {
        $row['help_key'] = 'customer_outcome_'.($row['key'] ?? '');
        $row['sublabel'] = translate('Progress_of_customer_leads') ?? translate('Customer');

        return $row;
    })->all(),
    'gridClass' => 'lead-metric-grid lead-metric-grid--outcomes',
])
<div class="grid-2">
    <div class="chart-card">
        @include('adminmodule::partials._employee-progress-chart-head', [
            'title' => translate('Progress_customer_conversion') ?? translate('Bookings_completed'),
            'subtitle' => translate('Progress_leads_converted') ?? translate('Bookings_completed'),
            'helpKey' => 'chart_customer_outcomes',
        ])
        <div class="chart-card-body"><div id="chart-customer-outcomes" class="chart-donut"></div></div>
    </div>
    <div class="data-table-wrap">
        <div class="table-head-with-info">
            @include('adminmodule::partials._employee-progress-section-label', [
                'label' => translate('Progress_cancellation_reason') ?? translate('Cancelled'),
                'helpKey' => 'customer_cancel_reasons',
            ])
        </div>
        <table class="data-table" style="min-width:0">
            <thead><tr><th>{{ translate('Progress_cancellation_reason') ?? translate('Cancelled') }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Share') ?? '%' }}</th></tr></thead>
            <tbody>
                @forelse($customer['cancel_reasons'] ?? [] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>
                            @include('adminmodule::partials._employee-progress-metric-value', [
                                'count' => $row['count'] ?? 0,
                                'total' => $row['total'] ?? null,
                                'ofClass' => 'mc-of',
                            ])
                        </td>
                        <td><div class="cell-bar"><div class="cell-bar-track"><div class="cell-bar-fill" style="width: {{ min(100, (float) ($row['pct'] ?? 0)) }}%"></div></div>{{ $row['pct'] }}%</div></td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => translate('Provider').' '.translate('Leads'),
    'helpKey' => 'provider_leads_section',
])
@include('adminmodule::partials._employee-progress-lead-metric-grid', [
    'rows' => collect($provider['outcome_rows'] ?? [])->map(function ($row) {
        $row['help_key'] = 'provider_outcome_'.($row['key'] ?? '');
        $row['sublabel'] = translate('Progress_of_provider_leads') ?? translate('Provider');

        return $row;
    })->all(),
    'gridClass' => 'lead-metric-grid lead-metric-grid--outcomes',
])
<div class="grid-2">
    <div class="chart-card">
        @include('adminmodule::partials._employee-progress-chart-head', [
            'title' => translate('Progress_provider_outcomes') ?? translate('Provider'),
            'subtitle' => (translate('Progress_provider_registered') ?? translate('completed')).' · '.translate('Pending').' · '.translate('Cancelled'),
            'helpKey' => 'chart_provider_outcomes',
        ])
        <div class="chart-card-body"><div id="chart-provider-outcomes" class="chart-donut"></div></div>
    </div>
    <div class="data-table-wrap">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => translate('Progress_cancellation_reason') ?? translate('Cancelled'),
            'helpKey' => 'provider_cancel_reasons',
        ])
        <table class="data-table" style="min-width:0">
            <thead><tr><th>{{ translate('Progress_cancellation_reason') ?? translate('Cancelled') }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Share') ?? '%' }}</th></tr></thead>
            <tbody>
                @forelse($provider['cancel_reasons'] ?? [] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>
                            @include('adminmodule::partials._employee-progress-metric-value', [
                                'count' => $row['count'] ?? 0,
                                'total' => $row['total'] ?? null,
                                'ofClass' => 'mc-of',
                            ])
                        </td>
                        <td><div class="cell-bar"><div class="cell-bar-track"><div class="cell-bar-fill" style="width: {{ min(100, (float) ($row['pct'] ?? 0)) }}%"></div></div>{{ $row['pct'] }}%</div></td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => (translate('Future_Customer') ?? 'Future Customer').' '.translate('Leads'),
    'helpKey' => 'future_customer_leads_table',
])
<div class="data-table-wrap">
    <table class="data-table" style="min-width:0">
        <thead><tr><th>{{ translate('Progress_future_customer_reason') ?? translate('Reason') ?? 'Reason' }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Share') ?? '%' }}</th></tr></thead>
        <tbody>
            @forelse($futureCustomerReasons as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>
                        @include('adminmodule::partials._employee-progress-metric-value', [
                            'count' => $row['count'] ?? 0,
                            'total' => $row['total'] ?? null,
                            'ofClass' => 'mc-of',
                        ])
                    </td>
                    <td><div class="cell-bar"><div class="cell-bar-track"><div class="cell-bar-fill" style="width: {{ min(100, (float) ($row['pct'] ?? 0)) }}%"></div></div>{{ $row['pct'] }}%</div></td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => translate('Invalid').' '.translate('Leads'),
    'helpKey' => 'invalid_leads_table',
])
<div class="data-table-wrap">
    <table class="data-table" style="min-width:0">
        <thead><tr><th>{{ translate('Progress_invalid_reason') ?? translate('Invalid') }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Share') ?? '%' }}</th></tr></thead>
        <tbody>
            @forelse($invalidReasons as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>
                        @include('adminmodule::partials._employee-progress-metric-value', [
                            'count' => $row['count'] ?? 0,
                            'total' => $row['total'] ?? null,
                            'ofClass' => 'mc-of',
                        ])
                    </td>
                    <td><div class="cell-bar"><div class="cell-bar-track"><div class="cell-bar-fill" style="width: {{ min(100, (float) ($row['pct'] ?? 0)) }}%"></div></div>{{ $row['pct'] }}%</div></td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => translate('Outbound_Enquiries'),
    'helpKey' => 'outbound_enquiries_section',
])
@include('adminmodule::partials._employee-progress-lead-metric-grid', [
    'rows' => collect($outbound['summary_rows'] ?? [])->map(function ($row) {
        $row['help_key'] = 'outbound_'.($row['key'] ?? '');
        $row['sublabel'] = translate('Progress_of_outbound') ?? translate('Outbound_Enquiries');

        return $row;
    })->all(),
    'gridClass' => 'lead-metric-grid lead-metric-grid--outcomes',
])
<div class="grid-2">
    <div class="data-table-wrap">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => translate('Status'),
            'helpKey' => 'outbound_by_status',
        ])
        <table class="data-table" style="min-width:0">
            <thead><tr><th>{{ translate('Status') }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Share') ?? '%' }}</th></tr></thead>
            <tbody>
                @forelse($outbound['by_status'] ?? [] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => $row['count'] ?? 0, 'total' => $row['total'] ?? null, 'ofClass' => 'mc-of'])</td><td>{{ $row['pct'] }}%</td></tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="data-table-wrap">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => translate('Source'),
            'helpKey' => 'outbound_by_channel',
        ])
        <table class="data-table" style="min-width:0">
            <thead><tr><th>{{ translate('Source') }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Share') ?? '%' }}</th></tr></thead>
            <tbody>
                @forelse($outbound['by_channel'] ?? [] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => $row['count'] ?? 0, 'total' => $row['total'] ?? null, 'ofClass' => 'mc-of'])</td><td>{{ $row['pct'] }}%</td></tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => translate('Progress_leads_by_source') ?? translate('Source'),
    'helpKey' => 'leads_by_source_table',
])
<div class="data-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ translate('Source') }}</th>
                <th>{{ translate('Total') }}</th>
                <th>{{ translate('Customer') }}</th>
                <th>{{ translate('Provider') }}</th>
                <th>{{ translate('Unknown') }}</th>
                <th>{{ translate('Invalid') }}</th>
                <th>{{ translate('Future_Customer') }}</th>
                <th>{{ translate('Share') ?? '%' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sources as $row)
                <tr>
                    <td>{{ $row['source'] }}</td>
                    <td>
                        @include('adminmodule::partials._employee-progress-metric-value', [
                            'count' => $row['total'] ?? 0,
                            'total' => $row['team_total'] ?? null,
                            'ofClass' => 'mc-of',
                        ])
                    </td>
                    <td>{{ $row['customer'] ?? 0 }}</td>
                    <td>{{ $row['provider'] ?? 0 }}</td>
                    <td>{{ $row['unknown'] ?? 0 }}</td>
                    <td>{{ $row['invalid'] ?? 0 }}</td>
                    <td>{{ $row['future_customer'] ?? 0 }}</td>
                    <td><div class="cell-bar"><div class="cell-bar-track"><div class="cell-bar-fill" style="width: {{ min(100, (float) ($row['pct'] ?? 0)) }}%"></div></div>{{ $row['pct'] }}%</div></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
